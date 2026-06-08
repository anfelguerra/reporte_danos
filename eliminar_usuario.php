<?php
session_start();
require_once 'config/database.php';

// REGLA DE SEGURIDAD N°1: Validar rol del Administrador
if (!isset($_SESSION['usuario_id']) || intval($_SESSION['usuario_rol_id']) !== 1) {
    header("Location: login.php");
    exit();
}

$id_eliminar = intval($_GET['id'] ?? 0);

if ($id_eliminar > 0) {
    // Protección contra auto-eliminación accidental
    if ($id_eliminar === intval($_SESSION['usuario_id'])) {
        header("Location: procesar_usuario.php?error=Operación inválida: No puedes eliminar tu propia sesión.");
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $id_eliminar]);
        
        header("Location: procesar_usuario.php?exito=El usuario ha sido removido del sistema con éxito.");
        exit();
    } catch (PDOException $e) {
        header("Location: procesar_usuario.php?error=No se puede eliminar el registro. El usuario cuenta con un historial de tickets activos.");
        exit();
    }
} else {
    header("Location: procesar_usuario.php");
    exit();
}
