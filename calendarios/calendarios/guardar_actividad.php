<?php 
include("conexion.php");
header('Content-Type: application/json');

// Activar reporte de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
	
    // Verificar que existe el ID
    if (!isset($_POST['idplanclases'])) {
        throw new Exception('ID no proporcionado');
    }

    $idplanclases = (int)$_POST['idplanclases'];

    // Obtener y sanitizar los valores
    $titulo = isset($_POST['activity-title']) ? mysqli_real_escape_string($conn, $_POST['activity-title']) : '';
    $tipo = isset($_POST['type']) ? mysqli_real_escape_string($conn, $_POST['type']) : '';
    $subtipo = isset($_POST['subtype']) ? mysqli_real_escape_string($conn, $_POST['subtype']) : '';
    $inicio = isset($_POST['start_time']) ? mysqli_real_escape_string($conn, $_POST['start_time']) : '';
    $termino = isset($_POST['end_time']) ? mysqli_real_escape_string($conn, $_POST['end_time']) : '';
    $condicion = isset($_POST['mandatory']) && $_POST['mandatory'] === 'true' ? "Obligatorio" : "Libre";
    $evaluacion = isset($_POST['is_evaluation']) && $_POST['is_evaluation'] === 'true' ? "S" : "N";
	
	 // Calcular duración
    $time1 = strtotime($inicio);
    $time2 = strtotime($termino);
    $difference = $time2 - $time1;
    
    // Convertir a formato HH:MM:SS
    $horas = floor($difference / 3600);
    $minutos = floor(($difference % 3600) / 60);
    $segundos = $difference % 60;
    
    $duracion = sprintf("%02d:%02d:%02d", $horas, $minutos, $segundos);

    // Query de actualización
    $query = "UPDATE planclases SET 
                pcl_tituloActividad = ?, 
                pcl_TipoSesion = ?,
                pcl_SubTipoSesion = ?,
                pcl_Inicio = ?,
                pcl_Termino = ?,
                pcl_condicion = ?,
                pcl_ActividadConEvaluacion = ?,
				pcl_HorasPresenciales = ?
              WHERE idplanclases = ?";

    if (!$stmt = $conn->prepare($query)) {
        throw new Exception('Error en la preparación de la consulta: ' . $conn->error);
    }

    if (!$stmt->bind_param("ssssssssi", 
        $titulo, 
        $tipo,
        $subtipo,
        $inicio,
        $termino,
        $condicion,
        $evaluacion,
		$duracion,
        $idplanclases
    )) {
        throw new Exception('Error en el bind_param: ' . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception('Error en la ejecución: ' . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Actividad actualizada exitosamente'
    ]);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>