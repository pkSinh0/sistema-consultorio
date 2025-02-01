<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: pacientes.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Buscar dados do paciente
$query = "SELECT p.*, 
          COUNT(DISTINCT a.id) as total_agendamentos,
          COUNT(DISTINCT pr.id) as total_prontuarios
          FROM pacientes p 
          LEFT JOIN agendamentos a ON p.id = a.paciente_id
          LEFT JOIN prontuarios pr ON p.id = pr.paciente_id
          WHERE p.id = :id
          GROUP BY p.id";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_GET['id']);
$stmt->execute();
$paciente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$paciente) {
    header("Location: pacientes.php");
    exit();
}

// Buscar últimos agendamentos
$query = "SELECT a.* 
          FROM agendamentos a
          WHERE a.paciente_id = :paciente_id 
          ORDER BY a.data_consulta DESC, a.horario DESC 
          LIMIT 5";

$stmt = $db->prepare($query);
$stmt->bindParam(':paciente_id', $_GET['id']);
$stmt->execute();
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$is_medico = isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'medico';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Visualizar Paciente - Consultório Oftalmológico</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-user"></i> Visualizar Paciente</h2>
                <div class="actions">
                    <a href="editar_paciente.php?id=<?php echo $paciente['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar Paciente
                    </a>
                    <?php if ($_SESSION['usuario_tipo'] === 'medico' || $_SESSION['usuario_tipo'] === 'admin'): ?>
                    <a href="prontuario.php?id=<?php echo $paciente['id']; ?>" class="btn btn-success">
                        <i class="fas fa-file-medical"></i> Prontuário
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="patient-details">
                <div class="patient-header">
                    <div class="paciente-foto">
                        <?php if($paciente['foto']): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($paciente['foto']); ?>" 
                                 class="paciente-foto-perfil" 
                                 alt="Foto do paciente"
                                 onclick="mostrarFotoAmpliada(this.src)">
                        <?php else: ?>
                            <img src="assets/img/default-user.png" 
                                 class="paciente-foto-perfil" 
                                 alt="Foto padrão">
                        <?php endif; ?>
                    </div>
                    <div class="patient-info">
                        <h3><?php echo htmlspecialchars($paciente['nome']); ?></h3>
                        <div class="patient-stats">
                            <span><i class="fas fa-calendar-check"></i> <?php echo $paciente['total_agendamentos']; ?> agendamentos</span>
                            <?php if ($is_medico): ?>
                            <span><i class="fas fa-file-medical"></i> <?php echo $paciente['total_prontuarios']; ?> prontuários</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="info-sections">
                    <div class="info-section">
                        <h4><i class="fas fa-info-circle"></i> Informações Pessoais</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>CPF:</label>
                                <span><?php echo htmlspecialchars($paciente['cpf']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>RG:</label>
                                <span><?php echo htmlspecialchars($paciente['rg']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Data de Nascimento:</label>
                                <span><?php echo date('d/m/Y', strtotime($paciente['data_nascimento'])); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Tipo de Consulta:</label>
                                <span><?php echo ucfirst($paciente['tipo_consulta']); ?></span>
                            </div>
                            <?php if ($paciente['tipo_consulta'] == 'plano'): ?>
                            <div class="info-item">
                                <label>Plano de Saúde:</label>
                                <span><?php echo htmlspecialchars($paciente['plano_saude']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-section">
                        <h4><i class="fas fa-map-marker-alt"></i> Endereço</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Rua:</label>
                                <span><?php echo htmlspecialchars($paciente['rua']); ?>, <?php echo htmlspecialchars($paciente['numero']); ?></span>
                            </div>
                            <?php if ($paciente['complemento']): ?>
                            <div class="info-item">
                                <label>Complemento:</label>
                                <span><?php echo htmlspecialchars($paciente['complemento']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <label>Cidade/Estado:</label>
                                <span><?php echo htmlspecialchars($paciente['cidade']); ?>/<?php echo htmlspecialchars($paciente['estado']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>CEP:</label>
                                <span><?php echo htmlspecialchars($paciente['cep']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h4><i class="fas fa-phone"></i> Contato</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Telefone:</label>
                                <span><?php echo htmlspecialchars($paciente['telefone']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Email:</label>
                                <span><?php echo htmlspecialchars($paciente['email']); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($agendamentos): ?>
                    <div class="info-section">
                        <h4><i class="fas fa-calendar-alt"></i> Últimas Consultas</h4>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Horário</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agendamentos as $agendamento): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($agendamento['data_consulta'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($agendamento['horario'])); ?></td>
                                    <td><?php echo ucfirst($agendamento['tipo_atendimento']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $agendamento['status']; ?>">
                                            <?php echo ucfirst($agendamento['status']); ?>
                                        </span>
                                    </td>
                                    <td>R$ <?php echo number_format($agendamento['valor'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Adicionar modal para visualização ampliada -->
    <div id="modalFoto" class="modal-foto" onclick="this.style.display='none'">
        <img id="fotoAmpliada" src="" alt="Foto ampliada">
    </div>

    <script>
    function mostrarFotoAmpliada(src) {
        document.getElementById('fotoAmpliada').src = src;
        document.getElementById('modalFoto').style.display = 'block';
    }
    </script>
</body>
</html> 