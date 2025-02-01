<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Não autorizado']);
    exit();
}

$data = $_GET['data'] ?? date('Y-m-d');
$agendamento_id = $_GET['agendamento_id'] ?? null;

$database = new Database();
$db = $database->getConnection();

try {
    // Horários possíveis
    $horarios = [
        '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
        '13:00', '13:30',
        '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'
    ];

    // Verificar se é um dia sem atendimento
    $query = "SELECT 1 FROM dias_sem_atendimento WHERE data = :data";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':data', $data);
    $stmt->execute();

    if ($stmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode([]);
        exit();
    }

    // Buscar horários já agendados, excluindo o agendamento atual se estiver editando
    $query = "SELECT horario 
             FROM agendamentos 
             WHERE data_consulta = :data 
             AND status != 'cancelado'";
    
    if ($agendamento_id) {
        $query .= " AND id != :agendamento_id";
    }

    $stmt = $db->prepare($query);
    $stmt->bindParam(':data', $data);
    
    if ($agendamento_id) {
        $stmt->bindParam(':agendamento_id', $agendamento_id);
    }
    
    $stmt->execute();
    
    $agendados = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Remover horários já agendados
    $horarios_disponiveis = array_values(array_diff($horarios, $agendados));

    header('Content-Type: application/json');
    echo json_encode($horarios_disponiveis);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => $e->getMessage()]);
}
?> 