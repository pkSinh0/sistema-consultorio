<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Listar todos os usuários
    $query = "SELECT id, nome, login, tipo FROM usuarios";
    $stmt = $db->query($query);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Usuários cadastrados:</h3>";
    foreach ($usuarios as $usuario) {
        echo "ID: " . $usuario['id'] . "<br>";
        echo "Nome: " . $usuario['nome'] . "<br>";
        echo "Login: " . $usuario['login'] . "<br>";
        echo "Tipo: " . $usuario['tipo'] . "<br>";
        echo "-------------------<br>";
    }
    
    // Atualizar a senha do admin
    $senha = 'Jmlopes123@!';
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    
    $query = "UPDATE usuarios SET senha = :senha WHERE login = 'Manel'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':senha', $senha_hash);
    
    if ($stmt->execute()) {
        echo "<br>Senha do usuário admin atualizada com sucesso!<br>";
        echo "Login: Manel<br>";
        echo "Senha: Jmlopes123@!<br>";
    }
    
    // Verificar se o tipo 'admin' está na coluna tipo
    $query = "SHOW COLUMNS FROM usuarios LIKE 'tipo'";
    $stmt = $db->query($query);
    $coluna = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<br>Configuração da coluna 'tipo':<br>";
    echo $coluna['Type'] . "<br>";
    
    // Atualizar o tipo para admin se necessário
    $query = "UPDATE usuarios SET tipo = 'admin' WHERE login = 'Manel'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?> 