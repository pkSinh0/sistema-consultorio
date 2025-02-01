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
    $status = isset($_POST['status']) ? $_POST['status'] : null;
    
    if ($agendamento_id && $status) {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "UPDATE agendamentos SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $agendamento_id);
        
        if ($stmt->execute()) {
            echo json_encode(['sucesso' => true]);
        } else {
            echo json_encode(['erro' => 'Erro ao atualizar status']);
        }
    } else {
        echo json_encode(['erro' => 'Parâmetros inválidos']);
    }
}
?> 