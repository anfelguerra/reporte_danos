<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo']);
    $password = trim($_POST['password']);

    if (!empty($correo) && !empty($password)) {
        try {
            // Consultamos el usuario y traemos el nombre del rol adjunto para cualquier validación visual
            $stmt = $pdo->prepare("SELECT u.*, r.nombre AS rol_nombre 
                                   FROM usuarios u 
                                   LEFT JOIN roles r ON u.rol_id = r.id 
                                   WHERE u.correo = :correo");
            $stmt->execute([':correo' => $correo]);
            $usuario = $stmt->fetch();

            // Verificación de credenciales en texto plano (según la configuración actual de tu BD)
            if ($usuario && ($password === $usuario['password'])) {
                
                // --- PERSISTENCIA DE SESIÓN CRÍTICA ---
                $_SESSION['usuario_id'] = intval($usuario['id']);
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_rol_id'] = intval($usuario['rol_id']); // ID numérico para las consultas (1, 2, 3)
                $_SESSION['usuario_rol_nombre'] = $usuario['rol_nombre']; // Nombre del rol por si se requiere en interfaz

                // Redirección inmediata al panel de control integrado
                header("Location: dashboard.php");
                exit;
            } else {
                if (!$usuario) {
                    $error = "El correo electrónico ingresado no se encuentra registrado.";
                } else {
                    $error = "La contraseña ingresada es incorrecta.";
                }
            }
        } catch (PDOException $e) {
            $error = "Error de comunicación con la infraestructura de datos: " . $e->getMessage();
        }
    } else {
        $error = "Por favor, complete todos los campos de autenticación.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ecosistema Residencial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card-login {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            background-color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card card-login p-4">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fa-solid fa-building-shield text-primary" style="font-size: 3.5rem;"></i>
                            <h4 class="mt-3 fw-bold text-dark">Ecosistema Residencial</h4>
                            <small class="text-muted d-block">Autenticación Centralizada de Daños</small>
                        </div>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger d-flex align-items-center small py-2 mb-3" role="alert">
                                <i class="fa-solid fa-triangle-exclamation me-2 flex-shrink-0"></i>
                                <div><?= htmlspecialchars($error) ?></div>
                            </div>
                        <?php endif; ?>

                        <form action="login.php" method="POST" autocomplete="off">
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-bold">Correo Electrónico</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-envelope text-muted"></i></span>
                                    <input type="email" name="correo" class="form-control border-start-0 bg-light" placeholder="admin@residencial.com" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-secondary small fw-bold">Contraseña de Acceso</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                                    <input type="password" name="password" class="form-control border-start-0 bg-light" placeholder="••••••••" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                                <i class="fa-solid fa-right-to-bracket me-1"></i> Ingresar al Sistema
                            </button>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <small class="text-light text-opacity-50">Infraestructura local de TI &bull; CESDE 2026</small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>