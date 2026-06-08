<?php
session_start();
require_once 'config/database.php';

// Validar que sea administrador (rol_id = 1)
if (!isset($_SESSION['usuario_id']) || intval($_SESSION['usuario_rol_id']) !== 1) {
    die("No tiene privilegios para realizar esta acción.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'];
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $password = trim($_POST['password']);
    $rol_id = intval($_POST['rol_id']); // Recibe el ID numérico (1, 2, 3)

    if ($accion === 'crear') {
        if (!empty($nombre) && !empty($correo) && !empty($password) && !empty($rol_id)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, password, rol_id) VALUES (:nombre, :correo, :password, :rol_id)");
                $stmt->execute([
                    ':nombre'   => $nombre,
                    ':correo'   => $correo,
                    ':password' => $password, 
                    ':rol_id'   => $rol_id
                ]);
                header("Location: dashboard.php?status=user_created");
                exit;
            } catch (PDOException $e) {
                die("Error al crear usuario (posible correo duplicado): " . $e->getMessage());
            }
        }
    } elseif ($accion === 'editar') {
        $id = intval($_POST['id']);
        if (!empty($id) && !empty($nombre) && !empty($correo) && !empty($rol_id)) {
            try {
                if (!empty($password)) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET nombre = :nombre, correo = :correo, password = :password, rol_id = :rol_id WHERE id = :id");
                    $params = [':nombre' => $nombre, ':correo' => $correo, ':password' => $password, ':rol_id' => $rol_id, ':id' => $id];
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET nombre = :nombre, correo = :correo, rol_id = :rol_id WHERE id = :id");
                    $params = [':nombre' => $nombre, ':correo' => $correo, ':rol_id' => $rol_id, ':id' => $id];
                }
                $stmt->execute($params);
                header("Location: dashboard.php?status=user_updated");
                exit;
            } catch (PDOException $e) {
                die("Error al actualizar usuario: " . $e->getMessage());
            }
        }
    }
}

header("Location: dashboard.php");
exit;