const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
Chart.defaults.color = isDark ? '#dee2e6' : '#495057';
Chart.defaults.borderColor = isDark ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.1)';

const readData = (canvas) => ({
    labels: JSON.parse(canvas.dataset.labels),
    values: JSON.parse(canvas.dataset.values),
});

const subjectCanvas = document.getElementById('subjectChart');
if (subjectCanvas) {
    const { labels, values } = readData(subjectCanvas);
    new Chart(subjectCanvas, {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Times borrowed', data: values, backgroundColor: '#6c757d' }] },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    });
}

const statusCanvas = document.getElementById('statusChart');
if (statusCanvas) {
    const { labels, values } = readData(statusCanvas);
    new Chart(statusCanvas, {
        type: 'pie',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#6c757d', '#dc3545'],
            }],
        },
        options: { plugins: { legend: { position: 'bottom' } } },
    });
}