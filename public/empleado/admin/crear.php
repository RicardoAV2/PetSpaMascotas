<?php
require_once "../../../config/database.php";
require_once "../../../core/middleware.php";
require_once "../../../core/Auth.php";

Auth::setConnection($conn);
Middleware::requireAdmin();

// Obtener roles
$sqlRoles = "SELECT * FROM rol ORDER BY nombre";
$roles = $conn->query($sqlRoles)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Crear Usuario</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>

body{
    background:#f4f6f9;
}

.card-custom{
    border:none;
    border-radius:20px;
    overflow:hidden;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
}

.header-gradient{
    background:linear-gradient(135deg,#0d6efd,#0b5ed7);
    color:white;
    padding:25px;
}

.form-control,
.form-select{
    border-radius:12px;
    padding:12px;
}

.btn{
    border-radius:12px;
    padding:10px 18px;
}

.role-info{
    background:#f8f9fa;
    border-radius:12px;
    padding:15px;
    margin-top:15px;
    display:none;
}

.icon-circle{
    width:60px;
    height:60px;
    border-radius:50%;
    background:rgba(255,255,255,0.2);

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:1.5rem;
}

</style>

</head>
<body>

<div class="container py-5">

<div class="row justify-content-center">

<div class="col-lg-8">

<div class="card card-custom">

    <!-- HEADER -->
    <div class="header-gradient d-flex align-items-center gap-3">

        <div class="icon-circle">
            <i class="bi bi-person-plus-fill"></i>
        </div>

        <div>
            <h2 class="mb-1">Crear Nuevo Usuario</h2>
            <p class="mb-0">
                Registrar empleados o clientes dentro del sistema
            </p>
        </div>

    </div>

    <!-- BODY -->
    <div class="card-body p-4">

        <form
        action="/petspa/api/admin/crear_usuario.php"
        method="POST">

            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <!-- NOMBRE -->
            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label fw-bold">
                        Nombre
                    </label>

                    <input
                    type="text"
                    name="nombre"
                    class="form-control"
                    placeholder="Ingrese el nombre"
                    required>

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label fw-bold">
                        Apellido
                    </label>

                    <input
                    type="text"
                    name="apellido"
                    class="form-control"
                    placeholder="Ingrese el apellido"
                    required>

                </div>

            </div>

            <!-- EMAIL -->
            <div class="mb-3">

                <label class="form-label fw-bold">
                    Correo Electrónico
                </label>

                <div class="input-group">

                    <span class="input-group-text">
                        <i class="bi bi-envelope-fill"></i>
                    </span>

                    <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="correo@ejemplo.com"
                    required>

                </div>

            </div>

            <!-- TELEFONO -->
            <div class="mb-3">

                <label class="form-label fw-bold">
                    Teléfono
                </label>

                <div class="input-group">

                    <span class="input-group-text">
                        <i class="bi bi-telephone-fill"></i>
                    </span>

                    <input
                    type="text"
                    name="telefono"
                    class="form-control"
                    placeholder="77777777">

                </div>

            </div>

            <!-- PASSWORD -->
            <div class="mb-3">

                <label class="form-label fw-bold">
                    Contraseña
                </label>

                <div class="input-group">

                    <span class="input-group-text">
                        <i class="bi bi-lock-fill"></i>
                    </span>

                    <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="Ingrese una contraseña"
                    required>

                </div>

                <small class="text-muted">
                    La contraseña será cifrada automáticamente
                </small>

            </div>

            <!-- ESTADO -->
            <div class="mb-3">

                <label class="form-label fw-bold">
                    Estado del Usuario
                </label>

                <select name="estado" class="form-select">
                    <option value="1" selected>Activo</option>
                    <option value="0">Inactivo</option>
                </select>

            </div>

            <!-- ROL -->
            <div class="mb-3">

                <label class="form-label fw-bold">
                    Rol del Usuario
                </label>

                <select
                name="rol"
                id="rolSelect"
                class="form-select"
                required>

                    <option value="">
                        Seleccione un rol
                    </option>

                    <?php foreach($roles as $r): ?>

                    <option value="<?= $r['id_rol'] ?>">
                        <?= ucfirst($r['nombre']) ?>
                    </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <!-- INFO ROL -->
            <div id="roleInfo" class="role-info">

                <h5 id="roleTitle"></h5>

                <p id="roleDescription" class="mb-0"></p>

            </div>

            <!-- CAMPOS SEGÚN ROL -->
            <div id="roleFields" style="display:none;" class="border rounded p-4 bg-light mb-4">
                <h5 class="mb-3">Configuración adicional por rol</h5>

                <div class="role-section" data-role="groomer" style="display:none;">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Especialidad</label>
                        <input type="text" name="especialidad" class="form-control" placeholder="Ej: Baño medicinal">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Capacidad simultánea</label>
                        <input type="number" name="capacidad_simultanea" class="form-control" min="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Horario de trabajo</label>
                        <input type="text" name="horario_trabajo" class="form-control" placeholder="Ej: Lun-Vie 09:00-18:00">
                    </div>
                </div>

                <div class="role-section" data-role="recepcion" style="display:none;">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Turno</label>
                        <input type="text" name="turno" class="form-control" placeholder="Ej: Mañana / Tarde">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Idiomas</label>
                        <input type="text" name="idiomas" class="form-control" placeholder="Ej: Español, Inglés">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Años de experiencia</label>
                        <input type="number" name="experiencia" class="form-control" min="0" value="0">
                    </div>
                </div>

                <div class="role-section" data-role="admin" style="display:none;">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nivel de acceso</label>
                        <input type="number" name="nivel_acceso" class="form-control" min="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Área de responsabilidad</label>
                        <input type="text" name="area_responsabilidad" class="form-control" placeholder="Ej: Gestión administrativa">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="puede_contratar" id="puedeContratar">
                        <label class="form-check-label" for="puedeContratar">Puede contratar personal</label>
                    </div>
                </div>

                <div class="role-section" data-role="cliente" style="display:none;">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Dirección</label>
                        <input type="text" name="direccion" class="form-control" placeholder="Calle y número">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">CI / NIT</label>
                        <input type="text" name="ci" class="form-control" placeholder="Documento de identidad">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Canal de notificación</label>
                        <select name="canal_notificacion_preferido" class="form-select">
                            <option value="email">Email</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="sms">SMS</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Horario preferido</label>
                        <input type="text" name="horario_preferido" class="form-control" placeholder="Ej: Mañanas de Lunes a Viernes">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="recibe_promociones" id="recibePromociones" checked>
                        <label class="form-check-label" for="recibePromociones">Recibe promociones</label>
                    </div>
                </div>
            </div>

            <!-- BOTONES -->
            <div class="d-flex justify-content-between mt-4">

                <a href="empleados.php"
                class="btn btn-secondary">

                    <i class="bi bi-arrow-left"></i>
                    Volver

                </a>

                <button class="btn btn-primary">

                    <i class="bi bi-check-circle-fill"></i>
                    Crear Usuario

                </button>

            </div>

        </form>

    </div>

</div>

</div>

</div>

</div>

<script>

const roleInfo = document.getElementById("roleInfo");
const roleTitle = document.getElementById("roleTitle");
const roleDescription = document.getElementById("roleDescription");

const roleFields = document.getElementById("roleFields");
const roleSections = document.querySelectorAll('.role-section');

function showRoleFields(role) {
    roleInfo.style.display = "block";
    roleFields.style.display = "block";

    roleSections.forEach(section => {
        section.style.display = section.dataset.role === role ? 'block' : 'none';
    });

    if (role === 'admin') {
        roleTitle.innerHTML = "Administrador";
        roleDescription.innerHTML = "Acceso total al sistema, gestión de usuarios, auditoría y configuración.";
    } else if (role === 'recepcion') {
        roleTitle.innerHTML = "Recepcionista";
        roleDescription.innerHTML = "Gestiona reservas, citas, clientes y atención en recepción.";
    } else if (role === 'groomer') {
        roleTitle.innerHTML = "Groomer";
        roleDescription.innerHTML = "Especialista encargado del cuidado, higiene y estética de mascotas.";
    } else if (role === 'cliente') {
        roleTitle.innerHTML = "Cliente";
        roleDescription.innerHTML = "Usuario final que puede reservar servicios y gestionar sus mascotas.";
    } else {
        roleInfo.style.display = 'none';
        roleFields.style.display = 'none';
    }
}

document.getElementById("rolSelect").addEventListener("change", function(){
    const selectedOption = this.options[this.selectedIndex];
    const value = selectedOption.text.toLowerCase();

    if (value.includes("admin")) {
        showRoleFields('admin');
    } else if (value.includes("recepcion")) {
        showRoleFields('recepcion');
    } else if (value.includes("groomer")) {
        showRoleFields('groomer');
    } else if (value.includes("cliente")) {
        showRoleFields('cliente');
    } else {
        showRoleFields('');
    }
});

</script>

</body>
</html>