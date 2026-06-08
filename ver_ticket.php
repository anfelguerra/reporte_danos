<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$rol_id = intval($_SESSION['usuario_rol_id']);
$usuario_id = intval($_SESSION['usuario_id']);
$ticket_uuid = $_GET['uuid'] ?? '';

// Obtener detalles del ticket
try {
    $stmt = $pdo->prepare("SELECT r.*, e.nombre AS nombre_estado FROM reportes r LEFT JOIN estados e ON r.estado_id = e.id WHERE r.ticket_uuid = :uuid");
    $stmt->execute(['uuid' => $ticket_uuid]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        die("Ticket no encontrado.");
    }
    
    // Obtener la lista de estados para el formulario desplegable
    $estados = $pdo->query("SELECT * FROM estados")->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// PROCESAR CAMBIO DE ESTADO (Reglas 1 y 2)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_estado_id'])) {
    $nuevo_estado_id = intval($_POST['nuevo_estado_id']);
    
    // Validación de seguridad: Solo el admin (1) o el técnico asignado (2) pueden actualizar
    if ($rol_id === 1 || ($rol_id === 2 && intval($ticket['usuario_asignado_id']) === $usuario_id)) {
        try {
            $update = $pdo->prepare("UPDATE reportes SET estado_id = :estado_id WHERE ticket_uuid = :uuid");
            $update->execute(['estado_id' => $nuevo_estado_id, 'uuid' => $ticket_uuid]);
            
            // Aquí se disparará la notificación de AWS SES en el siguiente paso
            
            header("Location: ver_ticket.php?uuid=" . $ticket_uuid);
            exit();
        } catch (PDOException $e) {
            $error = "Error al actualizar el estado: " . $e->getMessage();
        }
    } else {
        $error = "No tienes permisos para modificar el estado de este ticket.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Ticket</title>
    <link href="https://jsdelivr.net" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5" style="max-width: 700px;">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm mb-3">← Volver al Dashboard</a>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <h3 class="card-title h5 border-bottom pb-2">Ticket N° <?= substr($ticket['ticket_uuid'], 0, 8) ?></h3>
            
            <p><strong>Ubicación:</strong> <?= htmlspecialchars($ticket['ubicacion']) ?></p>
            <p><strong>Descripción:</strong> <?= htmlspecialchars($ticket['descripcion']) ?></p>
            <p><strong>Prioridad:</strong> <?= htmlspecialchars($ticket['prioridad']) ?></p>
            <p><strong>Estado Actual:</strong> <span class="badge bg-primary"><?= htmlspecialchars($ticket['nombre_estado']) ?></span></p>

            <!-- FORMULARIO DE CAMBIO DE ESTADO CONDICIONAL -->
            <?php if ($rol_id === 1 || ($rol_id === 2 && intval($ticket['usuario_asignado_id']) === $usuario_id)): ?>
                <div class="mt-4 p-3 bg-light rounded border">
                    <h6 class="fw-bold">Actualizar Estado del Ticket</h6>
                    <form action="" method="POST">
                        <div class="input-group mt-2">
                            <select name="nuevo_estado_id" class="form-select" required>
                                <?php foreach ($estados as $est): ?>
                                    <option value="<?= $est['id'] ?>" <?= $ticket['estado_id'] == $est['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($est['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-success">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-warning small mt-3">
                    Solo el Administrador o el Técnico asignado a este reporte pueden modificar su estado.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
