<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 15px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .logo-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .logo-subtitle {
            font-size: 12px;
            opacity: 0.9;
        }

        .card-body {
            padding: 30px 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-google {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            background: white;
            color: #333;
        }

        .btn-google:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
        }

        .divider-line {
            flex: 1;
            height: 1px;
            background: #e9ecef;
        }

        .divider-text {
            padding: 0 10px;
            color: #999;
            font-size: 12px;
        }

        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .footer-text a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }

        .form-check {
            margin-bottom: 15px;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            border-radius: 4px;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }

        .form-check-label {
            cursor: pointer;
            margin-left: 8px;
        }

        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        .input-group-password {
            position: relative;
        }

        .loading-spinner {
            display: none;
            margin-right: 8px;
        }

        .two-fa-container {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .two-fa-code-input {
            letter-spacing: 10px;
            font-size: 20px;
            font-weight: bold;
            text-align: center;
        }

        @media (max-width: 576px) {
            .login-container {
                padding: 10px;
            }

            .card-body {
                padding: 20px;
            }

            .logo-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <div class="logo-title">
                    <i class="fas fa-spa"></i> Pet Spa
                </div>
                <div class="logo-subtitle">Sistema de Gestión</div>
            </div>

            <div class="card-body">
                <!-- Mensajes de alerta -->
                <div id="alertContainer"></div>

                <!-- Formulario Normal -->
                <form id="loginForm" method="POST">
                    <h5 class="mb-4 text-center" style="color: #333; font-weight: 600;">Iniciar Sesión</h5>

                    <div class="form-group">
                        <label class="form-label" for="email">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="ejemplo@correo.com" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Contraseña</label>
                        <div class="input-group-password">
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                            <span class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                        <label class="form-check-label" for="rememberMe">
                            Recuérdame
                        </label>
                    </div>

                    <button type="submit" class="btn btn-login w-100 text-white">
                        <span class="loading-spinner" id="loadingSpinner">
                            <span class="spinner-border spinner-border-sm"></span>
                        </span>
                        Ingresar
                    </button>
                </form>

                <!-- Formulario 2FA -->
                <div class="two-fa-container" id="twoFAContainer">
                    <h5 class="mb-3 text-center">Verificación de Dos Factores</h5>
                    <p class="text-muted text-center small mb-3">
                        Ingrese el código de 6 dígitos de su aplicación de autenticación
                    </p>
                    <form id="twoFAForm">
                        <div class="form-group">
                            <input type="text" class="form-control two-fa-code-input" id="twoFACode" name="2fa_code" 
                                   maxlength="6" pattern="[0-9]{6}" placeholder="000000" autocomplete="off" required>
                        </div>
                        <button type="submit" class="btn btn-login w-100 text-white">Verificar</button>
                        <button type="button" class="btn btn-outline-secondary w-100 mt-2" onclick="backToLogin()">Volver</button>
                    </form>
                </div>

                <div class="divider">
                    <div class="divider-line"></div>
                    <div class="divider-text">O CONTINÚA CON</div>
                    <div class="divider-line"></div>
                </div>

                <a href="/petspa/api/auth/google_oauth.php" class="btn btn-google w-100">
                    <i class="fab fa-google"></i> Google
                </a>

                <div class="footer-text">
                    ¿No tienes cuenta? <a href="/petspa/public/register.php">Regístrate aquí</a>
                </div>

                <div class="footer-text mt-3">
                    <a href="/petspa/public/forgot_password.php" style="font-size: 12px;">¿Olvidaste tu contraseña?</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = event.target.closest('.password-toggle').querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function showAlert(message, type = 'danger') {
            const alertHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.getElementById('alertContainer').innerHTML = alertHTML;
        }

        function showLoading(show = true) {
            document.getElementById('loadingSpinner').style.display = show ? 'inline-block' : 'none';
        }

        // Mostrar mensajes de GET
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.get('logout')) {
                showAlert('Sesión cerrada correctamente', 'success');
            }
            if (urlParams.get('verified')) {
                showAlert('¡Email verificado! Ya puedes iniciar sesión', 'success');
            }
            if (urlParams.get('expired')) {
                showAlert('Tu sesión ha expirado. Por favor, inicia sesión de nuevo', 'warning');
            }
            if (urlParams.get('google_error')) {
                showAlert('Error de Google: ' + urlParams.get('google_error'), 'danger');
            }
        });

        // Manejo del formulario de login
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            showLoading(true);

            try {
                const response = await fetch('/petspa/api/auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        email: email,
                        password: password
                    })
                });

                const data = await response.json();

                if (data.require_2fa) {
                    // Mostrar formulario de 2FA
                    document.getElementById('loginForm').style.display = 'none';
                    document.getElementById('twoFAContainer').style.display = 'block';
                    document.getElementById('twoFACode').focus();
                } else if (data.success && data.redirect) {
                    // Redirigir al dashboard
                    window.location.href = data.redirect;
                } else if (data.use_google) {
                    // Usuario registrado con Google, mostrar mensaje y redirigir
                    showAlert(data.message + ' <button class="btn btn-sm btn-primary ms-2" onclick="loginWithGoogle()">Usar Google</button>', 'info');
                    // Opcional: redirigir automáticamente después de 3 segundos
                    setTimeout(() => {
                        window.location.href = '/petspa/api/auth/google_oauth.php';
                    }, 3000);
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                showAlert('Error al conectarse al servidor', 'danger');
                console.error(error);
            } finally {
                showLoading(false);
            }
        });

        // Manejo del formulario 2FA
        document.getElementById('twoFAForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const code = document.getElementById('twoFACode').value;

            showLoading(true);

            try {
                const response = await fetch('/petspa/api/auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        email: email,
                        password: password,
                        '2fa_code': code
                    })
                });

                const data = await response.json();

                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                showAlert('Error al verificar código', 'danger');
            } finally {
                showLoading(false);
            }
        });

        // Volver al login desde 2FA
        function backToLogin() {
            document.getElementById('loginForm').style.display = 'block';
            document.getElementById('twoFAContainer').style.display = 'none';
            document.getElementById('twoFACode').value = '';
        }

        // Función para iniciar sesión con Google
        function loginWithGoogle() {
            window.location.href = '/petspa/api/auth/google_oauth.php';
        }

        // Auto-focus en campo 2FA - solo números
        document.getElementById('twoFACode')?.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>