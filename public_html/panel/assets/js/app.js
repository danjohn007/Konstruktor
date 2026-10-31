function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}

function debounce(fn, ms) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
    };
}

const MXN = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

function money(n) {
    return MXN.format(Number(n) || 0);
}

function numero(n, dec = 2) {
    return (Number(n) || 0).toLocaleString('es-MX', { minimumFractionDigits: dec, maximumFractionDigits: dec });
}

function pct(n) {
    return numero((Number(n) || 0) * 100, 2) + '%';
}

/** Montos cortos para ejes y tarjetas: $1.28 M, $742 k. */
function moneyCorto(n) {
    const v = Number(n) || 0;
    const a = Math.abs(v);
    const signo = v < 0 ? '-' : '';
    if (a >= 1e6) return signo + '$' + numero(a / 1e6, a >= 1e7 ? 1 : 2) + ' M';
    if (a >= 1e3) return signo + '$' + numero(a / 1e3, 0) + ' k';
    return money(v);
}

const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

/** "2026-03" → "mar 26" */
function mesCorto(ym) {
    const [y, m] = ym.split('-');
    return `${MESES[Number(m) - 1]} ${y.slice(2)}`;
}

/** Colores de las gráficas: los de la marca y los de estado. */
const COLORES = {
    brand: '#2f75b5', claro: '#8fbbe2', navy: '#0f2742', success: '#22a06b', warning: '#f7881f',
    danger: '#e5484d', teal: '#00a0a8', purple: '#6048d0', slate: '#94a3b8', linea: '#eef2f6', texto: '#64748b',
};

function ejeCategorias(datos) {
    return {
        type: 'category', data: datos, axisTick: { show: false },
        axisLine: { lineStyle: { color: '#e2e8f0' } }, axisLabel: { color: COLORES.texto, fontSize: 11 },
    };
}

function ejeMontos() {
    return {
        type: 'value', axisLabel: { color: COLORES.texto, fontSize: 11, formatter: moneyCorto },
        splitLine: { lineStyle: { color: COLORES.linea } },
    };
}

/**
 * Gráfica ECharts con el estilo del sistema (requiere 'graficas' => true en la vista).
 * Se redibuja sola al cambiar de tamaño, también cuando estaba en una pestaña oculta.
 */
function grafica(el, opciones) {
    if (typeof el === 'string') el = document.getElementById(el);
    const g = echarts.getInstanceByDom(el) || echarts.init(el, null, { renderer: 'svg' });
    g.setOption({
        textStyle: { fontFamily: getComputedStyle(document.body).fontFamily },
        color: [COLORES.brand, COLORES.success, COLORES.warning, COLORES.navy, COLORES.teal, COLORES.purple, COLORES.slate],
        grid: { left: 4, right: 8, top: 36, bottom: 4, containLabel: true },
        legend: { top: 0, left: 0, icon: 'roundRect', itemWidth: 10, itemHeight: 10, textStyle: { color: COLORES.texto, fontSize: 12 } },
        tooltip: {
            trigger: 'axis', backgroundColor: COLORES.navy, borderWidth: 0, padding: [8, 12],
            textStyle: { color: '#fff', fontSize: 12 }, valueFormatter: (v) => money(v),
            axisPointer: { type: 'shadow', shadowStyle: { color: 'rgba(47,117,181,.06)' } },
        },
        ...opciones,
    }, true);
    if (!el.dataset.observada) {
        new ResizeObserver(() => g.resize()).observe(el);
        el.dataset.observada = '1';
    }
    return g;
}

/** Barra de avance real (relleno) contra programado (marca); en naranja si va atrasado. */
function barraRealVsProgramado(real, programado) {
    const atrasada = real + 0.005 < programado;
    const p = (v) => Math.max(0, Math.min(100, v * 100)).toFixed(1);
    return `<div class="avance-barra${atrasada ? ' atrasada' : ''}" title="Real ${pct(real)} · programado ${pct(programado)}">`
        + `<span style="width:${p(real)}%"></span><i style="left:${p(programado)}%"></i></div>`;
}

/** Monto sin centavos para tarjetas de indicadores. */
function moneyEntero(n) {
    return (Number(n) || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });
}

function fecha(iso) {
    if (!iso) return '';
    const [y, m, d] = iso.slice(0, 10).split('-');
    return `${d}/${m}/${y}`;
}

/** fetch con JSON; lanza Error con el mensaje del servidor si la respuesta no es 2xx. */
async function api(url, method = 'GET', body = null) {
    const opts = { method, headers: {} };
    if (body !== null) {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(body);
    }
    const res = await fetch(url, opts);
    const data = await res.json().catch(() => ({}));
    if (res.status === 401) {
        window.location.href = 'login';
    }
    if (!res.ok) {
        throw new Error(data.error || 'Error en el servidor');
    }
    return data;
}

/** Objeto con los valores de un formulario (checkboxes como booleanos). */
function formData(form) {
    const out = {};
    for (const el of form.elements) {
        if (!el.name) continue;
        out[el.name] = el.type === 'checkbox' ? el.checked : el.value;
    }
    return out;
}

function fillForm(form, data) {
    for (const el of form.elements) {
        if (!el.name || !(el.name in data)) continue;
        if (el.type === 'checkbox') el.checked = !!Number(data[el.name]) || data[el.name] === true;
        else el.value = data[el.name] ?? '';
    }
}

/** Fecha local de hoy en formato YYYY-MM-DD (toISOString usa UTC y de noche da mañana). */
function hoyISO() {
    const d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

/**
 * Las fotos de celular pesan varios MB: si la imagen mide más de 1600 px o pesa más de 1.5 MB
 * se reduce a 1600 px en JPEG antes de subirla. Los PDF se suben tal cual.
 */
async function reducirImagen(file, max = 1600) {
    if (!file.type.startsWith('image/')) return file;
    const img = await new Promise((resolve, reject) => {
        const i = new Image();
        i.onload = () => resolve(i);
        i.onerror = reject;
        i.src = URL.createObjectURL(file);
    }).catch(() => null);
    if (!img) return file;
    URL.revokeObjectURL(img.src);
    const lado = Math.max(img.naturalWidth, img.naturalHeight);
    if (lado <= max && file.size <= 1.5 * 1024 * 1024) return file;
    const k = Math.min(1, max / lado);
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(img.naturalWidth * k);
    canvas.height = Math.round(img.naturalHeight * k);
    canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.8));
    return blob ? new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : file;
}

/** Sube un archivo (campo "archivo"); el encabezado X-Requested-With lo exige el servidor para frenar envíos de otros sitios. */
async function apiUpload(url, file) {
    const fd = new FormData();
    const f = await reducirImagen(file);
    fd.append('archivo', f, f.name);
    const res = await fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'konstruktor' } });
    const data = await res.json().catch(() => ({}));
    if (res.status === 401) {
        window.location.href = 'login';
    }
    if (!res.ok) {
        throw new Error(data.error || 'Error al subir el archivo');
    }
    return data;
}
