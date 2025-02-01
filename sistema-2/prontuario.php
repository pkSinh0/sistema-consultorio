<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Criar conexão com o banco de dados
$database = new Database();
$db = $database->getConnection();

// Verificar se o usuário é médico ou admin
if (!isset($_SESSION['usuario_tipo']) || ($_SESSION['usuario_tipo'] !== 'medico' && $_SESSION['usuario_tipo'] !== 'admin')) {
    // Se não for médico nem admin, mostrar página de acesso negado
    header("Location: acesso_negado.php");
    exit();
}

$paciente_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$paciente_id) {
    header("Location: agenda.php");
    exit();
}

// Buscar dados do paciente
$query = "SELECT * FROM pacientes WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $paciente_id);
$stmt->execute();
$paciente = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar histórico de prontuários
$query = "SELECT p.*, u.nome as medico_nome 
          FROM prontuarios p 
          JOIN usuarios u ON p.medico_id = u.id 
          WHERE p.paciente_id = :paciente_id 
          ORDER BY p.data_consulta DESC";
$stmt = $db->prepare($query);
$stmt->bindValue(':paciente_id', $paciente_id);
$stmt->execute();
$historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Se for um POST, salvar o prontuário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = "INSERT INTO prontuarios (
        paciente_id, 
        data_consulta,
        acuidade_od,
        acuidade_oe,
        tonometria_od,
        tonometria_oe,
        biomicroscopia,
        fundoscopia,
        conduta,
        observacoes,
        od_esferico,
        od_cilindrico,
        od_eixo,
        od_dnp,
        od_altura,
        oe_esferico,
        oe_cilindrico,
        oe_eixo,
        oe_dnp,
        oe_altura,
        adicao,
        obs_receita,
        medico_id
    ) VALUES (
        :paciente_id,
        NOW(),
        :acuidade_od,
        :acuidade_oe,
        :tonometria_od,
        :tonometria_oe,
        :biomicroscopia,
        :fundoscopia,
        :conduta,
        :observacoes,
        :od_esferico,
        :od_cilindrico,
        :od_eixo,
        :od_dnp,
        :od_altura,
        :oe_esferico,
        :oe_cilindrico,
        :oe_eixo,
        :oe_dnp,
        :oe_altura,
        :adicao,
        :obs_receita,
        :medico_id
    )";
    
    try {
        $stmt = $db->prepare($query);
        
        // Bind dos valores
        $stmt->bindValue(':paciente_id', $paciente_id);
        $stmt->bindValue(':acuidade_od', $_POST['acuidade_od']);
        $stmt->bindValue(':acuidade_oe', $_POST['acuidade_oe']);
        $stmt->bindValue(':tonometria_od', $_POST['tonometria_od']);
        $stmt->bindValue(':tonometria_oe', $_POST['tonometria_oe']);
        $stmt->bindValue(':biomicroscopia', $_POST['biomicroscopia']);
        $stmt->bindValue(':fundoscopia', $_POST['fundoscopia']);
        $stmt->bindValue(':conduta', $_POST['conduta']);
        $stmt->bindValue(':observacoes', $_POST['observacoes']);
        $stmt->bindValue(':od_esferico', $_POST['od_esferico']);
        $stmt->bindValue(':od_cilindrico', $_POST['od_cilindrico']);
        $stmt->bindValue(':od_eixo', $_POST['od_eixo']);
        $stmt->bindValue(':od_dnp', $_POST['od_dnp']);
        $stmt->bindValue(':od_altura', $_POST['od_altura']);
        $stmt->bindValue(':oe_esferico', $_POST['oe_esferico']);
        $stmt->bindValue(':oe_cilindrico', $_POST['oe_cilindrico']);
        $stmt->bindValue(':oe_eixo', $_POST['oe_eixo']);
        $stmt->bindValue(':oe_dnp', $_POST['oe_dnp']);
        $stmt->bindValue(':oe_altura', $_POST['oe_altura']);
        $stmt->bindValue(':adicao', $_POST['adicao']);
        $stmt->bindValue(':obs_receita', $_POST['obs_receita']);
        $stmt->bindValue(':medico_id', $_SESSION['usuario_id']);
        
        if ($stmt->execute()) {
            // Após salvar, recarregar os dados do histórico
            $query = "SELECT p.*, u.nome as medico_nome 
                     FROM prontuarios p 
                     JOIN usuarios u ON p.medico_id = u.id 
                     WHERE p.paciente_id = :paciente_id 
                     ORDER BY p.data_consulta DESC";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':paciente_id', $paciente_id);
            $stmt->execute();
            $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Adicionar mensagem de sucesso
            $mensagem_sucesso = "Prontuário registrado com sucesso!";
        }
    } catch (PDOException $e) {
        $erro = "Erro ao salvar prontuário: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Prontuário - <?php echo $paciente['nome']; ?></title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-file-medical"></i> Prontuário</h2>
                <div class="print-buttons">
                    <?php if (!empty($historico)): ?>
                        <a href="imprimir_prontuario.php?id=<?php echo $paciente_id; ?>&visualizar=true" target="_blank" class="btn btn-primary">
                            <i class="fas fa-print"></i> Imprimir Prontuário
                        </a>
                        <a href="imprimir_receita.php?id=<?php echo $paciente_id; ?>&visualizar=true" target="_blank" class="btn btn-primary">
                            <i class="fas fa-glasses"></i> Imprimir Receita
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="prontuario-container">
                <?php if (isset($_GET['sucesso'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($_GET['sucesso']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['erro'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($_GET['erro']); ?>
                    </div>
                <?php endif; ?>

                <!-- Informações do Paciente -->
                <div class="paciente-info-card">
                    <div class="paciente-header">
                        <div class="paciente-foto">
                            <?php if($paciente['foto']): ?>
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($paciente['foto']); ?>" 
                                     class="paciente-foto" 
                                     alt="Foto do paciente"
                                     onclick="mostrarFotoAmpliada(this.src)">
                            <?php else: ?>
                                <img src="assets/img/default-user.png" 
                                     class="paciente-foto" 
                                     alt="Foto padrão">
                            <?php endif; ?>
                        </div>
                        <div class="paciente-info">
                            <h3><?php echo htmlspecialchars($paciente['nome']); ?></h3>
                            <div class="paciente-detalhes">
                                <p><strong>Data de Nascimento:</strong> <?php echo date('d/m/Y', strtotime($paciente['data_nascimento'])); ?></p>
                                <p><strong>CPF:</strong> <?php echo $paciente['cpf']; ?></p>
                                <p><strong>Tipo de Consulta:</strong> <?php echo ucfirst($paciente['tipo_consulta']); ?></p>
                                <?php if ($paciente['tipo_consulta'] === 'plano'): ?>
                                    <p><strong>Plano de Saúde:</strong> <?php echo $paciente['plano_saude']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Novo Registro -->
                <div class="prontuario-form-container">
                    <h3>Novo Registro</h3>
                    <form method="POST" class="prontuario-form" id="prontuarioForm">
                        <!-- Acuidade Visual -->
                        <div class="form-section">
                            <h4><i class="fas fa-eye"></i> Acuidade Visual</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Olho Direito (OD):</label>
                                    <input type="text" name="acuidade_od" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Olho Esquerdo (OE):</label>
                                    <input type="text" name="acuidade_oe" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <!-- Tonometria -->
                        <div class="form-section">
                            <h4><i class="fas fa-compress-arrows-alt"></i> Tonometria</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>OD (mmHg):</label>
                                    <input type="text" name="tonometria_od" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>OE (mmHg):</label>
                                    <input type="text" name="tonometria_oe" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <!-- Biomicroscopia -->
                        <div class="form-section">
                            <h4><i class="fas fa-microscope"></i> Biomicroscopia</h4>
                            <div class="form-group">
                                <textarea name="biomicroscopia" class="form-control" rows="4"></textarea>
                            </div>
                        </div>

                        <!-- Fundoscopia -->
                        <div class="form-section">
                            <h4><i class="fas fa-search"></i> Fundoscopia</h4>
                            <div class="form-group">
                                <textarea name="fundoscopia" class="form-control" rows="4"></textarea>
                            </div>
                        </div>

                        <!-- Conduta -->
                        <div class="form-section">
                            <h4><i class="fas fa-clipboard-list"></i> Conduta</h4>
                            <div class="form-group">
                                <textarea name="conduta" class="form-control" rows="4" required></textarea>
                            </div>
                        </div>

                        <!-- Observações -->
                        <div class="form-section">
                            <h4><i class="fas fa-comment-medical"></i> Observações</h4>
                            <div class="form-group">
                                <textarea name="observacoes" class="form-control" rows="4"></textarea>
                            </div>
                        </div>

                        <!-- Receita de Óculos -->
                        <div class="form-section">
                            <h4><i class="fas fa-glasses"></i> Receita de Óculos</h4>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <h5>Olho Direito (OD)</h5>
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label>Esférico:</label>
                                            <input type="text" name="od_esferico" class="form-control">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Cilíndrico:</label>
                                            <input type="text" name="od_cilindrico" class="form-control">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Eixo:</label>
                                            <input type="text" name="od_eixo" class="form-control">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>DNP:</label>
                                            <input type="text" name="od_dnp" class="form-control">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Altura:</label>
                                            <input type="text" name="od_altura" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <h5>Olho Esquerdo (OE)</h5>
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label>Esférico:</label>
                                            <input type="text" name="oe_esferico" class="form-control">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Cilíndrico:</label>
                                            <input type="text" name="oe_cilindrico" class="form-control">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Eixo:</label>
                                            <input type="text" name="oe_eixo" class="form-control">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>DNP:</label>
                                            <input type="text" name="oe_dnp" class="form-control">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Altura:</label>
                                            <input type="text" name="oe_altura" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Adição:</label>
                                    <input type="text" name="adicao" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Observações da Receita:</label>
                                <textarea name="obs_receita" class="form-control" rows="4"></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Salvar Registro
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Histórico -->
                <div class="historico-container">
                    <h3>Histórico de Consultas</h3>
                    <?php if (!empty($historico)): ?>
                        <?php foreach ($historico as $registro): ?>
                            <div class="registro-card">
                                <div class="registro-header">
                                    <div class="registro-data">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('d/m/Y', strtotime($registro['data_consulta'])); ?>
                                    </div>
                                    <div class="registro-medico">
                                        <i class="fas fa-user-md"></i>
                                        Dr. José Manoel Lopes
                                    </div>
                                    <div class="registro-acoes">
                                        <a href="imprimir_prontuario.php?id=<?php echo $paciente_id; ?>&prontuario_id=<?php echo $registro['id']; ?>&visualizar=true" 
                                           target="_blank" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-print"></i> Imprimir
                                        </a>
                                        <a href="imprimir_receita.php?id=<?php echo $paciente_id; ?>&prontuario_id=<?php echo $registro['id']; ?>&visualizar=true" 
                                           target="_blank" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-glasses"></i> Receita
                                        </a>
                                        <a href="editar_prontuario.php?id=<?php echo $registro['id']; ?>&paciente_id=<?php echo $paciente_id; ?>" 
                                           class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <button onclick="confirmarExclusao(<?php echo $registro['id']; ?>)" 
                                                class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                    </div>
                                </div>
                                <div class="registro-content">
                                    <div class="registro-section">
                                        <h5>Acuidade Visual</h5>
                                        <p>OD: <?php echo htmlspecialchars($registro['acuidade_od']); ?></p>
                                        <p>OE: <?php echo htmlspecialchars($registro['acuidade_oe']); ?></p>
                                    </div>
                                    <div class="registro-section">
                                        <h5>Tonometria</h5>
                                        <p>OD: <?php echo htmlspecialchars($registro['tonometria_od']); ?> mmHg</p>
                                        <p>OE: <?php echo htmlspecialchars($registro['tonometria_oe']); ?> mmHg</p>
                                    </div>
                                    <?php if ($registro['biomicroscopia']): ?>
                                    <div class="registro-section">
                                        <h5>Biomicroscopia</h5>
                                        <p><?php echo nl2br(htmlspecialchars($registro['biomicroscopia'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($registro['fundoscopia']): ?>
                                    <div class="registro-section">
                                        <h5>Fundoscopia</h5>
                                        <p><?php echo nl2br(htmlspecialchars($registro['fundoscopia'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <div class="registro-section">
                                        <h5>Conduta</h5>
                                        <p><?php echo nl2br(htmlspecialchars($registro['conduta'])); ?></p>
                                    </div>
                                    <?php if ($registro['observacoes']): ?>
                                    <div class="registro-section">
                                        <h5>Observações</h5>
                                        <p><?php echo nl2br(htmlspecialchars($registro['observacoes'])); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Adicionar seção da Receita de Óculos -->
                                    <?php if (isset($registro['od_esferico']) || isset($registro['oe_esferico'])): ?>
                                    <div class="registro-section">
                                        <h5>Receita de Óculos</h5>
                                        <table class="table-receita">
                                            <tr>
                                                <th></th>
                                                <th>Esférico</th>
                                                <th>Cilíndrico</th>
                                                <th>Eixo</th>
                                                <th>DNP</th>
                                                <th>Altura</th>
                                            </tr>
                                            <tr>
                                                <td><strong>OD</strong></td>
                                                <td><?php echo isset($registro['od_esferico']) ? $registro['od_esferico'] : '-'; ?></td>
                                                <td><?php echo isset($registro['od_cilindrico']) ? $registro['od_cilindrico'] : '-'; ?></td>
                                                <td><?php echo isset($registro['od_eixo']) ? $registro['od_eixo'] : '-'; ?></td>
                                                <td><?php echo isset($registro['od_dnp']) ? $registro['od_dnp'] : '-'; ?></td>
                                                <td><?php echo isset($registro['od_altura']) ? $registro['od_altura'] : '-'; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>OE</strong></td>
                                                <td><?php echo isset($registro['oe_esferico']) ? $registro['oe_esferico'] : '-'; ?></td>
                                                <td><?php echo isset($registro['oe_cilindrico']) ? $registro['oe_cilindrico'] : '-'; ?></td>
                                                <td><?php echo isset($registro['oe_eixo']) ? $registro['oe_eixo'] : '-'; ?></td>
                                                <td><?php echo isset($registro['oe_dnp']) ? $registro['oe_dnp'] : '-'; ?></td>
                                                <td><?php echo isset($registro['oe_altura']) ? $registro['oe_altura'] : '-'; ?></td>
                                            </tr>
                                        </table>
                                        <?php if (isset($registro['adicao']) && !empty($registro['adicao'])): ?>
                                        <p><strong>Adição:</strong> <?php echo htmlspecialchars($registro['adicao']); ?></p>
                                        <?php endif; ?>
                                        <?php if (isset($registro['obs_receita']) && !empty($registro['obs_receita'])): ?>
                                        <p><strong>Observações da Receita:</strong></p>
                                        <p><?php echo nl2br(htmlspecialchars($registro['obs_receita'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Nenhum registro de consulta encontrado para este paciente.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para visualização ampliada -->
    <div id="modalFoto" class="modal-foto" onclick="this.style.display='none'">
        <img id="fotoAmpliada" src="" alt="Foto ampliada">
    </div>

    <script>
    function imprimirReceita() {
        const conteudo = document.body.innerHTML;
        const receita = document.querySelector('.receita-print').innerHTML;
        
        document.body.innerHTML = receita;
        window.print();
        document.body.innerHTML = conteudo;
    }

    // Função para limpar o formulário após sucesso
    <?php if (isset($mensagem_sucesso)): ?>
    document.getElementById('prontuarioForm').reset();
    <?php endif; ?>

    function confirmarExclusao(prontuarioId) {
        if (confirm('Tem certeza que deseja excluir este registro? Esta ação não pode ser desfeita.')) {
            window.location.href = `excluir_prontuario.php?id=${prontuarioId}&paciente_id=<?php echo $paciente_id; ?>`;
        }
    }

    function mostrarFotoAmpliada(src) {
        document.getElementById('fotoAmpliada').src = src;
        document.getElementById('modalFoto').style.display = 'block';
    }
    </script>
</body>
</html> 