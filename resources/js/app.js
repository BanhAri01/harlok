import Alpine from 'alpinejs';
import L from 'leaflet';
import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip } from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip);

const rupiah = (value) => 'Rp' + Math.round(value).toLocaleString('id-ID');
const kg = (value) => Number(value).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' kg';
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const escapeHtml = (text) => String(text ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);

const waFormat = (text) => escapeHtml(text)
    .replace(/\*([^*\n]+)\*/g, '<strong>$1</strong>')
    .replace(/(^|\s)_([^_\n]+)_/g, '$1<em>$2</em>')
    .replace(/\n/g, '<br>');

const AREAS = {
    bali: { center: [-8.42, 115.18], zoom: 9 },
    diy: { center: [-7.85, 110.4], zoom: 10 },
};

window.rupiah = rupiah;
window.kg = kg;

Alpine.data('papan', ({ url, commodity, buyer = false, offerUrlTemplate = null, focus = null }) => ({
    commodity,
    buyer,
    data: null,
    error: '',
    seen: new Set(),
    firstLoad: true,
    newIds: [],
    selected: null,
    offer: { price: '', qty: '' },
    sending: false,
    notice: '',
    offerError: '',
    area: 'bali',

    init() {
        this.map = L.map(this.$refs.map, { scrollWheelZoom: false, attributionControl: true }).setView(AREAS.bali.center, AREAS.bali.zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap',
        }).addTo(this.map);
        this.layer = L.layerGroup().addTo(this.map);
        this.load();
        this.timer = setInterval(() => this.load(), 12000);
    },

    destroy() {
        clearInterval(this.timer);
    },

    async load() {
        try {
            const response = await fetch(`${url}?komoditas=${encodeURIComponent(this.commodity)}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error();
            const data = await response.json();
            this.newIds = this.firstLoad ? [] : data.points.filter((point) => !this.seen.has(point.id)).map((point) => point.id);
            data.points.forEach((point) => this.seen.add(point.id));
            this.data = data;
            this.error = '';
            this.render();
            this.renderChart();
            if (this.firstLoad && focus) {
                const target = data.points.find((point) => point.id === focus);
                if (target) this.select(target, true);
            }
            this.firstLoad = false;
        } catch {
            this.error = 'Data belum bisa dimuat. Mencoba lagi…';
        }
    },

    switchCommodity(slug) {
        this.commodity = slug;
        this.seen.clear();
        this.firstLoad = true;
        this.selected = null;
        const params = new URLSearchParams(window.location.search);
        params.set('komoditas', slug);
        params.delete('report');
        history.replaceState(null, '', `${window.location.pathname}?${params}`);
        this.load();
    },

    goTo(area) {
        this.area = area;
        this.map.flyTo(AREAS[area].center, AREAS[area].zoom, { duration: 0.8 });
    },

    get averages() {
        const map = {};
        (this.data?.regions ?? []).forEach((row) => (map[row.region] = row.avg));
        return map;
    },

    colorFor(point) {
        if (!point.verified) return '#8A968D';
        const average = this.averages[point.region];
        if (!average) return '#1E7A46';
        if (point.price < average * 0.95) return '#B42318';
        if (point.price > average * 1.05) return '#C98A00';
        return '#1E7A46';
    },

    render() {
        this.layer.clearLayers();
        (this.data?.points ?? []).forEach((point) => {
            const fresh = this.newIds.includes(point.id);
            const icon = L.divIcon({
                className: '',
                html: `<div class="titik-stok ${fresh ? 'titik-baru' : ''}" style="position:relative;background:${this.colorFor(point)}"></div>`,
                iconSize: [14, 14],
                iconAnchor: [7, 7],
            });
            const marker = L.marker([point.lat, point.lng], { icon, keyboard: true, title: `${point.region} ${rupiah(point.price)}/kg` });
            marker.bindPopup(this.popup(point));
            if (this.buyer) marker.on('click', () => this.select(point));
            marker.addTo(this.layer);
            if (fresh) setTimeout(() => marker.openPopup(), 50);
        });
    },

    popup(point) {
        const label = point.sample ? `<span style="color:#8A968D">${point.source === 'simulator' ? 'dari simulator' : 'data contoh'}</span>` : (point.verified ? '✔ terverifikasi' : 'belum terverifikasi');
        return `<div style="min-width:170px">
            <div style="font-weight:700;font-size:15px">${escapeHtml(rupiah(point.price))}/kg</div>
            <div>${escapeHtml(kg(point.quantity_kg))} · ${escapeHtml(point.region)}</div>
            ${point.farmer ? `<div>Peternak: ${escapeHtml(point.farmer)}</div>` : ''}
            <div style="font-size:12px;color:#5b665f;margin-top:4px">${label} · ${escapeHtml(point.ago)}</div>
        </div>`;
    },

    select(point, fly = false) {
        this.selected = point;
        this.offer = { price: point.price, qty: point.quantity_kg };
        this.notice = '';
        this.offerError = '';
        if (fly) this.map.flyTo([point.lat, point.lng], 11, { duration: 0.6 });
    },

    get offerPercent() {
        if (!this.selected) return null;
        const average = this.averages[this.selected.region];
        if (!average || !this.offer.price) return null;
        return ((this.offer.price - average) / average) * 100;
    },

    async sendOffer() {
        if (!this.selected || this.sending) return;
        this.sending = true;
        this.offerError = '';
        try {
            const response = await fetch(offerUrlTemplate.replace('__ID__', this.selected.id), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ price_per_kg: Number(this.offer.price), quantity_kg: Number(this.offer.qty) }),
            });
            const body = await response.json();
            if (!response.ok) {
                this.offerError = Object.values(body.errors ?? {}).flat()[0] ?? body.message ?? 'Tawaran gagal dikirim.';
                return;
            }
            this.notice = 'Tawaran terkirim ke WhatsApp peternak. Jawabannya akan muncul di halaman Tawaran Saya.';
            this.load();
        } catch {
            this.offerError = 'Koneksi terputus. Coba lagi.';
        } finally {
            this.sending = false;
        }
    },

    renderChart() {
        const trend = this.data?.trend ?? [];
        if (!this.$refs.chart) return;
        const labels = trend.map((row) => new Date(row.date + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }));
        const values = trend.map((row) => row.avg);
        if (this.chart) {
            this.chart.data.labels = labels;
            this.chart.data.datasets[0].data = values;
            this.chart.update('none');
            return;
        }
        this.chart = new Chart(this.$refs.chart, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    data: values,
                    borderColor: '#F5B700',
                    backgroundColor: 'rgba(245,183,0,0.12)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2,
                    pointBackgroundColor: '#F5B700',
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (context) => rupiah(context.parsed.y) + '/kg' } } },
                scales: {
                    x: { ticks: { color: '#B9C7BC', maxRotation: 0, autoSkip: true, maxTicksLimit: 7 }, grid: { display: false } },
                    y: { ticks: { color: '#B9C7BC', callback: (value) => (value / 1000).toLocaleString('id-ID') + 'rb' }, grid: { color: 'rgba(232,239,230,0.08)' } },
                },
            },
        });
    },

    rupiah,
    kg,
}));

Alpine.data('simulator', ({ sendUrl, pollUrl, resetUrl }) => ({
    messages: [],
    text: '',
    after: 0,
    busy: false,
    error: '',
    suggestions: ['HARLOK', 'telur 2 ton 26rb', 'ya', 'harga', 'stok', 'tawaran', 'menu'],

    init() {
        this.poll();
        this.timer = setInterval(() => this.poll(), 4000);
    },

    destroy() {
        clearInterval(this.timer);
    },

    push(list) {
        list.forEach((message) => {
            if (message.id > this.after) {
                this.messages.push({ from: 'bot', html: waFormat(message.body), time: message.time, key: 'b' + message.id });
                this.after = message.id;
            }
        });
        this.$nextTick(() => this.$refs.feed?.scrollTo({ top: this.$refs.feed.scrollHeight, behavior: 'smooth' }));
    },

    async poll() {
        try {
            const response = await fetch(`${pollUrl}?after=${this.after}`, { headers: { Accept: 'application/json' } });
            if (response.ok) this.push((await response.json()).messages);
        } catch {}
    },

    async send(value = null) {
        const body = (value ?? this.text).trim();
        if (!body || this.busy) return;
        this.busy = true;
        this.error = '';
        this.text = '';
        const now = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace(':', '.');
        this.messages.push({ from: 'me', html: waFormat(body), time: now, key: 'u' + Date.now() });
        this.$nextTick(() => this.$refs.feed?.scrollTo({ top: this.$refs.feed.scrollHeight, behavior: 'smooth' }));
        try {
            const response = await fetch(sendUrl + `?after=${this.after}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ message: body }),
            });
            const data = await response.json();
            if (!response.ok) {
                this.error = data.message ?? 'Pesan gagal dikirim.';
                return;
            }
            this.push(data.messages);
        } catch {
            this.error = 'Koneksi terputus. Coba lagi.';
        } finally {
            this.busy = false;
            this.$refs.input?.focus();
        }
    },

    async reset() {
        await fetch(resetUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() } });
        this.messages = [];
        this.after = 0;
    },
}));

Alpine.data('widgetMaker', ({ baseUrl, commodity }) => ({
    commodity,
    region: '',
    theme: '',
    copied: false,

    get src() {
        const query = new URLSearchParams({ komoditas: this.commodity });
        if (this.region) query.set('wilayah', this.region);
        if (this.theme) query.set('tema', this.theme);
        return `${baseUrl}?${query}`;
    },

    get height() {
        return this.region ? 130 : 420;
    },

    get code() {
        return `<iframe src="${this.src}" title="Harga pasar Harlok" width="100%" height="${this.height}" style="border:0" loading="lazy"></iframe>`;
    },

    async copy() {
        try {
            await navigator.clipboard.writeText(this.code);
            this.copied = true;
            setTimeout(() => (this.copied = false), 2000);
        } catch {}
    },
}));

window.Alpine = Alpine;
Alpine.start();
