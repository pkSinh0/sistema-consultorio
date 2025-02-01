<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: agenda.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Primeiro, buscar a data do agendamento para redirecionar de volta ao dia correto
    $query = "SELECT data_consulta FROM agendamentos WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agendamento) {
        throw new Exception("Agendamento não encontrado.");
    }

    // Deletar o agendamento ao invés de apenas marcar como cancelado
    $query = "DELETE FROM agendamentos WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    
    if ($stmt->execute()) {
        $mensagem = "Horário liberado com sucesso!";
    } else {
        throw new Exception("Erro ao liberar o horário.");
    }

    // Redirecionar de volta para a agenda no dia do agendamento
    header("Location: agenda.php?data=" . $agendamento['data_consulta'] . "&mensagem=" . urlencode($mensagem));
    exit();

} catch (Exception $e) {
    header("Location: agenda.php?erro=" . urlencode($e->getMessage()));
    exit();
}
?> 