<?php
session_start();
require_once 'config/database.php';

// Validar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    die("Sesión no iniciada o caducada.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generar UUID único para el ticket (32 caracteres hexadecimales)
    $ticket_uuid = bin2hex(random_bytes(16)); 
    $usuario_id = intval($_SESSION['usuario_id']);
    $categoria = $_POST['categoria'];
    $descripcion = $_POST['descripcion'];
    $ubicacion = $_POST['ubicacion'];
    
    // Procesamiento de la imagen (Ruta o NULL si no se sube nada)
    $imagen_url = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $dir_subida = 'uploads/';
        if (!is_dir($dir_subida)) {
            mkdir($dir_subida, 0755, true);
        }
        $nombre_archivo = time() . '_' . basename($_FILES['imagen']['name']);
        $ruta_final = $dir_subida . $nombre_archivo;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_final)) {
            $imagen_url = $ruta_final;
        }
    }

    try {
        $pdo->beginTransaction(); 

        // Inserción adaptada exactamente al script de tu Base de Datos
        $stmt = $pdo->prepare("INSERT INTO reportes (ticket_uuid, categoria, descripcion, ubicacion, usuario_reporta_id, estado_id, imagen_url) 
                               VALUES (:ticket_uuid, :categoria, :descripcion, :ubicacion, :usuario_reporta_id, 1, :imagen_url)");
        
        $stmt->execute([
            ':ticket_uuid'        => $ticket_uuid,
            ':categoria'          => $categoria,
            ':descripcion'        => $descripcion,
            ':ubicacion'          => $ubicacion,
            ':usuario_reporta_id' => $usuario_id,
            ':imagen_url'         => $imagen_url
        ]);

        // Obtener administradores (1) y técnicos de mantenimiento (2) para enviarles la notificación
        $stmtUsers = $pdo->query("SELECT id FROM usuarios WHERE rol_id IN (1, 2)");
        $empleados = $stmtUsers->fetchAll();

        $msg = "Nuevo ticket radicado (#" . substr($ticket_uuid, 0, 8) . ") en la ubicación: " . $ubicacion;
        $stmtNotif = $pdo->prepare("INSERT INTO notificaciones (usuario_id, ticket_uuid, mensaje) VALUES (:uid, :uuid, :msg)");
        
        foreach ($empleados as $empleado) {
            $stmtNotif->execute([
                ':uid'  => $empleado['id'],
                ':uuid' => $ticket_uuid,
                ':msg'  => $msg
            ]);
        }

        $pdo->commit();
        header("Location: dashboard.php?status=success&ticket=" . $ticket_uuid);
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error crítico al guardar el reporte: " . $e->getMessage());
    }
}
?>