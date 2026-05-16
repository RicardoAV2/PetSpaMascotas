<?php 
//session_start(); 
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
    
    <!-- LOGO -->
    <a class="navbar-brand" href="/petspa/public/cliente/tienda.php">
        PetSpa
    </a>

    <!-- BUSCADOR -->
    <form class="d-flex ms-3" action="/petspa/public/cliente/tienda.php" method="GET">
        <select name="categoria" class="form-select me-2">
            <option value="">Todas</option>
            <option value="1">Accesorios</option>
            <option value="2">Salud</option>
            <option value="3">Alimentos</option>
        </select>

        <input class="form-control me-2" type="search" name="buscar" placeholder="Buscar productos">

        <button class="btn btn-warning">Buscar</button>
    </form>

    <!-- DERECHA -->
    <div class="ms-auto d-flex align-items-center">

        <!-- LOGIN -->
        <?php if (!isset($_SESSION['usuario_id'])): ?>
            <a href="/petspa/public/login.php" class="text-white me-3">
                Hola, inicia sesión
            </a>
        <?php else: ?>
            <span class="text-white me-3">
                Hola Usuario
            </span>
        <?php endif; ?>

        <!-- PEDIDOS -->
        <a href="/petspa/public/cliente/perfil.php" class="text-white">
            Perfiles groomers
        </a>

        <!-- CARRITO -->
        <a href="/petspa/public/cliente/carrito.php" class="text-white">
            🛒 Carrito
            (<?= isset($_SESSION['carrito']) ? count($_SESSION['carrito']) : 0 ?>)
        </a>

    </div>
    <div class="bg-light p-2">
    <a href="tienda.php?categoria=1">Accesorios</a> |
    <a href="tienda.php?categoria=2">Salud</a> |
    <a href="tienda.php?categoria=3">Alimentos</a>
</div>
</nav>