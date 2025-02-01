<?php
session_start();
require_once 'config/database.php';

// Verificar se é médico
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'medico') {
    header("Location: login.php");
    exit();
}

$prontuario_id = isset($_GET['id']) ? $_GET['id'] : null;
$paciente_id = isset($_GET['paciente_id']) ? $_GET['paciente_id'] : null;

if (!$prontuario_id || !$paciente_id) {
    header("Location: prontuario.php?id=" . $paciente_id);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verificar se o prontuário pertence ao paciente
    $query = "SELECT id FROM prontuarios WHERE id = :id AND paciente_id = :paciente_id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':id', $prontuario_id);
    $stmt->bindValue(':paciente_id', $paciente_id);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        // Excluir o prontuário
        $query = "DELETE FROM prontuarios WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':id', $prontuario_id);
        
        if ($stmt->execute()) {
            header("Location: prontuario.php?id=" . $paciente_id . "&sucesso=" . urlencode("Registro excluído com sucesso!"));
        } else {
            header("Location: prontuario.php?id=" . $paciente_id . "&erro=" . urlencode("Erro ao excluir o registro."));
        }
    } else {
        header("Location: prontuario.php?id=" . $paciente_id . "&erro=" . urlencode("Registro não encontrado."));
    }
} catch (PDOException $e) {
    header("Location: prontuario.php?id=" . $paciente_id . "&erro=" . urlencode("Erro ao excluir: " . $e->getMessage()));
}

exit(); 