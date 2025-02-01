<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Dados dos usuários
$usuarios = [
    [
        'nome' => 'José Manoel Lopes',
        'login' => 'JoseML',
        'senha' => 'JML511512',
        'tipo' => 'medico'
    ],
    [
        'nome' => 'Rosa',
        'login' => 'Rosa',
        'senha' => 'JML511512',
        'tipo' => 'secretaria'
    ]
];

// Inserir usuários
foreach ($usuarios as $usuario) {
    $senha_hash = password_hash($usuario['senha'], PASSWORD_DEFAULT);
    
    $query = "INSERT INTO usuarios (nome, login, senha, tipo) 
              VALUES (:nome, :login, :senha, :tipo)";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nome', $usuario['nome']);
        $stmt->bindParam(':login', $usuario['login']);
        $stmt->bindParam(':senha', $senha_hash);
        $stmt->bindParam(':tipo', $usuario['tipo']);
        
        if ($stmt->execute()) {
            echo "Usuário {$usuario['nome']} inserido com sucesso!<br>";
        }
    } catch (PDOException $e) {
        echo "Erro ao inserir usuário {$usuario['nome']}: " . $e->getMessage() . "<br>";
    }
}
?> 