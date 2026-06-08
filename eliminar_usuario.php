<?php
session_start();
require_once 'config/database.php';

// Validar que sea administrador usando la ID numérica correcta (1)
if (!isset($_SESSION['usuario_id']) || intval($_SESSION['usuario_rol_id']) !== 1) {
    die("No tiene privilegios para realizar esta acción.");
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Cláusula de seguridad: Evitar auto-eliminarse y romper la sesión activa
    if ($id === intval($_SESSION['usuario_id'])) {
        die("Error de seguridad: No puedes eliminar tu propio usuario mientras estás en sesión.");
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header("Location: dashboard.php?status=user_deleted");
        exit;
    } catch (PDOException $e) {
        die("Error al eliminar el usuario (comprueba restricciones de clave foránea): " . $e->getMessage());
    }
}

header("Location: dashboard.php");
exit;