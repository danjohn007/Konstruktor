"""
Convierte un presupuesto con el formato del Excel 2024 (hojas CATALOGO, VOLUMENES,
BASICOS, PU, PROGRAMA y CONTROL) en un .sql que se importa en phpMyAdmin y crea la
obra en el sistema web: catálogo, análisis de P.U., generadores, programa, movimientos
de CONTROL y, opcionalmente, el avance como concluido.

Uso (requiere Python 3.10+ y openpyxl: pip install openpyxl):
    python importar_excel_2024.py "Presupuestos Konstruktor 2024.xlsx" obra.sql --cliente "PERLA RODRÍGUEZ" --iva 0

El sistema debe tener la Fase 4 (categorías de movimiento UTILIDAD y PRESTAMO): sin ella,
un retiro de utilidad o un préstamo se contaría como gasto de la obra.

Cómo se traduce el Excel:
- Cada línea del análisis conserva su cantidad y su precio de 2024 (congelado).
- La herramienta menor del Excel es un % distinto en cada concepto; aquí entra como una
  línea más (insumo HERR-MENOR: cantidad = el %, precio = la base) y la cotización
  queda con 0 % de herramienta, para que el P.U. salga igual al del Excel.
- La mano de obra entra con su cantidad de jornadas fija (no 1/rendimiento) y el
  rendimiento del concepto se ajusta para que la duración sea la de la hoja PROGRAMA.
- Las cuadrillas de 2024 entran como insumos inactivos CUAD-n-2024; no tocan las
  cuadrillas vigentes del sistema.
El script revisa que cada P.U. calculado con las reglas del sistema sea igual al del
Excel y se detiene si alguno no cuadra.
"""

import argparse
import datetime as dt
import math
import sys
import warnings
from decimal import ROUND_HALF_UP, Decimal

import openpyxl

warnings.filterwarnings('ignore')

CATEGORIA_EGRESO = {
    'MATERIALES': 'MAT', 'GENTE': 'MO', 'EQUIPO': 'EQ', 'HERRAMIENTA': 'EQ',
    'INDIRECTOS': 'INDIRECTO', 'PRESTAMO': 'PRESTAMO',
}
SECCIONES = {'MATERIALES': 'MAT', 'MANO DE OBRA': 'MO', 'HERRAMIENTA Y EQUIPO': 'EQ'}


def redondear(x, d=2):
    """round() de PHP: mitad hacia arriba sobre el valor a 15 cifras (3081.1049999… → 3081.11, como Excel)."""
    return float(Decimal(f'{float(x):.15g}').quantize(Decimal(1).scaleb(-d), rounding=ROUND_HALF_UP))


def clave(v):
    if isinstance(v, (int, float)):
        return f'{v:.2f}'
    return str(v).strip() if v is not None else None


def es_clave_concepto(v):
    c = clave(v)
    return c is not None and '.' in c and c.replace('.', '').isdigit()


def sql(v):
    if v is None:
        return 'NULL'
    if isinstance(v, (int, float)):
        return repr(round(v, 6)) if isinstance(v, float) else str(v)
    if isinstance(v, (dt.date, dt.datetime)):
        return f"'{v:%Y-%m-%d}'"
    return "'" + str(v).replace('\\', '\\\\').replace("'", "''") + "'"


def var(c):
    return '@c_' + c.replace('.', '_')


def leer(path):
    wb = openpyxl.load_workbook(path, data_only=True)
    cat, vol, bas, pu, prog, ctl = (wb[n] for n in ('CATALOGO', 'VOLUMENES', 'BASICOS', 'PU', 'PROGRAMA', 'CONTROL'))

    obra = {
        'obra': str(cat['B1'].value).strip(), 'direccion': cat['B2'].value, 'municipio': cat['B3'].value,
        'folio': str(cat['D1'].value).strip(),
        'pct_indirectos': float(pu['D3'].value), 'pct_utilidad': float(pu['F3'].value),
        'num_estimaciones': int(prog['E49'].value or 1),
    }

    # CATALOGO: partidas (clave entera) y conceptos (clave con punto) en el orden del Excel
    partidas, conceptos = [], []
    for r in range(8, cat.max_row + 1):
        a, b = cat.cell(r, 1).value, cat.cell(r, 2).value
        if a is None or b is None:
            continue
        if isinstance(a, (int, float)) and float(a).is_integer():
            partidas.append({'clave': str(int(a)), 'nombre': str(b).strip(), 'conceptos': []})
        elif es_clave_concepto(a) and partidas:
            c = {'clave': clave(a), 'descripcion': str(b).strip(), 'unidad': cat.cell(r, 3).value,
                 'cantidad': float(cat.cell(r, 4).value), 'pu_excel': float(cat.cell(r, 5).value),
                 'importe_excel': float(cat.cell(r, 6).value)}
            partidas[-1]['conceptos'].append(c)
            conceptos.append(c)
    por_clave = {c['clave']: c for c in conceptos}

    # PROGRAMA: inicio y días de cada concepto
    for r in range(9, prog.max_row + 1):
        k = clave(prog.cell(r, 1).value)
        if k in por_clave and isinstance(prog.cell(r, 8).value, dt.datetime):
            por_clave[k]['dias'] = int(round(prog.cell(r, 7).value))
            por_clave[k]['inicio'] = prog.cell(r, 8).value.date()

    # VOLUMENES: renglones de generadores bajo cada concepto
    actual = None
    for r in range(8, vol.max_row + 1):
        fila = [vol.cell(r, col).value for col in range(1, 10)]
        if fila[0] is not None:
            actual = por_clave.get(clave(fila[0]))
            if actual is not None:
                actual['volumenes'] = []
            continue
        if actual is None or all(v is None for v in fila[3:9]):
            continue
        largo, ancho, alto, pza = (x if isinstance(x, (int, float)) else None for x in fila[4:8])
        resultado = float(fila[8] or 0)
        if not any(x is not None for x in (largo, ancho, alto, pza)):
            pza = resultado  # resultado tecleado a mano (AJUSTE, ÁREA DE LOSA...)
        actual['volumenes'].append({'localizacion': fila[3], 'largo': largo, 'ancho': ancho, 'alto': alto,
                                    'piezas': pza, 'resultado_excel': resultado})

    # BASICOS: datos de cada insumo
    basicos = {}
    seccion = None
    for r in range(1, bas.max_row + 1):
        a, b, e, f = (bas.cell(r, col).value for col in (1, 2, 5, 6))
        if a and not b and f is None:
            seccion = str(a).strip()
        if a and b and isinstance(f, (int, float)):
            basicos[str(a).replace(' ', '').strip()] = {'descripcion': str(b).strip(), 'unidad': e,
                                                        'precio': float(f), 'categoria': seccion}

    # PU: el primer bloque de cada concepto del catálogo
    for r in range(1, pu.max_row + 1):
        k = clave(pu.cell(r, 1).value)
        if k not in por_clave or 'lineas' in por_clave[k] or not es_clave_concepto(pu.cell(r, 1).value):
            continue
        c = por_clave[k]
        c['lineas'] = []
        tipo = None
        for rr in range(r + 1, pu.max_row + 1):
            a = pu.cell(rr, 1).value
            if pu.cell(rr, 5).value == 'PRECIO UNITARIO':
                c['pu_analisis'] = float(pu.cell(rr, 6).value)
                break
            if isinstance(a, str) and a.strip() in SECCIONES:
                tipo = SECCIONES[a.strip()]
                continue
            if not isinstance(a, str) or a.strip() in ('CLAVE',) or tipo is None:
                continue
            ins = a.replace(' ', '').strip()
            d, e, f = (pu.cell(rr, col).value for col in (4, 5, 6))
            if not isinstance(d, (int, float)) or not d:
                continue
            c['lineas'].append({'clave': ins, 'tipo': tipo, 'cantidad': float(d), 'precio': float(e),
                                'total': float(f), 'unidad': pu.cell(rr, 3).value})

    # CONTROL: movimientos con fecha
    movs = []
    for row in ctl.iter_rows(min_row=2, values_only=True):
        fecha, ing, egr, forma, concepto, desc, catalogo = row[:7]
        if not isinstance(fecha, dt.datetime):
            continue
        num = lambda x: float(x) if isinstance(x, (int, float)) else 0.0
        movs.append({'fecha': fecha.date(), 'ingreso': num(ing), 'egreso': num(egr), 'forma': forma,
                     'concepto': (concepto or '').strip(), 'descripcion': (desc or concepto or '').strip(),
                     'catalogo': (catalogo or '').strip() if isinstance(catalogo, str) else catalogo})
    return obra, partidas, conceptos, basicos, movs


def preparar_lineas(c):
    """Traduce las líneas del Excel a concepto_insumos y comprueba el P.U. con las reglas del sistema."""
    out = []
    for l in c['lineas']:
        if l['clave'] == 'HERR-A':  # herramienta menor: % sobre la base
            ins, cantidad, precio = 'HERR-MENOR', redondear(l['cantidad'], 6), redondear(l['precio'], 2)
        else:
            ins = f"{l['clave']}-2024" if l['clave'].startswith('CUAD-') else l['clave']
            cantidad, precio = redondear(l['cantidad'], 6), redondear(l['precio'], 2)
        nota = None
        if redondear(cantidad * precio, 2) != redondear(l['total'], 2):
            # El sistema guarda precios con 2 decimales (p. ej. 3,081.105 → 3,081.11): se ajusta la
            # cantidad en el sexto decimal para que el importe de la línea sea el mismo del Excel.
            ajustada = redondear(l['total'] / precio, 6)
            if redondear(ajustada * precio, 2) != redondear(l['total'], 2):
                sys.exit(f"{c['clave']} {ins}: no se pudo igualar el importe {l['total']}")
            nota = f"{ins}: cantidad {cantidad} → {ajustada}"
            cantidad = ajustada
        out.append({'clave': ins, 'tipo': l['tipo'], 'cantidad': cantidad, 'precio': precio,
                    'importe': redondear(cantidad * precio, 2), 'nota': nota})
    return out


def pu_sistema(lineas, obra):
    directo = redondear(sum(l['importe'] for l in lineas), 2)
    return redondear(directo + redondear(directo * obra['pct_indirectos']) + redondear(directo * obra['pct_utilidad']), 2)


def rendimiento(cantidad, dias):
    """Rendimiento con 4 decimales que da exactamente los días del programa (cantidad ÷ rend, a 2 decimales, hacia arriba)."""
    rend = math.ceil(cantidad / dias * 10000 - 1e-9) / 10000
    assert math.ceil(redondear(cantidad / rend, 2)) == dias, (cantidad, dias, rend)
    return rend


def generar(args):
    obra, partidas, conceptos, basicos, movs = leer(args.xlsx)
    avisos = []
    subtotal = 0.0
    for c in conceptos:
        for campo in ('lineas', 'dias', 'inicio'):
            if campo not in c:
                sys.exit(f"{c['clave']}: falta {campo} (hojas PU / PROGRAMA)")
        c['ins'] = preparar_lineas(c)
        avisos += [f"{c['clave']} {l['nota']}" for l in c['ins'] if l['nota']]
        pu = pu_sistema(c['ins'], obra)
        if abs(pu - c['pu_excel']) > 0.005:
            sys.exit(f"{c['clave']}: el P.U. calculado ({pu}) no es el del Excel ({c['pu_excel']})")
        vols = c.get('volumenes') or []
        c['usa_volumenes'] = 1 if vols else 0
        if vols:
            suma = redondear(sum(redondear(math.prod(x for x in (v['largo'], v['ancho'], v['alto'], v['piezas']) if x is not None), 4)
                                 for v in vols), 2)
            if abs(suma - c['cantidad']) > 0.005:
                sys.exit(f"{c['clave']}: los generadores suman {suma} y el catálogo dice {c['cantidad']}")
        c['rend'] = rendimiento(c['cantidad'], c['dias'])
        c['termino'] = c['inicio'] + dt.timedelta(days=c['dias'] - 1)
        subtotal += redondear(pu * c['cantidad'], 2)
    subtotal = redondear(subtotal, 2)

    anticipo = next((m['ingreso'] for m in movs if m['ingreso'] and 'ANTICIPO' in m['descripcion'].upper()), 0.0)
    pct_anticipo = round(anticipo / subtotal, 4) if subtotal else 0
    inicio = min(c['inicio'] for c in conceptos)
    fecha_cot = min([inicio] + [m['fecha'] for m in movs])

    # Insumos que usan los análisis: los de 2024 se agregan si la base no los tiene.
    usados = {}
    for c in conceptos:
        for l in c['ins']:
            usados.setdefault(l['clave'], l)
    insumos = []
    for k, l in usados.items():
        if k == 'HERR-MENOR':
            insumos.append((k, 'HERRAMIENTA MENOR (% SOBRE MATERIALES Y MANO DE OBRA)', '%', 'EQ', 'HERRAMIENTA Y EQUIPO', 0, 0))
        elif k.endswith('-2024'):
            b = basicos[k[:-5]]
            insumos.append((k, f"{b['descripcion']} (2024)", b['unidad'], 'MO', 'MANO DE OBRA', redondear(b['precio']), 0))
        else:
            b = basicos.get(k)
            if b is None:
                sys.exit(f'El insumo {k} no está en BASICOS')
            insumos.append((k, b['descripcion'], b['unidad'], l['tipo'], b['categoria'], redondear(b['precio']), 1))

    folio = args.folio or obra['folio']
    nombre_archivo = args.xlsx.replace('\\', '/').split('/')[-1]
    notas = (f"Importada de \"{nombre_archivo}\" el {dt.date.today():%d/%m/%Y}. Precios de insumos de 2024 congelados. "
             "La herramienta menor es una línea de cada análisis (el Excel usa un % distinto por concepto), "
             "por eso la cotización tiene 0 % de herramienta.")

    o = ['-- Obra importada del Excel 2024: ' + obra['obra'],
         f'-- Generado por herramientas/importar_excel_2024.py el {dt.datetime.now():%Y-%m-%d %H:%M}.',
         '-- Requiere el sistema con Fase 4 (categorías UTILIDAD y PRESTAMO). Si el folio ya existe, la importación se detiene sin cambios.',
         'SET NAMES utf8mb4;', 'START TRANSACTION;', '',
         '-- Insumos que usan los análisis (si ya existen, se respetan los del catálogo)',
         'INSERT IGNORE INTO insumos (clave, descripcion, unidad, tipo, categoria, precio_captura, mas_iva, precio, activo) VALUES']
    o.append(',\n'.join(f'({sql(k)}, {sql(d)}, {sql(u)}, {sql(t)}, {sql(cat)}, {p:.2f}, 0, {p:.2f}, {act})'
                        for k, d, u, t, cat, p, act in insumos) + ';')
    o.append('')
    if args.cliente:
        o += [f"INSERT INTO clientes (nombre, direccion) SELECT {sql(args.cliente)}, {sql(obra['direccion'])} FROM DUAL",
              f"    WHERE NOT EXISTS (SELECT 1 FROM clientes WHERE nombre = {sql(args.cliente)});",
              f"SET @cliente = (SELECT MIN(id) FROM clientes WHERE nombre = {sql(args.cliente)});"]
    else:
        o.append('SET @cliente = NULL;')
    o += ['',
          'INSERT INTO cotizaciones (folio, cliente_id, obra, direccion, municipio, fecha_cotizacion, fecha_inicio, estado,',
          '    pct_indirectos, pct_utilidad, pct_herramienta, pct_iva, pct_anticipo, pct_retencion, num_estimaciones, notas, subtotal, total) VALUES',
          f"({sql(folio)}, @cliente, {sql(obra['obra'])}, {sql(obra['direccion'])}, {sql(obra['municipio'])}, {sql(fecha_cot)}, {sql(inicio)}, 'aceptada',",
          f"    {obra['pct_indirectos']}, {obra['pct_utilidad']}, 0, {args.iva}, {pct_anticipo}, 0, {obra['num_estimaciones']}, {sql(notas)},",
          f"    {subtotal:.2f}, {redondear(subtotal + redondear(subtotal * args.iva)):.2f});",
          'SET @cot = LAST_INSERT_ID();', '']

    for i, p in enumerate(partidas, 1):
        o += [f"INSERT INTO partidas (cotizacion_id, orden, clave, nombre) VALUES (@cot, {i}, {sql(p['clave'])}, {sql(p['nombre'])});",
              f'SET @p{i} = LAST_INSERT_ID();']
        for j, c in enumerate(p['conceptos'], 1):
            c['partida'] = f'@p{i}'
            o += ['',
                  f"-- {c['clave']} {c['descripcion']}: P.U. {c['pu_excel']:,.2f}, {c['dias']} días desde {c['inicio']:%d/%m/%Y}",
                  'INSERT INTO conceptos (cotizacion_id, partida_id, orden, clave, descripcion, unidad, cantidad, usa_volumenes, rendimiento, fecha_inicio)',
                  f"    VALUES (@cot, @p{i}, {j}, {sql(c['clave'])}, {sql(c['descripcion'])}, {sql(c['unidad'])}, {c['cantidad']}, "
                  f"{c['usa_volumenes']}, {c['rend']}, {sql(c['inicio'])});",
                  f"SET {var(c['clave'])} = LAST_INSERT_ID();",
                  'INSERT INTO concepto_insumos (concepto_id, insumo_id, cantidad, por_rendimiento, precio)']
            o.append('\nUNION ALL '.join(f"SELECT {var(c['clave'])}, id, {l['cantidad']}, 0, {l['precio']:.2f} FROM insumos WHERE clave = {sql(l['clave'])}"
                                         for l in c['ins']) + ';')
            if c['usa_volumenes']:
                o.append('INSERT INTO volumenes (concepto_id, localizacion, largo, ancho, alto, piezas) VALUES')
                o.append(',\n'.join(f"({var(c['clave'])}, {sql(v['localizacion'])}, {sql(v['largo'])}, {sql(v['ancho'])}, {sql(v['alto'])}, {sql(v['piezas'])})"
                                    for v in c['volumenes']) + ';')

    o += ['', '-- Hoja CONTROL']
    filas = []
    for m in movs:
        cat = m['catalogo'] if isinstance(m['catalogo'], str) else ''
        if m['ingreso']:
            tipo, monto, concepto = 'ingreso', m['ingreso'], None
            if m['concepto'] == 'PRESTAMO':
                categoria = 'PRESTAMO'
            elif m['concepto'] == 'ESTIMACIONES':
                categoria = 'ANTICIPO' if 'ANTICIPO' in m['descripcion'].upper() else 'ESTIMACION'
            else:
                categoria = 'OTRO'
        else:
            tipo, monto = 'egreso', m['egreso']
            categoria = 'UTILIDAD' if cat.upper() == 'UTILIDAD' else CATEGORIA_EGRESO.get(m['concepto'], 'OTRO')
            concepto = next((c for c in conceptos if cat.split(' ')[0] == c['clave']), None)
        if not monto:
            continue
        filas.append(f"(@cot, {sql(m['fecha'])}, '{tipo}', '{categoria}', {concepto['partida'] if concepto else 'NULL'}, "
                     f"{var(concepto['clave']) if concepto else 'NULL'}, {sql(m['descripcion'][:255])}, {sql(m['forma'])}, {monto:.2f})")
    o.append('INSERT INTO movimientos (cotizacion_id, fecha, tipo, categoria, partida_id, concepto_id, descripcion, forma_pago, monto) VALUES')
    o.append(',\n'.join(filas) + ';')

    if args.avance_completo:
        o += ['', '-- Avance: cada concepto concluido el último día de su programa']
        o.append('INSERT INTO avances (cotizacion_id, concepto_id, fecha, cantidad, notas) VALUES')
        o.append(',\n'.join(f"(@cot, {var(c['clave'])}, {sql(c['termino'])}, {c['cantidad']}, 'Carga histórica: concepto terminado')"
                            for c in conceptos) + ';')

    o += ['', 'COMMIT;', '']
    with open(args.sql, 'w', encoding='utf-8', newline='\n') as f:
        f.write('\n'.join(o))

    print(f"{obra['obra']} ({folio}): {len(conceptos)} conceptos, subtotal {subtotal:,.2f}, "
          f"{len(filas)} movimientos, anticipo {pct_anticipo:.2%}")
    for a in avisos:
        print('  ajuste de precisión:', a)


if __name__ == '__main__':
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument('xlsx')
    ap.add_argument('sql')
    ap.add_argument('--folio', help='folio en el sistema (por defecto la CLAVE de la hoja CATALOGO)')
    ap.add_argument('--cliente', help='nombre del cliente; se crea si no existe')
    ap.add_argument('--iva', type=float, default=0.16, help='tasa de IVA de la obra (0 si no se facturó IVA)')
    ap.add_argument('--sin-avance', dest='avance_completo', action='store_false',
                    help='no registrar los conceptos como terminados (obra en curso)')
    generar(ap.parse_args())
