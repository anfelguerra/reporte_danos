<?php
session_start();
require_once 'config/database.php';

// Validar privilegios mediante IDs numéricos de rol (1 = Admin, 2 = Mantenimiento)
if (!isset($_SESSION['usuario_id']) || !in_array(intval($_SESSION['usuario_rol_id']), [1, 2])) {
    die("No tiene privilegios para realizar esta acción.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = intval($_POST['ticket_id']);
    $nuevo_estado_id = intval($_POST['estado_id']); // Recibe el ID numérico (1 a 5)

    try {
        $pdo->beginTransaction();

        // 1. Si es Administrador (rol_id = 1), puede cambiar el estado y reasignar el técnico
        if (intval($_SESSION['usuario_rol_id']) === 1 && isset($_POST['asignado_a'])) {
            $asignado_a = !empty($_POST['asignado_a']) ? intval($_POST['asignado_a']) : null;
            
            $stmt = $pdo->prepare("UPDATE reportes SET estado_id = :estado_id, usuario_asignado_id = :asignado WHERE id = :id");
            $stmt->execute([
                ':estado_id' => $nuevo_estado_id,
                ':asignado'  => $asignado_a,
                ':id'        => $ticket_id
            ]);
        } else {
            // Si es técnico de mantenimiento, solo actualiza el estado del ticket
            $stmt = $pdo->prepare("UPDATE reportes SET estado_id = :estado_id WHERE id = :id");
            $stmt->execute([
                ':estado_id' => $nuevo_estado_id, 
                ':id'        => $ticket_id
            ]);
        }

        // 2. Consultar el UUID, el residente original y el nombre del estado para la notificación
        $stmtTicket = $pdo->prepare("SELECT r.ticket_uuid, r.usuario_reporta_id, r.ubicacion, e.nombre AS nombre_estado 
                                     FROM reportes r 
                                     LEFT JOIN estados e ON r.estado_id = e.id 
                                     WHERE r.id = :id");
        $stmtTicket->execute([':id' => $ticket_id]);
        $ticketData = $stmtTicket->fetch();

        if ($ticketData) {
            $msg_residente = "Tu ticket #" . substr($ticketData['ticket_uuid'], 0, 8) . " en " . $ticketData['ubicacion'] . " ha cambiado al estado: " . strtoupper($ticketData['nombre_estado']);
            
            // 3. Insertar la notificación dirigida al propietario real
            $stmtNotif = $pdo->prepare("INSERT INTO notificaciones (usuario_id, ticket_uuid, mensaje) VALUES (:uid, :uuid, :msg)");
            $stmtNotif->execute([
                ':uid'  => $ticketData['usuario_reporta_id'], 
                ':uuid' => $ticketData['ticket_uuid'],
                ':msg'  => $msg_residente
            ]);
        }

        $pdo->commit();
        header("Location: dashboard.php?status=updated");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error en el flujo de actualización: " . $e->getMessage());
    }
}
?>