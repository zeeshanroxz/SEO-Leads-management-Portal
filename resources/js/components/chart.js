import Alpine from 'alpinejs';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);
Chart.defaults.font.family = "'Inter Variable', system-ui, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#64748b';
Chart.defaults.borderColor = '#e2e8f0';

const PALETTE = {
    brand: '#3d6af0', blue: '#3b82f6', indigo: '#6366f1', violet: '#8b5cf6', teal: '#14b8a6',
    green: '#10b981', amber: '#f59e0b', orange: '#f97316', red: '#ef4444', slate: '#94a3b8', stone: '#a8a29e',
};
const PIE = ['brand', 'teal', 'amber', 'violet', 'green', 'orange', 'red', 'slate', 'indigo', 'stone'];
const color = (c) => PALETTE[c] ?? c;

/*
 * Chart spec (built by <x-chart>):
 *  { type: bar|line|doughnut, labels: [], datasets: [{label, data, color, type?}],
 *    xTitle, yTitle, unit: '$'|'%'|'', stacked, horizontal, legend }
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('chart', (spec) => ({
        instance: null,
        init() {
            const isPie = spec.type === 'doughnut' || spec.type === 'pie';
            const unit = spec.unit ?? '';
            const fmt = (v) => (unit === '$' ? '$' + Number(v).toLocaleString() : unit === '%' ? v + '%' : Number(v).toLocaleString() + (unit ? ' ' + unit : ''));

            const datasets = spec.datasets.map((d) => {
                const base = { label: d.label, data: d.data, type: d.type };
                if (isPie) {
                    return { ...base, backgroundColor: (d.colors ?? spec.labels.map((_, i) => PIE[i % PIE.length])).map(color), borderWidth: 2, borderColor: '#fff' };
                }
                const c = color(d.color ?? 'brand');
                return {
                    ...base, backgroundColor: spec.type === 'line' || d.type === 'line' ? c + '22' : c, borderColor: c,
                    borderWidth: spec.type === 'line' || d.type === 'line' ? 2 : 0, borderRadius: 4, maxBarThickness: 36,
                    tension: 0.3, fill: d.fill ?? false, pointRadius: 3,
                };
            });

            const scales = isPie ? {} : {
                x: { stacked: !!spec.stacked, grid: { display: false }, title: { display: !!spec.xTitle, text: spec.xTitle } },
                y: { stacked: !!spec.stacked, beginAtZero: true, title: { display: !!spec.yTitle, text: spec.yTitle }, ticks: { callback: (v) => fmt(v) } },
            };
            if (spec.horizontal && !isPie) {
                [scales.x, scales.y] = [
                    { ...scales.y, ticks: { callback: (v) => fmt(v) }, title: { display: !!spec.yTitle, text: spec.yTitle } },
                    { ...scales.x, grid: { display: false }, title: { display: !!spec.xTitle, text: spec.xTitle } },
                ];
            }

            this.instance = new Chart(this.$refs.canvas, {
                type: spec.type === 'bar' && spec.horizontal ? 'bar' : spec.type,
                data: { labels: spec.labels, datasets },
                options: {
                    indexAxis: spec.horizontal ? 'y' : 'x',
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 350 },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: spec.legend ?? (spec.datasets.length > 1 || isPie), position: isPie ? 'right' : 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } },
                        tooltip: { callbacks: { label: (ctx) => ` ${ctx.dataset.label ? ctx.dataset.label + ': ' : ''}${fmt(ctx.parsed.y ?? ctx.parsed.x ?? ctx.parsed)}` } },
                    },
                    scales,
                },
            });
        },
        destroy() {
            this.instance?.destroy();
        },
    }));
});
