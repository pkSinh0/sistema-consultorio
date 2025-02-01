<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Não autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $query = "DELETE FROM dias_sem_atendimento WHERE data = :data";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':data', $_POST['data']);
        
        if ($stmt->execute()) {
            echo json_encode(['sucesso' => true]);
        } else {
            echo json_encode(['erro' => 'Erro ao reativar atendimentos']);
        }
    } catch (PDOException $e) {
        echo json_encode(['erro' => $e->getMessage()]);
    }
}
?> 