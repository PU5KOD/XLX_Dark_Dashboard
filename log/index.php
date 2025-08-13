<?php
session_start();
session_set_cookie_params(3600); // Expira em 1 hora
if (!isset($_SESSION['csrf_token'])) {
   $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Configuração da senha
if (isset($_POST['password'])) {
   $_SESSION['password'] = $_POST['password'];
}
// Configuração do tempo de recarga
$reload_time = 5000;
if (isset($_POST['reload_time']) && is_numeric($_POST['reload_time']) && $_POST['reload_time'] >= 1) {
   if ($_POST['csrf_token'] === $_SESSION['csrf_token']) {
      $_SESSION['reload_time'] = (int)$_POST['reload_time'] * 1000;
   } else {
      die('CSRF token inválido');
   }
}
if (isset($_SESSION['reload_time'])) {
   $reload_time = $_SESSION['reload_time'];
}
// Limpar log acumulado
if (isset($_POST['clear_log']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
   $_SESSION['log_content'] = [];
} elseif (isset($_POST['clear_log'])) {
   die('CSRF token inválido');
}
// Verificação da senha
if (isset($_SESSION['password'])) {
   if ($_SESSION['password'] != "XLX_log") {
      echo '<form name="frmpass" action="./index.php" method="post" style="text-align: center; margin-top: 20px;">
         <input type="password" name="password" style="padding: 5px; background-color: #333333; color: #c3dcba; border: 1px solid #444444;" />
         <input type="submit" value="Entrar" style="padding: 5px 10px; background-color: #333333; color: #c3dcba; border: 1px solid #444444; cursor: pointer;" />
         <input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">
      </form>';
      die();
   }
} else {
   echo '<form name="frmpass" action="./index.php" method="post" style="text-align: center; margin-top: 20px;">
         <input type="password" name="password" style="padding: 5px; background-color: #333333; color: #c3dcba; border: 1px solid #444444;" />
         <input type="submit" value="Entrar" style="padding: 5px 10px; background-color: #333333; color: #c3dcba; border: 1px solid #444444; cursor: pointer;" />
         <input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">
      </form>';
   die();
}
?>
<!DOCTYPE html>
<html>
<head>
   <meta charset="utf-8" />
   <title>XLX Live Log Monitor</title>

   <!-- Fontes monoespacadas -->
   <link href="https://fonts.googleapis.com/css2?family=Fira+Code&family=Source+Code+Pro&family=Roboto+Mono&family=JetBrains+Mono&family=Inconsolata&family=Ubuntu+Mono&display=swap" rel="stylesheet">

   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" />

   <style>
      /* Para manutenção, considere mover o CSS para um arquivo externo (ex.: styles.css) */
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
         font-size: 32px;
         text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
         background: linear-gradient(to right, #c3dcba, #a3b25a);
         -webkit-background-clip: text;
         background-clip: text;
         color: transparent;
      }
      .error-message {
         color: #cc0000;
         text-align: center;
         margin-bottom: 15px;
         font-size: 14px;
      }
      .controls {
         display: flex;
         gap: 10px;
         align-items: center;
         margin-bottom: 15px;
         flex-wrap: wrap;
         justify-content: space-between;
      }
      .control-group {
         display: flex;
         gap: 10px;
         align-items: center;
         flex-shrink: 0;
         white-space: nowrap;
         position: relative;
      }
      .reload-time-container {
         position: relative;
         display: inline-block;
      }
      input[type="number"], input[type="text"], select {
         padding: 5px;
         background-color: #333333;
         color: #c3dcba;
         border: 1px solid #444444;
         border-radius: 3px;
         font-size: 14px;
      }
      input#reload_time_input {
         width: 50px;
         padding-right: 28px;
      }
      input#filter_input {
         padding-right: 25px;
         width: 130px;
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
         font-size: 14px;
      }
      button:hover, input[type="button"]:hover, input[type="submit"]:hover {
         background-color: #444444;
      }
      button#pause_button.paused {
         background-color: #a3b25a;
         color: #1a1a1a;
      }
      #clear_filter_button {
         position: absolute;
         right: 5px;
         top: 50%;
         transform: translateY(-50%);
         background: transparent;
         border: none;
         color: #c3dcba;
         font-weight: bold;
         cursor: pointer;
         font-size: 25px;
         padding: 0;
         line-height: 1;
      }
      #clear_filter_button:hover {
         color: #a3b25a;
      }
      #clear_filter_button:focus {
         outline: none;
      }
      #reload_time_btn {
         position: absolute;
         right: 5px;
         top: 50%;
         transform: translateY(-50%);
         background: transparent;
         border: none;
         color: #c3dcba;
         cursor: pointer;
         padding: 0;
         font-size: 16px;
         display: flex;
         align-itemsグロ

         align-items: center;
         justify-content: center;
      }
      #reload_time_btn:hover {
         color: #a3b25a;
      }
      #reload_time_btn:focus {
         outline: none;
      }
      #log_content {
         background-color: #a0a0a0;
         color: #000000;
         padding: 10px;
         max-height: 600px;
         overflow-y: auto;
         font-family: 'JetBrains Mono', monospace;
         font-size: 10px;
         line-height: 1.2;
         white-space: pre-wrap;
         border: 2px solid #444444;
         border-radius: 5px;
         box-shadow: 0 2px 5px rgba(0,0,0,0.3);
         transition: background-color 0.3s;
         position: relative;
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
      .log-footer {
         display: flex;
         justify-content: space-between;
         align-items: center;
         margin-top: 5px;
         font-size: 14px;
         color: #c3dcba;
      }
      .font-selector, .size-selector {
         background-color: #333333;
         color: #c3dcba;
         border: 1px solid #444444;
         border-radius: 3px;
         padding: 5px;
         cursor: pointer;
         margin-left: 5px;
      }
      .font-selector {
         margin-left: 0;
      }
      .font-size-group {
         display: flex;
         align-items: center;
         white-space: nowrap;
      }
      .back-link-button {
         display: inline-flex;
         align-items: center;
         gap: 5px;
         padding: 5px 10px;
         background-color: #333333;
         color: #c3dcba;
         border: 1px solid #444444;
         border-radius: 3px;
         cursor: pointer;
         text-decoration: none;
         transition: background-color 0.2s;
      }
      .back-link-button:hover {
         background-color: #444444;
      }
      @media (max-width: 600px) {
         .controls {
            flex-wrap: wrap;
         }
         .control-group {
            flex-basis: 100%;
         }
         .log-footer {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
         }
         .font-size-group {
            margin-top: 5px;
         }
      }
   </style>

   <script>
      let reloadInterval;
      let isPaused = false;
      let autoScroll = true;

      function debounce(func, wait) {
         let timeout;
         return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
         };
      }

      function startLogUpdate(reloadTime) {
         if (reloadInterval) clearInterval(reloadInterval);
         if (!isPaused) {
            reloadInterval = setInterval(fetchLog, reloadTime);
            fetchLog();
         }
      }

      function fetchLog() {
         const filter = document.getElementById('filter_input').value;
         fetch('fetch_log.php?filter=' + encodeURIComponent(filter))
            .then(response => {
               if (!response.ok) throw new Error('Falha ao carregar o log');
               return response.text();
            })
            .then(data => {
               const logContent = document.getElementById('log_content');
               if (data.startsWith('Erro:')) {
                  logContent.textContent = data;
                  return;
               }
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
               if (autoScroll) logContent.scrollTop = 0;
            })
            .catch(error => {
               console.error('Erro ao carregar log:', error);
               document.getElementById('log_content').textContent = 'Erro ao carregar o log. Tente novamente.';
            });
      }

      function UpdateReloadTime() {
         const reloadTimeInput = document.getElementById('reload_time_input').value;
         if (reloadTimeInput && !isNaN(reloadTimeInput) && reloadTimeInput >= 1) {
            const formData = new FormData();
            formData.append('reload_time', reloadTimeInput);
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            fetch('./index.php', { method: 'POST', body: formData })
               .then(response => {
                  if (!response.ok) throw new Error('Falha ao atualizar tempo');
                  startLogUpdate(reloadTimeInput * 1000);
               })
               .catch(error => {
                  console.error('Erro ao atualizar tempo:', error);
                  alert('Erro ao atualizar tempo de recarga.');
               });
         } else {
            alert('Por favor, insira um valor numérico maior ou igual a 1.');
         }
      }

      function togglePause() {
         isPaused = !isPaused;
         const pauseButton = document.getElementById('pause_button');
         const logContent = document.getElementById('log_content');
         if (isPaused) {
            clearInterval(reloadInterval);
            pauseButton.innerHTML = '<i class="fas fa-play"></i> Retomar';
            pauseButton.classList.add('paused');
            logContent.classList.add('paused');
         } else {
            const reloadTime = document.getElementById('reload_time_input').value * 1000;
            startLogUpdate(reloadTime);
            pauseButton.innerHTML = '<i class="fas fa-pause"></i> Pausar';
            pauseButton.classList.remove('paused');
            logContent.classList.remove('paused');
         }
      }

      function clearLog() {
         const formData = new FormData();
         formData.append('clear_log', true);
         formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
         fetch('./index.php', { method: 'POST', body: formData })
            .then(() => fetchLog())
            .catch(error => {
               console.error('Erro ao limpar log:', error);
               document.getElementById('log_content').textContent = 'Erro ao limpar o log.';
            });
      }

      function exportLog() {
         window.location.href = 'export_log.php?csrf_token=<?php echo $_SESSION['csrf_token']; ?>';
      }

      function toggleAutoScroll() {
         autoScroll = !autoScroll;
         document.getElementById('scroll_toggle').innerHTML = '<i class="fas fa-scroll"></i> ' + (autoScroll ? 'Desativar Auto-Scroll' : 'Ativar Auto-Scroll');
      }

      window.onload = () => {
         startLogUpdate(<?php echo $reload_time; ?>);
         const filterInput = document.getElementById('filter_input');
         filterInput.oninput = debounce(fetchLog, 500);

         const clearFilterButton = document.getElementById('clear_filter_button');
         clearFilterButton.addEventListener('click', () => {
            filterInput.value = '';
            filterInput.dispatchEvent(new Event('input'));
         });

         const fontSelect = document.getElementById('font_select');
         const sizeSelect = document.getElementById('font_size_select');
         const logContent = document.getElementById('log_content');

         fontSelect.value = "'JetBrains Mono', monospace";
         logContent.style.fontFamily = fontSelect.value;

         fontSelect.addEventListener('change', () => {
            logContent.style.fontFamily = fontSelect.value;
         });

         sizeSelect.addEventListener('change', () => {
            logContent.style.fontSize = sizeSelect.value + 'px';
         });

         const reloadBtn = document.getElementById('reload_time_btn');
         reloadBtn.addEventListener('click', UpdateReloadTime);
      };
   </script>
</head>
<body>
   <div class="container">
      <div class="header"><h1>XLX Live Log Monitor</h1></div>
      <?php
      if (isset($_SESSION['error'])) {
         echo '<div class="error-message">' . htmlspecialchars($_SESSION['error']) . '</div>';
         unset($_SESSION['error']);
      }
      ?>
      <div class="controls">
         <div class="control-group reload-time-container">
            <label for="reload_time_input">Reload (s):</label>
            <input type="number" id="reload_time_input" name="reload_time" value="<?php echo $reload_time / 1000; ?>" min="1" aria-label="Tempo de recarga em segundos" />
            <button type="button" id="reload_time_btn" title="Atualizar" aria-label="Atualizar tempo de recarga">
               <i class="fas fa-sync-alt"></i>
            </button>
         </div>

         <div class="control-group" style="position: relative;">
            <label for="filter_input" style="margin-right: 5px;">Filtro:</label>
            <input type="text" id="filter_input" name="filter" placeholder="Ex.: PU5KOD" aria-label="Filtrar logs" />
            <button id="clear_filter_button" title="Limpar filtro" aria-label="Limpar filtro">×</button>
         </div>

         <div class="control-group">
            <button id="pause_button" onclick="togglePause()" aria-label="Pausar atualização do log"><i class="fas fa-pause"></i> Pausar</button>
            <button onclick="clearLog()" aria-label="Limpar log"><i class="fas fa-trash"></i> Limpar</button>
            <button onclick="exportLog()" aria-label="Exportar log"><i class="fas fa-download"></i> Exportar</button>
            <button onclick="toggleAutoScroll()" id="scroll_toggle" aria-label="Alternar auto-scroll"><i class="fas fa-scroll"></i> Desativar Auto-Scroll</button>
         </div>
      </div>

      <div id="log_content" aria-live="polite">Carregando log...</div>

      <div class="log-footer">
         <div class="back-link">
            <a href="../index.php" class="back-link-button" aria-label="Voltar para a página inicial">
               <i class="fas fa-arrow-left"></i> Voltar
            </a>
         </div>
         <div class="font-size-group">
            <label for="font_select">Fonte:</label>
            <select id="font_select" class="font-selector" aria-label="Selecionar fonte do log">
               <option value="'Fira Code', monospace">Fira Code</option>
               <option value="'Source Code Pro', monospace">Source Code Pro</option>
               <option value="'Roboto Mono', monospace">Roboto Mono</option>
               <option value="'JetBrains Mono', monospace" selected>JetBrains Mono</option>
               <option value="'Inconsolata', monospace">Inconsolata</option>
               <option value="'Ubuntu Mono', monospace">Ubuntu Mono</option>
               <option value="'monospace'">Monospace padrão</option>
            </select>

            <label for="font_size_select" style="margin-left: 15px;">Tamanho:</label>
            <select id="font_size_select" class="size-selector" aria-label="Selecionar tamanho da fonte">
               <option value="7">7 px</option>
               <option value="8">8 px</option>
               <option value="9">9 px</option>
               <option value="10" selected>10 px</option>
               <option value="11">11 px</option>
               <option value="12">12 px</option>
               <option value="13">13 px</option>
               <option value="14">14 px</option>
            </select>
         </div>
      </div>
   </div>
</body>
</html>