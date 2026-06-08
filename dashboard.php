<?php
session_start();
require_once 'config/database.php';

// 1. REGLA DE ACCESO GENERAL: Si no hay sesión válida, se expulsa al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Captura exacta de variables provenientes de tu login.php original
$usuario_id = intval($_SESSION['usuario_id']);
$nombre_usuario = $_SESSION['usuario_nombre'];
$rol_id = intval($_SESSION['usuario_rol_id']); // 1: administracion, 2: mantenimiento, 3: propietarios

// 2. CONSTRUCCIÓN DE CONSULTA RELACIONAL
$query = "SELECT r.*, c.nombre AS nombre_categoria, e.nombre AS nombre_estado 
          FROM reportes r
          LEFT JOIN categorias c ON r.categoria_id = c.id
          LEFT JOIN estados e ON r.estado_id = e.id";

// REGLA N°2: El técnico (rol_id = 2) SOLO visualiza los tickets que tiene asignados.
// REGLA N°1 y N°3: El Administrador y el Propietario pueden visualizar TODOS los tickets de la plataforma.
if ($rol_id === 2) {
    $query .= " WHERE r.usuario_asignado_id = :usuario_id";
}

$query .= " ORDER BY r.fecha_reporte DESC";

try {
    $stmt = $pdo->prepare($query);
    if ($rol_id === 2) {
        $stmt->execute(['usuario_id' => $usuario_id]);
    } else {
        $stmt->execute();
    }
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error de comunicación con la infraestructura de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestión Residencial</title>
    <link href="https://jsdelivr.net" rel="stylesheet">
    <link rel="stylesheet" href="https://cloudflare.com">
    <style>
        body { background-color: #f8fafc; font-family: 'Segoe UI', system-ui, sans-serif; }
        .navbar-custom { background-color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .card-custom { border: none; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .table-custom th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; background-color: #f8fafc; }
        .badge-creado { background-color: #e0f2fe; color: #0369a1; }
        .badge-proceso { background-color: #fef3c7; color: #b45309; }
        .badge-resuelto { background-color: #dcfce7; color: #15803d; }
        .badge-cerrado { background-color: #f1f5f9; color: #475569; }
        .badge-activo { background-color: #e0e7ff; color: #4338ca; }
    </style>
</head>
<body>

    <!-- Barra de Navegación Superior -->
    <nav class="navbar navbar-expand-lg navbar-custom py-3 mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold text-dark" href="dashboard.php">
                <i class="fa-solid fa-building-shield text-primary me-2"></i>Ecosistema Residencial
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary small">
                    Bienvenido, <strong class="text-dark"><?= htmlspecialchars($nombre_usuario) ?></strong> 
                    <span class="badge bg-secondary text-uppercase ms-1" style="font-size: 10px;"><?= htmlspecialchars($_SESSION['usuario_rol_nombre'] ?? 'Usuario') ?></span>
                </span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger fw-bold px-3">
                    <i class="fa-solid fa-right-from-bracket me-1"></i>Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Barra de Acciones y Herramientas Dinámicas -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            
            <!-- REGLA N°4: Todos los usuarios de la plataforma pueden crear tickets -->
            <div>
                <a href="guardar_reporte.php" class="btn btn-primary fw-bold px-3 shadow-sm">
                    <i class="fa-solid fa-circle-plus me-1"></i> Crear Nuevo Reporte
                </a>
            </div>

            <!-- REGLA N°1: Herramientas administrativas de gestión exclusivas para rol_id = 1 -->
            <?php if ($rol_id === 1): ?>
                <div class="d-flex gap-2">
                    <a href="procesar_usuario.php" class="btn btn-dark fw-bold btn-sm px-3 shadow-sm">
                        <i class="fa-solid fa-users-gear me-1"></i> Gestión de Usuarios
                    </a>
                    <a href="admin_categorias.php" class="btn btn-outline-secondary fw-bold btn-sm px-3">
                        <i class="fa-solid fa-tags me-1"></i> Configurar Categorías
                    </a>
                    <a href="reportes_estadisticas.php" class="btn btn-outline-secondary fw-bold btn-sm px-3">
                        <i class="fa-solid fa-chart-pie me-1"></i> Métricas de Rendimiento
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tabla Principal de Control de Daños -->
        <div class="card card-custom">
            <div class="card-body p-4 bg-white rounded">
                <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-check text-muted me-2"></i>Historial Operativo de Daños</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Código UUID</th>
                                <th>Categoría del Daño</th>
                                <th>Ubicación</th>
                                <th>Prioridad</th>
                                <th>Estado Actual</th>
                                <th>Fecha Registro</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($tickets)): ?>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td>
                                            <span class="text-muted small font-monospace fw-bold">
                                                #<?= substr($ticket['ticket_uuid'], 0, 8) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark">
                                                <?= htmlspecialchars($ticket['nombre_categoria'] ?? $ticket['categoria']) ?>
                                            </span>
                                        </td>
                                        <td class="text-secondary small"><?= htmlspecialchars($ticket['ubicacion']) ?></td>
                                        <td>
                                            <?php 
                                                $prioClass = 'text-success';
                                                if(in_array($ticket['prioridad'], ['Alta', 'Crítica'])) $prioClass = 'text-danger fw-bold';
                                                if($ticket['prioridad'] === 'Media') $prioClass = 'text-warning';
                                            ?>
                                            <span class="<?= $prioClass ?> small"><?= $ticket['prioridad'] ?></span>
                                        </td>
                                        <td>
                                            <?php $estado_limpio = strtolower($ticket['nombre_estado'] ?? 'creado'); ?>
                                            <span class="badge badge-<?= $estado_limpio ?> px-2.5 py-1.5 rounded-pill text-capitalize" style="font-size: 11px;">
                                                <?= htmlspecialchars($ticket['nombre_estado'] ?? 'Creado') ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small"><?= date('d/m/Y g:i A', strtotime($ticket['fecha_reporte'])) ?></td>
                                        <td class="text-center">
                                            <a href="ver_ticket.php?uuid=<?= $ticket['ticket_uuid'] ?>" class="btn btn-sm btn-light border fw-semibold text-primary px-3 py-1">
                                                <i class="fa-regular fa-eye me-1"></i>Detalles
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fa-solid fa-folder-open d-block mb-2 text-opacity-25 text-dark" style="font-size: 2rem;"></i>
                                        No se encontraron registros de daños disponibles para visualizar.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://jsdelivr.net"></script>
</body>
</html>
