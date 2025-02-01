<?php
header('Content-Type: text/plain');

echo "Verificação de Ambiente\n";
echo "----------------------\n\n";

echo "Servidor: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Path: " . __FILE__ . "\n";
echo "Base URL: " . (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "\n\n";

echo "Extensões PHP:\n";
echo "- PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'OK' : 'Não instalado') . "\n";
echo "- GD: " . (extension_loaded('gd') ? 'OK' : 'Não instalado') . "\n";

echo "\nPermissões:\n";
$uploadDir = 'uploads/';
echo "- Upload dir: " . (is_writable($uploadDir) ? 'Gravável' : 'Não gravável') . "\n";

echo "\nConexão com Banco:\n";
require_once 'config/database.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "- Conexão: OK\n";
} catch (Exception $e) {
    echo "- Conexão: ERRO - " . $e->getMessage() . "\n";
} 