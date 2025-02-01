<?php
session_start();
require_once 'config/database.php';

// Adicione isso logo após o session_start()
setlocale(LC_TIME, 'pt_BR.UTF-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Pegar a data selecionada ou usar a data atual
$data_selecionada = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');

// Horários disponíveis para agendamento
$horarios = [
    '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
    '13:00', '13:30',
    '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'
];

// Buscar agendamentos do dia selecionado
$query = "SELECT a.*, p.nome as paciente_nome, p.tipo_consulta 
          FROM agendamentos a 
          JOIN pacientes p ON a.paciente_id = p.id 
          WHERE DATE(a.data_consulta) = :data 
          ORDER BY a.horario";

$stmt = $db->prepare($query);
$stmt->bindParam(':data', $data_selecionada);
$stmt->execute();

// Criar array associativo dos horários ocupados
$horarios_ocupados = [];
while ($agendamento = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $hora = date('H:i', strtotime($agendamento['horario']));
    $horarios_ocupados[$hora] = $agendamento;
}

// Verificar se é fim de semana ou dia sem atendimento
$dia_semana = date('w', strtotime($data_selecionada));
$is_fim_de_semana = ($dia_semana == 0 || $dia_semana == 6); // 0 = domingo, 6 = sábado

// Verificar se é um dia marcado como sem atendimento
$query = "SELECT * FROM dias_sem_atendimento WHERE data = :data";
$stmt = $db->prepare($query);
$stmt->bindParam(':data', $data_selecionada);
$stmt->execute();
$dia_sem_atendimento = $stmt->fetch(PDO::FETCH_ASSOC);

$sem_atendimento = $is_fim_de_semana || $dia_sem_atendimento;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Agenda - Consultório Oftalmológico</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-calendar-alt"></i> Agenda</h2>
            </div>

            <?php if (isset($_GET['mensagem'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_GET['mensagem']); ?>
                </div>
            <?php endif; ?>

            <div class="agenda-container">
                <div class="agenda-header">
                    <div class="data-navegacao">
                        <a href="?data=<?php echo date('Y-m-d', strtotime($data_selecionada . ' -1 day')); ?>" 
                           class="btn btn-outline">
                            <i class="fas fa-chevron-left"></i> Dia Anterior
                        </a>
                        
                        <div class="data-atual">
                            <div class="month-selector">
                                <select id="month-select" class="form-control" onchange="mudarMes(this.value)">
                                    <?php
                                    // Mês atual
                                    $mes_atual = date('Y-m');
                                    
                                    for ($i = 0; $i < 12; $i++) {
                                        $data = date('Y-m-d', strtotime("+$i months"));
                                        $primeiro_dia = date('Y-m-01', strtotime($data));
                                        $mes_ano = strftime('%B de %Y', strtotime($data));
                                        
                                        // Arrays de substituição para acentuação correta
                                        $meses = array(
                                            'January' => 'Janeiro',
                                            'February' => 'Fevereiro',
                                            'March' => 'Março',
                                            'April' => 'Abril',
                                            'May' => 'Maio',
                                            'June' => 'Junho',
                                            'July' => 'Julho',
                                            'August' => 'Agosto',
                                            'September' => 'Setembro',
                                            'October' => 'Outubro',
                                            'November' => 'Novembro',
                                            'December' => 'Dezembro'
                                        );
                                        
                                        $mes = $meses[date('F', strtotime($data))];
                                        $mes_ano = $mes . ' de ' . date('Y', strtotime($data));
                                        
                                        $selected = date('Y-m', strtotime($data_selecionada)) == date('Y-m', strtotime($data)) ? 'selected' : '';
                                        echo "<option value='" . $primeiro_dia . "' $selected>" . $mes_ano . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <input type="date" id="data" name="data" value="<?php echo $data_selecionada; ?>" onchange="window.location.href='?data='+this.value">
                            <div class="data-display <?php echo $data_selecionada == date('Y-m-d') ? 'hoje' : ''; ?>" onclick="document.getElementById('data').showPicker()">
                                <?php 
                                $dia = date('d', strtotime($data_selecionada));
                                $mes = $meses[date('F', strtotime($data_selecionada))];
                                $ano = date('Y', strtotime($data_selecionada));
                                
                                $dias = array(
                                    'Sunday' => 'Domingo',
                                    'Monday' => 'Segunda-feira',
                                    'Tuesday' => 'Terça-feira',
                                    'Wednesday' => 'Quarta-feira',
                                    'Thursday' => 'Quinta-feira',
                                    'Friday' => 'Sexta-feira',
                                    'Saturday' => 'Sábado'
                                );
                                
                                $dia_semana = $dias[date('l', strtotime($data_selecionada))];
                                
                                echo "<span class='dia'>" . $dia . "</span>";
                                echo "<span class='mes-ano'>" . $mes . " de " . $ano . "</span>";
                                echo "<span class='semana'>" . $dia_semana . "</span>";
                                ?>
                            </div>
                        </div>

                        <a href="?data=<?php echo date('Y-m-d', strtotime($data_selecionada . ' +1 day')); ?>" 
                           class="btn btn-outline">
                            Próximo Dia <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>

                    <?php if (!$is_fim_de_semana): ?>
                    <div class="agenda-actions">
                        <button onclick="marcarSemAtendimento('<?php echo $data_selecionada; ?>')" class="btn btn-warning">
                            <i class="fas fa-ban"></i>
                            <span>Marcar sem Atendimento</span>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($sem_atendimento): ?>
                    <div class="sem-atendimento-card">
                        <i class="fas fa-calendar-times"></i>
                        <h3>Não haverá atendimentos neste dia</h3>
                        <?php if ($is_fim_de_semana): ?>
                            <p>Não há atendimentos aos <?php echo $dia_semana == 0 ? 'domingos' : 'sábados'; ?>.</p>
                        <?php else: ?>
                            <p>Este dia está marcado como sem atendimentos.</p>
                            <button onclick="reativarAtendimento('<?php echo $data_selecionada; ?>')" class="btn btn-primary">
                                <i class="fas fa-undo"></i> Reativar Atendimentos
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <table class="agenda-table">
                        <thead>
                            <tr>
                                <th width="10%">Horário</th>
                                <th width="15%">Status</th>
                                <th width="25%">Paciente</th>
                                <th width="20%">Tipo</th>
                                <th width="15%">Valor</th>
                                <th width="15%">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($horarios as $horario): ?>
                                <tr class="<?php echo isset($horarios_ocupados[$horario]) ? 'horario-ocupado' : 'horario-disponivel'; ?>">
                                    <td data-label="Horário"><?php echo $horario; ?></td>
                                    <?php if (isset($horarios_ocupados[$horario])): 
                                        $agendamento = $horarios_ocupados[$horario];
                                    ?>
                                        <td data-label="Status">
                                            <span class="status-badge status-<?php echo $agendamento['status']; ?>">
                                                <?php echo ucfirst($agendamento['status']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Paciente"><?php echo htmlspecialchars($agendamento['paciente_nome']); ?></td>
                                        <td data-label="Tipo"><?php echo ucfirst($agendamento['tipo_atendimento']); ?></td>
                                        <td data-label="Valor">R$ <?php echo number_format($agendamento['valor'], 2, ',', '.'); ?></td>
                                        <td class="acoes" data-label="Ações">
                                            <a href="visualizar_agendamento.php?id=<?php echo $agendamento['id']; ?>" 
                                               class="btn-icon" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="editar_agendamento.php?id=<?php echo $agendamento['id']; ?>" 
                                               class="btn-icon" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" onclick="confirmarCancelamento(<?php echo $agendamento['id']; ?>)" 
                                               class="btn-icon" title="Cancelar">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </td>
                                    <?php else: ?>
                                        <td colspan="4" class="horario-livre">Horário Livre</td>
                                        <td>
                                            <a href="#" class="btn btn-success btn-sm" 
                                               onclick="abrirModalAgendamento('<?php echo $data_selecionada; ?>', '<?php echo $horario; ?>')">
                                                <i class="fas fa-plus"></i> Agendar
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de Agendamento -->
    <div id="modalAgendamento" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Novo Agendamento</h2>
            
            <form id="formAgendamento" method="POST" action="processar_agendamento.php">
                <!-- Campos ocultos necessários -->
                <input type="hidden" id="paciente_id" name="paciente_id" required>
                <input type="hidden" id="paciente_tipo" name="paciente_tipo">
                <input type="hidden" id="data_selecionada" name="data">
                <input type="hidden" id="horario_selecionado" name="horario">
                <input type="hidden" id="agendamento_id" name="agendamento_id">
                
                <!-- Data e Hora do Agendamento -->
                <div class="agendamento-info">
                    <div class="info-item">
                        <label>Data:</label>
                        <input type="text" id="data_display" class="form-control" readonly>
                    </div>
                    <div class="info-item">
                        <label>Horário:</label>
                        <input type="text" id="horario_display" class="form-control" readonly>
                    </div>
                </div>

                <!-- Busca de Paciente -->
                <div class="form-group">
                    <label>Buscar Paciente:</label>
                    <input type="text" 
                           id="busca_paciente" 
                           class="form-control" 
                           placeholder="Digite o nome ou CPF do paciente"
                           autocomplete="off">
                    <div id="resultados_busca" class="resultados-busca"></div>
                </div>

                <!-- Paciente Selecionado -->
                <div id="paciente_selecionado" class="paciente-info" style="display: none;">
                    <strong>Paciente:</strong> <span id="nome_paciente"></span>
                </div>

                <!-- Tipo de Atendimento -->
                <div class="form-group">
                    <label>Tipo de Atendimento:</label>
                    <select name="tipo_atendimento" id="tipo_atendimento" class="form-control" required>
                        <option value="">Selecione...</option>
                        <option value="consulta">Consulta</option>
                        <option value="retorno">Retorno</option>
                        <option value="mapeamento">MAP</option>
                    </select>
                </div>

                <!-- Valor -->
                <div class="form-group">
                    <label>Valor:</label>
                    <input type="text" id="valor" name="valor" class="form-control" readonly>
                </div>

                <!-- Botões -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-success" id="btn_confirmar" disabled>
                        <i class="fas fa-check"></i> Confirmar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="abrirModalCadastroRapido()">
                        <i class="fas fa-user-plus"></i> Cadastro Rápido
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="fecharModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Cadastro Rápido -->
    <div id="modalCadastroRapido" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharModalCadastroRapido()">&times;</span>
            <h2>Cadastro Rápido de Paciente</h2>
            
            <form id="formCadastroRapido">
                <div class="form-group">
                    <label>Nome Completo:</label>
                    <input type="text" name="nome" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>CPF:</label>
                        <input type="text" name="cpf" class="form-control" required>
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label>Data de Nascimento:</label>
                        <input type="date" 
                               name="data_nascimento" 
                               class="form-control" 
                               required 
                               max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Telefone:</label>
                    <input type="text" name="telefone" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Tipo de Consulta:</label>
                    <select name="tipo_consulta" id="tipo_consulta_rapido" class="form-control" required>
                        <option value="">Selecione...</option>
                        <option value="particular">Particular</option>
                        <option value="plano">Plano de Saúde</option>
                    </select>
                </div>

                <div class="form-group" id="plano_saude_group" style="display: none;">
                    <label>Plano de Saúde:</label>
                    <select name="plano_saude" class="form-control">
                        <option value="">Selecione o plano...</option>
                        <option value="unimed">Unimed</option>
                        <option value="imas">IMAS</option>
                        <option value="plamhuv">Plamhuv</option>
                        <option value="plan_minas">Plan Minas</option>
                        <option value="primicias">Primícias</option>
                        <option value="zelo">Zelo</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>

                <div class="form-group" id="outro_plano_group" style="display: none;">
                    <label>Especifique o Plano:</label>
                    <input type="text" name="outro_plano" class="form-control" placeholder="Digite o nome do plano">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Salvar e Selecionar
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="fecharModalCadastroRapido()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function confirmarCancelamento(id) {
        if (confirm('Tem certeza que deseja cancelar este agendamento?')) {
            window.location.href = 'cancelar_agendamento.php?id=' + id;
        }
    }

    function mudarMes(data) {
        // Se o mês selecionado é o mês atual, vai para o dia atual
        if (data.substring(0, 7) === '<?php echo date('Y-m'); ?>') {
            window.location.href = '?data=<?php echo date('Y-m-d'); ?>';
        } else {
            // Caso contrário, vai para o primeiro dia do mês selecionado
            window.location.href = '?data=' + data;
        }
    }

    function marcarSemAtendimento(data) {
        if (confirm('Tem certeza que deseja marcar este dia como sem atendimento?')) {
            fetch('marcar_sem_atendimento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'data=' + data
            })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    location.reload();
                } else {
                    alert('Erro ao marcar dia sem atendimento: ' + data.erro);
                }
            });
        }
    }

    function reativarAtendimento(data) {
        if (confirm('Deseja reativar os atendimentos para este dia?')) {
            fetch('reativar_atendimento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'data=' + data
            })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    location.reload();
                } else {
                    alert('Erro ao reativar atendimentos: ' + data.erro);
                }
            });
        }
    }

    // Função para abrir o modal de agendamento
    function abrirModalAgendamento(data, horario, agendamentoId = null) {
        // Formatar a data para exibição
        const dataObj = new Date(data);
        const dataFormatada = dataObj.toLocaleDateString('pt-BR');
        
        // Preencher os campos
        document.getElementById('data_selecionada').value = data;
        document.getElementById('horario_selecionado').value = horario;
        document.getElementById('data_display').value = dataFormatada;
        document.getElementById('horario_display').value = horario;
        
        // Se for edição, guardar o ID do agendamento
        if (agendamentoId) {
            document.getElementById('agendamento_id').value = agendamentoId;
        } else {
            document.getElementById('agendamento_id').value = '';
        }
        
        // Limpar outros campos
        document.getElementById('busca_paciente').value = '';
        document.getElementById('paciente_selecionado').style.display = 'none';
        document.getElementById('tipo_atendimento').value = '';
        document.getElementById('valor').value = '';
        document.getElementById('btn_confirmar').disabled = true;
        
        // Exibir o modal
        document.getElementById('modalAgendamento').style.display = 'block';
        
        // Focar no campo de busca
        document.getElementById('busca_paciente').focus();
    }

    // Adicione esta função para buscar horários disponíveis
    function buscarHorariosDisponiveis(data, agendamentoId = null) {
        fetch(`buscar_horarios.php?data=${data}&agendamento_id=${agendamentoId || ''}`)
            .then(response => response.json())
            .then(horarios => {
                const select = document.getElementById('horarios_disponiveis');
                select.innerHTML = '<option value="">Selecione um horário</option>';
                
                horarios.forEach(horario => {
                    const option = document.createElement('option');
                    option.value = horario;
                    option.textContent = horario;
                    select.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Erro:', error);
                select.innerHTML = '<option value="">Erro ao carregar horários</option>';
            });
    }

    // Busca de pacientes
    let timeoutBusca;
    document.getElementById('busca_paciente').addEventListener('input', function() {
        clearTimeout(timeoutBusca);
        const busca = this.value.trim();
        const resultados = document.getElementById('resultados_busca');
        
        if (busca.length < 2) {
            resultados.style.display = 'none';
            return;
        }
        
        timeoutBusca = setTimeout(() => {
            fetch('buscar_pacientes.php?busca=' + encodeURIComponent(busca))
                .then(response => response.json())
                .then(pacientes => {
                    resultados.innerHTML = '';
                    
                    if (pacientes.length === 0) {
                        resultados.innerHTML = '<div class="resultado-item">Nenhum paciente encontrado</div>';
                    } else {
                        pacientes.forEach(paciente => {
                            const div = document.createElement('div');
                            div.className = 'resultado-item';
                            
                            // Formatar data de nascimento
                            let dataNascimento = '';
                            if (paciente.data_nascimento) {
                                const [ano, mes, dia] = paciente.data_nascimento.split('-');
                                dataNascimento = `${dia}/${mes}/${ano}`;
                            }
                            
                            // Montar texto do resultado com nome completo e data de nascimento
                            div.textContent = `${paciente.nome} (${dataNascimento})`;
                            
                            div.onclick = () => selecionarPaciente(paciente);
                            resultados.appendChild(div);
                        });
                    }
                    
                    resultados.style.display = 'block';
                })
                .catch(error => {
                    console.error('Erro:', error);
                    resultados.innerHTML = '<div class="resultado-item">Erro ao buscar pacientes</div>';
                    resultados.style.display = 'block';
                });
        }, 300);
    });

    function calcularIdade(dataNascimento) {
        const hoje = new Date();
        const nascimento = new Date(dataNascimento);
        let idade = hoje.getFullYear() - nascimento.getFullYear();
        const mes = hoje.getMonth() - nascimento.getMonth();
        
        if (mes < 0 || (mes === 0 && hoje.getDate() < nascimento.getDate())) {
            idade--;
        }
        
        return idade;
    }

    function selecionarPaciente(paciente) {
        document.getElementById('paciente_id').value = paciente.id;
        document.getElementById('paciente_tipo').value = paciente.tipo_consulta;
        
        // Formatar data de nascimento
        let dataNascimento = '';
        if (paciente.data_nascimento) {
            const [ano, mes, dia] = paciente.data_nascimento.split('-');
            dataNascimento = `${dia}/${mes}/${ano}`;
        }
        
        // Montar informação do paciente
        let infoDisplay = `${paciente.nome} (${dataNascimento})`;
        if (paciente.plano_saude) {
            infoDisplay += ` - ${paciente.plano_saude}`;
        }
        
        document.getElementById('nome_paciente').textContent = infoDisplay;
        document.getElementById('paciente_selecionado').style.display = 'block';
        
        // Limpar campo de busca e resultados
        document.getElementById('busca_paciente').value = '';
        document.getElementById('resultados_busca').style.display = 'none';
        
        // Habilitar botão de confirmar
        document.getElementById('btn_confirmar').disabled = false;
        
        // Se já tiver um tipo de atendimento selecionado, atualizar o valor
        if (document.getElementById('tipo_atendimento').value) {
            atualizarValor();
        }
    }

    function atualizarValor() {
        const tipo = document.getElementById('tipo_atendimento').value;
        const tipoConsulta = document.getElementById('paciente_tipo').value;
        let valor = 0;
        
        switch(tipo) {
            case 'consulta':
                valor = tipoConsulta === 'particular' ? 350.00 : 250.00;
                break;
            case 'retorno':
                valor = 0.00;
                break;
            case 'mapeamento':
                valor = 150.00;
                break;
        }
        
        document.getElementById('valor').value = valor.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    }

    // Event Listeners
    document.getElementById('tipo_atendimento').addEventListener('change', atualizarValor);

    document.getElementById('formAgendamento').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            alert('Por favor, preencha todos os campos obrigatórios');
            return;
        }
        
        const pacienteNome = document.getElementById('nome_paciente').textContent;
        const data = document.getElementById('data_display').value;
        const horario = document.getElementById('horario_display').value;
        const tipo = document.getElementById('tipo_atendimento').value;
        const valor = document.getElementById('valor').value;
        
        const mensagem = `Confirmar agendamento para ${pacienteNome}\n\n` +
                        `Data: ${data}\n` +
                        `Horário: ${horario}\n` +
                        `Tipo: ${tipo}\n` +
                        `Valor: ${valor}`;
        
        if (confirm(mensagem)) {
            this.submit();
        }
    });

    function fecharModal() {
        document.getElementById('modalAgendamento').style.display = 'none';
        document.getElementById('formAgendamento').reset();
        document.getElementById('paciente_selecionado').style.display = 'none';
        document.getElementById('resultados_busca').style.display = 'none';
    }

    // Fechar modal ao clicar no X ou fora dele
    document.querySelector('.close').onclick = fecharModal;
    window.onclick = function(event) {
        if (event.target == document.getElementById('modalAgendamento')) {
            fecharModal();
        }
    };

    function abrirModalCadastroRapido() {
        document.getElementById('modalCadastroRapido').style.display = 'block';
    }

    function fecharModalCadastroRapido() {
        document.getElementById('modalCadastroRapido').style.display = 'none';
        document.getElementById('formCadastroRapido').reset();
    }

    // Controle dos campos de plano de saúde
    document.getElementById('tipo_consulta_rapido').addEventListener('change', function() {
        const planoGroup = document.getElementById('plano_saude_group');
        const outroPlanoGroup = document.getElementById('outro_plano_group');
        const planoSelect = planoGroup.querySelector('select');
        
        if (this.value === 'plano') {
            planoGroup.style.display = 'block';
            planoSelect.required = true;
        } else {
            planoGroup.style.display = 'none';
            outroPlanoGroup.style.display = 'none';
            planoSelect.required = false;
            planoSelect.value = '';
            if (outroPlanoGroup.querySelector('input')) {
                outroPlanoGroup.querySelector('input').value = '';
            }
        }
    });

    // Controle do campo outro plano
    document.querySelector('select[name="plano_saude"]').addEventListener('change', function() {
        const outroPlanoGroup = document.getElementById('outro_plano_group');
        const outroPlanoInput = outroPlanoGroup.querySelector('input');
        
        if (this.value === 'outro') {
            outroPlanoGroup.style.display = 'block';
            outroPlanoInput.required = true;
        } else {
            outroPlanoGroup.style.display = 'none';
            outroPlanoInput.required = false;
            outroPlanoInput.value = '';
        }
    });

    // Atualize a validação do formulário de cadastro rápido
    document.getElementById('formCadastroRapido').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar campos obrigatórios
        const nome = this.querySelector('input[name="nome"]').value.trim();
        const cpf = this.querySelector('input[name="cpf"]').value.trim();
        const dataNascimento = this.querySelector('input[name="data_nascimento"]').value.trim();
        const telefone = this.querySelector('input[name="telefone"]').value.trim();
        const tipoConsulta = this.querySelector('select[name="tipo_consulta"]').value;
        
        if (!nome || !cpf || !dataNascimento || !telefone || !tipoConsulta) {
            alert('Por favor, preencha todos os campos obrigatórios');
            return;
        }
        
        // Validar data de nascimento
        const hoje = new Date();
        const dataNasc = new Date(dataNascimento);
        if (dataNasc > hoje) {
            alert('A data de nascimento não pode ser futura');
            return;
        }
        
        // Validar plano de saúde quando necessário
        if (tipoConsulta === 'plano') {
            const planoSaude = this.querySelector('select[name="plano_saude"]').value;
            if (!planoSaude) {
                alert('Por favor, selecione o plano de saúde');
                return;
            }
            if (planoSaude === 'outro' && !this.querySelector('input[name="outro_plano"]').value.trim()) {
                alert('Por favor, especifique o plano de saúde');
                return;
            }
        }
        
        // Mostrar loading
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        submitBtn.disabled = true;

        const formData = new FormData(this);

        // Tratar plano de saúde
        if (formData.get('tipo_consulta') === 'plano') {
            const planoSaude = formData.get('plano_saude');
            if (planoSaude === 'outro') {
                formData.set('plano_saude', formData.get('outro_plano'));
            }
        }

        // Adicionar data e hora do agendamento
        formData.append('data_agendamento', document.getElementById('data_selecionada').value);
        formData.append('horario_agendamento', document.getElementById('horario_selecionado').value);
        
        fetch('cadastro_rapido_paciente.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na requisição');
            }
            return response.json();
        })
        .then(data => {
            if (data.sucesso) {
                // Criar objeto do paciente
                const paciente = {
                    id: data.paciente_id,
                    nome: data.nome,
                    cpf: data.cpf,
                    tipo_consulta: data.tipo_consulta,
                    data_nascimento: data.data_nascimento,
                    plano_saude: data.plano_saude
                };
                
                // Fechar modal de cadastro
                fecharModalCadastroRapido();
                
                // Selecionar o paciente no formulário de agendamento
                selecionarPaciente(paciente);
                
                // Pré-selecionar consulta como tipo de atendimento
                document.getElementById('tipo_atendimento').value = 'consulta';
                atualizarValor();
                
                // Mostrar mensagem de sucesso
                alert('Paciente cadastrado com sucesso! Continue o agendamento.');
            } else {
                throw new Error(data.erro || 'Erro ao cadastrar paciente');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert(error.message || 'Erro ao cadastrar paciente. Por favor, tente novamente.');
        })
        .finally(() => {
            // Restaurar botão
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });

    // Melhorar a função de máscara do CPF
    function mascaraCPF(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length <= 11) {
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            input.value = value;
        }
    }

    // Melhorar a função de máscara do telefone
    function mascaraTelefone(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length <= 11) {
            if (value.length > 2) {
                value = '(' + value.substring(0,2) + ') ' + value.substring(2);
            }
            if (value.length > 10) {
                value = value.substring(0,10) + '-' + value.substring(10);
            }
            input.value = value;
        }
    }

    // Adicionar as máscaras aos campos
    document.querySelector('input[name="cpf"]').addEventListener('input', function() {
        mascaraCPF(this);
    });

    document.querySelector('input[name="telefone"]').addEventListener('input', function() {
        mascaraTelefone(this);
    });
    </script>
</body>
</html> 