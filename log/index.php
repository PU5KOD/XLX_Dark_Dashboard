<?php
session_start();

// Configuração da senha
if (isset($_POST['password'])) {
   $_SESSION['password'] = $_POST['password'];
}

// Configuração do tempo de recarga
$reload_time = 10000; // Valor padrão: 10 segundos (em milissegundos)
if (isset($_POST['reload_time']) && is_numeric($_POST['reload_time']) && $_POST['reload_time'] >= 1) {
   $_SESSION['reload_time'] = (int)$_POST['reload_time'] * 1000; // Converte segundos para milissegundos
}
if (isset($_SESSION['reload_time'])) {
   $reload_time = $_SESSION['reload_time'];
}

// Limpar o log acumulado
if (isset($_POST['clear_log'])) {
   $_SESSION['log_content'] = [];
}

// Verificação da senha
if (isset($_SESSION['password'])) {
   if ($_SESSION['password'] != "XLX_log") {
      echo '
      <form name="frmpass" action="./index.php" method="post" style="text-align: center; margin-top: 20px;">
         <input type="password" name="password" style="padding: 5px; background-color: #333333; color: #c3dcba; border: 1px solid #444444;" />
         <input type="submit" value="Entrar" style="padding: 5px 10px; background-color: #333333; color: #c3dcba; border: 1px solid #444444; cursor: pointer;" />
      </form>';
      die();
   }
} else {
   echo '
      <form name="frmpass" action="./index.php" method="post" style="text-align: center; margin-top: 20px;">
         <input type="password" name="password" style="padding: 5px; background-color: #333333; color: #c3dcba; border: 1px solid #444444;" />
         <input type="submit" value="Entrar" style="padding: 5px 10px; background-color: #333333; color: #c3dcba; border: 1px solid #444444; cursor: pointer;" />
      </form>';
   die();
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
   <meta http-equiv="content-type" content="text/html; charset=utf-8" />
   <title>XLX Live Log Monitor</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
   <style>
      body {
         background-color: #1a1a1a;
         color: #c3dcba;
         font-family: 'Arial', sans-serif;
         margin: 0;
         padding: 20px;
      }
      .container {
         width: 90%;
         margin: 0 auto;
         padding: 10px;
      }
      .header {
         text-align: center;
         margin-bottom: 20px;
      }
      .header h1 {
         font-size: 32px; /* Tamanho maior para o título */
         color: #c3dcba;
         margin: 0;
         padding: 15px 0; /* Ajustado para equilibrar */
         text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
         background: linear-gradient(to right, #c3dcba, #a3b25a); /* Gradiente no título */
         -webkit-background-clip: text; /* Aplica o gradiente apenas ao texto */
         background-clip: text;
         color: transparent; /* Torna o texto transparente para o gradiente aparecer */
      }
      .controls {
         display: flex;
         gap: 10px;
         align-items: center;
         margin-bottom: 15px;
         flex-wrap: nowrap; /* Impede a quebra de linha */
         justify-content: space-between; /* Alinha os elementos com espaço uniforme */
      }
      .control-group {
         display: flex;
         gap: 10px;
         align-items: center;
         flex-shrink: 0; /* Impede que os grupos encolham demais */
         white-space: nowrap; /* Garante que os elementos internos fiquem em uma linha */
      }
      input[type="number"], input[type="text"] {
         padding: 5px;
         background-color: #333333;
         color: #c3dcba;
         border: 1px solid #444444;
         border-radius: 3px;
      }
      input#reload_time_input {
         width: 50px; /* Largura reduzida para o campo de tempo de recarga */
      }
      input#filter_input {
         width: 130px; /* Largura aumentada para o campo de filtro */
      }
      button, input[type="button"], input[type="submit"] {
         padding: 5px 10px;
         background-color: #333333;
         color: #c3dcba;
         border: 1px solid #444444;
         border-radius: 3px;
         cursor: pointer;
         transition: background-color 0.2s;
         display: flex;
         align-items: center;
         gap: 5px;
      }
      button:hover, input[type="button"]:hover, input[type="submit"]:hover {
         background-color: #444444;
      }
      button#pause_button.paused {
         background-color: #a3b25a; /* Cor destacada para o botão pausado */
         color: #1a1a1a; /* Contraste com o fundo escuro */
      }
      button i {
         font-size: 14px; /* Tamanho consistente para os ícones */
      }
      label {
         color: #c3dcba;
      }
      #log_content {
         background-color: #909090;
         color: #000000;
         padding: 10px;
         max-height: 600px;
         overflow-y: auto;
         font-family: 'Courier New', monospace;
         font-size: 13px;
         line-height: 1.2;
         white-space: pre-wrap;
         border: 2px solid #444444;
         border-radius: 5px;
         box-shadow: 0 2px 5px rgba(0,0,0,0.3);
         transition: background-color 0.3s;
      }
      #log_content.paused {
         background-color: #d4d488 !important;
      }
      .log-line {
         margin: 0;
         padding: 0;
      }
      .log-error {
         color: #cc0000;
         font-weight: bold;
      }
      .log-warning {
         color: #ff9900;
      }
      /* Media query para telas menores */
      @media (max-width: 600px) {
         .controls {
            flex-wrap: wrap; /* Permite quebra de linha em telas pequenas */
         }
         .control-group {
            flex-basis: 100%; /* Cada grupo ocupa 100% da largura */
         }
      }
   </style>
   <script>
      let reloadInterval;
      let isPaused = false;

      // Função de debounce
      function debounce(func, wait) {
         let timeout;
         return function executedFunction(...args) {
            const later = () => {
               clearTimeout(timeout);
               func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
         };
      }

      function startLogUpdate(reloadTime) {
         if (reloadInterval) {
            clearInterval(reloadInterval);
         }
         if (!isPaused) {
            reloadInterval = setInterval(fetchLog, reloadTime);
            fetchLog();
         }
      }

      function fetchLog() {
         const filter = document.getElementById('filter_input').value;
         fetch('fetch_log.php?filter=' + encodeURIComponent(filter))
            .then(response => response.text())
            .then(data => {
               const logContent = document.getElementById('log_content');
               logContent.innerHTML = '';
               data.split('\n').forEach(line => {
                  const div = document.createElement('div');
                  div.className = 'log-line';
                  if (line.includes('ERROR')) {
                     div.className += ' log-error';
                  } else if (line.includes('WARNING')) {
                     div.className += ' log-warning';
                  }
                  div.textContent = line;
                  logContent.appendChild(div);
               });
               logContent.scrollTop = 0;
            })
            .catch(error => {
               console.error('Erro ao buscar log:', error);
               document.getElementById('log_content').textContent = 'Erro ao carregar o log.';
            });
      }

      function UpdateReloadTime() {
         const reloadTimeInput = document.getElementById('reload_time_input').value;
         if (reloadTimeInput >= 1) {
            const formData = new FormData();
            formData.append('reload_time', reloadTimeInput);

            fetch('./index.php', {
               method: 'POST',
               body: formData
            })
            .then(() => {
               startLogUpdate(reloadTimeInput * 1000);
            })
            .catch(error => console.error('Erro ao atualizar tempo:', error));
         } else {
            alert('Por favor, insira um valor maior ou igual a 1.');
         }
      }

      function togglePause() {
         isPaused = !isPaused;
         const pauseButton = document.getElementById('pause_button');
         const logContent = document.getElementById('log_content');
         if (isPaused) {
            clearInterval(reloadInterval);
            pauseButton.innerHTML = '<i class="fas fa-play"></i> Retomar';
            pauseButton.classList.add('paused'); // Adiciona a classe paused ao botão
            logContent.classList.add('paused');
         } else {
            const reloadTime = document.getElementById('reload_time_input').value * 1000;
            startLogUpdate(reloadTime);
            pauseButton.innerHTML = '<i class="fas fa-pause"></i> Pausar';
            pauseButton.classList.remove('paused'); // Remove a classe paused do botão
            logContent.classList.remove('paused');
         }
      }

      function clearLog() {
         const formData = new FormData();
         formData.append('clear_log', true);

         fetch('./index.php', {
            method: 'POST',
            body: formData
         })
         .then(() => fetchLog())
         .catch(error => console.error('Erro ao limpar log:', error));
      }

      function exportLog() {
         window.location.href = 'export_log.php';
      }

      window.onload = () => {
         startLogUpdate(<?php echo $reload_time; ?>);
         // Aplica debounce ao evento de filtro com 300ms de atraso
         const debouncedFetchLog = debounce(fetchLog, 500);
         document.getElementById('filter_input').oninput = debouncedFetchLog;
      };
   </script>
</head>
<body>
   <div class="container">
      <div class="header">
         <h1>XLX Live Log Monitor</h1>
      </div>
      <div class="controls">
         <div class="control-group">
            <label for="reload_time_input">Tempo de recarga (segundos):</label>
            <input type="number" id="reload_time_input" name="reload_time" value="<?php echo $reload_time / 1000; ?>" min="1" step="1" />
            <button type="button" onclick="UpdateReloadTime()"><i class="fas fa-sync-alt"></i> Atualizar</button>
         </div>
         <div class="control-group">
            <label for="filter_input">Filtrar log:</label>
            <input type="text" id="filter_input" name="filter" placeholder="Ex.: PU5KOD" />
         </div>
         <div class="control-group">
            <button id="pause_button" onclick="togglePause()"><i class="fas fa-pause"></i> Pausar</button>
            <button onclick="clearLog()"><i class="fas fa-trash"></i> Limpar Log</button>
            <button onclick="exportLog()"><i class="fas fa-download"></i> Exportar Log</button>
         </div>
      </div>
      <div id="log_content">
         Carregando log...
      </div>
   </div>
</body>
</html>
