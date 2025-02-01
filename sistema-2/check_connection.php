<?php
header('Content-Type: application/json');

$response = array(
    'status' => 'success',
    'server_ip' => $_SERVER['SERVER_ADDR'],
    'client_ip' => $_SERVER['REMOTE_ADDR'],
    'database' => false,
    'upload_dir' => false
);

// Verificar conexão com banco
require_once 'config/database.php';
try {
    $database = new Database();
    $db = $database->getConnection();
    $response['database'] = true;
} catch (Exception $e) {
    $response['database'] = false;
    $response['database_error'] = $e->getMessage();
}

// Verificar diretório de upload
$uploadDir = 'uploads/';
if (is_dir($uploadDir) && is_writable($uploadDir)) {
    $response['upload_dir'] = true;
} else {
    $response['upload_dir'] = false;
}

echo json_encode($response);
?> 