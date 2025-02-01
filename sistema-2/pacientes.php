<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Atualizar a query para incluir a foto e data de nascimento
$query = "SELECT id, nome, cpf, telefone, email, tipo_consulta, plano_saude, data_cadastro, 
          data_nascimento, foto, foto_tipo 
          FROM pacientes 
          ORDER BY nome ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Função para calcular idade
function calcularIdade($data_nascimento) {
    $hoje = new DateTime();
    $nascimento = new DateTime($data_nascimento);
    $idade = $hoje->diff($nascimento);
    return $idade->y;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lista de Pacientes - Consultório Oftalmológico</title>
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
                <h2><i class="fas fa-users"></i> Lista de Pacientes</h2>
                <div class="header-actions">
                    <a href="cadastro_paciente.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Novo Paciente
                    </a>
                    <?php if ($_SESSION['usuario_tipo'] === 'admin'): ?>
                    <button onclick="confirmarLimparBanco()" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Apagar Banco de Dados
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="search-box">
                <input type="text" id="busca" placeholder="Buscar paciente..." class="form-control">
                <i class="fas fa-search"></i>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nome</th>
                            <th>Idade</th>
                            <th>Data Nasc.</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Plano</th>
                            <th>Data Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pacientes as $paciente): ?>
                            <tr>
                                <td class="foto-paciente">
                                    <?php if($paciente['foto']): ?>
                                        <img src="data:image/jpeg;base64,<?php echo base64_encode($paciente['foto']); ?>" 
                                             class="paciente-foto-lista" 
                                             alt="Foto do paciente"
                                             onclick="mostrarFotoAmpliada(this.src)">
                                    <?php else: ?>
                                        <img src="assets/img/default-user.png" 
                                             class="paciente-foto-lista" 
                                             alt="Foto padrão">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($paciente['nome']); ?></td>
                                <td><?php echo calcularIdade($paciente['data_nascimento']); ?> anos</td>
                                <td><?php echo date('d/m/Y', strtotime($paciente['data_nascimento'])); ?></td>
                                <td><?php echo htmlspecialchars($paciente['cpf']); ?></td>
                                <td><?php echo htmlspecialchars($paciente['telefone']); ?></td>
                                <td><?php echo htmlspecialchars($paciente['email']); ?></td>
                                <td><?php echo ucfirst($paciente['tipo_consulta']); ?></td>
                                <td><?php echo $paciente['plano_saude'] ?: '-'; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($paciente['data_cadastro'])); ?></td>
                                <td class="acoes">
                                    <a href="visualizar_paciente.php?id=<?php echo $paciente['id']; ?>" class="btn-icon" title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="editar_paciente.php?id=<?php echo $paciente['id']; ?>" class="btn-icon" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($_SESSION['usuario_tipo'] === 'medico' || $_SESSION['usuario_tipo'] === 'admin'): ?>
                                    <a href="prontuario.php?id=<?php echo $paciente['id']; ?>" class="btn-icon" title="Prontuário">
                                        <i class="fas fa-file-medical"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="#" class="btn-icon" title="Agendar" 
                                       onclick="abrirModalAgendamento(<?php echo $paciente['id']; ?>, 
                                                                    '<?php echo htmlspecialchars($paciente['nome']); ?>', 
                                                                    '<?php echo $paciente['tipo_consulta']; ?>')">
                                        <i class="fas fa-calendar-plus"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modalFoto" class="modal-foto" onclick="this.style.display='none'">
        <img id="fotoAmpliada" src="" alt="Foto ampliada">
    </div>

    <div id="modalAgendamento" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Novo Agendamento</h2>
            
            <form id="formAgendamento" method="POST" action="processar_agendamento.php">
                <input type="hidden" id="paciente_id" name="paciente_id">
                
                <div class="form-group">
                    <label>Paciente:</label>
                    <input type="text" id="nome_paciente" readonly class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Tipo de Atendimento:</label>
                    <select name="tipo_atendimento" id="tipo_atendimento" class="form-control" required>
                        <option value="">Selecione...</option>
                        <option value="consulta">Consulta</option>
                        <option value="retorno">Retorno</option>
                        <option value="mapeamento">MAP</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Data:</label>
                    <input type="date" name="data" id="data_consulta" class="form-control" required 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label>Horário:</label>
                    <select name="horario" id="horarios_disponiveis" class="form-control" required>
                        <option value="">Selecione uma data primeiro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Valor:</label>
                    <input type="text" id="valor" name="valor" readonly class="form-control">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Confirmar Agendamento
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="fecharModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Busca em tempo real
            $('#busca').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('.data-table tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });

        function confirmarLimparBanco() {
            if (confirm('ATENÇÃO! Esta ação irá apagar permanentemente todos os registros de pacientes e prontuários. Esta ação não pode ser desfeita. Deseja continuar?')) {
                if (confirm('Tem absoluta certeza? Todos os dados serão perdidos!')) {
                    window.location.href = 'limpar_banco.php';
                }
            }
        }

        function mostrarFotoAmpliada(src) {
            const modal = document.getElementById('modalFoto');
            const img = document.getElementById('fotoAmpliada');
            img.src = src;
            modal.style.display = 'block';
        }

        // Fechar modal ao pressionar ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('modalFoto').style.display = 'none';
            }
        });

        function abrirModalAgendamento(pacienteId, nome, tipoConsulta) {
            document.getElementById('paciente_id').value = pacienteId;
            document.getElementById('nome_paciente').value = nome;
            document.getElementById('modalAgendamento').style.display = 'block';
            
            // Armazenar o tipo de consulta do paciente para cálculo do valor
            window.tipoConsultaPaciente = tipoConsulta;
        }

        function fecharModal() {
            document.getElementById('modalAgendamento').style.display = 'none';
        }

        // Fechar modal ao clicar no X
        document.querySelector('.close').onclick = fecharModal;

        // Fechar modal ao clicar fora dele
        window.onclick = function(event) {
            if (event.target == document.getElementById('modalAgendamento')) {
                fecharModal();
            }
        }

        // Atualizar valor conforme tipo de atendimento
        document.getElementById('tipo_atendimento').addEventListener('change', function() {
            const tipo = this.value;
            let valor = 0;
            
            if (tipo === 'consulta') {
                valor = window.tipoConsultaPaciente === 'particular' ? 350.00 : 250.00;
            } else if (tipo === 'mapeamento') {
                valor = 150.00;
            }
            
            document.getElementById('valor').value = valor.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        });

        // Buscar horários disponíveis quando a data for selecionada
        document.getElementById('data_consulta').addEventListener('change', function() {
            const data = this.value;
            const select = document.getElementById('horarios_disponiveis');
            
            // Limpar opções atuais
            select.innerHTML = '<option value="">Carregando horários...</option>';
            
            // Buscar horários disponíveis via AJAX
            fetch('buscar_horarios.php?data=' + data)
                .then(response => response.json())
                .then(horarios => {
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
        });
    </script>
</body>
</html> 