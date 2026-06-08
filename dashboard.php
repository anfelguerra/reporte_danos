<?php
session_start();
require_once 'config/database.php';

// 1. CONTROL DE ACCESO GLOBAL: Si no hay sesión válida, al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$user_id_actual  = intval($_SESSION['usuario_id']);
$rol_actual_id   = intval($_SESSION['usuario_rol_id']); // 1 = Admin, 2 = Mantenimiento, 3 = Propietario
$rol_nombre      = $_SESSION['usuario_rol_nombre'];
$nombre_usuario  = $_SESSION['usuario_nombre'];

// 2. PROCESAR ACCIONES DE NOTIFICACIONES (Marcar como leída)
if (isset($_GET['leer_notif'])) {
    $notif_id = intval($_GET['leer_notif']);
    try {
        $stmt = $pdo->prepare("DELETE FROM notificaciones WHERE id = :id AND usuario_id = :uid");
        $stmt->execute([':id' => $notif_id, ':uid' => $user_id_actual]);
        header("Location: dashboard.php");
        exit;
    } catch (PDOException $e) {
        die("Error al procesar notificación: " . $e->getMessage());
    }
}

// 3. CONSULTA DE TICKETS Y ECOSISTEMA RELACIONAL
try {
    if ($rol_actual_id === 3) {
        // PROPIETARIOS: Visualizan solo lo que ellos radicaron (usuario_reporta_id)
        $stmt = $pdo->prepare("SELECT r.*, r.id AS id, 
                                      u.nombre as residente, 
                                      m.nombre as tecnico, 
                                      e.nombre as estado_nombre 
                               FROM reportes r 
                               LEFT JOIN usuarios u ON r.usuario_reporta_id = u.id 
                               LEFT JOIN usuarios m ON r.usuario_asignado_id = m.id
                               LEFT JOIN estados e ON r.estado_id = e.id
                               WHERE r.usuario_reporta_id = :uid 
                               ORDER BY r.fecha_reporte DESC");
        $stmt->execute([':uid' => $user_id_actual]);
    } elseif ($rol_actual_id === 2) {
        // MANTENIMIENTO: Ve los asignados a su ID o los que estén huérfanos de asignación
        $stmt = $pdo->prepare("SELECT r.*, r.id AS id, 
                                      u.nombre as residente, 
                                      m.nombre as tecnico, 
                                      e.nombre as estado_nombre 
                               FROM reportes r 
                               LEFT JOIN usuarios u ON r.usuario_reporta_id = u.id 
                               LEFT JOIN usuarios m ON r.usuario_asignado_id = m.id
                               LEFT JOIN estados e ON r.estado_id = e.id
                               WHERE r.usuario_asignado_id = :uid OR r.usuario_asignado_id IS NULL
                               ORDER BY r.fecha_reporte DESC");
        $stmt->execute([':uid' => $user_id_actual]);
    } else {
        // ADMINISTRACIÓN: Trazabilidad completa de todo el ecosistema
        $stmt = $pdo->query("SELECT r.*, r.id AS id, 
                                    u.nombre as residente, 
                                    m.nombre as tecnico, 
                                    e.nombre as estado_nombre 
                             FROM reportes r 
                             LEFT JOIN usuarios u ON r.usuario_reporta_id = u.id 
                             LEFT JOIN usuarios m ON r.usuario_asignado_id = m.id
                             LEFT JOIN estados e ON r.estado_id = e.id
                             ORDER BY r.fecha_reporte DESC");
    }
    $reportes = $stmt->fetchAll();

    // Consultas complementarias para la barra de alertas y gestión de usuarios
    $stmtNotif = $pdo->prepare("SELECT * FROM notificaciones WHERE usuario_id = :uid ORDER BY id DESC");
    $stmtNotif->execute([':uid' => $user_id_actual]);
    $notificaciones = $stmtNotif->fetchAll();

    // Inicializar variables de administración si el rol es 1
    $tecnicos_mantenimiento = [];
    $todos_los_estados = [];
    $usuarios_totales = [];
    $roles_disponibles = [];

    if ($rol_actual_id === 1) {
        $tecnicos_mantenimiento = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol_id = 2 ORDER BY nombre ASC")->fetchAll();
        $todos_los_estados      = $pdo->query("SELECT id, nombre FROM estados ORDER BY id ASC")->fetchAll();
        $roles_disponibles      = $pdo->query("SELECT id, nombre FROM roles ORDER BY id ASC")->fetchAll();
        
        $stmtUsers = $pdo->query("SELECT u.*, r.nombre as rol_nombre FROM usuarios u LEFT JOIN roles r ON u.rol_id = r.id ORDER BY u.nombre ASC");
        $usuarios_totales = $stmtUsers->fetchAll();
    } elseif ($rol_actual_id === 2) {
        // Los técnicos solo pueden ver la lista completa de estados para cambiarlos
        $todos_los_estados = $pdo->query("SELECT id, nombre FROM estados ORDER BY id ASC")->fetchAll();
    }

} catch (PDOException $e) {
    die("Error crítico de consistencia relacional: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Gestión - Reporte de Daños</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .badge-creado { bg-color: #6c757d; color: white; }
        .badge-activo { bg-color: #0d6efd; color: white; }
        .badge-proceso { bg-color: #ffc107; color: #212529; }
        .badge-resuelto { bg-color: #198754; color: white; }
        .badge-cerrado { bg-color: #212529; color: white; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark px-4 py-3 shadow-sm">
        <span class="navbar-brand mb-0 h1"><i class="fa-solid fa-chart-line me-2"></i>Panel Operativo - TI</span>
        <div class="text-white">
            <span class="me-3 text-light">Bienvenido: <strong><?= htmlspecialchars($nombre_usuario) ?></strong> (<span class="badge bg-secondary"><?= htmlspecialchars($rol_nombre) ?></span>)</span>
            <?php if (in_array($rol_actual_id, [1, 3])): ?>
                <a href="index.php" class="btn btn-success btn-sm me-2"><i class="fa-solid fa-plus"></i> Nuevo Reporte</a>
            <?php endif; ?>
            <a href="login.php" class="btn btn-danger btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
        </div>
    </nav>

    <div class="container-fluid mt-4 px-4">
        
        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                    switch ($_GET['status']) {
                        case 'success': echo "<strong>¡Éxito!</strong> El reporte ha sido guardado de manera correcta en el inventario. Ticket UUID: " . htmlspecialchars($_GET['ticket'] ?? ''); break;
                        case 'updated': echo "<strong>Flujo Actualizado:</strong> El ticket ha mutado de estado y la notificación fue despachada."; break;
                        case 'user_created': echo "<strong>Usuario Registrado:</strong> Las credenciales y rol han sido inyectados exitosamente."; break;
                        case 'user_updated': echo "<strong>Cambio Guardado:</strong> El perfil del usuario ha sido modificado."; break;
                        case 'user_deleted': echo "<strong>Usuario Eliminado:</strong> El registro de la cuenta fue purgado."; break;
                    }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-bell me-2"></i>Buzón de Notificaciones (<?= count($notificaciones) ?>)</h6>
                    </div>
                    <div class="card-body p-2" style="max-height: 450px; overflow-y: auto;">
                        <?php if (empty($notificaciones)): ?>
                            <p class="text-muted text-center py-3 small mb-0">Sin novedades pendientes.</p>
                        <?php else: ?>
                            <?php foreach ($notificaciones as $notif): ?>
                                <div class="alert alert-warning p-2 mb-2 border-0 shadow-sm small position-relative">
                                    <p class="mb-1 pe-3 text-dark"><?= htmlspecialchars($notif['mensaje']) ?></p>
                                    <small class="text-muted d-block"><?= $notif['fecha_notificacion'] ?></small>
                                    <a href="dashboard.php?leer_notif=<?= $notif['id'] ?>" class="position-absolute top-0 end-0 p-1 text-danger" title="Marcar como leída">
                                        <i class="fa-solid fa-circle-xmark"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-9 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-list-check me-2"></i>Trazabilidad de Daños e Incidencias</h6>
                        <span class="badge bg-info text-dark">Registros: <?= count($reportes) ?></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center">
                                <thead class="table-secondary small text-uppercase">
                                    <tr>
                                        <th>Ticket ID (Hex)</th>
                                        <th>Categoría</th>
                                        <th>Ubicación</th>
                                        <th>Residente</th>
                                        <th>Asignado A</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    <?php if (empty($reportes)): ?>
                                        <tr>
                                            <td colspan="8" class="text-muted py-4">No se registran solicitudes en esta categoría.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($reportes as $rep): 
                                            // Configurar color dinámico de badges según el ID del estado
                                            $clase_badge = 'bg-secondary';
                                            if ($rep['estado_id'] == 1) $clase_badge = 'bg-secondary';
                                            if ($rep['estado_id'] == 2) $clase_badge = 'bg-primary';
                                            if ($rep['estado_id'] == 3) $clase_badge = 'bg-warning text-dark';
                                            if ($rep['estado_id'] == 4) $clase_badge = 'bg-success';
                                            if ($rep['estado_id'] == 5) $clase_badge = 'bg-dark';
                                        ?>
                                            <tr>
                                                <td class="fw-bold text-primary">#<?= substr(htmlspecialchars($rep['ticket_uuid']), 0, 8) ?></td>
                                                <td><?= htmlspecialchars($rep['categoria']) ?></td>
                                                <td><?= htmlspecialchars($rep['ubicacion']) ?></td>
                                                <td><?= htmlspecialchars($rep['residente'] ?? 'N/A') ?></td>
                                                <td class="text-muted font-monospace"><?= htmlspecialchars($rep['tecnico'] ?? '👉 Sin Asignar') ?></td>
                                                <td><span class="badge <?= $clase_badge ?>"><?= strtoupper(htmlspecialchars($rep['estado_nombre'])) ?></span></td>
                                                <td><?= date('d/m/Y H:i', strtotime($rep['fecha_reporte'])) ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalVer_<?= $rep['id'] ?>" title="Ver Detalles"><i class="fa-solid fa-eye"></i></button>
                                                        <?php if (in_array($rol_actual_id, [1, 2])): ?>
                                                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalGestion_<?= $rep['id'] ?>" title="Gestionar Ticket"><i class="fa-solid fa-gear"></i></button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="modalVer_<?= $rep['id'] ?>" isset-id="modalVer" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-dark text-white">
                                                            <h6 class="modal-title fw-bold">Ticket Completo #<?= htmlspecialchars($rep['ticket_uuid']) ?></h6>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <p><strong>Descripción:</strong></p>
                                                            <div class="p-3 bg-light rounded text-secondary mb-3 border"><?= nl2br(htmlspecialchars($rep['descripcion'])) ?></div>
                                                            <?php if (!empty($rep['imagen_url'])): ?>
                                                                <p><strong>Evidencia:</strong></p>
                                                                <img src="<?= htmlspecialchars($rep['imagen_url']) ?>" class="img-fluid rounded border mb-2 shadow-sm d-block mx-auto" alt="Evidencia de daño">
                                                            <?php else: ?>
                                                                <p class="text-muted small"><i class="fa-solid fa-image-slash"></i> Sin archivos adjuntos.</p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php if (in_array($rol_actual_id, [1, 2])): ?>
                                                <div class="modal fade" id="modalGestion_<?= $rep['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-primary text-white">
                                                                <h6 class="modal-title fw-bold"><i class="fa-solid fa-sliders me-2"></i>Modificar Flujo Operativo</h6>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form action="actualizar_estado.php" method="POST">
                                                                <div class="modal-body text-start">
                                                                    <input type="hidden" name="ticket_id" value="<?= $rep['id'] ?>">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-bold">Cambiar Estado Técnico</label>
                                                                        <select name="estado_id" class="form-select" required>
                                                                            <?php foreach ($todos_los_estados as $est): ?>
                                                                                <option value="<?= $est['id'] ?>" <?= $rep['estado_id'] == $est['id'] ? 'selected' : '' ?>><?= strtoupper(htmlspecialchars($est['nombre'])) ?></option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>

                                                                    <?php if ($rol_actual_id === 1): ?>
                                                                        <div class="mb-3">
                                                                            <label class="form-label fw-bold">Asignar Operario / Técnico</label>
                                                                            <select name="asignado_a" class="form-select">
                                                                                <option value="">-- Dejar Sin Asignar --</option>
                                                                                <?php foreach ($tecnicos_mantenimiento as $tec): ?>
                                                                                    <option value="<?= $tec['id'] ?>" <?= $rep['usuario_assigned_id'] ?? $rep['usuario_asignado_id'] == $tec['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tec['nombre']) ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="modal-footer bg-light py-2">
                                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                                                                    <button type="submit" class="btn btn-primary btn-sm fw-bold">Guardar Cambios</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($rol_actual_id === 1): ?>
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card shadow-sm border-0 mb-5">
                        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="fa-solid fa-users-gear me-2"></i>Consola Administrativa de Usuarios y Permisos</h6>
                            <button class="btn btn-info btn-sm fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario"><i class="fa-solid fa-user-plus text-dark"></i> Crear Nuevo Usuario</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 text-center">
                                    <thead class="table-light small text-uppercase">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre Completo</th>
                                            <th>Correo Institucional</th>
                                            <th>Contraseña (Plana)</th>
                                            <th>Rol Asignado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        <?php foreach ($usuarios_totales as $usr): ?>
                                            <tr>
                                                <td class="fw-bold text-muted"><?= $usr['id'] ?></td>
                                                <td class="fw-bold"><?= htmlspecialchars($usr['nombre']) ?></td>
                                                <td><?= htmlspecialchars($usr['correo']) ?></td>
                                                <td class="font-monospace text-secondary"><?= htmlspecialchars($usr['password']) ?></td>
                                                <td><span class="badge bg-secondary py-1.5 px-3"><?= strtoupper(htmlspecialchars($usr['rol_nombre'])) ?></span></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditarUsuario_<?= $usr['id'] ?>"><i class="fa-solid fa-pen"></i></button>
                                                        <a href="eliminar_usuario.php?id=<?= $usr['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('¿Seguro que desea purgar este usuario del ecosistema? Esta acción posee integridad referencial.');"><i class="fa-solid fa-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="modalEditarUsuario_<?= $usr['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-warning text-dark">
                                                            <h6 class="modal-title fw-bold"><i class="fa-solid fa-user-pen me-2"></i>Modificar Registro de Usuario</h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form action="procesar_usuario.php" method="POST">
                                                            <div class="modal-body text-start">
                                                                <input type="hidden" name="accion" value="editar">
                                                                <input type="hidden" name="id" value="<?= $usr['id'] ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">Nombre Completo</label>
                                                                    <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($usr['nombre']) ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">Correo Electrónico</label>
                                                                    <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($usr['correo']) ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">Contraseña (Dejar en blanco para conservar actual)</label>
                                                                    <input type="text" name="password" class="form-control" placeholder="Escriba nueva clave si desea cambiarla">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">Nivel de Privilegios (Rol)</label>
                                                                    <select name="rol_id" class="form-select" required>
                                                                        <?php foreach ($roles_disponibles as $rl): ?>
                                                                            <option value="<?= $rl['id'] ?>" <?= $usr['rol_id'] == $rl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($rl['nombre']) ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer bg-light py-2">
                                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-warning btn-sm fw-bold">Actualizar Datos</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalCrearUsuario" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h6 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i>Inyectar Nuevo Usuario</h6>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="procesar_usuario.php" method="POST">
                            <div class="modal-body text-start">
                                <input type="hidden" name="accion" value="crear">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nombre Completo</label>
                                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Juan Pérez" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Correo Electrónico</label>
                                    <input type="email" name="correo" class="form-control" placeholder="ejemplo@cesde.edu.co" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Contraseña de Acceso</label>
                                    <input type="text" name="password" class="form-control" placeholder="Asigne una contraseña explícita" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Rol Operativo</label>
                                    <select name="rol_id" class="form-select" required>
                                        <option value="" disabled selected>Seleccione Rol institucional...</option>
                                        <?php foreach ($roles_disponibles as $rl): ?>
                                            <option value="<?= $rl['id'] ?>"><?= htmlspecialchars($rl['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer bg-light py-2">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                                <button type="submit" class="btn btn-success btn-sm fw-bold">Registrar Cuenta</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>