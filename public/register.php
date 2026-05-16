<?php
/**
 * PÁGINA DE REGISTRO DE CLIENTES
 */
require_once __DIR__ . '/../core/helpers.php';
$csrf_token = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 15px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .register-container {
            max-width: 600px;
            margin: 0 auto;
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

        .card-body {
            padding: 30px 25px;
        }

        .form-group {
            margin-bottom: 18px;
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

        .password-strength {
            margin-top: 8px;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            transition: all 0.3s;
            width: 0%;
        }

        .password-strength-text {
            font-size: 12px;
            margin-top: 5px;
            font-weight: 600;
        }

        .requirements-list {
            font-size: 12px;
            margin-top: 10px;
            display: none;
        }

        .requirement {
            padding: 5px 0;
            color: #999;
        }

        .requirement.met {
            color: #28a745;
        }

        .requirement i {
            width: 16px;
            margin-right: 6px;
        }

        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
            color: white;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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

        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .input-group-password {
            position: relative;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }

        .loading-spinner {
            display: none;
            margin-right: 8px;
        }

        @media (max-width: 576px) {
            .two-columns {
                grid-template-columns: 1fr;
            }

            .card-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="card">
            <div class="card-header">
                <div class="logo-title">
                    <i class="fas fa-spa"></i> Pet Spa
                </div>
                <div style="font-size: 14px; opacity: 0.9;">Crear una Nueva Cuenta</div>
            </div>

            <div class="card-body">
                <div id="alertContainer"></div>

                <form id="registerForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <!-- Nombre y Apellido -->
                    <div class="two-columns">
                        <div class="form-group">
                            <label class="form-label">Nombre *</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Apellido *</label>
                            <input type="text" class="form-control" name="apellido">
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label">Correo Electrónico *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>

                    <!-- Teléfono y CI -->
                    <div class="two-columns">
                        <div class="form-group">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" name="telefono" placeholder="Ej: +591-7123456">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cédula de Identidad</label>
                            <input type="text" class="form-control" name="ci" placeholder="Ej: 1234567">
                        </div>
                    </div>

                    <!-- Dirección -->
                    <div class="form-group">
                        <label class="form-label">Dirección</label>
                        <input type="text" class="form-control" name="direccion" placeholder="Calle, Número, Barrio">
                    </div>

                    <!-- Contraseña -->
                    <div class="form-group">
                        <label class="form-label">Contraseña *</label>
                        <div class="input-group-password">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <span class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="password-strength-text" id="strengthText"></div>

                        <div class="requirements-list" id="requirementsList">
                            <div class="requirement" id="req-length">
                                <i class="fas fa-times-circle"></i> Mínimo 8 caracteres
                            </div>
                            <div class="requirement" id="req-uppercase">
                                <i class="fas fa-times-circle"></i> Una letra mayúscula (A-Z)
                            </div>
                            <div class="requirement" id="req-lowercase">
                                <i class="fas fa-times-circle"></i> Una letra minúscula (a-z)
                            </div>
                            <div class="requirement" id="req-number">
                                <i class="fas fa-times-circle"></i> Un número (0-9)
                            </div>
                            <div class="requirement" id="req-symbol">
                                <i class="fas fa-times-circle"></i> Un símbolo especial (!@#$%^&*)
                            </div>
                        </div>
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div class="form-group">
                        <label class="form-label">Confirmar Contraseña *</label>
                        <div class="input-group-password">
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                            <span class="password-toggle" onclick="togglePassword2()">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <small id="matchText" style="display: none; color: #dc3545; margin-top: 5px;"></small>
                    </div>

                    <button type="submit" class="btn btn-register w-100">
                        <span class="loading-spinner" id="loadingSpinner">
                            <span class="spinner-border spinner-border-sm"></span>
                        </span>
                        Crear Cuenta
                    </button>

                    <div class="footer-text">
                        ¿Ya tienes cuenta? <a href="/petspa/public/login.php">Inicia sesión aquí</a>
                    </div>
                </form>
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

        function togglePassword2() {
            const input = document.getElementById('password_confirm');
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

        function updatePasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');

            // Mostrar lista de requisitos
            document.getElementById('requirementsList').style.display = 'block';

            let strength = 0;
            let allMet = true;

            // Comprobar requisitos
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSymbol = /[!@#$%^&*()_\-+=\[\]{};:'",./<>?\\|`~]/.test(password);

            updateRequirement('req-length', hasLength);
            updateRequirement('req-uppercase', hasUppercase);
            updateRequirement('req-lowercase', hasLowercase);
            updateRequirement('req-number', hasNumber);
            updateRequirement('req-symbol', hasSymbol);

            if (hasLength) strength++;
            if (hasUppercase) strength++;
            if (hasLowercase) strength++;
            if (hasNumber) strength++;
            if (hasSymbol) strength++;

            allMet = hasLength && hasUppercase && hasLowercase && hasNumber && hasSymbol;

            // Actualizar barra
            const percent = (strength / 5) * 100;
            strengthBar.style.width = percent + '%';

            if (!password) {
                strengthBar.style.width = '0%';
                strengthText.textContent = '';
            } else if (strength <= 2) {
                strengthBar.style.backgroundColor = '#dc3545';
                strengthText.textContent = '🔴 Contraseña débil';
                strengthText.style.color = '#dc3545';
            } else if (strength <= 3) {
                strengthBar.style.backgroundColor = '#ffc107';
                strengthText.textContent = '🟡 Contraseña media';
                strengthText.style.color = '#ffc107';
            } else if (strength <= 4) {
                strengthBar.style.backgroundColor = '#17a2b8';
                strengthText.textContent = '🔵 Contraseña fuerte';
                strengthText.style.color = '#17a2b8';
            } else {
                strengthBar.style.backgroundColor = '#28a745';
                strengthText.textContent = '🟢 Contraseña muy fuerte';
                strengthText.style.color = '#28a745';
            }
        }

        function updateRequirement(id, met) {
            const element = document.getElementById(id);
            const icon = element.querySelector('i');
            
            if (met) {
                element.classList.add('met');
                icon.classList.remove('fa-times-circle');
                icon.classList.add('fa-check-circle');
            } else {
                element.classList.remove('met');
                icon.classList.remove('fa-check-circle');
                icon.classList.add('fa-times-circle');
            }
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirm').value;
            const matchText = document.getElementById('matchText');

            if (password && confirm) {
                if (password !== confirm) {
                    matchText.style.display = 'block';
                    matchText.textContent = '❌ Las contraseñas no coinciden';
                } else {
                    matchText.style.display = 'none';
                }
            } else {
                matchText.style.display = 'none';
            }
        }

        // Event listeners
        document.getElementById('password').addEventListener('input', updatePasswordStrength);
        document.getElementById('password_confirm').addEventListener('input', checkPasswordMatch);

        // Submit form
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirm').value;

            if (password !== confirm) {
                showAlert('Las contraseñas no coinciden', 'danger');
                return;
            }

            const formData = new FormData(this);
            const loadingSpinner = document.getElementById('loadingSpinner');
            loadingSpinner.style.display = 'inline-block';

            try {
                const response = await fetch('/petspa/api/auth/register.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('¡Registro exitoso! Por favor, verifica tu correo electrónico.', 'success');
                    
                    // Limpiar formulario
                    document.getElementById('registerForm').reset();
                    document.getElementById('strengthBar').style.width = '0%';
                    document.getElementById('strengthText').textContent = '';
                    document.getElementById('requirementsList').style.display = 'none';
                    
                    // Redirigir al login en 3 segundos
                    setTimeout(() => {
                        window.location.href = '/petspa/public/login.php';
                    }, 3000);
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                showAlert('Error al conectarse al servidor', 'danger');
                console.error(error);
            } finally {
                loadingSpinner.style.display = 'none';
            }
        });
    </script>
</body>
</html>