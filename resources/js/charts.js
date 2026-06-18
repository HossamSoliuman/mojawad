import {
    Chart,
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler,
    Tooltip,
} from 'chart.js';

Chart.register(
    BarController, BarElement,
    LineController, LineElement, PointElement,
    CategoryScale, LinearScale, Filler, Tooltip,
);

const GOLD = '#c9a153';
const GRID = 'rgba(255,255,255,.06)';
const TICK = '#9a9ab0';

Chart.defaults.color = TICK;
Chart.defaults.font.family = "'Cairo', sans-serif";

const instances = new WeakMap();

const minutes = (seconds) => Math.round(seconds / 60);

function readJson(el, attr, fallback) {
    try {
        return JSON.parse(el.dataset[attr]);
    } catch (e) {
        return fallback;
    }
}

function buildHours(canvas) {
    const values = readJson(canvas, 'values', []).map(minutes);
    const labels = values.map((_, h) => `${String(h).padStart(2, '0')}`);

    return {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: GOLD,
                borderRadius: 4,
                maxBarThickness: 14,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { autoSkip: true, maxRotation: 0 } },
                y: { grid: { color: GRID }, beginAtZero: true, ticks: { precision: 0 } },
            },
        },
    };
}

function buildTrend(canvas) {
    const labels = readJson(canvas, 'labels', []);
    const values = readJson(canvas, 'values', []).map(minutes);

    return {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data: values,
                borderColor: GOLD,
                backgroundColor: 'rgba(201,161,83,.14)',
                fill: true,
                tension: 0.35,
                pointRadius: 0,
                borderWidth: 2,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 6, maxRotation: 0 } },
                y: { grid: { color: GRID }, beginAtZero: true, ticks: { precision: 0 } },
            },
        },
    };
}

function initCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        const existing = instances.get(canvas);
        if (existing) existing.destroy();

        const config = canvas.dataset.chart === 'hours' ? buildHours(canvas) : buildTrend(canvas);
        instances.set(canvas, new Chart(canvas, config));
    });
}

document.addEventListener('DOMContentLoaded', initCharts);
document.addEventListener('livewire:navigated', initCharts);
