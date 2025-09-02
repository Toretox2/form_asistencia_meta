<?php
$estilo = '<style>
body { font-family: Arial, Helvetica, sans-serif; background: #f7f7f7; margin: 0; padding: 0; }
.container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px #ccc; padding: 30px; }
h2 { color: #ff6600; margin-top: 30px; margin-bottom: 10px; font-size: 1.5em; }
table { width: 100%; border-collapse: collapse; margin-bottom: 30px; background: #fafafa; }
th, td { padding: 10px 8px; border: 1px solid #e0e0e0; text-align: left; }
th { background: #ff6600; color: #fff; font-weight: bold; }
tr:nth-child(even) { background: #f2f2f2; }
.error { color: #d8000c; background: #ffd2d2; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
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
// --- FILTROS DE PERIODO ---
$modo = isset($_GET['modo']) ? $_GET['modo'] : 'dia';
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');
$quincena = isset($_GET['quincena']) ? $_GET['quincena'] : '1';

// Consulta principal según modo
if($modo=='dia') {
    $sql = "SELECT a.id_empleado, e.nombre, e.apellido, a.fecha_hora, a.tipo_registro FROM asistencia a JOIN empleados e ON a.id_empleado = e.id_empleado WHERE DATE(a.fecha_hora) = '" . $conexion->real_escape_string($fecha) . "' ORDER BY a.fecha_hora ASC";
    $queryAsis = "SELECT id_empleado, fecha_hora, tipo_registro FROM asistencia WHERE DATE(fecha_hora) = '".$conexion->real_escape_string($fecha)."' ORDER BY fecha_hora ASC";
} elseif($modo=='mes') {
    $sql = "SELECT a.id_empleado, e.nombre, e.apellido, a.fecha_hora, a.tipo_registro FROM asistencia a JOIN empleados e ON a.id_empleado = e.id_empleado WHERE DATE_FORMAT(a.fecha_hora, '%Y-%m') = '" . $conexion->real_escape_string($mes) . "' ORDER BY a.fecha_hora ASC";
    $queryAsis = "SELECT id_empleado, fecha_hora, tipo_registro FROM asistencia WHERE DATE_FORMAT(fecha_hora, '%Y-%m') = '".$conexion->real_escape_string($mes)."' ORDER BY fecha_hora ASC";
} else { // quincena
    $inicio = $mes.'-01';
    $fin = date('Y-m-t', strtotime($mes.'-01'));
    if($quincena=='1') {
        $fin = $mes.'-15';
    } else {
        $inicio = $mes.'-16';
    }
    $sql = "SELECT a.id_empleado, e.nombre, e.apellido, a.fecha_hora, a.tipo_registro FROM asistencia a JOIN empleados e ON a.id_empleado = e.id_empleado WHERE a.fecha_hora >= '".$conexion->real_escape_string($inicio)." 00:00:00' AND a.fecha_hora <= '".$conexion->real_escape_string($fin)." 23:59:59' ORDER BY a.fecha_hora ASC";
    $queryAsis = "SELECT id_empleado, fecha_hora, tipo_registro FROM asistencia WHERE fecha_hora >= '".$conexion->real_escape_string($inicio)." 00:00:00' AND fecha_hora <= '".$conexion->real_escape_string($fin)." 23:59:59' ORDER BY fecha_hora ASC";
}

// Mostrar tabla principal según modo
$result = $conexion->query($sql);
echo '<h2>Consulta de Asistencia</h2>';
echo '<div style="margin-bottom:20px;">';
echo '<form method="get" style="display:inline; margin-right:10px;">';
echo '<input type="hidden" name="modo" value="dia">';
echo 'Día: <input type="date" name="fecha" value="' . $fecha . '">';
echo ' <button type="submit" style="background:#ff6600;color:#fff;padding:6px 14px;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">Ver por día</button>';
echo '</form>';
echo '<form method="get" style="display:inline; margin-right:10px;">';
echo '<input type="hidden" name="modo" value="mes">';
echo 'Mes: <input type="month" name="mes" value="' . $mes . '">';
echo ' <button type="submit" style="background:#ff6600;color:#fff;padding:6px 14px;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">Ver por mes</button>';
echo '</form>';
echo '<form method="get" style="display:inline;">';
echo '<input type="hidden" name="modo" value="quincena">';
echo 'Mes: <input type="month" name="mes" value="' . $mes . '">';
echo ' Quincena: <select name="quincena"><option value="1"' . ($quincena=='1'?' selected':'') . '>1-15</option><option value="2"' . ($quincena=='2'?' selected':'') . '>16-fin</option></select>';
echo ' <button type="submit" style="background:#ff6600;color:#fff;padding:6px 14px;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">Ver por quincena</button>';
echo '</form>';
echo '</div>';
if ($result) {
    if ($result->num_rows > 0) {
        echo '<table border="1">';
        echo '<tr><th>ID Empleado</th><th>Nombre</th><th>Apellido</th><th>Fecha y Hora</th><th>Tipo de Registro</th></tr>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['id_empleado'] . '</td>';
            echo '<td>' . $row['nombre'] . '</td>';
            echo '<td>' . $row['apellido'] . '</td>';
            echo '<td>' . $row['fecha_hora'] . '</td>';
            echo '<td>' . ucfirst($row['tipo_registro']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<tr><td colspan="5" class="error">No hay registros para este periodo.</td></tr>';
    }
} else {
    echo '<tr><td colspan="5" class="error">Error en la consulta de asistencia: ' . $conexion->error . '</td></tr>';
}

// Obtener registros de asistencia para resumen
$asistencias = [];
$res = $conexion->query($queryAsis);
while($row = $res->fetch_assoc()) {
    $asistencias[$row['id_empleado']][] = $row;
}

// --- RESUMEN DE ASISTENCIA ---
$hora_inicio = '07:00:00';
$hora_fin = '16:00:00';
$horas_laborales = 8; // 8 horas base, descontando 1h de almuerzo
// Obtener todos los empleados
$empleados = [];
$res = $conexion->query("SELECT id_empleado, nombre, apellido FROM empleados");
while($row = $res->fetch_assoc()) { $empleados[$row['id_empleado']] = $row; }
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
        if($r['tipo_registro']==='entrada' && !$entrada) { $entrada = $r['fecha_hora']; }
        if($r['tipo_registro']==='salida') { $salida = $r['fecha_hora']; }
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
 echo '<h2>Cumplimiento de horas laboradas (>= 8h)</h2>';
echo '<form method="get" action="exportar_pagos_excel.php" style="display:inline; margin-bottom:10px;">';
if($modo=='dia') {
    echo '<input type="hidden" name="fecha" value="' . $fecha . '">';
} elseif($modo=='mes') {
    echo '<input type="hidden" name="mes" value="' . $mes . '">';
} else {
    echo '<input type="hidden" name="mes" value="' . $mes . '"><input type="hidden" name="quincena" value="' . $quincena . '">';
}
echo '<input type="hidden" name="tabla" value="cumplimiento">';
echo '<button type="submit" style="background:#27ae60;color:#fff;padding:6px 14px;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">Descargar</button>';
echo '</form>';
echo '<table><tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Horas laboradas</th></tr>';
foreach($cumplen as $c) { echo '<tr class="cumple"><td>'.$c['id'].'</td><td>'.$c['nombre'].'</td><td>'.$c['apellido'].'</td><td>'.$c['horas'].'</td></tr>'; }
echo '</table>';
// Tabla de horas extra
 echo '<h2>Horas extra (verde)</h2>';
echo '<form method="get" action="exportar_pagos_excel.php" style="display:inline; margin-bottom:10px;">';
if($modo=='dia') {
    echo '<input type="hidden" name="fecha" value="' . $fecha . '">';
} elseif($modo=='mes') {
    echo '<input type="hidden" name="mes" value="' . $mes . '">';
} else {
    echo '<input type="hidden" name="mes" value="' . $mes . '"><input type="hidden" name="quincena" value="' . $quincena . '">';
}
echo '<input type="hidden" name="tabla" value="extra">';
echo '<button type="submit" style="background:#27ae60;color:#fff;padding:6px 14px;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">Descargar</button>';
echo '</form>';
echo '<table><tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Horas extra</th></tr>';
foreach($extras as $c) { echo '<tr class="extra"><td>'.$c['id'].'</td><td>'.$c['nombre'].'</td><td>'.$c['apellido'].'</td><td>'.$c['horas'].'</td></tr>'; }
echo '</table>';
// Tabla de horas faltantes
 echo '<h2>Horas faltantes (rojo)</h2>';
echo '<form method="get" action="exportar_pagos_excel.php" style="display:inline; margin-bottom:10px;">';
if($modo=='dia') {
    echo '<input type="hidden" name="fecha" value="' . $fecha . '">';
} elseif($modo=='mes') {
    echo '<input type="hidden" name="mes" value="' . $mes . '">';
} else {
    echo '<input type="hidden" name="mes" value="' . $mes . '"><input type="hidden" name="quincena" value="' . $quincena . '">';
}
echo '<input type="hidden" name="tabla" value="faltantes">';
echo '<button type="submit" style="background:#27ae60;color:#fff;padding:6px 14px;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">Descargar</button>';
echo '</form>';
echo '<table><tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Horas faltantes</th></tr>';
foreach($faltantes as $c) { echo '<tr class="faltante"><td>'.$c['id'].'</td><td>'.$c['nombre'].'</td><td>'.$c['apellido'].'</td><td>'.$c['horas'].'</td></tr>'; }
echo '</table>';
// Tabla de totales
 echo '<h2>Total de horas laboradas</h2>';
echo '<form method="get" action="exportar_pagos_excel.php" style="display:inline; margin-bottom:10px;">';
if($modo=='dia') {
    echo '<input type="hidden" name="fecha" value="' . $fecha . '">';
} elseif($modo=='mes') {
    echo '<input type="hidden" name="mes" value="' . $mes . '">';
} else {
    echo '<input type="hidden" name="mes" value="' . $mes . '"><input type="hidden" name="quincena" value="' . $quincena . '">';
}
echo '<input type="hidden" name="tabla" value="totales">';
echo '<button type="submit" style="background:#27ae60;color:#fff;padding:6px 14px;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">Descargar</button>';
echo '</form>';
echo '<table id="tabla-horas"><tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Horas totales</th></tr>';
foreach($totales as $c) {
    echo '<tr class="total"><td>'.$c['id'].'</td><td>'.$c['nombre'].'</td><td>'.$c['apellido'].'</td><td class="horas">'.$c['horas'].'</td></tr>';
}
echo '</table>';

// --- TABLA DE SALARIO INDIVIDUAL ---
echo '<h2>Cálculo de pago por horas laboradas</h2>';
echo '<button type="button" onclick="calcularPagosInd()" style="background:#ff6600;color:#fff;padding:8px 18px;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">Calcular</button>';
echo '<form method="get" action="exportar_pagos_excel.php" style="margin:10px 0; display:inline;" onsubmit="return setPagosExcel()">';
if($modo=='dia') {
    echo '<input type="hidden" name="fecha" value="' . $fecha . '">';
} elseif($modo=='mes') {
    echo '<input type="hidden" name="mes" value="' . $mes . '">';
} else {
    echo '<input type="hidden" name="mes" value="' . $mes . '"><input type="hidden" name="quincena" value="' . $quincena . '">';
}
echo '<input type="hidden" id="pagos_excel" name="pagos_excel" value="">';
echo '<button type="submit" id="btn-descargar-pagos" style="background:#27ae60;color:#fff;padding:8px 18px;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">Descargar tabla pago por hora</button>';
echo '</form>';
echo '<table id="tabla-pagos" style="margin-top:15px;"><tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Horas laboradas</th><th>Pago por hora</th><th>Pago total</th></tr>';
foreach($totales as $c) {
    echo '<tr><td>'.$c['id'].'</td><td>'.$c['nombre'].'</td><td>'.$c['apellido'].'</td><td class="horas">'.$c['horas'].'</td><td><input type="number" class="pago_hora_ind" value="0" min="0" step="0.01" style="width:90px;"></td><td class="pago">0</td></tr>';
}
echo '</table>';
// --- TABLA DE PAGO POR DÍA ---
echo '<h2>Cálculo de pago por días laborados</h2>';
echo '<button type="button" onclick="calcularPagosDia()" style="background:#ff6600;color:#fff;padding:8px 18px;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">Calcular</button>';
echo '<form method="get" action="exportar_pagos_excel.php" style="margin:10px 0; display:inline;" onsubmit="return setPagosDiaExcel()">';
if($modo=='dia') {
    echo '<input type="hidden" name="fecha" value="' . $fecha . '">';
} elseif($modo=='mes') {
    echo '<input type="hidden" name="mes" value="' . $mes . '">';
} else {
    echo '<input type="hidden" name="mes" value="' . $mes . '"><input type="hidden" name="quincena" value="' . $quincena . '">';
}
echo '<input type="hidden" id="pagos_dia_excel" name="pagos_dia_excel" value="">';
echo '<button type="submit" style="background:#27ae60;color:#fff;padding:8px 18px;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">Descargar tabla pago por día</button>';
echo '</form>';
echo '<table id="tabla-pagos-dia" style="margin-top:15px;"><tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Horas totales</th><th>Días laborados</th><th>Pago por día</th><th>Pago total</th></tr>';
foreach($totales as $c) {
    $dias = $c['horas']/8;
    echo '<tr><td>'.$c['id'].'</td><td>'.$c['nombre'].'</td><td>'.$c['apellido'].'</td><td class="horas">'.$c['horas'].'</td><td class="dias">'.round($dias,2).'</td><td><input type="number" class="pago_dia_ind" value="0" min="0" step="0.01" style="width:90px;"></td><td class="pago">0</td></tr>';
}
echo '</table>';
// Botón de descarga para la tabla principal
echo '<form method="get" action="exportar_asistencia_excel.php" style="display:inline; margin-bottom:10px;">';
if($modo=='dia') {
    echo '<input type="hidden" name="fecha" value="' . $fecha . '">';
} elseif($modo=='mes') {
    echo '<input type="hidden" name="mes" value="' . $mes . '">';
} else {
    echo '<input type="hidden" name="mes" value="' . $mes . '"><input type="hidden" name="quincena" value="' . $quincena . '">';
}
echo '<button type="submit" style="background:#ff6600;color:#fff;padding:8px 18px;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">Descargar tabla principal</button>';
echo '</form>';
?>
<script>
function calcularPagosInd() {
    var tabla = document.getElementById("tabla-pagos");
    for(var i=1; i<tabla.rows.length; i++) {
        var horas = parseFloat(tabla.rows[i].getElementsByClassName("horas")[0].textContent);
        var pagoHora = parseFloat(tabla.rows[i].getElementsByClassName("pago_hora_ind")[0].value);
        var pago = (isNaN(pagoHora) ? 0 : pagoHora) * horas;
        tabla.rows[i].getElementsByClassName("pago")[0].textContent = pago.toFixed(2);
    }
}
function setPagosExcel() {
    var tabla = document.getElementById("tabla-pagos");
    var pagos = [];
    for(var i=1; i<tabla.rows.length; i++) {
        var pagoHora = tabla.rows[i].getElementsByClassName("pago_hora_ind")[0].value;
        pagos.push(pagoHora);
    }
    document.getElementById("pagos_excel").value = pagos.join(",");
    return true;
}
function calcularPagosDia() {
    var tabla = document.getElementById("tabla-pagos-dia");
    for(var i=1; i<tabla.rows.length; i++) {
        var dias = parseFloat(tabla.rows[i].getElementsByClassName("dias")[0].textContent);
        var pagoDia = parseFloat(tabla.rows[i].getElementsByClassName("pago_dia_ind")[0].value);
        var pago = (isNaN(pagoDia) ? 0 : pagoDia) * dias;
        tabla.rows[i].getElementsByClassName("pago")[0].textContent = pago.toFixed(2);
    }
}
function setPagosDiaExcel() {
    var tabla = document.getElementById("tabla-pagos-dia");
    var pagos = [];
    for(var i=1; i<tabla.rows.length; i++) {
        var pagoDia = tabla.rows[i].getElementsByClassName("pago_dia_ind")[0].value;
        pagos.push(pagoDia);
    }
    document.getElementById("pagos_dia_excel").value = pagos.join(",");
    return true;
}
</script>
<?php
echo '<div style="text-align:center; color:#888; margin-top:40px; font-size:1.1em;">';
echo '<strong>METACOM</strong> — Metales, Construcciones y Más.<br>';
echo 'Sistema de Asistencia © 2025';
echo '</div>';
echo '</div>';
$conexion->close();
?>
