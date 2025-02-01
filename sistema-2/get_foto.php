<?php
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Configurar PDO para retornar os BLOBs como strings
    $db->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
    
    $query = "SELECT foto, foto_tipo FROM pacientes WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();

    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($paciente && $paciente['foto']) {
        // Prevenir cache
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        
        // Enviar a imagem
        header("Content-Type: " . $paciente['foto_tipo']);
        echo $paciente['foto'];
        
        // Debug
        error_log("Foto enviada: " . strlen($paciente['foto']) . " bytes");
        error_log("Tipo MIME: " . $paciente['foto_tipo']);
    } else {
        // Retornar imagem padrão
        header("Content-Type: image/png");
        echo file_get_contents('assets/images/user-placeholder.png');
    }
} catch (Exception $e) {
    error_log("Erro ao buscar foto: " . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
}
?> 