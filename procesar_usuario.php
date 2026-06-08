<?php
session_start();
require_once 'config/database.php';

// 1. REGLA DE SEGURIDAD N°1: Solo el Administrador (usuario_rol_id = 1) puede estar aquí
if (!isset($_SESSION['usuario_id']) || intval($_SESSION['usuario_rol_id']) !== 1) {
    header("Location: login.php");
    exit();
}

$error = '';
$exito = '';

// Capturar alertas de redirecciones externas (como eliminar_usuario.php)
if (isset($_GET['error'])) $error = $_GET['error'];
if (isset($_GET['exito'])) $exito = $_GET['exito'];

// 2. PROCESAR FORMULARIO (CREAR O EDITAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $rol_id   = intval($_POST['rol_id'] ?? 3);

    if (!empty($nombre) && !empty($correo) && !empty($password)) {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // ACCIÓN: ACTUALIZAR
            $id = intval($_POST['id']);
            try {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = :nombre, correo = :correo, password = :password, rol_id = :rol_id WHERE id = :id");
                $stmt->execute([
                    'nombre'   => $nombre,
                    'correo'   => $correo,
                    'password' => $password,
                    'rol_id'   => $rol_id,
                    'id'       => $id
                ]);
                $exito = "Usuario actualizado correctamente.";
            } catch (PDOException $e) {
                $error = "El correo electrónico ya se encuentra registrado por otro usuario.";
            }
        } else {
            // ACCIÓN: CREAR
            try {
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, password, rol_id) VALUES (:nombre, :correo, :password, :rol_id)");
                $stmt->execute([
                    'nombre'   => $nombre,
                    'correo'   => $correo,
                    'password' => $password,
                    'rol_id'   => $rol_id
                ]);
                $exito = "Nuevo usuario registrado con éxito.";
            } catch (PDOException $e) {
                $error = "El correo electrónico ya se encuentra registrado.";
            }
        }
    } else {
        $error = "Por favor, complete todos los campos requeridos.";
    }
}

// 3. CONSULTAR ROLES Y USUARIOS PARA RENDERIZAR LA VISTA
try {
    $roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
    $usuarios = $pdo->query("SELECT u.*, r.nombre AS nombre_rol FROM usuarios u LEFT JOIN roles r ON u.rol_id = r.id ORDER BY u.fecha_creacion DESC")->fetchAll();
} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
    <link href="https://jsdelivr.net" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-4" style="max-width: 900px;">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm mb-3">← Volver al Dashboard</a>
    
    <h2 class="h4 mb-3">Panel de Control: Gestión de Usuarios</h2>

    <?php if(!empty($error)): ?>
        <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if(!empty($exito)): ?>
        <div class="alert alert-success py-2 small"><?= htmlspecialchars($exito) ?></div>
    <?php endif; ?>

    <!-- Formulario Dinámico Unificado (Creación y Edición) -->
    <div class="card shadow-sm mb-4">
        <div class="card-body bg-white text-dark">
            <h5 class="card-title h6 mb-3 border-bottom pb-2" id="form-titulo">Registrar Nuevo Usuario</h5>
            <form action="procesar_usuario.php" method="POST" id="form-usuario">
                <input type="hidden" name="id" id="user_id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Nombre Completo</label>
                        <input type="text" name="nombre" id="user_nombre" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Correo Electrónico</label>
                        <input type="email" name="correo" id="user_correo" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Contraseña</label>
                        <input type="text" name="password" id="user_password" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Rol asignado</label>
                        <select name="rol_id" id="user_rol" class="form-select form-select-sm" required>
                            <?php foreach($roles as $rol): ?>
                                <option value="<?= $rol['id'] ?>"><?= htmlspecialchars($rol['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" id="btn-cancelar" class="btn btn-sm btn-secondary me-1" style="display:none;" onclick="resetearFormulario()">Cancelar</button>
                        <button type="submit" id="btn-guardar" class="btn btn-sm btn-primary">Registrar Usuario</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla del listado completo de usuarios en el sistema -->
    <div class="card shadow-sm">
        <div class="card-body bg-white">
            <h5 class="card-title h6 mb-3 text-secondary">Usuarios en la Plataforma</h5>
            <table class="table table-sm table-hover align-middle" style="font-size: 14px;">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($usuarios as $user): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($user['nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($user['correo']) ?></td>
                            <td><span class="badge bg-dark text-capitalize"><?= htmlspecialchars($user['nombre_rol']) ?></span></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-warning text-dark py-0 px-2" onclick="cargarEdicion(<?= $user['id'] ?>, '<?= urlencode($user['nombre']) ?>', '<?= urlencode($user['correo']) ?>', '<?= urlencode($user['password']) ?>', <?= $user['rol_id'] ?>)">Editar</button>
                                <a href="eliminar_usuario.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-danger py-0 px-2" onclick="return confirm('¿Está seguro de eliminar este usuario?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function cargarEdicion(id, nombre, correo, password, rolId) {
    document.getElementById('user_id').value = id;
    document.getElementById('user_nombre').value = decodeURIComponent(nombre);
    document.getElementById('user_correo').value = decodeURIComponent(correo);
    document.getElementById('user_password').value = decodeURIComponent(password);
    document.getElementById('user_rol').value = rolId;
    
    document.getElementById('form-titulo').innerText = "Modificar Datos de Usuario";
    document.getElementById('btn-guardar').innerText = "Guardar Cambios";
    document.getElementById('btn-cancelar').style.display = "inline-block";
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function resetearFormulario() {
    document.getElementById('user_id').value = "";
    document.getElementById('form-usuario').reset();
    document.getElementById('form-titulo').innerText = "Registrar Nuevo Usuario";
    document.getElementById('btn-guardar').innerText = "Registrar Usuario";
    document.getElementById('btn-cancelar').style.display = "none";
}
</script>
</body>
</html>
