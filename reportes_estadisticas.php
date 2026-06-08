<?php
session_start();
require_once 'config/database.php';

// Validamos acceso estricto al Administrador
if (!isset($_SESSION['usuario_id']) || intval($_SESSION['usuario_rol_id']) !== 1) {
    header("Location: login.php");
    exit();
}

$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-d', strtotime('-6 months'));
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
$vista = isset($_GET['vista']) ? $_GET['vista'] : 'mes'; 

if ($vista == 'semana') {
    $agrupacion_sql = "YEARWEEK(fecha_actualizacion, 1)";
    $formato_texto = "CONCAT('Semana ', WEEK(fecha_actualizacion, 1), ' - Año ', YEAR(fecha_actualizacion))";
} elseif ($vista == 'quincena') {
    $agrupacion_sql = "CONCAT(YEAR(fecha_actualizacion), '-', MONTH(fecha_actualizacion), '-', IF(DAY(fecha_actualizacion) <= 15, 'Q1', 'Q2'))";
    $formato_texto = "CONCAT(IF(DAY(fecha_actualizacion) <= 15, '1ra Quincena ', '2da Quincena '), DATE_FORMAT(fecha_actualizacion, '%b %Y'))";
} else { 
    $agrupacion_sql = "DATE_FORMAT(fecha_actualizacion, '%Y-%m')";
    $formato_texto = "DATE_FORMAT(fecha_actualizacion, '%M %Y')";
}

// Filtra por estado_id 4 (resuelto) o 5 (cerrado) basándose en tu script de BD
$query = "SELECT $formato_texto AS periodo, COUNT(*) AS total 
          FROM reportes 
          WHERE estado_id IN (4, 5) 
          AND fecha_actualizacion BETWEEN :fecha_inicio AND :fecha_fin
          GROUP BY $agrupacion_sql
          ORDER BY fecha_actualizacion DESC";

$f_inicio_completa = $fecha_inicio . " 00:00:00";
$f_fin_completa = $fecha_fin . " 23:59:59";

$datos_tabla = [];
$total_general = 0;

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'fecha_inicio' => $f_inicio_completa,
        'fecha_fin' => $f_fin_completa
    ]);
    $datos_tabla = $stmt->fetchAll();
    
    foreach ($datos_tabla as $fila) {
        $total_general += $fila['total'];
    }
} catch (PDOException $e) {
    die("Error al generar las estadísticas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Métricas de Tickets Atendidos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f6f9; color: #333; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .filtro-panel { background: #343a40; color: white; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .filtro-panel label { font-weight: bold; margin-right: 5px; margin-left: 10px; }
        .filtro-panel input, .filtro-panel select { padding: 6px; border-radius: 4px; border: none; }
        .btn-buscar { background-color: #28a745; color: white; padding: 6px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .kpi-box { background: #007bff; color: white; padding: 15px; border-radius: 6px; display: inline-block; margin-bottom: 20px; min-width: 200px; text-align: center; }
        .kpi-box span { display: block; font-size: 28px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #e9ecef; color: #495057; }
        .nav-link { display: inline-block; margin-bottom: 20px; color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="nav-link">← Volver al Dashboard</a>
    <h2>Panel de Consulta: Tickets Atendidos</h2>
    
    <div class="filtro-panel">
        <form method="GET" action="reportes_estadisticas.php">
            <label>Desde:</label>
            <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>" required>
            
            <label>Hasta:</label>
            <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>" required>
            
            <label>Periodicidad:</label>
            <select name="vista">
                <option value="mes" <?php if($vista=='mes') echo 'selected'; ?>>Mensual</option>
                <option value="quincena" <?php if($vista=='quincena') echo 'selected'; ?>>Quincenal</option>
                <option value="semana" <?php if($vista=='semana') echo 'selected'; ?>>Semanal</option>
            </select>
            
            <button type="submit" class="btn-buscar">Filtrar Datos</button>
        </form>
    </div>

    <div class="kpi-box">
        Total Atendidos en el Rango
        <span><?php echo $total_general; ?></span>
    </div>

    <h3>Desglose por Periodos</h3>
    <table>
        <thead>
            <tr>
                <th>Periodo Evaluado</th>
                <th style="width: 30%; text-align: center;">Cantidad de Tickets Resueltos/Cerrados</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($datos_tabla)): ?>
                <?php foreach($datos_tabla as $fila): ?>
                <tr>
                    <td><?php echo htmlspecialchars($fila['periodo']); ?></td>
                    <td style="text-align: center;">
                        <strong><?php echo $fila['total']; ?></strong>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="2" style="text-align:center; color: #6c757d;">No se encontraron tickets resueltos en este rango.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
