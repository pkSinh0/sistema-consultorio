<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Verificar tipo de usuário
$is_medico = isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'medico';

// Buscar estatísticas
$hoje = date('Y-m-d');

// Total de agendamentos para hoje
$query = "SELECT COUNT(*) as total FROM agendamentos WHERE data_consulta = :hoje";
$stmt = $db->prepare($query);
$stmt->bindParam(':hoje', $hoje);
$stmt->execute();
$agendamentos_hoje = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total de pacientes
$query = "SELECT COUNT(*) as total FROM pacientes";
$stmt = $db->prepare($query);
$stmt->execute();
$total_pacientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Próximos agendamentos
$query = "SELECT a.*, p.nome as paciente_nome 
          FROM agendamentos a 
          JOIN pacientes p ON a.paciente_id = p.id 
          WHERE a.data_consulta >= :hoje 
          ORDER BY a.data_consulta, a.horario 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':hoje', $hoje);
$stmt->execute();
$proximos_agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Consultório Oftalmológico</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <div class="page-header">
                <h2>Dashboard</h2>
            </div>

            <!-- Estatísticas -->
            <div class="stats-container">
                <div class="stat-card">
                    <i class="fas fa-calendar-day"></i>
                    <div class="stat-info">
                        <h3>Consultas Hoje</h3>
                        <p><?php echo $agendamentos_hoje; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="stat-info">
                        <h3>Total de Pacientes</h3>
                        <p><?php echo $total_pacientes; ?></p>
                    </div>
                </div>
            </div>

            <!-- Container Flexível para Ações Rápidas e Próximos Agendamentos -->
            <div class="dashboard-flex-container">
                <!-- Ações Rápidas -->
                <div class="dashboard-column">
                    <div class="dashboard-card">
                        <h3><i class="fas fa-bolt"></i> Ações Rápidas</h3>
                        <div class="actions-grid">
                            <a href="agenda.php" class="action-card">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Agenda</span>
                            </a>
                            <a href="cadastro_paciente.php" class="action-card">
                                <i class="fas fa-user-plus"></i>
                                <span>Novo Paciente</span>
                            </a>
                            <a href="pacientes.php" class="action-card">
                                <i class="fas fa-users"></i>
                                <span>Lista de Pacientes</span>
                            </a>
                            <?php if ($_SESSION['usuario_tipo'] === 'medico'): ?>
                            <a href="pacientes.php" class="action-card">
                                <i class="fas fa-file-medical"></i>
                                <span>Prontuários</span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Próximos Agendamentos -->
                <div class="dashboard-column">
                    <div class="dashboard-card">
                        <h3>Próximos Agendamentos</h3>
                        <div class="table-container">
                            <?php if (!empty($proximos_agendamentos)): ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Horário</th>
                                            <th>Paciente</th>
                                            <th>Tipo</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($proximos_agendamentos as $agendamento): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($agendamento['data_consulta'])); ?></td>
                                            <td><?php echo date('H:i', strtotime($agendamento['horario'])); ?></td>
                                            <td><?php echo htmlspecialchars($agendamento['paciente_nome']); ?></td>
                                            <td><?php echo ucfirst($agendamento['tipo_atendimento']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $agendamento['status']; ?>">
                                                    <?php echo ucfirst($agendamento['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    Não há agendamentos para hoje.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 