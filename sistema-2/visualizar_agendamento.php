<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: agenda.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Buscar dados do agendamento
$query = "SELECT a.*, p.nome as paciente_nome, p.cpf, p.telefone, p.tipo_consulta, p.plano_saude 
          FROM agendamentos a 
          JOIN pacientes p ON a.paciente_id = p.id 
          WHERE a.id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_GET['id']);
$stmt->execute();
$agendamento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agendamento) {
    header("Location: agenda.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Visualizar Agendamento - Consultório Oftalmológico</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-calendar-check"></i> Detalhes do Agendamento</h2>
                <div class="header-actions">
                    <a href="editar_agendamento.php?id=<?php echo $agendamento['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar Agendamento
                    </a>
                    <a href="agenda.php?data=<?php echo $agendamento['data_consulta']; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar para Agenda
                    </a>
                </div>
            </div>

            <div class="agendamento-details">
                <!-- Status do Agendamento -->
                <div class="status-section">
                    <span class="status-badge status-<?php echo $agendamento['status']; ?>">
                        <?php echo ucfirst($agendamento['status']); ?>
                    </span>
                </div>

                <!-- Informações do Agendamento -->
                <div class="info-card">
                    <h3><i class="fas fa-clock"></i> Horário da Consulta</h3>
                    <div class="info-content">
                        <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($agendamento['data_consulta'])); ?></p>
                        <p><strong>Hora:</strong> <?php echo date('H:i', strtotime($agendamento['horario'])); ?></p>
                        <p><strong>Tipo:</strong> <?php echo ucfirst($agendamento['tipo_atendimento']); ?></p>
                        <p><strong>Valor:</strong> R$ <?php echo number_format($agendamento['valor'], 2, ',', '.'); ?></p>
                    </div>
                </div>

                <!-- Informações do Paciente -->
                <div class="info-card">
                    <h3><i class="fas fa-user"></i> Dados do Paciente</h3>
                    <div class="info-content">
                        <p><strong>Nome:</strong> <?php echo htmlspecialchars($agendamento['paciente_nome']); ?></p>
                        <p><strong>CPF:</strong> <?php echo htmlspecialchars($agendamento['cpf']); ?></p>
                        <p><strong>Telefone:</strong> <?php echo htmlspecialchars($agendamento['telefone']); ?></p>
                        <p><strong>Tipo de Consulta:</strong> <?php echo ucfirst($agendamento['tipo_consulta']); ?></p>
                        <?php if ($agendamento['tipo_consulta'] == 'plano'): ?>
                            <p><strong>Plano de Saúde:</strong> <?php echo htmlspecialchars($agendamento['plano_saude']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ações -->
                <div class="actions-card">
                    <h3><i class="fas fa-cog"></i> Ações</h3>
                    <div class="actions-content">
                        <!-- Atualizar Status -->
                        <div class="status-update">
                            <label>Atualizar Status:</label>
                            <select id="status" class="form-control" onchange="atualizarStatus(this.value)">
                                <option value="agendado" <?php echo $agendamento['status'] == 'agendado' ? 'selected' : ''; ?>>Agendado</option>
                                <option value="atendido" <?php echo $agendamento['status'] == 'atendido' ? 'selected' : ''; ?>>Atendido</option>
                                <option value="faltou" <?php echo $agendamento['status'] == 'faltou' ? 'selected' : ''; ?>>Faltou</option>
                                <option value="cancelado" <?php echo $agendamento['status'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>

                        <div class="action-buttons">
                            <?php if ($_SESSION['usuario_tipo'] === 'medico'): ?>
                            <a href="prontuario.php?id=<?php echo $agendamento['paciente_id']; ?>" class="btn btn-info">
                                <i class="fas fa-file-medical"></i> Acessar Prontuário
                            </a>
                            <?php endif; ?>
                            <button onclick="confirmarCancelamento()" class="btn btn-danger">
                                <i class="fas fa-times"></i> Cancelar Agendamento
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function atualizarStatus(status) {
        if (confirm('Confirma a alteração do status para ' + status + '?')) {
            fetch('atualizar_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=<?php echo $agendamento['id']; ?>&status=' + status
            })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    location.reload();
                } else {
                    alert('Erro ao atualizar status: ' + data.erro);
                }
            });
        }
    }

    function confirmarCancelamento() {
        if (confirm('Tem certeza que deseja cancelar este agendamento?')) {
            fetch('cancelar_consulta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=<?php echo $agendamento['id']; ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    window.location.href = 'agenda.php?data=<?php echo $agendamento['data_consulta']; ?>&mensagem=Agendamento cancelado com sucesso';
                } else {
                    alert('Erro ao cancelar agendamento: ' + data.erro);
                }
            });
        }
    }
    </script>
</body>
</html> 