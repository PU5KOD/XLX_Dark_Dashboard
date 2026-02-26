<?php
$logFile = '/var/log/xlx.log';
$hoursBack = 24;

// Monta array com as últimas 24 horas (hora cheia)
$hours = [];
for ($i = $hoursBack - 1; $i >= 0; $i--) {
    $hours[] = date('d M, H', strtotime("-$i hours"));
}

// Lê o log e conta transmissões por módulo por hora
$data = [];
if (file_exists($logFile) && is_readable($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Formato: "19 Feb, 00:20:22: Opening stream on module D for client ..."
        if (preg_match('/^(\d+ \w+), (\d+):\d+:\d+: Opening stream on module ([A-Z])/', $line, $m)) {
            $key = $m[1] . ', ' . $m[2]; // "19 Feb, 00"
            $module = $m[3];
            if (!isset($data[$key])) $data[$key] = [];
            if (!isset($data[$key][$module])) $data[$key][$module] = 0;
            $data[$key][$module]++;
        }
    }
}

// Coleta todos os módulos presentes nos dados
$modules = [];
foreach ($data as $hourData) {
    foreach (array_keys($hourData) as $mod) {
        $modules[$mod] = true;
    }
}
ksort($modules);
$modules = array_keys($modules);

// Paleta otimizada para daltônismo (alto contraste, distinção por forma e brilho)
$colors = [
    'A' => '#0077BB', // azul
    'B' => '#EE7733', // laranja
    'C' => '#009988', // teal
    'D' => '#EE3377', // magenta
    'E' => '#BBBBBB', // cinza claro
    'F' => '#FFDD00', // amarelo
    'G' => '#AA3377', // roxo-avermelhado
    'H' => '#44BB99', // verde-água
    'I' => '#DDAA33', // ocre/dourado
    'J' => '#CC3311', // vermelho tijolo
    'K' => '#33BBEE', // azul claro
    'L' => '#FFFFFF', // branco
    'M' => '#117733', // verde escuro
    'N' => '#882255', // vinho
    'O' => '#88CCEE', // azul pastel
    'P' => '#999933', // verde oliva
    'Q' => '#FF6600', // laranja forte
    'R' => '#004488', // azul marinho
    'S' => '#DDDDDD', // cinza prata
    'T' => '#66CCEE', // ciano
    'U' => '#994455', // rosa escuro
    'V' => '#55AA44', // verde médio
    'W' => '#FFAA33', // âmbar
    'X' => '#6600AA', // violeta
    'Y' => '#22AACC', // azul esverdeado
    'Z' => '#FF99AA', // rosa claro
];

// Monta datasets para Chart.js
$datasets = [];
foreach ($modules as $mod) {
    $values = [];
    foreach ($hours as $h) {
        $values[] = isset($data[$h][$mod]) ? $data[$h][$mod] : 0;
    }
    $color = isset($colors[$mod]) ? $colors[$mod] : '#c3dcba';
    $datasets[] = [
        'label'           => 'Mod. ' . $mod,
        'data'            => $values,
        'backgroundColor' => $color . 'aa',
        'borderColor'     => $color,
        'borderWidth'     => 1,
    ];
}

// Labels legíveis (só hora)
$labels = array_map(function($h) {
    return substr($h, -2) . 'h';
}, $hours);

// Se chamado via AJAX pelo gráfico, retorna só o JSON
if (isset($_GET['chartdata'])) {
    header('Content-Type: application/json');
    echo json_encode(['labels' => $labels, 'datasets' => $datasets]);
    exit;
}

?>
<div style="padding: 15px;">
    <canvas id="activityChart" style="width:100%; max-height:350px;"></canvas>
    <div style="text-align:right; font-size:10pt; color:#666; margin-top:4px;">
        Updated: <span id="chartLastUpdate">--:--:--</span> &nbsp;|&nbsp; next update in <span id="chartCountdown">60</span>s
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function() {
    var chartInstance = null;
    var countdown = 60;
    var countdownInterval = null;

    function renderChart(labels, datasets) {
        var ctx = document.getElementById('activityChart').getContext('2d');
        if (chartInstance) {
            chartInstance.destroy();
        }
        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: { color: '#c3dcba' }
                    },
                    title: {
                        display: true,
                        text: 'Transmissions by Module - Last 24h',
                        color: '#c3dcba',
                        font: { size: 16 }
                    },
                    tooltip: {
                        callbacks: {
                            title: function(items) { return 'Hour: ' + items[0].label; }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        ticks: { color: '#c3dcba' },
                        grid:  { color: '#333333' }
                    },
                    y: {
                        stacked: true,
                        ticks: { color: '#c3dcba' },
                        grid:  { color: '#333333' },
                        title: {
                            display: true,
                            text: 'No. of Transmissions',
                            color: '#c3dcba'
                        }
                    }
                }
            }
        });
    }

    function loadChart() {
        $.get('./pgs/chart.php?chartdata=1', function(data) {
            try {
                var json = JSON.parse(data);
                renderChart(json.labels, json.datasets);
                var now = new Date();
                document.getElementById('chartLastUpdate').textContent =
                    String(now.getHours()).padStart(2,'0') + ':' +
                    String(now.getMinutes()).padStart(2,'0') + ':' +
                    String(now.getSeconds()).padStart(2,'0');
            } catch(e) {
                // se não for JSON (reload normal), usa os dados embutidos
                renderChart(initialLabels, initialDatasets);
            }
            resetCountdown();
        }).fail(function() {
            renderChart(initialLabels, initialDatasets);
            resetCountdown();
        });
    }

    function resetCountdown() {
        countdown = 60;
        if (countdownInterval) clearInterval(countdownInterval);
        countdownInterval = setInterval(function() {
            countdown--;
            var el = document.getElementById('chartCountdown');
            if (el) el.textContent = countdown;
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                loadChart();
            }
        }, 1000);
    }

    // Dados iniciais embutidos pelo PHP (sem AJAX no primeiro carregamento)
    var initialLabels   = <?php echo json_encode($labels); ?>;
    var initialDatasets = <?php echo json_encode($datasets); ?>;

    renderChart(initialLabels, initialDatasets);
    var now = new Date();
    document.getElementById('chartLastUpdate').textContent =
        String(now.getHours()).padStart(2,'0') + ':' +
        String(now.getMinutes()).padStart(2,'0') + ':' +
        String(now.getSeconds()).padStart(2,'0');
    resetCountdown();
})();
</script>
