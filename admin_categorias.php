<?php
session_start();
require_once 'config/database.php';

// Validamos acceso estricto al Administrador
if (!isset($_SESSION['usuario_id']) || intval($_SESSION['usuario_rol_id']) !== 1) {
    header("Location: login.php");
    exit();
}

$error = "";
$exito = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);

    if (!empty($nombre)) {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $id = intval($_POST['id']);
            try {
                $stmt = $pdo->prepare("UPDATE categorias SET nombre = :nombre WHERE id = :id");
                $stmt->execute(['nombre' => $nombre, 'id' => $id]);
                $exito = "Categoría actualizada correctamente.";
            } catch (PDOException $e) {
                $error = "Error al actualizar. Nombre duplicado.";
            }
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO categorias (nombre, activo) VALUES (:nombre, 1)");
                $stmt->execute(['nombre' => $nombre]);
                $exito = "Nueva categoría agregada con éxito.";
            } catch (PDOException $e) {
                $error = "La categoría ya existe.";
            }
        }
    } else {
        $error = "El nombre no puede estar vacío.";
    }
}

$resultado = $pdo->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Categorías</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f6f9; color: #333; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        input[type="text"] { width: 65%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; margin-right: 10px; }
        button { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-edit { background-color: #ffc107; color: #212529; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #343a40; color: white; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-danger { background-color: #f8d7da; color: #721c24; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .nav-link { display: inline-block; margin-bottom: 20px; color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="nav-link">← Volver al Dashboard</a>
    <h2>Gestión de Categorías de Daños</h2>

    <?php if(!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if(!empty($exito)): ?>
        <div class="alert alert-success"><?php echo $exito; ?></div>
    <?php endif; ?>

    <form action="admin_categorias.php" method="POST" style="background: #e9ecef; padding: 15px; border-radius: 5px;">
        <input type="hidden" name="id" id="cat_id">
        <div class="form-group">
            <label for="cat_nombre" style="font-weight:bold; display:block; margin-bottom: 5px;">Nombre de la Categoría:</label>
            <input type="text" name="nombre" id="cat_nombre" placeholder="Ej. Ascensores / Áreas Comunes" required>
            <button type="submit" id="btn_submit" class="btn-primary">Añadir Categoría</button>
            <button type="button" id="btn_cancelar" class="btn-secondary" style="display:none;" onclick="cancelarEdicion()">Cancelar</button>
        </div>
    </form>

    <h3>Categorías Configuradas</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 15%;">ID</th>
                <th>Nombre de la Categoría</th>
                <th style="width: 20%; text-align: center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($resultado)): ?>
                <?php foreach ($resultado as $row): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                    <td style="text-align: center;">
                        <button class="btn-edit" onclick="cargarDatosEdicion(<?php echo $row['id']; ?>, '<?php echo urlencode($row['nombre']); ?>')">Editar</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" style="text-align:center;">No hay categorías registradas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function cargarDatosEdicion(id, nombreCodificado) {
    const nombre = decodeURIComponent(nombreCodificado);
    document.getElementById('cat_id').value = id;
    document.getElementById('cat_nombre').value = nombre;
    document.getElementById('btn_submit').innerText = "Guardar Cambios";
    document.getElementById('btn_cancelar').style.display = "inline-block";
    document.getElementById('cat_nombre').focus();
}

function cancelarEdicion() {
    document.getElementById('cat_id').value = "";
    document.getElementById('cat_nombre').value = "";
    document.getElementById('btn_submit').innerText = "Añadir Categoría";
    document.getElementById('btn_cancelar').style.display = "none";
}
</script>
</body>
</html>
