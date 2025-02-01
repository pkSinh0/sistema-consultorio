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
$query = "SELECT a.*, p.nome as paciente_nome, p.tipo_consulta 
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

// Buscar todos os pacientes para o select
$query = "SELECT id, nome, tipo_consulta, data_nascimento 
          FROM pacientes 
          ORDER BY nome ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Horários disponíveis para agendamento (com formato correto)
$horarios = [
    '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
    '13:00', '13:30', // Novos horários
    '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'
];

// Formatar o horário atual do agendamento para garantir o formato correto
$horario_atual = date('H:i', strtotime($agendamento['horario']));

// Buscar horários ocupados na mesma data (exceto o horário atual)
$query = "SELECT TIME_FORMAT(horario, '%H:%i') as horario 
          FROM agendamentos 
          WHERE data_consulta = :data 
          AND id != :agendamento_id 
          AND status != 'cancelado'";
$stmt = $db->prepare($query);
$stmt->bindParam(':data', $agendamento['data_consulta']);
$stmt->bindParam(':agendamento_id', $agendamento['id']);
$stmt->execute();
$horarios_ocupados = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Remover horários ocupados da lista de horários disponíveis
$horarios_disponiveis = array_values(array_diff($horarios, $horarios_ocupados));

// Garantir que o horário atual esteja na lista
if (!in_array($horario_atual, $horarios_disponiveis)) {
    $horarios_disponiveis[] = $horario_atual;
    sort($horarios_disponiveis); // Ordenar horários
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Verificar se o novo horário já não está ocupado
        $stmt = $db->prepare("SELECT id FROM agendamentos WHERE data_consulta = ? AND horario = ? AND id != ?");
        $stmt->execute([$_POST['data_consulta'], $_POST['horario'], $_GET['id']]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("Este horário já está ocupado. Por favor, escolha outro horário.");
        }

        // Calcular o valor baseado no tipo de atendimento
        $valor = 0;
        if ($_POST['tipo_atendimento'] === 'consulta') {
            // Buscar tipo de consulta do paciente
            $stmt = $db->prepare("SELECT tipo_consulta FROM pacientes WHERE id = ?");
            $stmt->execute([$_POST['paciente_id']]);
            $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $valor = ($paciente['tipo_consulta'] === 'particular') ? 350.00 : 250.00;
        } elseif ($_POST['tipo_atendimento'] === 'mapeamento') {
            $valor = 150.00;
        }

        // Atualizar o agendamento
        $query = "UPDATE agendamentos SET 
            paciente_id = :paciente_id,
            data_consulta = :data_consulta,
            horario = :horario,
            tipo_atendimento = :tipo_atendimento,
            valor = :valor,
            status = :status
            WHERE id = :id";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':paciente_id', $_POST['paciente_id']);
        $stmt->bindParam(':data_consulta', $_POST['data_consulta']);
        $stmt->bindParam(':horario', $_POST['horario']);
        $stmt->bindParam(':tipo_atendimento', $_POST['tipo_atendimento']);
        $stmt->bindParam(':valor', $valor);
        $stmt->bindParam(':status', $_POST['status']);
        $stmt->bindParam(':id', $_GET['id']);

        if ($stmt->execute()) {
            header("Location: agenda.php?data=" . $_POST['data_consulta'] . "&mensagem=Agendamento atualizado com sucesso!");
            exit();
        }
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Agendamento - Consultório Oftalmológico</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="layout-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-edit"></i> Editar Agendamento</h2>
            </div>

            <?php if (isset($erro)): ?>
                <div class="alert alert-danger">
                    <?php echo $erro; ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" class="agendamento-form">
                    <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                    
                    <div class="form-section">
                        <h3><i class="fas fa-clock"></i> Data e Hora</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Data da Consulta:</label>
                                <input type="date" name="data_consulta" 
                                       value="<?php echo $agendamento['data_consulta']; ?>" 
                                       class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Horário:</label>
                                <select name="horario" class="form-control" required>
                                    <?php foreach ($horarios_disponiveis as $horario): ?>
                                        <option value="<?php echo $horario; ?>" 
                                                <?php echo $horario === $horario_atual ? 'selected' : ''; ?>>
                                            <?php echo $horario; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Paciente</h3>
                        <div class="form-group">
                            <select name="paciente_id" class="form-control" required>
                                <?php foreach ($pacientes as $paciente): ?>
                                    <option value="<?php echo $paciente['id']; ?>" 
                                            <?php echo $agendamento['paciente_id'] == $paciente['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($paciente['nome']); ?> - 
                                        <?php echo date('d/m/Y', strtotime($paciente['data_nascimento'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-stethoscope"></i> Tipo de Atendimento</h3>
                        <div class="form-group">
                            <select name="tipo_atendimento" class="form-control" required>
                                <option value="consulta" <?php echo $agendamento['tipo_atendimento'] == 'consulta' ? 'selected' : ''; ?>>
                                    Consulta
                                </option>
                                <option value="retorno" <?php echo $agendamento['tipo_atendimento'] == 'retorno' ? 'selected' : ''; ?>>
                                    Retorno
                                </option>
                                <option value="mapeamento" <?php echo $agendamento['tipo_atendimento'] == 'mapeamento' ? 'selected' : ''; ?>>
                                    Mapeamento de Retina (MAP)
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-check-circle"></i> Status</h3>
                        <div class="form-group">
                            <select name="status" class="form-control" required>
                                <option value="agendado" <?php echo $agendamento['status'] == 'agendado' ? 'selected' : ''; ?>>
                                    Agendado
                                </option>
                                <option value="atendido" <?php echo $agendamento['status'] == 'atendido' ? 'selected' : ''; ?>>
                                    Atendido
                                </option>
                                <option value="faltou" <?php echo $agendamento['status'] == 'faltou' ? 'selected' : ''; ?>>
                                    Faltou
                                </option>
                                <option value="cancelado" <?php echo $agendamento['status'] == 'cancelado' ? 'selected' : ''; ?>>
                                    Cancelado
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                        <a href="agenda.php?data=<?php echo $agendamento['data_consulta']; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Atualizar valor quando mudar o tipo de atendimento
            $('select[name="tipo_atendimento"]').change(function() {
                // Aqui você pode adicionar lógica adicional se necessário
            });
        });
    </script>
</body>
</html> 