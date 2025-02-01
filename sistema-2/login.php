<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $login = $_POST['login'];
    $senha = $_POST['senha'];
    
    $query = "SELECT * FROM usuarios WHERE login = :login";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":login", $login);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo'];
            header("Location: dashboard.php");
            exit();
        }
    }
    
    $erro = "Login ou senha inválidos";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Consultório Oftalmológico</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Consultório Dr. José Manoel Lopes</h1>
            <p>Oftalmologista</p>
        </div>
        
        <?php if (isset($erro)): ?>
            <div class="erro"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Login:</label>
                <input type="text" name="login" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Senha:</label>
                <input type="password" name="senha" required>
            </div>
            
            <button type="submit" class="btn-login">Entrar</button>
        </form>
    </div>
</body>
</html> 