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

if (!$prontuario) {
    header("Location: prontuario.php?id=" . $paciente_id . "&erro=" . urlencode("Não há registros de consulta para este paciente."));
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Receita de Óculos</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="print-page">
    <!-- Botão de Impressão -->
    <div class="no-print print-actions">
        <button onclick="window.print();" class="btn btn-primary">
            <i class="fas fa-print"></i> Imprimir
        </button>
    </div>

    <div class="print-container">
        <!-- Cabeçalho com dados do médico -->
        <div class="receita-header">
            <h2>Dr. José Manoel Lopes</h2>
            <p>Médico Oftalmologista</p>
            <p>CRM-MG 12965</p>
        </div>

        <div class="print-section">
            <h3>Receita de Óculos</h3>
            
            <div class="paciente-info">
                <p><strong>Paciente:</strong> <?php echo htmlspecialchars($paciente['nome']); ?></p>
                <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($prontuario['data_consulta'])); ?></p>
            </div>

            <table class="print-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Esférico</th>
                        <th>Cilíndrico</th>
                        <th>Eixo</th>
                        <th>DNP</th>
                        <th>Altura</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>OD</strong></td>
                        <td><?php echo isset($prontuario['od_esferico']) ? $prontuario['od_esferico'] : '_______'; ?></td>
                        <td><?php echo isset($prontuario['od_cilindrico']) ? $prontuario['od_cilindrico'] : '_______'; ?></td>
                        <td><?php echo isset($prontuario['od_eixo']) ? $prontuario['od_eixo'] : '_______'; ?></td>
                        <td><?php echo isset($prontuario['od_dnp']) ? $prontuario['od_dnp'] : '_______'; ?></td>
                        <td><?php echo isset($prontuario['od_altura']) ? $prontuario['od_altura'] : '_______'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>OE</strong></td>
                        <td><?php echo isset($prontuario['oe_esferico']) ? $prontuario['oe_esferico'] : '_______'; ?></td>
                        <td><?php echo isset($prontuario['oe_cilindrico']) ? $prontuario['oe_cilindrico'] : '_______'; ?></td>
                        <td><?php echo isset($prontuario['oe_eixo']) ? $prontuario['oe_eixo'] : '_______'; ?></td>
                        <td><?php echo isset($prontuario['oe_dnp']) ? $prontuario['oe_dnp'] : '_______'; ?></td>
                        <td><?php echo isset($prontuario['oe_altura']) ? $prontuario['oe_altura'] : '_______'; ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="receita-adicional">
                <div class="adicao">
                    <p><strong>Adição:</strong> <?php echo isset($prontuario['adicao']) ? $prontuario['adicao'] : '_______'; ?></p>
                </div>
                
                <?php if (!empty($prontuario['obs_receita'])): ?>
                <div class="observacoes">
                    <p><strong>Observações:</strong></p>
                    <p><?php echo nl2br(htmlspecialchars($prontuario['obs_receita'])); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <div class="receita-footer">
                <div class="validade">
                    <p>Válido por 90 dias</p>
                </div>
                
                <div class="assinatura">
                    <p>_____________________________________</p>
                    <p>Dr. José Manoel Lopes</p>
                    <p>CRM-MG 12965</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 