<?php
session_start();
require_once 'config/database.php';

// Validar inicio de sesión básico
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$rol_id = intval($_SESSION['usuario_rol_id']);
$usuario_id = intval($_SESSION['usuario_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_uuid = trim($_POST['ticket_uuid'] ?? '');
    $nuevo_estado_id = intval($_POST['estado_id'] ?? 0);

    if (!empty($ticket_uuid) && $nuevo_estado_id > 0) {
        try {
            // Verificar primero las propiedades del ticket para aplicar reglas 1 y 2
            $stmtCheck = $pdo->prepare("SELECT usuario_asignado_id FROM reportes WHERE ticket_uuid = :uuid");
            $stmtCheck->execute(['uuid' => $ticket_uuid]);
            $ticket = $stmtCheck->fetch();

            if ($ticket) {
                // Validación estricta de permisos de modificación
                if ($rol_id === 1 || ($rol_id === 2 && intval($ticket['usuario_asignado_id']) === $usuario_id)) {
                    
                    $stmtUpdate = $pdo->prepare("UPDATE reportes SET estado_id = :estado_id, fecha_actualizacion = CURRENT_TIMESTAMP() WHERE ticket_uuid = :uuid");
                    $stmtUpdate->execute([
                        'estado_id' => $nuevo_estado_id,
                        'uuid'      => $ticket_uuid
                    ]);

                    // Redireccionar de vuelta a la vista de detalles del ticket con éxito
                    header("Location: ver_ticket.php?uuid=" . $ticket_uuid . "&exito=Estado actualizado");
                    exit();
                } else {
                    header("Location: ver_ticket.php?uuid=" . $ticket_uuid . "&error=No tienes permisos para modificar este registro.");
                    exit();
                }
            } else {
                header("Location: dashboard.php?error=Ticket inexistente");
                exit();
            }
        } catch (PDOException $e) {
            die("Error crítico al actualizar el estado: " . $e->getMessage());
        }
    }
}
header("Location: dashboard.php");
exit();
