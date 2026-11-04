// Graficos de barras de los resultados de las encuestas (usa la libreria Chart.js).
// Cada <canvas class="grafico"> trae sus etiquetas y valores en data-etiquetas y data-valores.
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // si la libreria no se pudo cargar, las tablas con porcentajes igual muestran los datos
        if (typeof Chart === 'undefined') { return; }

        var lienzos = document.querySelectorAll('canvas.grafico');
        for (var i = 0; i < lienzos.length; i++) {
            var lienzo = lienzos[i];
            var etiquetas = JSON.parse(lienzo.getAttribute('data-etiquetas') || '[]');
            var valores = JSON.parse(lienzo.getAttribute('data-valores') || '[]');

            new Chart(lienzo, {
                type: 'bar',
                data: {
                    labels: etiquetas,
                    datasets: [{
                        label: 'Respuestas',
                        data: valores,
                        backgroundColor: '#0f766e',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        }
    });
})();
