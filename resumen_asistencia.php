<?php
// resumen_asistencia.php
// Muestra resumen de cumplimiento, horas extra, horas faltantes y total de horas laboradas
$estilo = '<style>
body { font-family: Arial, Helvetica, sans-serif; background: #f7f7f7; margin: 0; padding: 0; }
.container { max-width: 1100px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px #ccc; padding: 30px; }
h2 { color: #ff6600; margin-top: 30px; margin-bottom: 10px; font-size: 1.5em; }
table { width: 100%; border-collapse: collapse; margin-bottom: 30px; background: #fafafa; }
th, td { padding: 10px 8px; border: 1px solid #e0e0e0; text-align: left; }
th { background: #ff6600; color: #fff; font-weight: bold; }
tr:nth-child(even) { background: #f2f2f2; }
.cumple { background: #eafaf1; color: #27ae60; }
.extra { background: #eafaf1; color: #27ae60; }
.faltante { background: #fff4f0; color: #e74c3c; }
.total { background: #e0e7ff; color: #333; }
</style>';
echo $estilo;
echo '<div class="container">';
echo '<div style="text-align:center; margin-bottom:30px;">'
    . '<img src="WhatsApp Image 2024-10-18 at 9.57.59 AM.jpeg" alt="Logo METACOM" style="max-width:180px; margin-bottom:10px;">'
    . '</div>';
$conexion = new mysqli('localhost', 'root', '', 'asistencia_metacom');
if ($conexion->connect_error) {
    die('Error de conexión: ' . $conexion->connect_error);
}
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$hora_inicio = '07:00:00';
$hora_fin = '16:00:00';
$horas_laborales = 9; // 9 horas de 7am a 4pm
// Obtener todos los empleados
$empleados = [];
$res = $conexion->query("SELECT id_empleado, nombre, apellido FROM empleados");
while($row = $res->fetch_assoc()) $empleados[$row['id_empleado']] = $row;
// Obtener registros de asistencia del día
$asistencias = [];
$res = $conexion->query("SELECT id_empleado, fecha_hora, tipo_registro FROM asistencia WHERE DATE(fecha_hora) = '".$conexion->real_escape_string($fecha)."' ORDER BY fecha_hora ASC");
while($row = $res->fetch_assoc()) {
    $asistencias[$row['id_empleado']][] = $row;
}
// Procesar por empleado
$cumplen = [];
$extras = [];
$faltantes = [];
$totales = [];
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
        $totales[] = ['id'=>$id,'nombre'=>$emp['nombre'],'apellido'=>$emp['apellido'],'horas'=>$horas];
        if($horas >= $horas_laborales) {
            $cumplen[] = ['id'=>$id,'nombre'=>$emp['nombre'],'apellido'=>$emp['apellido'],'horas'=>$horas];
        }
        if($horas > $horas_laborales) {
            $extras[] = ['id'=>$id,'nombre'=>$emp['nombre'],'apellido'=>$emp['apellido'],'horas'=>($horas-$horas_laborales)];
        }
        if($horas < $horas_laborales) {
            $faltantes[] = ['id'=>$id,'nombre'=>$emp['nombre'],'apellido'=>$emp['apellido'],'horas'=>($horas_laborales-$horas)];
        }
    }
}
// Tabla de cumplimiento
 echo '<h2>Cumplimiento de horas laboradas (>= 9h)</h2>';
echo '<table><tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Horas laboradas</th></tr>';
foreach($cumplen as $c) echo '<tr class="cumple"><td>'.$c['id'].'</td><td>'.$c['nombre'].'</td><td>'.$c['apellido'].'</td><td>'.$c['horas'].'</td></tr>';
echo '</table>';
// Tabla de horas extra
 echo '<h2>Horas extra (verde)</h2>';
echo '<table><tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Horas extra</th></tr>';
foreach($extras as $c) echo '<tr class="extra"><td>'.$c['id'].'</td><td>'.$c['nombre'].'</td><td>'.$c['apellido'].'</td><td>'.$c['horas'].'</td></tr>';
echo '</table>';
// Tabla de horas faltantes
 echo '<h2>Horas faltantes (rojo)</h2>';
echo '<table><tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Horas faltantes</th></tr>';
foreach($faltantes as $c) echo '<tr class="faltante"><td>'.$c['id'].'</td><td>'.$c['nombre'].'</td><td>'.$c['apellido'].'</td><td>'.$c['horas'].'</td></tr>';
echo '</table>';
// Tabla de totales
 echo '<h2>Total de horas laboradas</h2>';
echo '<table><tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Horas totales</th></tr>';
foreach($totales as $c) echo '<tr class="total"><td>'.$c['id'].'</td><td>'.$c['nombre'].'</td><td>'.$c['apellido'].'</td><td>'.$c['horas'].'</td></tr>';
echo '</table>';
$footer = '<div style="text-align:center; color:#888; margin-top:40px; font-size:1.1em;">
    <strong>METACOM</strong> — Metales, Construcciones y Más.<br>
    Sistema de Asistencia © 2025
</div>';
echo $footer;
echo '</div>';
$conexion->close();
