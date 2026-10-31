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
