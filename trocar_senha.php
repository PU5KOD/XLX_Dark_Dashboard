<?php
session_start();
$htpasswd_file = "/var/www/restricted/.htpasswd";
$usuarios_pendentes = "/var/www/restricted/pendentes.txt";
$pagina_restrita = "/";

// Verifica se o servidor passou a variável de autenticação
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('HTTP/1.0 401 Unauthorized');
    echo "Acesso negado!";
    exit;
}
$usuario = $_SERVER['PHP_AUTH_USER'];

// Verifica se o usuário está na lista de troca de senha
$usuarios_pendentes_lista = file($usuarios_pendentes, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$precisa_trocar = in_array($usuario, $usuarios_pendentes_lista);

function exibirPagina($titulo, $mensagem, $formulario = "") {
    echo "<!DOCTYPE html>
    <html lang='pt-br'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>$titulo</title>
        <style>
            body {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                background-color: #1a1a1a;
                font-family: Arial, sans-serif;
                margin: 0;
            }
            .container {
                text-align: center;
                background: #3a3a3a;
                padding: 20px;
                color: #c3dcba;
                background-image: linear-gradient(to bottom, #444444 0%, #222222 90%);
                border-radius: 10px;
                box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
                width: 350px;
            }
            .error {
                color: red;
                font-weight: bold;
            }
            input {
                padding: 10px;
                margin: 5px;
                width: 90%;
                border: 1px solid #ccc;
                border-radius: 5px;
            }
            input[type='submit'] {
                background-color: #28a745;
                color: #000000;
                cursor: pointer;
                border: none;
                width: 95%;
                font-size: 16px;
            }
            input[type='submit']:hover {
                background-color: #218838;
                color: #c3dcba;
                font-weight: bold;
            }
            input[type='submit']:disabled {
                background-color: #ffa193;
                color: #000000;
                font-weight: normal;
                cursor: not-allowed;
            }
            .requirements {
                text-align: left;
                margin: 10px 0;
                font-size: 14px;
            }
            .requirements li {
                list-style: none;
                margin: 5px 0;
            }
            .valid {
                color: #6eaa5e;
            }
            .valid:before {
                content: '✔ ';
                color: #6eaa5e;
            }
            .invalid {
                color: red;
            }
            .invalid:before {
                content: '✖ ';
                color: red;
            }
            .confirm-message {
                font-size: 14px;
                margin: 5px 0;
                text-align: left;
            }
            .confirm-valid {
                color: #6eaa5e;
            }
            .confirm-invalid {
                color: red;
            }
            .capslock-message {
                font-size: 14px;
                margin: 5px 0;
                text-align: left;
            }
            .capslock-warning {
                color: #ffff00;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>$titulo</h2>
            <p>$mensagem</p>
            $formulario
        </div>
    </body>
    </html>";
    exit;
}

if ($precisa_trocar) {
    $erro = "";
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nova_senha = $_POST["nova_senha"] ?? "";
        $confirmar_senha = $_POST["confirmar_senha"] ?? "";
        // Verifica se a senha atende aos requisitos de segurança
        if (
            strlen($nova_senha) < 8 ||
            !preg_match('/[A-Z]/', $nova_senha) ||
            !preg_match('/[a-z]/', $nova_senha) ||
            !preg_match('/[0-9]/', $nova_senha) ||
            !preg_match('/[\W_]/', $nova_senha)
        ) {
            $erro = "<p class='error'>A senha deve ter pelo menos 8 caracteres, incluindo uma letra maiúscula, uma letra minúscula, um número e um caractere especial.</p>";
        } elseif ($nova_senha !== $confirmar_senha) {
            $erro = "<p class='error'>As senhas não coincidem! Tente novamente.</p>";
        } else {
            // Gera a nova senha criptografada corretamente
            $nova_senha_escapada = escapeshellarg($nova_senha);
            $novo_hash = shell_exec("htpasswd -nbm $usuario $nova_senha_escapada | cut -d':' -f2");
            // Atualiza o arquivo .htpasswd
            $linhas = file($htpasswd_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $nova_lista = [];
            foreach ($linhas as $linha) {
                list($user, $hash) = explode(":", $linha, 2);
                if ($user === $usuario) {
                    $nova_lista[] = "$usuario:$novo_hash"; // Atualiza a senha
                } else {
                    $nova_lista[] = $linha;
                }
            }
            file_put_contents($htpasswd_file, implode("\n", $nova_lista) . "\n");
            // Remove o usuário da lista de pendentes
            $novos_pendentes = array_filter($usuarios_pendentes_lista, function ($u) use ($usuario) {
                return $u !== $usuario;
            });
            file_put_contents($usuarios_pendentes, implode("\n", $novos_pendentes) . "\n");
            exibirPagina(
                "Senha Alterada com Sucesso!",
                "Você será redirecionado em 5 segundos.",
                "<meta http-equiv='refresh' content='5;url=$pagina_restrita'>"
            );
        }
    }
    // Exibe o formulário com validação em tempo real
    exibirPagina(
        "Troca de Senha",
        "Você deve alterar sua senha antes de continuar." . $erro,
        '<form method="POST">
            <input type="password" name="nova_senha" id="nova_senha" placeholder="Nova Senha" required aria-describedby="requirements capslock-message"><br>
            <div class="capslock-message" id="capslock-message" aria-live="polite"></div>
            <ul class="requirements" id="requirements" aria-live="polite">
                <li id="length" class="invalid">Pelo menos 8 caracteres</li>
                <li id="uppercase" class="invalid">Pelo menos uma letra maiúscula</li>
                <li id="lowercase" class="invalid">Pelo menos uma letra minúscula</li>
                <li id="number" class="invalid">Pelo menos um número</li>
                <li id="special" class="invalid">Pelo menos um caractere especial</li>
            </ul>
            <input type="password" name="confirmar_senha" id="confirmar_senha" placeholder="Confirmar Nova Senha" required aria-describedby="confirm-message"><br>
            <div class="confirm-message" id="confirm-message" aria-live="polite"></div>
            <input type="submit" id="submitBtn" value="Alterar Senha" disabled>
        </form>
        <script>
            const passwordInput = document.getElementById("nova_senha");
            const confirmInput = document.getElementById("confirmar_senha");
            const submitBtn = document.getElementById("submitBtn");
            const confirmMessage = document.getElementById("confirm-message");
            const capslockMessage = document.getElementById("capslock-message");

            function validatePassword() {
                const password = passwordInput.value;

                // Validações
                const lengthValid = password.length >= 8;
                const uppercaseValid = /[A-Z]/.test(password);
                const lowercaseValid = /[a-z]/.test(password);
                const numberValid = /[0-9]/.test(password);
                const specialValid = /[\W_]/.test(password);

                // Atualiza classes dos indicadores
                document.getElementById("length").className = lengthValid ? "valid" : "invalid";
                document.getElementById("uppercase").className = uppercaseValid ? "valid" : "invalid";
                document.getElementById("lowercase").className = lowercaseValid ? "valid" : "invalid";
                document.getElementById("number").className = numberValid ? "valid" : "invalid";
                document.getElementById("special").className = specialValid ? "valid" : "invalid";

                return lengthValid && uppercaseValid && lowercaseValid && numberValid && specialValid;
            }

            function validateConfirm() {
                const password = passwordInput.value;
                const confirm = confirmInput.value;
                const passwordsMatch = password === confirm && password !== "";

                // Atualiza mensagem de confirmação
                if (confirm === "") {
                    confirmMessage.textContent = "";
                    confirmMessage.className = "confirm-message";
                } else if (passwordsMatch) {
                    confirmMessage.textContent = "Senhas coincidem";
                    confirmMessage.className = "confirm-message confirm-valid";
                } else {
                    confirmMessage.textContent = "Senhas não coincidem";
                    confirmMessage.className = "confirm-message confirm-invalid";
                }

                return passwordsMatch;
            }

            function updateSubmitButton() {
                const passwordValid = validatePassword();
                const confirmValid = validateConfirm();
                submitBtn.disabled = !(passwordValid && confirmValid);
            }

            function checkCapsLock(event) {
                const capsLockOn = event.getModifierState && event.getModifierState("CapsLock");
                if (capsLockOn) {
                    capslockMessage.textContent = "⚠ Caps Lock está ativado";
                    capslockMessage.className = "capslock-message capslock-warning";
                } else {
                    capslockMessage.textContent = "";
                    capslockMessage.className = "capslock-message";
                }
            }

            passwordInput.addEventListener("input", updateSubmitButton);
            confirmInput.addEventListener("input", updateSubmitButton);
            passwordInput.addEventListener("keydown", checkCapsLock);
            passwordInput.addEventListener("keyup", checkCapsLock);
            passwordInput.addEventListener("focus", checkCapsLock);
        </script>'
    );
}

// Se o usuário não precisa trocar a senha, redireciona normalmente
header("Location: $pagina_restrita");
exit;
?>
