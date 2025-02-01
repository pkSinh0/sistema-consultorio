<?php
session_start();
require_once 'config/database.php';

// Verificar se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Não autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paciente_id'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Iniciar transação
        $db->beginTransaction();
        
        // Verificar se existem agendamentos futuros
        $stmt = $db->prepare("SELECT COUNT(*) FROM agendamentos WHERE paciente_id = ? AND data >= CURDATE()");
        $stmt->execute([$_POST['paciente_id']]);
        $tem_agendamentos = $stmt->fetchColumn() > 0;
        
        if ($tem_agendamentos) {
            throw new Exception("Não é possível excluir paciente com agendamentos futuros.");
        }
        
        // Excluir registros relacionados
        $stmt = $db->prepare("DELETE FROM agendamentos WHERE paciente_id = ?");
        $stmt->execute([$_POST['paciente_id']]);
        
        // Excluir o paciente
        $stmt = $db->prepare("DELETE FROM pacientes WHERE id = ?");
        $stmt->execute([$_POST['paciente_id']]);
        
        // Confirmar transação
        $db->commit();
        
        echo json_encode(['sucesso' => true]);
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo json_encode(['erro' => $e->getMessage()]);
    }
} else {
    echo json_encode(['erro' => 'Requisição inválida']);
}
?> 