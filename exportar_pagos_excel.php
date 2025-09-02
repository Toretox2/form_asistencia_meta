<?php
// exportar_pagos_excel.php
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="pagos_horas_laboradas.xls"');
$conexion = new mysqli('localhost', 'root', '', 'asistencia_metacom');
if ($conexion->connect_error) {
    die('Error de conexión: ' . $conexion->connect_error);
}
$modo = isset($_GET['modo']) ? $_GET['modo'] : '';
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');
$quincena = isset($_GET['quincena']) ? $_GET['quincena'] : '1';
$pagos_excel = isset($_GET['pagos_excel']) ? $_GET['pagos_excel'] : '';
$pagos_array = $pagos_excel ? explode(',', $pagos_excel) : [];
$pagos_dia_excel = isset($_GET['pagos_dia_excel']) ? $_GET['pagos_dia_excel'] : '';
$pagos_dia_array = $pagos_dia_excel ? explode(',', $pagos_dia_excel) : [];
$horas_laborales = 9;
$tabla = isset($_GET['tabla']) ? $_GET['tabla'] : '';
// Obtener empleados y horas
$empleados = [];
$res = $conexion->query("SELECT id_empleado, nombre, apellido FROM empleados");
while($row = $res->fetch_assoc()) $empleados[$row['id_empleado']] = $row;
$asistencias = [];
// Filtro de periodo
if(isset($_GET['fecha'])) {
    $query = "SELECT id_empleado, fecha_hora, tipo_registro FROM asistencia WHERE DATE(fecha_hora) = '".$conexion->real_escape_string($fecha)."' ORDER BY fecha_hora ASC";
} elseif(isset($_GET['mes']) && !isset($_GET['quincena'])) {
    $query = "SELECT id_empleado, fecha_hora, tipo_registro FROM asistencia WHERE DATE_FORMAT(fecha_hora, '%Y-%m') = '".$conexion->real_escape_string($mes)."' ORDER BY fecha_hora ASC";
} elseif(isset($_GET['mes']) && isset($_GET['quincena'])) {
    $inicio = $mes.'-01';
    $fin = date('Y-m-t', strtotime($mes.'-01'));
    if($quincena=='1') {
        $fin = $mes.'-15';
    } else {
        $inicio = $mes.'-16';
    }
    $query = "SELECT id_empleado, fecha_hora, tipo_registro FROM asistencia WHERE fecha_hora >= '".$conexion->real_escape_string($inicio)." 00:00:00' AND fecha_hora <= '".$conexion->real_escape_string($fin)." 23:59:59' ORDER BY fecha_hora ASC";
} else {
    $query = "SELECT id_empleado, fecha_hora, tipo_registro FROM asistencia WHERE DATE(fecha_hora) = '".$conexion->real_escape_string($fecha)."' ORDER BY fecha_hora ASC";
}
$res = $conexion->query($query);
while($row = $res->fetch_assoc()) {
    $asistencias[$row['id_empleado']][] = $row;
}
$totales = [];
$ix = 0;
foreach($empleados as $id => $emp) {
    $registros = isset($asistencias[$id]) ? $asistencias[$id] : [];
    $entrada = null;
    $salida = null;
    foreach($registros as $r) {
        if($r['tipo_registro']==='entrada' && !$entrada) $entrada = $r['fecha_hora'];
        if($r['tipo_registro']==='salida') $salida = $r['fecha_hora'];
    }
    if($entrada && $salida) {
        $h_entrada = strtotime($entrada);
        $h_salida = strtotime($salida);
        $horas = round(($h_salida-$h_entrada)/3600,2);
        $dias = $horas / $horas_laborales;
        $pago_hora = isset($pagos_array[$ix]) ? floatval($pagos_array[$ix]) : 0;
        $pago_dia = isset($pagos_dia_array[$ix]) ? floatval($pagos_dia_array[$ix]) : 0;
        $pago_total_hora = $horas * $pago_hora;
        $pago_total_dia = $dias * $pago_dia;
        $totales[] = [
            'id'=>$id,
            'nombre'=>$emp['nombre'],
            'apellido'=>$emp['apellido'],
            'horas'=>$horas,
            'dias'=>$dias,
            'pago_hora'=>$pago_hora,
            'pago_total_hora'=>$pago_total_hora,
            'pago_dia'=>$pago_dia,
            'pago_total_dia'=>$pago_total_dia
        ];
        $ix++;
    }
}
// Mostrar tabla según el parámetro recibido
if($tabla==="cumplimiento") {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Horas laboradas</th></tr>";
    foreach($totales as $c) {
        if($c['horas'] >= $horas_laborales) {
            echo "<tr><td>{$c['id']}</td><td>{$c['nombre']}</td><td>{$c['apellido']}</td><td>{$c['horas']}</td></tr>";
        }
    }
    echo "</table>";
    $conexion->close();
    exit();
} elseif($tabla==="extra") {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Horas extra</th></tr>";
    foreach($totales as $c) {
        if($c['horas'] > $horas_laborales) {
            $extra = $c['horas']-$horas_laborales;
            echo "<tr><td>{$c['id']}</td><td>{$c['nombre']}</td><td>{$c['apellido']}</td><td>{$extra}</td></tr>";
        }
    }
    echo "</table>";
    $conexion->close();
    exit();
} elseif($tabla==="faltantes") {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Horas faltantes</th></tr>";
    foreach($totales as $c) {
        if($c['horas'] < $horas_laborales) {
            $falt = $horas_laborales-$c['horas'];
            echo "<tr><td>{$c['id']}</td><td>{$c['nombre']}</td><td>{$c['apellido']}</td><td>{$falt}</td></tr>";
        }
    }
    echo "</table>";
    $conexion->close();
    exit();
} elseif($tabla==="totales") {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Horas totales</th></tr>";
    foreach($totales as $c) {
        echo "<tr><td>{$c['id']}</td><td>{$c['nombre']}</td><td>{$c['apellido']}</td><td>{$c['horas']}</td></tr>";
    }
    echo "</table>";
    $conexion->close();
    exit();
} elseif($pagos_dia_excel) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Horas totales</th><th>Días laborados</th><th>Pago por día</th><th>Pago total</th></tr>";
    $ix = 0;
    foreach($totales as $c) {
        $pago_dia = isset($pagos_dia_array[$ix]) ? floatval($pagos_dia_array[$ix]) : 0;
        echo "<tr><td>{$c['id']}</td><td>{$c['nombre']}</td><td>{$c['apellido']}</td><td>{$c['horas']}</td><td>".round($c['dias'],2)."</td><td>".number_format($pago_dia,2)."</td><td>".number_format($c['pago_total_dia'],2)."</td></tr>";
        $ix++;
    }
    echo "</table>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Horas laboradas</th><th>Pago por hora</th><th>Pago total</th></tr>";
    $ix = 0;
    foreach($totales as $c) {
        $pago_hora = isset($pagos_array[$ix]) ? floatval($pagos_array[$ix]) : 0;
        echo "<tr><td>{$c['id']}</td><td>{$c['nombre']}</td><td>{$c['apellido']}</td><td>{$c['horas']}</td><td>".number_format($pago_hora,2)."</td><td>".number_format($c['pago_total_hora'],2)."</td></tr>";
        $ix++;
    }
    echo "</table>";
}
$conexion->close();
?>
