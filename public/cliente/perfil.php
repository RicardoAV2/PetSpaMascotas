<?php
require_once "../../config/database.php";

// Traer usuarios con rol 'groomer'
$sql = "SELECT u.id_usuario, u.nombre, u.apellido, g.id_groomer
        FROM usuario u
        JOIN rol r ON u.id_rol = r.id_rol
        JOIN groomer g ON g.id_groomer = u.id_usuario
        WHERE r.nombre = 'groomer'";

$groomers = $conn->query($sql)->fetchAll();
?>

<h2>Groomers Especialistas</h2>

<?php foreach ($groomers as $g): ?>
    <div class="card p-3 mb-2">
        <h5><?= $g['nombre'] . " " . $g['apellido'] ?></h5>

        <!-- rating -->
        <?php
        $stmt = $conn->prepare("
            SELECT AVG(puntuacion) as promedio 
            FROM calificacion 
            WHERE id_groomer = :id
        ");
        $stmt->bindParam(":id", $g['id_groomer']);
        $stmt->execute();
        $rating = $stmt->fetch()['promedio'];
        ?>

        ⭐ <?= $rating ? round($rating,1) : 'Sin calificación' ?>

        <a href="perfil_groomer.php?id=<?= $g['id_usuario'] ?>" class="btn btn-info btn-sm">
            Ver Perfil
        </a>
    </div>
<?php endforeach; ?>
