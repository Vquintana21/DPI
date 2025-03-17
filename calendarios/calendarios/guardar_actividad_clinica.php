<?php
// guardar_actividad_clinica.php
include("conexion.php");
header('Content-Type: application/json');

try {
    // Validar que existan los campos mínimos necesarios
    $requiredFields = ['activity-title', 'type', 'date', 'start_time', 'end_time', 'cursos_idcursos', 'dia'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }
    
    // Obtener y sanitizar los valores
    $idplanclases = isset($_POST['idplanclases']) && $_POST['idplanclases'] != '0' 
        ? (int)$_POST['idplanclases'] 
        : null;
    
    $cursos_idcursos = (int)$_POST['cursos_idcursos'];
    $titulo = mysqli_real_escape_string($conn, $_POST['activity-title']);
    $tipo = mysqli_real_escape_string($conn, $_POST['type']);
    $subtipo = isset($_POST['subtype']) ? mysqli_real_escape_string($conn, $_POST['subtype']) : null;
    $fecha = mysqli_real_escape_string($conn, $_POST['date']);
    $inicio = mysqli_real_escape_string($conn, $_POST['start_time']) . ':00';
    $termino = mysqli_real_escape_string($conn, $_POST['end_time']) . ':00';
    $dia = mysqli_real_escape_string($conn, $_POST['dia']);
    $obligatorio = $_POST['pcl_condicion'] === 'Obligatorio' ? 'Obligatorio' : 'Libre';
    $evaluacion = $_POST['pcl_ActividadConEvaluacion'] === 'S' ? 'S' : 'N';
    
    // Calcular la duración en formato HH:MM:SS
    $time1 = strtotime($inicio);
    $time2 = strtotime($termino);
    $difference = $time2 - $time1;
    $horas = floor($difference / 3600);
    $minutos = floor(($difference % 3600) / 60);
    $segundos = $difference % 60;
    $duracion = sprintf("%02d:%02d:%02d", $horas, $minutos, $segundos);
    
    // Determinar si es inserción o actualización
    if ($idplanclases) {
        // Actualización
        $query = "UPDATE planclases_test SET 
                    pcl_tituloActividad = ?, 
                    pcl_TipoSesion = ?,
                    pcl_SubTipoSesion = ?,
                    pcl_Fecha = ?,
                    pcl_Inicio = ?,
                    pcl_Termino = ?,
                    dia = ?,
                    pcl_condicion = ?,
                    pcl_ActividadConEvaluacion = ?,
                    pcl_HorasPresenciales = ?,
                    pcl_fechamodifica = NOW(),
                    pcl_usermodifica = 'EditorClinico'
                  WHERE idplanclases = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssi", 
            $titulo, 
            $tipo, 
            $subtipo,
            $fecha,
            $inicio,
            $termino,
            $dia,
            $obligatorio,
            $evaluacion,
            $duracion,
            $idplanclases
        );
    } else {
        // Inserción - generar semana automáticamente
        $semana = date('W', strtotime($fecha)) - date('W', strtotime(date('Y') . '-01-01')) + 1;
        if ($semana < 1) $semana = 1;
        
        $query = "INSERT INTO planclases_test 
                 (cursos_idcursos, pcl_Periodo, pcl_tituloActividad, pcl_TipoSesion, pcl_SubTipoSesion, 
                  pcl_Fecha, pcl_Inicio, pcl_Termino, dia, pcl_condicion, pcl_ActividadConEvaluacion, 
                  pcl_HorasPresenciales, pcl_Semana, pcl_fechamodifica, pcl_usermodifica, 
                  pcl_FechaCreacion, pcl_Modalidad) 
                 VALUES 
                 (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'EditorClinico', NOW(), 'Sincrónico')";
                 
        $periodo = date('Y') . (date('n') > 6 ? '2' : '1'); // Determinar período basado en el mes actual
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issssssssssis", 
            $cursos_idcursos,
            $periodo,
            $titulo, 
            $tipo, 
            $subtipo,
            $fecha,
            $inicio,
            $termino,
            $dia,
            $obligatorio,
            $evaluacion,
            $duracion,
            $semana
        );
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Error al ejecutar la consulta: ' . $stmt->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => $idplanclases ? 'Actividad actualizada exitosamente' : 'Actividad creada exitosamente',
        'idplanclases' => $idplanclases ?: $conn->insert_id
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>