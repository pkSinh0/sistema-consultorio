<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Não autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agendamento_id = isset($_POST['id']) ? $_POST['id'] : null;
    
    if ($agendamento_id) {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "UPDATE agendamentos SET status = 'cancelado' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $agendamento_id);
        
        if ($stmt->execute()) {
            echo json_encode(['sucesso' => true]);
        } else {
            echo json_encode(['erro' => 'Erro ao cancelar consulta']);
        }
    } else {
        echo json_encode(['erro' => 'ID do agendamento não fornecido']);
    }
}
?> 