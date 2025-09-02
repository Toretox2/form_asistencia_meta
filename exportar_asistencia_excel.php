<?php
// exportar_asistencia_excel.php
// Exporta la asistencia diaria a Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="asistencia_diaria.xls"');

$conexion = new mysqli('localhost', 'root', '', 'asistencia_metacom');
if ($conexion->connect_error) {
    die('Error de conexión: ' . $conexion->connect_error);
}

// Determinar el filtro activo
$modo = 'dia';
if (isset($_GET['mes']) && isset($_GET['quincena'])) {
    $modo = 'quincena';
} elseif (isset($_GET['mes'])) {
    $modo = 'mes';
} elseif (isset($_GET['fecha'])) {
    $modo = 'dia';
}

if ($modo == 'dia') {
    $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
    $sql = "SELECT a.id_empleado, e.nombre, e.apellido, a.fecha_hora, a.tipo_registro FROM asistencia a JOIN empleados e ON a.id_empleado = e.id_empleado WHERE DATE(a.fecha_hora) = '" . $conexion->real_escape_string($fecha) . "' ORDER BY a.fecha_hora ASC";
    $mensaje = "para el día $fecha";
} elseif ($modo == 'mes') {
    $mes = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');
    $sql = "SELECT a.id_empleado, e.nombre, e.apellido, a.fecha_hora, a.tipo_registro FROM asistencia a JOIN empleados e ON a.id_empleado = e.id_empleado WHERE DATE_FORMAT(a.fecha_hora, '%Y-%m') = '" . $conexion->real_escape_string($mes) . "' ORDER BY a.fecha_hora ASC";
    $mensaje = "para el mes $mes";
} else { // quincena
    $mes = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');
    $quincena = isset($_GET['quincena']) ? $_GET['quincena'] : '1';
    $inicio = $mes.'-01';
    $fin = date('Y-m-t', strtotime($mes.'-01'));
    if($quincena=='1') {
        $fin = $mes.'-15';
    } else {
        $inicio = $mes.'-16';
    }
    $sql = "SELECT a.id_empleado, e.nombre, e.apellido, a.fecha_hora, a.tipo_registro FROM asistencia a JOIN empleados e ON a.id_empleado = e.id_empleado WHERE a.fecha_hora >= '".$conexion->real_escape_string($inicio)." 00:00:00' AND a.fecha_hora <= '".$conexion->real_escape_string($fin)." 23:59:59' ORDER BY a.fecha_hora ASC";
    $mensaje = "para la quincena ".$quincena." del mes $mes";
}

$result = $conexion->query($sql);
echo "<table border='1'>";
echo "<tr><th colspan='5' style='background:#ff6600;color:#fff;font-size:1.1em;'>Asistencia $mensaje</th></tr>";
echo "<tr><th>ID Empleado</th><th>Nombre</th><th>Apellido</th><th>Fecha y Hora</th><th>Tipo de Registro</th></tr>";
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id_empleado'] . "</td>";
        echo "<td>" . $row['nombre'] . "</td>";
        echo "<td>" . $row['apellido'] . "</td>";
        echo "<td>" . $row['fecha_hora'] . "</td>";
        echo "<td>" . ucfirst($row['tipo_registro']) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>No hay registros para este periodo.</td></tr>";
}
echo "</table>";
$conexion->close();
