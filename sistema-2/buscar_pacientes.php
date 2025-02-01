<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Não autorizado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$busca = isset($_GET['busca']) ? $_GET['busca'] : '';

$query = "SELECT id, nome, data_nascimento, tipo_consulta, plano_saude 
          FROM pacientes 
          WHERE nome LIKE :termo 
          OR cpf LIKE :termo 
          ORDER BY nome 
          LIMIT 10";

$stmt = $db->prepare($query);
$stmt->bindValue(':termo', "%$busca%");
$stmt->execute();

$pacientes = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $pacientes[] = [
        'id' => $row['id'],
        'nome' => $row['nome'],
        'data_nascimento' => $row['data_nascimento'],
        'tipo_consulta' => $row['tipo_consulta'],
        'plano_saude' => $row['plano_saude']
    ];
}

header('Content-Type: application/json');
echo json_encode($pacientes);
?> 