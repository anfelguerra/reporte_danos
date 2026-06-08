<?php
session_start();
// Validar el acceso por ID de rol o nombre de rol guardado en login.php
if (!isset($_SESSION['usuario_id']) || !in_array(intval($_SESSION['usuario_rol_id']), [1, 3])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportar Daño Residencial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class=\"bg-light\">
    <nav class="navbar navbar-dark bg-dark px-4 py-3">
        <span class="navbar-brand mb-0 h1"><i class="fa-solid fa-building-crack"></i> Sistema de Reportes</span>
        <div class="text-white">
            <span class="me-3 text-light">Hola, <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong> (<?= htmlspecialchars($_SESSION['usuario_rol_nombre']) ?>)</span>
            <a href="dashboard.php" class="btn btn-outline-info btn-sm me-2"><i class="fa-solid fa-chart-line"></i> Panel</a>
            <a href="login.php" class="btn btn-danger btn-sm"><i class="fa-solid fa-right-from-bracket\"></i> Salir</a>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-success text-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Radicar Formulario de Daño / Incidencia</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="guardar_reporte.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Categoría del Daño</label>
                                <select name="categoria" class="form-select" required>
                                    <option value="" disabled selected>Seleccione una categoría...</option>
                                    <option value="Fontanería / Plomería">Fontanería / Plomería</option>
                                    <option value="Electricidad">Electricidad</option>
                                    <option value="Estructural (Paredes/Techos)">Estructural (Paredes/Techos)</option>
                                    <option value="Ascensores / Áreas Comunes">Ascensores / Áreas Comunes</option>
                                    <option value="Notificaciones">Notificaciones</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Ubicación Exacta</label>
                                <input type="text" name="ubicacion" class="form-control" placeholder="Ej: Torre 3 - Apto 402" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Descripción Detallada del Daño</label>
                                <textarea name="descripcion" class="form-control" rows="4" placeholder="Describa la falla estructural o novedad encontrada..." required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Evidencia Fotográfica (Opcional)</label>
                                <input type="file" name="imagen" class="form-control" accept="image/*">
                            </div>

                            <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                                <i class="fa-solid fa-paper-plane"></i> Enviar Reporte Oficial
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>