<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Primeiro, alterar a tabela para aceitar o tipo 'admin'
    $query = "ALTER TABLE usuarios MODIFY COLUMN tipo ENUM('medico', 'secretaria', 'admin') NOT NULL";
    $db->exec($query);
    
    // Criar a senha hash
    $senha = 'Jmlopes123@!';
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    
    // Inserir o usuário admin
    $query = "INSERT INTO usuarios (nome, login, senha, tipo) VALUES 
              ('Manel', 'Manel', :senha, 'admin')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':senha', $senha_hash);
    
    if ($stmt->execute()) {
        echo "Usuário admin criado com sucesso!<br>";
        echo "Login: Manel<br>";
        echo "Senha: Jmlopes123@!";
    }
} catch (PDOException $e) {
    if ($e->getCode() == '23000') { // Erro de duplicidade
        echo "O usuário admin já existe.";
    } else {
        echo "Erro ao criar usuário admin: " . $e->getMessage();
    }
}
?> 