<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\Archivos;
use App\Services\Calculadora;

class CotizacionesController
{
    public const ESTADOS = ['borrador', 'enviada', 'aceptada', 'rechazada'];
    private const PCTS = ['pct_indirectos', 'pct_utilidad', 'pct_herramienta', 'pct_iva', 'pct_anticipo', 'pct_retencion'];

    // ------------------------------------------------------------------ páginas

    public function index(): void
    {
        Auth::requireLogin();
        Response::view('cotizaciones/index', Auth::esIngeniero()
            ? ['user' => Auth::user(), 'titulo' => 'Obras', 'subtitulo' => 'Tus obras asignadas: registra el avance y los gastos de cada una']
            : ['user' => Auth::user(), 'titulo' => 'Cotizaciones y obras', 'subtitulo' => 'Cada cotización aceptada es una obra: ahí se llevan avance, estimaciones y gastos']);
    }

    public function show(string $id): void
    {
        Auth::requireLogin();
        $cot = Calculadora::cotizacion((int) $id);
        if (!$cot) {
            http_response_code(404);
            echo 'Cotización no encontrada';
            return;
        }
        Response::view('cotizaciones/detalle', [
            'user' => Auth::user(),
            'titulo' => 'Cotización ' . $cot['folio'],
            'migas' => [(Auth::esIngeniero() ? 'Obras' : 'Cotizaciones') => 'cotizaciones'],
            'cotizacionId' => (int) $id,
            'graficas' => true,
            'sinTitulo' => true,
        ]);
    }

    public function presupuesto(string $id): void
    {
        Auth::requireLogin();
        $cot = Calculadora::cotizacion((int) $id);
        if (!$cot) {
            http_response_code(404);
            echo 'Cotización no encontrada';
            return;
        }
        Response::view('cotizaciones/presupuesto', ['cot' => $cot, 'cfg' => Config::all()], false);
    }

    // ------------------------------------------------------------- cotizaciones

    public function apiIndex(): void
    {
        Auth::requireLogin();
        $where = [];
        $params = [];
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $where[] = '(c.folio LIKE ? OR c.obra LIKE ? OR cl.nombre LIKE ?)';
            array_push($params, "%$q%", "%$q%", "%$q%");
        }
        if (Auth::esIngeniero()) {
            // el ingeniero solo ve las obras (aceptadas) que tiene asignadas
            $where[] = "c.estado = 'aceptada' AND EXISTS (SELECT 1 FROM obra_ingenieros oi WHERE oi.cotizacion_id = c.id AND oi.usuario_id = ?)";
            $params[] = Auth::user()['id'];
        } elseif (in_array($_GET['estado'] ?? '', self::ESTADOS, true)) {
            $where[] = 'c.estado = ?';
            $params[] = $_GET['estado'];
        }

        $stmt = Database::pdo()->prepare(
            'SELECT c.id, c.folio, c.obra, c.municipio, c.estado, c.fecha_cotizacion, c.subtotal, c.total, cl.nombre AS cliente,
                    (SELECT GROUP_CONCAT(u.nombre ORDER BY u.nombre SEPARATOR \', \') FROM obra_ingenieros oi JOIN usuarios u ON u.id = oi.usuario_id
                     WHERE oi.cotizacion_id = c.id) AS ingenieros
             FROM cotizaciones c LEFT JOIN clientes cl ON cl.id = c.cliente_id'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY c.id DESC'
        );
        $stmt->execute($params);
        Response::json($stmt->fetchAll());
    }

    public function apiShow(string $id): void
    {
        Auth::requireLogin();
        $cot = Calculadora::cotizacion((int) $id);
        $cot ? Response::json($cot) : Response::json(['error' => 'Cotización no encontrada'], 404);
    }

    public function apiStore(): void
    {
        Auth::requireLogin();
        $d = Request::json();
        $obra = Request::str($d['obra'] ?? null);
        if ($obra === null) {
            Response::json(['error' => 'El nombre de la obra es requerido'], 422);
            return;
        }

        $c = Config::all();
        $db = Database::pdo();
        $db->prepare(
            'INSERT INTO cotizaciones (folio, cliente_id, obra, direccion, municipio, fecha_cotizacion, vigencia_dias, fecha_inicio,
                pct_indirectos, pct_utilidad, pct_herramienta, pct_iva, pct_anticipo, pct_retencion, num_estimaciones,
                alcances, exclusiones, notas, usuario_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $this->siguienteFolio(),
            Request::num($d['cliente_id'] ?? null),
            $obra,
            Request::str($d['direccion'] ?? null),
            Request::str($d['municipio'] ?? null),
            Request::str($d['fecha_cotizacion'] ?? null) ?? date('Y-m-d'),
            (int) $c['vigencia_dias'],
            Request::str($d['fecha_inicio'] ?? null),
            $c['pct_indirectos'], $c['pct_utilidad'], $c['pct_herramienta'], $c['pct_iva'], $c['pct_anticipo'], $c['pct_retencion'],
            (int) $c['num_estimaciones'],
            $c['alcances'], $c['exclusiones'], $c['notas'],
            Auth::user()['id'],
        ]);

        Response::json(['id' => (int) $db->lastInsertId()], 201);
    }

    public function apiUpdate(string $id): void
    {
        Auth::requireLogin();
        $d = Request::json();
        $obra = Request::str($d['obra'] ?? null);
        $fecha = Request::str($d['fecha_cotizacion'] ?? null);
        if ($obra === null || $fecha === null) {
            Response::json(['error' => 'Obra y fecha de cotización son requeridas'], 422);
            return;
        }
        if (!in_array($d['estado'] ?? '', self::ESTADOS, true)) {
            Response::json(['error' => 'Estado inválido'], 422);
            return;
        }

        $actual = Database::pdo()->prepare('SELECT * FROM cotizaciones WHERE id = ?');
        $actual->execute([$id]);
        $actual = $actual->fetch();
        if (!$actual) {
            Response::json(['error' => 'Cotización no encontrada'], 404);
            return;
        }

        if ($actual['estado'] === 'aceptada') {
            // Contrato firmado: los porcentajes ya no cambian (las estimaciones dependen de ellos).
            $pcts = array_map(fn($k) => $actual[$k], self::PCTS);
            $numEst = Database::pdo()->prepare('SELECT COUNT(*) FROM estimaciones WHERE cotizacion_id = ?');
            $numEst->execute([$id]);
            if ($d['estado'] !== 'aceptada' && (int) $numEst->fetchColumn() > 0) {
                Response::json(['error' => 'La cotización ya tiene estimaciones; no puede dejar de estar aceptada'], 409);
                return;
            }
        } else {
            $pcts = [];
            foreach (self::PCTS as $k) {
                $n = Request::num($d[$k] ?? null);
                if ($n === null || $n < 0 || $n > 1) {
                    Response::json(['error' => "Porcentaje inválido: $k"], 422);
                    return;
                }
                $pcts[] = $n;
            }
        }

        Database::pdo()->prepare(
            'UPDATE cotizaciones SET cliente_id=?, obra=?, direccion=?, municipio=?, fecha_cotizacion=?, vigencia_dias=?, fecha_inicio=?,
                estado=?, pct_indirectos=?, pct_utilidad=?, pct_herramienta=?, pct_iva=?, pct_anticipo=?, pct_retencion=?, num_estimaciones=?,
                alcances=?, exclusiones=?, notas=? WHERE id=?'
        )->execute([
            Request::num($d['cliente_id'] ?? null),
            $obra,
            Request::str($d['direccion'] ?? null),
            Request::str($d['municipio'] ?? null),
            $fecha,
            max(0, (int) ($d['vigencia_dias'] ?? 30)),
            Request::str($d['fecha_inicio'] ?? null),
            $d['estado'],
            ...$pcts,
            max(1, (int) ($d['num_estimaciones'] ?? 1)),
            Request::str($d['alcances'] ?? null),
            Request::str($d['exclusiones'] ?? null),
            Request::str($d['notas'] ?? null),
            $id,
        ]);

        Calculadora::refrescarTotales((int) $id);
        Response::json(['ok' => true]);
    }

    public function apiDelete(string $id): void
    {
        Auth::requireRole('Admin');
        $db = Database::pdo();
        // Las filas se borran en cascada; las fotos y comprobantes hay que borrarlos a mano.
        $archivos = $db->prepare(
            'SELECT comprobante FROM movimientos WHERE cotizacion_id = ? AND comprobante IS NOT NULL
             UNION ALL SELECT foto FROM avances WHERE cotizacion_id = ? AND foto IS NOT NULL'
        );
        $archivos->execute([$id, $id]);
        $archivos = $archivos->fetchAll(\PDO::FETCH_COLUMN);
        $db->prepare('DELETE FROM cotizaciones WHERE id = ?')->execute([$id]);
        array_map([Archivos::class, 'borrar'], $archivos);
        Response::json(['ok' => true]);
    }

    /** Copia a los análisis el precio vigente del catálogo de insumos. */
    public function apiActualizarPrecios(string $id): void
    {
        Auth::requireLogin();
        if (!$this->editable('cotizaciones', $id)) {
            return;
        }
        $stmt = Database::pdo()->prepare(
            'UPDATE concepto_insumos ci JOIN conceptos co ON co.id = ci.concepto_id JOIN insumos i ON i.id = ci.insumo_id
             SET ci.precio = i.precio WHERE co.cotizacion_id = ?'
        );
        $stmt->execute([$id]);
        Calculadora::refrescarTotales((int) $id);
        Response::json(['ok' => true, 'actualizados' => $stmt->rowCount()]);
    }

    // ---------------------------------------------------------------- partidas

    public function apiStorePartida(string $cotizacionId): void
    {
        Auth::requireLogin();
        if (!$this->editable('cotizaciones', $cotizacionId)) {
            return;
        }
        $d = Request::json();
        $clave = Request::str($d['clave'] ?? null);
        $nombre = Request::str($d['nombre'] ?? null);
        if ($clave === null || $nombre === null) {
            Response::json(['error' => 'Clave y nombre de la partida son requeridos'], 422);
            return;
        }

        $db = Database::pdo();
        $existe = $db->prepare('SELECT COUNT(*) FROM cotizaciones WHERE id = ?');
        $existe->execute([$cotizacionId]);
        if (!(int) $existe->fetchColumn()) {
            Response::json(['error' => 'Cotización no encontrada'], 404);
            return;
        }

        $db->prepare(
            'INSERT INTO partidas (cotizacion_id, orden, clave, nombre)
             SELECT ?, COALESCE(MAX(orden), 0) + 1, ?, ? FROM partidas WHERE cotizacion_id = ?'
        )->execute([$cotizacionId, $clave, $nombre, $cotizacionId]);
        Response::json(['id' => (int) $db->lastInsertId()], 201);
    }

    public function apiUpdatePartida(string $id): void
    {
        Auth::requireLogin();
        if (!$this->editable('partidas', $id)) {
            return;
        }
        $d = Request::json();
        $clave = Request::str($d['clave'] ?? null);
        $nombre = Request::str($d['nombre'] ?? null);
        if ($clave === null || $nombre === null) {
            Response::json(['error' => 'Clave y nombre de la partida son requeridos'], 422);
            return;
        }
        Database::pdo()->prepare('UPDATE partidas SET clave = ?, nombre = ? WHERE id = ?')->execute([$clave, $nombre, $id]);
        Response::json(['ok' => true]);
    }

    public function apiDeletePartida(string $id): void
    {
        Auth::requireLogin();
        if (!$this->editable('partidas', $id)) {
            return;
        }
        $cot = $this->cotizacionDe('partidas', $id);
        Database::pdo()->prepare('DELETE FROM partidas WHERE id = ?')->execute([$id]);
        $cot && Calculadora::refrescarTotales($cot);
        Response::json(['ok' => true]);
    }

    // --------------------------------------------------------------- conceptos

    public function apiStoreConcepto(string $partidaId): void
    {
        Auth::requireLogin();
        if (!$this->editable('partidas', $partidaId)) {
            return;
        }
        $cot = $this->cotizacionDe('partidas', $partidaId);
        if (!$cot) {
            Response::json(['error' => 'Partida no encontrada'], 404);
            return;
        }
        $d = $this->validarConcepto(Request::json());
        if ($d === null) {
            return;
        }

        $db = Database::pdo();
        $db->prepare(
            'INSERT INTO conceptos (cotizacion_id, partida_id, orden, clave, descripcion, unidad, cantidad, usa_volumenes, rendimiento, fecha_inicio)
             SELECT ?, ?, COALESCE(MAX(orden), 0) + 1, ?, ?, ?, ?, ?, ?, ? FROM conceptos WHERE partida_id = ?'
        )->execute([$cot, $partidaId, $d['clave'], $d['descripcion'], $d['unidad'], $d['cantidad'], $d['usa_volumenes'],
            $d['rendimiento'], $d['fecha_inicio'], $partidaId]);
        $nuevo = (int) $db->lastInsertId();
        Calculadora::refrescarTotales($cot);
        Response::json(['id' => $nuevo], 201);
    }

    public function apiUpdateConcepto(string $id): void
    {
        Auth::requireLogin();
        if (!$this->editable('conceptos', $id)) {
            return;
        }
        $d = $this->validarConcepto(Request::json());
        if ($d === null) {
            return;
        }
        Database::pdo()->prepare(
            'UPDATE conceptos SET clave=?, descripcion=?, unidad=?, cantidad=?, usa_volumenes=?, rendimiento=?, fecha_inicio=? WHERE id=?'
        )->execute([$d['clave'], $d['descripcion'], $d['unidad'], $d['cantidad'], $d['usa_volumenes'], $d['rendimiento'], $d['fecha_inicio'], $id]);
        $this->refrescarPor('conceptos', $id);
        Response::json(['ok' => true]);
    }

    public function apiDeleteConcepto(string $id): void
    {
        Auth::requireLogin();
        if (!$this->editable('conceptos', $id)) {
            return;
        }
        $cot = $this->cotizacionDe('conceptos', $id);
        Database::pdo()->prepare('DELETE FROM conceptos WHERE id = ?')->execute([$id]);
        $cot && Calculadora::refrescarTotales($cot);
        Response::json(['ok' => true]);
    }

    // ------------------------------------------------- análisis (insumos del concepto)

    public function apiStoreInsumo(string $conceptoId): void
    {
        Auth::requireLogin();
        if (!$this->editable('conceptos', $conceptoId)) {
            return;
        }
        $d = Request::json();
        $db = Database::pdo();

        $ins = $db->prepare('SELECT * FROM insumos WHERE id = ? AND activo = 1');
        $ins->execute([$d['insumo_id'] ?? 0]);
        $insumo = $ins->fetch();
        if (!$insumo || !$this->cotizacionDe('conceptos', $conceptoId)) {
            Response::json(['error' => 'Insumo o concepto no encontrado'], 404);
            return;
        }

        // La mano de obra se calcula por rendimiento (1/REND), como en el Excel.
        $porRend = isset($d['por_rendimiento']) ? (int) (bool) $d['por_rendimiento'] : (int) ($insumo['tipo'] === 'MO');
        $cantidad = Request::num($d['cantidad'] ?? null) ?? ($porRend ? 0 : 1);

        $db->prepare('INSERT INTO concepto_insumos (concepto_id, insumo_id, cantidad, por_rendimiento, precio) VALUES (?,?,?,?,?)')
            ->execute([$conceptoId, $insumo['id'], $cantidad, $porRend, $insumo['precio']]);
        $nuevo = (int) $db->lastInsertId();
        $this->refrescarPor('conceptos', $conceptoId);
        Response::json(['id' => $nuevo], 201);
    }

    public function apiUpdateInsumo(string $id): void
    {
        Auth::requireLogin();
        if (!$this->editable('concepto_insumos', $id)) {
            return;
        }
        $d = Request::json();
        $cantidad = Request::num($d['cantidad'] ?? null);
        $precio = Request::num($d['precio'] ?? null);
        if ($cantidad === null || $cantidad < 0 || $precio === null || $precio < 0) {
            Response::json(['error' => 'Cantidad y precio deben ser números positivos'], 422);
            return;
        }

        Database::pdo()->prepare('UPDATE concepto_insumos SET cantidad = ?, por_rendimiento = ?, precio = ? WHERE id = ?')
            ->execute([$cantidad, empty($d['por_rendimiento']) ? 0 : 1, $precio, $id]);
        $this->refrescarPor('concepto_insumos', $id);
        Response::json(['ok' => true]);
    }

    public function apiDeleteInsumo(string $id): void
    {
        Auth::requireLogin();
        if (!$this->editable('concepto_insumos', $id)) {
            return;
        }
        $cot = $this->cotizacionDe('concepto_insumos', $id);
        Database::pdo()->prepare('DELETE FROM concepto_insumos WHERE id = ?')->execute([$id]);
        $cot && Calculadora::refrescarTotales($cot);
        Response::json(['ok' => true]);
    }

    // ------------------------------------------------- volúmenes (números generadores)

    public function apiStoreVolumen(string $conceptoId): void
    {
        Auth::requireLogin();
        if (!$this->editable('conceptos', $conceptoId)) {
            return;
        }
        if (!$this->cotizacionDe('conceptos', $conceptoId)) {
            Response::json(['error' => 'Concepto no encontrado'], 404);
            return;
        }
        $v = $this->valoresVolumen(Request::json());
        $db = Database::pdo();
        $db->prepare('INSERT INTO volumenes (concepto_id, localizacion, largo, ancho, alto, piezas) VALUES (?,?,?,?,?,?)')
            ->execute([$conceptoId, ...$v]);
        $nuevo = (int) $db->lastInsertId();
        $this->refrescarPor('conceptos', $conceptoId);
        Response::json(['id' => $nuevo], 201);
    }

    public function apiUpdateVolumen(string $id): void
    {
        Auth::requireLogin();
        if (!$this->editable('volumenes', $id)) {
            return;
        }
        $v = $this->valoresVolumen(Request::json());
        Database::pdo()->prepare('UPDATE volumenes SET localizacion=?, largo=?, ancho=?, alto=?, piezas=? WHERE id=?')
            ->execute([...$v, $id]);
        $this->refrescarPor('volumenes', $id);
        Response::json(['ok' => true]);
    }

    public function apiDeleteVolumen(string $id): void
    {
        Auth::requireLogin();
        if (!$this->editable('volumenes', $id)) {
            return;
        }
        $cot = $this->cotizacionDe('volumenes', $id);
        Database::pdo()->prepare('DELETE FROM volumenes WHERE id = ?')->execute([$id]);
        $cot && Calculadora::refrescarTotales($cot);
        Response::json(['ok' => true]);
    }

    // ----------------------------------------------------------------- apoyo

    private function siguienteFolio(): string
    {
        $prefijo = strtoupper(Config::get('folio_prefijo') ?: 'KON') . '-' . date('y') . '-';
        $stmt = Database::pdo()->prepare('SELECT folio FROM cotizaciones WHERE folio LIKE ?');
        $stmt->execute([$prefijo . '%']);
        $max = 0;
        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $f) {
            $max = max($max, (int) substr($f, strlen($prefijo)));
        }

        return $prefijo . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    /** Id de la cotización a la que pertenece un registro hijo. */
    private function cotizacionDe(string $tabla, string|int $id): ?int
    {
        $sql = match ($tabla) {
            'partidas', 'conceptos' => "SELECT cotizacion_id FROM $tabla WHERE id = ?",
            'concepto_insumos' => 'SELECT co.cotizacion_id FROM concepto_insumos x JOIN conceptos co ON co.id = x.concepto_id WHERE x.id = ?',
            'volumenes' => 'SELECT co.cotizacion_id FROM volumenes x JOIN conceptos co ON co.id = x.concepto_id WHERE x.id = ?',
        };
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$id]);
        $cot = $stmt->fetchColumn();

        return $cot ? (int) $cot : null;
    }

    /** El ingeniero tiene asignada esta obra (solo entonces la ve). */
    public static function asignada(int $cotizacionId, int $usuarioId): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM obra_ingenieros WHERE cotizacion_id = ? AND usuario_id = ?');
        $stmt->execute([$cotizacionId, $usuarioId]);

        return (bool) $stmt->fetchColumn();
    }

    /** Ingenieros asignados a la obra y los que se pueden asignar (activos con rol Ingeniero). */
    public function apiIngenieros(string $id): void
    {
        Auth::requireLogin();
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT u.id, u.nombre, u.email, u.activo, (oi.usuario_id IS NOT NULL) AS asignado
             FROM usuarios u JOIN roles r ON r.id = u.rol_id AND r.nombre = 'Ingeniero'
             LEFT JOIN obra_ingenieros oi ON oi.usuario_id = u.id AND oi.cotizacion_id = ?
             WHERE u.activo = 1 OR oi.usuario_id IS NOT NULL ORDER BY u.nombre"
        );
        $stmt->execute([$id]);
        Response::json(array_map(fn($u) => [...$u, 'asignado' => (bool) $u['asignado'], 'activo' => (bool) $u['activo']], $stmt->fetchAll()));
    }

    /** Reemplaza los ingenieros asignados: {usuarios: [id, ...]}. */
    public function apiAsignarIngenieros(string $id): void
    {
        Auth::requireLogin();
        if (self::estado((int) $id) === null) {
            Response::json(['error' => 'Cotización no encontrada'], 404);
            return;
        }
        $ids = array_values(array_unique(array_map('intval', (array) (Request::json()['usuarios'] ?? []))));
        $db = Database::pdo();
        $validos = [];
        if ($ids) {
            $marcas = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("SELECT u.id FROM usuarios u JOIN roles r ON r.id = u.rol_id AND r.nombre = 'Ingeniero' WHERE u.id IN ($marcas)");
            $stmt->execute($ids);
            $validos = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }
        $db->beginTransaction();
        $db->prepare('DELETE FROM obra_ingenieros WHERE cotizacion_id = ?')->execute([$id]);
        $ins = $db->prepare('INSERT INTO obra_ingenieros (cotizacion_id, usuario_id) VALUES (?, ?)');
        foreach ($validos as $usuarioId) {
            $ins->execute([$id, $usuarioId]);
        }
        $db->commit();
        $this->apiIngenieros($id);
    }

    public static function estado(int $cotizacionId): ?string
    {
        $stmt = Database::pdo()->prepare('SELECT estado FROM cotizaciones WHERE id = ?');
        $stmt->execute([$cotizacionId]);
        $estado = $stmt->fetchColumn();

        return $estado === false ? null : $estado;
    }

    /** Una cotización aceptada es el contrato: su catálogo y análisis ya no se modifican. */
    private function editable(string $tabla, string|int $id): bool
    {
        $cot = $tabla === 'cotizaciones' ? (int) $id : $this->cotizacionDe($tabla, $id);
        if ($cot && self::estado($cot) === 'aceptada') {
            Response::json(['error' => 'La cotización está aceptada: el catálogo ya no se puede modificar. Regrésala a "enviada" si aún no tiene estimaciones.'], 409);
            return false;
        }

        return true;
    }

    private function refrescarPor(string $tabla, string|int $id): void
    {
        $cot = $this->cotizacionDe($tabla, $id);
        $cot && Calculadora::refrescarTotales($cot);
    }

    private function validarConcepto(array $d): ?array
    {
        $out = [
            'clave' => Request::str($d['clave'] ?? null),
            'descripcion' => Request::str($d['descripcion'] ?? null),
            'unidad' => strtoupper((string) Request::str($d['unidad'] ?? null)),
            'cantidad' => Request::num($d['cantidad'] ?? null) ?? 0,
            'usa_volumenes' => empty($d['usa_volumenes']) ? 0 : 1,
            'rendimiento' => Request::num($d['rendimiento'] ?? null) ?? 1,
            'fecha_inicio' => Request::str($d['fecha_inicio'] ?? null),
        ];

        if ($out['clave'] === null || $out['descripcion'] === null || $out['unidad'] === '') {
            Response::json(['error' => 'Clave, descripción y unidad son requeridas'], 422);
            return null;
        }
        if ($out['rendimiento'] <= 0) {
            Response::json(['error' => 'El rendimiento debe ser mayor que cero'], 422);
            return null;
        }
        if ($out['cantidad'] < 0) {
            Response::json(['error' => 'La cantidad no puede ser negativa'], 422);
            return null;
        }

        return $out;
    }

    private function valoresVolumen(array $d): array
    {
        return [
            Request::str($d['localizacion'] ?? null),
            Request::num($d['largo'] ?? null),
            Request::num($d['ancho'] ?? null),
            Request::num($d['alto'] ?? null),
            Request::num($d['piezas'] ?? null),
        ];
    }
}
