<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $query = "CREATE TABLE IF NOT EXISTS dias_sem_atendimento (
        id INT AUTO_INCREMENT PRIMARY KEY,
        data DATE NOT NULL UNIQUE,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($db->exec($query)) {
        echo "Tabela dias_sem_atendimento criada com sucesso!";
    }
} catch (PDOException $e) {
    echo "Erro ao criar tabela: " . $e->getMessage();
}
?> 