<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'medico') {
    header("Location: login.php");
    exit();
}

$paciente_id = isset($_GET['id']) ? $_GET['id'] : null;
$prontuario_id = isset($_GET['prontuario_id']) ? $_GET['prontuario_id'] : null;
$visualizar = isset($_GET['visualizar']) ? $_GET['visualizar'] : false;

if (!$paciente_id || !$visualizar) {
    header("Location: prontuario.php?id=" . $paciente_id);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Buscar dados do paciente
$query = "SELECT * FROM pacientes WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $paciente_id);
$stmt->execute();
$paciente = $stmt->fetch(PDO::FETCH_ASSOC);

// Para debug - remover depois
error_log("Paciente ID: " . $paciente_id);
error_log("Prontuário ID: " . $prontuario_id);

// Buscar prontuário específico ou o mais recente
if ($prontuario_id) {
    $query = "SELECT p.*, u.nome as medico_nome 
              FROM prontuarios p 
              JOIN usuarios u ON p.medico_id = u.id 
              WHERE p.id = :prontuario_id 
              AND p.paciente_id = :paciente_id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':prontuario_id', $prontuario_id);
    $stmt->bindValue(':paciente_id', $paciente_id);
} else {
    $query = "SELECT p.*, u.nome as medico_nome 
              FROM prontuarios p 
              JOIN usuarios u ON p.medico_id = u.id 
              WHERE p.paciente_id = :paciente_id 
              ORDER BY p.data_consulta DESC 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':paciente_id', $paciente_id);
}

$stmt->execute();
$prontuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Para debug - remover depois
error_log("Query: " . $query);
error_log("Prontuário encontrado: " . ($prontuario ? 'Sim' : 'Não'));

// Se não houver prontuário, redirecionar com mensagem
if (!$prontuario) {
    header("Location: prontuario.php?id=" . $paciente_id . "&erro=" . urlencode("Não há registros de consulta para este paciente."));
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Impressão de Prontuário</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="print-page">
    <div class="print-actions no-print">
        <button onclick="window.print();" class="btn btn-primary">
            <i class="fas fa-print"></i> Imprimir
        </button>
    </div>

    <div class="print-container">
        <!-- Cabeçalho com dados do médico -->
        <div class="prontuario-header">
            <h2>Dr. José Manoel Lopes</h2>
            <p>Médico Oftalmologista</p>
            <p>CRM-MG 12965</p>
        </div>

        <!-- Dados do Paciente -->
        <div class="print-section">
            <h3>Dados do Paciente</h3>
            <table class="print-table">
                <tr>
                    <td><strong>Nome:</strong> <?php echo htmlspecialchars($paciente['nome']); ?></td>
                    <td><strong>Data Nasc.:</strong> <?php echo date('d/m/Y', strtotime($paciente['data_nascimento'])); ?></td>
                </tr>
                <tr>
                    <td><strong>CPF:</strong> <?php echo htmlspecialchars($paciente['cpf']); ?></td>
                    <td><strong>Tipo:</strong> <?php echo ucfirst($paciente['tipo_consulta']); ?></td>
                </tr>
            </table>
        </div>

        <!-- Dados da Consulta -->
        <div class="print-section">
            <h3>Registro da Consulta - <?php echo date('d/m/Y', strtotime($prontuario['data_consulta'])); ?></h3>
            
            <!-- Acuidade Visual e Tonometria -->
            <table class="print-table">
                <tr>
                    <th colspan="2">Acuidade Visual</th>
                    <th colspan="2">Tonometria</th>
                </tr>
                <tr>
                    <td><strong>OD:</strong> <?php echo $prontuario['acuidade_od'] ?: '___________'; ?></td>
                    <td><strong>OE:</strong> <?php echo $prontuario['acuidade_oe'] ?: '___________'; ?></td>
                    <td><strong>OD:</strong> <?php echo $prontuario['tonometria_od'] ? $prontuario['tonometria_od'] . ' mmHg' : '___________'; ?></td>
                    <td><strong>OE:</strong> <?php echo $prontuario['tonometria_oe'] ? $prontuario['tonometria_oe'] . ' mmHg' : '___________'; ?></td>
                </tr>
            </table>

            <!-- Biomicroscopia -->
            <table class="print-table mt-20">
                <tr>
                    <th>Biomicroscopia</th>
                </tr>
                <tr>
                    <td><?php echo nl2br(htmlspecialchars($prontuario['biomicroscopia'] ?: '')); ?></td>
                </tr>
            </table>

            <!-- Fundoscopia -->
            <table class="print-table mt-20">
                <tr>
                    <th>Fundoscopia</th>
                </tr>
                <tr>
                    <td><?php echo nl2br(htmlspecialchars($prontuario['fundoscopia'] ?: '')); ?></td>
                </tr>
            </table>

            <!-- Conduta -->
            <table class="print-table mt-20">
                <tr>
                    <th>Conduta</th>
                </tr>
                <tr>
                    <td><?php echo nl2br(htmlspecialchars($prontuario['conduta'] ?: '')); ?></td>
                </tr>
            </table>

            <!-- Observações -->
            <?php if (!empty($prontuario['observacoes'])): ?>
            <table class="print-table mt-20">
                <tr>
                    <th>Observações</th>
                </tr>
                <tr>
                    <td><?php echo nl2br(htmlspecialchars($prontuario['observacoes'])); ?></td>
                </tr>
            </table>
            <?php endif; ?>
        </div>

        <!-- Assinatura -->
        <div class="assinatura">
            <p>_____________________________________</p>
            <p>Dr. José Manoel Lopes</p>
            <p>CRM-MG 12965</p>
        </div>
    </div>
</body>
</html> 