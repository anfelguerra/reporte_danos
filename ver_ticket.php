<?php
session_start();
require_once 'config/database.php';

// Control de acceso general
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$rol_id = intval($_SESSION['usuario_rol_id']);
$usuario_id = intval($_SESSION['usuario_id']);
$ticket_uuid = $_GET['uuid'] ?? '';

$error = '';
$exito = '';

if (isset($_GET['error'])) $error = $_GET['error'];
if (isset($_GET['exito'])) $exito = $_GET['exito'];

// 1. OBTENER DETALLES DEL TICKET CON PDO
try {
    $stmt = $pdo->prepare("SELECT r.*, c.nombre AS nombre_categoria, e.nombre AS nombre_estado 
                           FROM reportes r 
                           LEFT JOIN categorias c ON r.categoria_id = c.id
                           LEFT JOIN estados e ON r.estado_id = e.id 
                           WHERE r.ticket_uuid = :uuid");
    $stmt->execute(['uuid' => $ticket_uuid]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        header("Location: dashboard.php?error=Ticket no encontrado");
        exit();
    }
    
    // Obtener la lista completa de estados para el formulario desplegable
    $estados = $pdo->query("SELECT * FROM estados ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    die("Error de comunicación con la base de datos: " . $e->getMessage());
}

// 2. PROCESAR CAMBIO DE ESTADO (Regla N°1 y N°2)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_estado_id'])) {
    $nuevo_estado_id = intval($_POST['nuevo_estado_id']);
    
    // Validación estricta de seguridad: Solo admin (1) o técnico asignado (2)
    if ($rol_id === 1 || ($rol_id === 2 && intval($ticket['usuario_asignado_id']) === $usuario_id)) {
        try {
            $update = $pdo->prepare("UPDATE reportes SET estado_id = :estado_id, fecha_actualizacion = CURRENT_TIMESTAMP() WHERE ticket_uuid = :uuid");
            $update->execute([
                'estado_id' => $nuevo_estado_id, 
                'uuid' => $ticket_uuid
            ]);
            
            // ============================================================
            // 🚀 AQUÍ SE INTEGRARÁ EL DISPARADOR DE AWS SES EN EL PUNTO 3
            // ============================================================
            
            header("Location: ver_ticket.php?uuid=" . $ticket_uuid . "&exito=Estado actualizado correctamente.");
            exit();
        } catch (PDOException $e) {
            $error = "Error al actualizar el estado: " . $e->getMessage();
        }
    } else {
        $error = "Acceso denegado: No cuentas con los permisos para modificar este ticket.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Ticket - Ecosistema Residencial</title>
    
    <!-- ENLACES DE DISEÑO LOCALES VINCULADOS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/all.min.css">
</head>
<body class="bg-light">
<div class="container my-5" style="max-width: 750px;">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm mb-3 shadow-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Volver al Dashboard
    </a>
    
    <div class="card shadow-sm mb-4">
        <div class="card-body bg-white rounded p-4 text-dark">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                <h3 class="card-title h5 mb-0 fw-bold text-dark">
                    <i class="fa-solid fa-ticket text-primary me-2"></i>Ticket N° <?= substr($ticket['ticket_uuid'], 0, 8) ?>
                </h3>
                <span class="badge bg-primary px-3 py-1.5 rounded-pill text-capitalize">
                    <?= htmlspecialchars($ticket['nombre_estado'] ?? 'Creado') ?>
                </span>
            </div>
            
            <?php if(!empty($error)): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if(!empty($exito)): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($exito) ?></div><?php endif; ?>

            <div class="row g-3 style-datos mb-4" style="font-size: 15px;">
                <div class="col-md-6">
                    <span class="text-secondary small d-block fw-bold">Categoría del Daño</span>
                    <strong class="text-dark"><?= htmlspecialchars($ticket['nombre_categoria'] ?? $ticket['categoria']) ?></strong>
                </div>
                <div class="col-md-6">
                    <span class="text-secondary small d-block fw-bold">Ubicación / Área</span>
                    <span class="text-dark"><?= htmlspecialchars($ticket['ubicacion']) ?></span>
                </div>
                <div class="col-md-6">
                    <span class="text-secondary small d-block fw-bold">Prioridad Operativa</span>
                    <span class="text-dark fw-semibold"><?= htmlspecialchars($ticket['prioridad']) ?></span>
                </div>
                <div class="col-md-6">
                    <span class="text-secondary small d-block fw-bold">Fecha de Reporte</span>
                    <span class="text-dark"><?= date('d/m/Y g:i A', strtotime($ticket['fecha_reporte'])) ?></span>
                </div>
                <div class="col-12">
                    <span class="text-secondary small d-block fw-bold">Descripción del Incidente</span>
                    <p class="text-dark bg-light p-3 rounded border mt-1" style="white-space: pre-wrap;"><?= htmlspecialchars($ticket['descripcion']) ?></p>
                </div>
            </div>

            <!-- FORMULARIO DE CAMBIO DE ESTADO CONDICIONAL (Reglas N°1 y N°2) -->
            <?php if ($rol_id === 1 || ($rol_id === 2 && intval($ticket['usuario_asignado_id']) === $usuario_id)): ?>
                <div class="mt-4 p-3 bg-light rounded border">
                    <h6 class="fw-bold text-secondary mb-2"><i class="fa-solid fa-sliders text-muted me-2"></i>Actualizar Estado Operativo</h6>
                    <form action="" method="POST">
                        <div class="input-group">
                            <select name="nuevo_estado_id" class="form-select form-select-sm" required>
                                <?php foreach ($estados as $est): ?>
                                    <option value="<?= $est['id'] ?>" <?= $ticket['estado_id'] == $est['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($est['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-success fw-bold px-3">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Regla N°3: El propietario visualiza sin modificar nada -->
                <div class="alert alert-secondary small d-flex align-items-center mt-3 mb-0" role="alert">
                    <i class="fa-solid fa-circle-info text-secondary me-2"></i>
                    <div>Modo lectura. Solo la administración o el técnico asignado pueden modificar el estado del ticket.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- JAVASCRIPT LOCAL VINCULADO -->
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
