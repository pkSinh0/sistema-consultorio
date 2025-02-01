<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Preparar a query de inserção
        $query = "INSERT INTO agendamentos (
            paciente_id, data_consulta, horario, tipo_atendimento, valor, status
        ) VALUES (
            :paciente_id, :data_consulta, :horario, :tipo_atendimento, :valor, 'agendado'
        )";

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

        // Inserir o agendamento
        $stmt = $db->prepare($query);
        $stmt->bindParam(':paciente_id', $_POST['paciente_id']);
        $stmt->bindParam(':data_consulta', $_POST['data_consulta']);
        $stmt->bindParam(':horario', $_POST['horario']);
        $stmt->bindParam(':tipo_atendimento', $_POST['tipo_atendimento']);
        $stmt->bindParam(':valor', $valor);

        if ($stmt->execute()) {
            $mensagem = "Agendamento realizado com sucesso!";
            // Redirecionar para a agenda após o agendamento
            header("Location: agenda.php?mensagem=" . urlencode($mensagem));
            exit();
        }
    } catch (PDOException $e) {
        $erro = "Erro ao realizar agendamento: " . $e->getMessage();
    }
}

// Receber data e hora da URL
$data_selecionada = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');
$horario_selecionado = isset($_GET['horario']) ? $_GET['horario'] : '';

// Buscar todos os pacientes para a lista
$query = "SELECT id, nome, cpf, data_nascimento, tipo_consulta, plano_saude 
          FROM pacientes 
          ORDER BY nome ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Valores das consultas
$VALOR_CONSULTA_PARTICULAR = 350.00;
$VALOR_CONSULTA_PLANO = 250.00;
$VALOR_MAPEAMENTO = 150.00;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Novo Agendamento - Consultório Oftalmológico</title>
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
                <h2><i class="fas fa-calendar-plus"></i> Novo Agendamento</h2>
            </div>

            <?php if (isset($erro)): ?>
                <div class="alert alert-danger">
                    <?php echo $erro; ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="processar_agendamento.php" id="form-agendamento" class="agendamento-form">
                    <!-- Data e Hora Selecionadas -->
                    <div class="form-section highlighted-section">
                        <h3><i class="fas fa-clock"></i> Horário Selecionado</h3>
                        <div class="horario-selecionado">
                            <p>
                                <strong>Data:</strong> 
                                <?php echo date('d/m/Y', strtotime($data_selecionada)); ?>
                            </p>
                            <p>
                                <strong>Horário:</strong> 
                                <?php echo date('H:i', strtotime($horario_selecionado)); ?>
                            </p>
                        </div>
                        <input type="hidden" name="data_consulta" value="<?php echo $data_selecionada; ?>">
                        <input type="hidden" name="horario" value="<?php echo $horario_selecionado; ?>">
                    </div>

                    <!-- Busca de Paciente -->
                    <div class="form-section">
                        <h3><i class="fas fa-search"></i> Buscar Paciente</h3>
                        <div class="form-group">
                            <input type="text" id="busca_paciente" 
                                   class="form-control" 
                                   placeholder="Digite o nome ou CPF do paciente">
                            <div id="resultados_busca" class="resultados-busca"></div>
                        </div>

                        <!-- Lista de Pacientes -->
                        <div class="form-group">
                            <label><i class="fas fa-list"></i> Ou selecione da lista:</label>
                            <select name="paciente_id" id="paciente_id" class="form-control" required>
                                <option value="">Selecione o paciente</option>
                                <?php foreach ($pacientes as $paciente): ?>
                                    <option value="<?php echo $paciente['id']; ?>" 
                                            data-tipo="<?php echo $paciente['tipo_consulta']; ?>"
                                            data-plano="<?php echo $paciente['plano_saude']; ?>">
                                        <?php echo htmlspecialchars($paciente['nome']); ?> - 
                                        CPF: <?php echo htmlspecialchars($paciente['cpf']); ?> - 
                                        Nasc.: <?php echo date('d/m/Y', strtotime($paciente['data_nascimento'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Tipo de Atendimento -->
                    <div class="form-section">
                        <h3><i class="fas fa-stethoscope"></i> Tipo de Atendimento</h3>
                        <div class="form-group">
                            <select name="tipo_atendimento" id="tipo_atendimento" class="form-control" required>
                                <option value="">Selecione o tipo de atendimento</option>
                                <option value="consulta">Consulta</option>
                                <option value="retorno">Retorno</option>
                                <option value="mapeamento">Mapeamento de Retina (MAP)</option>
                            </select>
                        </div>

                        <div id="valor_container" class="form-group" style="display: none;">
                            <label>Valor do Atendimento:</label>
                            <div class="valor-display">
                                R$ <span id="valor_atendimento">0,00</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-calendar-check"></i> Agendar Consulta
                        </button>
                        <a href="cadastro_paciente.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Cadastrar Novo Paciente
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Busca de pacientes em tempo real
            $('#busca_paciente').on('keyup', function() {
                var termo = $(this).val();
                if (termo.length >= 3) {
                    $.ajax({
                        url: 'buscar_pacientes.php',
                        method: 'POST',
                        data: { termo: termo },
                        success: function(response) {
                            $('#resultados_busca').html(response).show();
                        }
                    });
                } else {
                    $('#resultados_busca').hide();
                }
            });

            // Seleção de paciente da busca
            $(document).on('click', '.resultado-item', function() {
                var pacienteId = $(this).data('id');
                $('#paciente_id').val(pacienteId).trigger('change');
                $('#busca_paciente').val($(this).text());
                $('#resultados_busca').hide();
            });

            // Cálculo do valor
            function calcularValor() {
                var tipoAtendimento = $('#tipo_atendimento').val();
                var pacienteOption = $('#paciente_id option:selected');
                var tipoConsulta = pacienteOption.data('tipo');
                var valor = 0;

                if (tipoAtendimento === 'consulta') {
                    if (tipoConsulta === 'particular') {
                        valor = <?php echo $VALOR_CONSULTA_PARTICULAR; ?>;
                    } else {
                        valor = <?php echo $VALOR_CONSULTA_PLANO; ?>;
                    }
                } else if (tipoAtendimento === 'mapeamento') {
                    valor = <?php echo $VALOR_MAPEAMENTO; ?>;
                }

                $('#valor_atendimento').text(valor.toFixed(2));
                $('#valor_container').show();
            }

            $('#tipo_atendimento, #paciente_id').change(calcularValor);
        });
    </script>
</body>
</html> 