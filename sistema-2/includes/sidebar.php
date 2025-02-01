<?php
// Verificar se $db não está definido e criar conexão se necessário
if (!isset($db)) {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
}

if (!isset($usuario)) {
    $query = "SELECT nome, tipo FROM usuarios WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_SESSION['usuario_id']);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Verificar o tipo de usuário
$is_medico = isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'medico';
?>

<div class="sidebar">
    <div class="sidebar-header">
        <h3>Dr. José Manoel Lopes</h3>
        <p>CRM-MG 12965</p>
    </div>
    <nav class="sidebar-menu">
        <ul>
            <li>
                <a href="dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="agenda.php" <?php echo basename($_SERVER['PHP_SELF']) == 'agenda.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-calendar-alt"></i> Agenda
                </a>
            </li>
            <li>
                <a href="cadastro_paciente.php" <?php echo basename($_SERVER['PHP_SELF']) == 'cadastro_paciente.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-user-plus"></i> Novo Paciente
                </a>
            </li>
            <li>
                <a href="pacientes.php" <?php echo basename($_SERVER['PHP_SELF']) == 'pacientes.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-users"></i> Lista de Pacientes
                </a>
            </li>
            <?php if ($is_medico): ?>
            <li>
                <a href="pacientes.php">
                    <i class="fas fa-file-medical"></i> Prontuários
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-logo">
        <img src="assets/logo.png" alt="Logo Clínica" style="max-width: 300px !important;" class="sidebar-logo-img">
    </div>
    
    <div class="sidebar-footer">
        <p>Usuário: <?php echo $usuario['nome']; ?></p>
        <p><?php echo ucfirst($usuario['tipo']); ?></p>
    </div>
</div> 