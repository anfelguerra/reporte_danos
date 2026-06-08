<?php
session_start();
require_once 'config/database.php';

// Control de acceso general
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$exito = '';

// Procesar radicación del ticket (Regla N°4: Todos los usuarios pueden crear)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria_id = intval($_POST['categoria_id'] ?? 0);
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $ubicacion    = trim($_POST['ubicacion'] ?? '');
    $prioridad    = $_POST['prioridad'] ?? 'Media';
    $usuario_id   = intval($_SESSION['usuario_id']);
    
    // Generar un UUID único para el ticket de forma segura
    $ticket_uuid  = bin2hex(random_bytes(16)); 

    if ($categoria_id > 0 && !empty($descripcion) && !empty($ubicacion)) {
        try {
            // Consulta adaptada a tus columnas reales
            $stmt = $pdo->prepare("INSERT INTO reportes (categoria_id, ticket_uuid, descripcion, ubicacion, prioridad, usuario_reporta_id, estado_id, fecha_reporte) 
                                   VALUES (:categoria_id, :ticket_uuid, :descripcion, :ubicacion, :prioridad, :usuario_id, 1, CURRENT_TIMESTAMP())");
            
            $stmt->execute([
                'categoria_id' => $categoria_id,
                'ticket_uuid'  => $ticket_uuid,
                'descripcion'  => $descripcion,
                'ubicacion'    => $ubicacion,
                'prioridad'    => $prioridad,
                'usuario_id'   => $usuario_id
            ]);
            
            $exito = "El ticket se ha radicado con éxito. Código de seguimiento: #" . substr($ticket_uuid, 0, 8);
        } catch (PDOException $e) {
            $error = "Error al guardar el reporte: " . $e->getMessage();
        }
    } else {
        $error = "Por favor, complete todos los campos mandatorios del formulario.";
    }
}

// Obtener las categorías dinámicas para el select del formulario (Punto 1)
$categorias = $pdo->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Reporte de Daño</title>
    
    <!-- ENLACES DE DISEÑO LOCALES VINCULADOS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/all.min.css">
</head>
<body class="bg-light">
<div class="container my-5" style="max-width: 600px;">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm mb-3">← Volver al Dashboard</a>
    
    <div class="card shadow-sm">
        <div class="card-body bg-white rounded text-dark">
            <h5 class="card-title fw-bold mb-3 border-bottom pb-2">Radicar Nuevo Reporte de Daño</h5>
            
            <?php if(!empty($error)): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if(!empty($exito)): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($exito) ?></div><?php endif; ?>

            <form action="guardar_reporte.php" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Categoría del Incidente</label>
                    <select name="categoria_id" class="form-select form-select-sm" required>
                        <option value="">-- Seleccione un tipo de daño --</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Ubicación / Apartamento o Área</label>
                    <input type="text" name="ubicacion" class="form-control form-control-sm" placeholder="Ej. Apto 418 o Salón Social" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Prioridad Inicial</label>
                    <select name="prioridad" class="form-select form-select-sm">
                        <option value="Baja">Baja</option>
                        <option value="Media" selected>Media</option>
                        <option value="Alta">Alta</option>
                        <option value="Crítica">Crítica</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Descripción Detallada del Daño</label>
                    <textarea name="descripcion" class="form-control form-control-sm" rows="4" placeholder="Describa el problema observado..." required></textarea>
                </div>
                <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold py-2">Enviar Reporte</button>
            </form>
        </div>
    </div>
</div>

<!-- JAVASCRIPT LOCAL VINCULADO -->
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
