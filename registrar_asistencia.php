<?php
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "asistencia_metacom");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$id_empleado = $_POST['id_empleado'];
$tipo_registro = $_POST['tipo_registro'];
$fecha_hora = isset($_POST['fecha_hora']) ? $_POST['fecha_hora'] : date('Y-m-d H:i:s');
$fecha = date('Y-m-d', strtotime($fecha_hora));

// Verificar si ya existe un registro de este tipo para este empleado en el día
$sql_check = "SELECT COUNT(*) FROM asistencia WHERE id_empleado = ? AND tipo_registro = ? AND DATE(fecha_hora) = ?";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("sss", $id_empleado, $tipo_registro, $fecha);
$stmt_check->execute();
$stmt_check->bind_result($existe);
$stmt_check->fetch();
$stmt_check->close();

if ($existe > 0) {
    $error = urlencode('Ya existe un registro de ' . $tipo_registro . ' para este empleado hoy.');
    header('Location: formulario_asistencia.html?error=' . $error);
    exit();
}

// Insertar registro
$sql = "INSERT INTO asistencia (id_empleado, fecha_hora, tipo_registro) VALUES (?, ?, ?)";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("sss", $id_empleado, $fecha_hora, $tipo_registro);

if ($stmt->execute()) {
    header('Location: formulario_asistencia.html?ok=1');
    exit();
} else {
    $error = urlencode('Error: ' . $stmt->error);
    header('Location: formulario_asistencia.html?error=' . $error);
    exit();
}

$stmt->close();
$conexion->close();