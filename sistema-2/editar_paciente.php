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

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Função para capitalizar nome
        function formatarNome($nome) {
            return mb_convert_case($nome, MB_CASE_TITLE, 'UTF-8');
        }

        $nome = formatarNome($_POST['nome']);
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
        $rg = $_POST['rg'];
        $data_nascimento = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['data_nascimento'])));
        $tipo_consulta = $_POST['tipo_consulta'];
        $plano_saude = isset($_POST['plano_saude']) ? $_POST['plano_saude'] : null;
        $outro_plano = isset($_POST['outro_plano']) ? $_POST['outro_plano'] : null;

        // Processar foto
        $foto = null;
        $foto_tipo = null;
        $update_foto = false;

        if (!empty($_POST['foto_data'])) {
            $foto_data = $_POST['foto_data'];
            if (preg_match('/^data:image\/(\w+);base64,/', $foto_data, $tipo)) {
                $foto_data = substr($foto_data, strpos($foto_data, ',') + 1);
                $foto_tipo = 'image/' . strtolower($tipo[1]);
                $foto = base64_decode($foto_data);
                $update_foto = true;
                
                error_log("Edição - Nova foto: " . strlen($foto) . " bytes");
                error_log("Edição - Tipo da foto: " . $foto_tipo);
                
                if ($foto === false) {
                    error_log("Erro na decodificação da foto base64");
                    $update_foto = false;
                }
            }
        } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto = file_get_contents($_FILES['foto']['tmp_name']);
            $foto_tipo = $_FILES['foto']['type'];
            $update_foto = true;
            
            error_log("Edição - Nova foto upload: " . strlen($foto) . " bytes");
            error_log("Edição - Tipo da foto: " . $foto_tipo);
        }

        // Preparar query base
        $query = "UPDATE pacientes SET 
            nome = :nome,
            cpf = :cpf,
            rg = :rg,
            data_nascimento = :data_nascimento,
            tipo_consulta = :tipo_consulta,
            plano_saude = :plano_saude,
            outro_plano = :outro_plano,
            rua = :rua,
            numero = :numero,
            complemento = :complemento,
            cidade = :cidade,
            estado = :estado,
            cep = :cep,
            telefone = :telefone,
            email = :email";

        // Adicionar campos de foto se houver atualização
        if ($update_foto) {
            $query .= ", foto = :foto, foto_tipo = :foto_tipo";
        }

        $query .= " WHERE id = :id";

        $stmt = $db->prepare($query);
        
        // Bind dos parâmetros básicos
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->bindParam(':rg', $rg);
        $stmt->bindParam(':data_nascimento', $data_nascimento);
        $stmt->bindParam(':tipo_consulta', $tipo_consulta);
        $stmt->bindParam(':plano_saude', $plano_saude);
        $stmt->bindParam(':outro_plano', $outro_plano);
        $stmt->bindParam(':rua', $_POST['rua']);
        $stmt->bindParam(':numero', $_POST['numero']);
        $stmt->bindParam(':complemento', $_POST['complemento']);
        $stmt->bindParam(':cidade', $_POST['cidade']);
        $stmt->bindParam(':estado', $_POST['estado']);
        $stmt->bindParam(':cep', $_POST['cep']);
        $stmt->bindParam(':telefone', $_POST['telefone']);
        $stmt->bindParam(':email', $_POST['email']);

        // Bind dos parâmetros da foto se houver atualização
        if ($update_foto) {
            $stmt->bindParam(':foto', $foto, PDO::PARAM_LOB);
            $stmt->bindParam(':foto_tipo', $foto_tipo);
        }

        $stmt->bindParam(':id', $_GET['id']);

        if ($stmt->execute()) {
            // Adicionar timestamp para evitar cache
            header("Location: visualizar_paciente.php?id=" . $_GET['id'] . "&sucesso=Paciente atualizado com sucesso!&t=" . time());
            exit();
        }
    } catch (PDOException $e) {
        $erro = "Erro ao atualizar paciente: " . $e->getMessage();
        error_log($erro);
    }
}

// Buscar dados atuais do paciente
$query = "SELECT * FROM pacientes WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_GET['id']);
$stmt->execute();
$paciente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$paciente) {
    header("Location: pacientes.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Paciente - Consultório Oftalmológico</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
</head>
<body>
    <div class="layout-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-user-edit"></i> Editar Paciente</h2>
            </div>

            <?php if (isset($erro)): ?>
                <div class="alert alert-danger">
                    <?php echo $erro; ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" class="cadastro-form">
                    <!-- Informações Pessoais -->
                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Informações Pessoais</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nome Completo:</label>
                                <input type="text" name="nome" value="<?php echo htmlspecialchars($paciente['nome']); ?>" 
                                       class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>CPF:</label>
                                <input type="text" name="cpf" value="<?php echo htmlspecialchars($paciente['cpf']); ?>" 
                                       class="form-control cpf-mask" required>
                            </div>
                            <div class="form-group">
                                <label>RG:</label>
                                <input type="text" name="rg" value="<?php echo htmlspecialchars($paciente['rg']); ?>" 
                                       class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Data de Nascimento:</label>
                                <input type="date" name="data_nascimento" 
                                       value="<?php echo $paciente['data_nascimento']; ?>" 
                                       class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <!-- Tipo de Consulta -->
                    <div class="form-section">
                        <h3><i class="fas fa-clipboard-list"></i> Tipo de Consulta</h3>
                        
                        <div class="form-group">
                            <label>Tipo de Consulta:</label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="tipo_consulta" value="particular" 
                                           <?php echo $paciente['tipo_consulta'] === 'particular' ? 'checked' : ''; ?>>
                                    <i class="fas fa-user"></i> Particular
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="tipo_consulta" value="plano" 
                                           <?php echo $paciente['tipo_consulta'] === 'plano' ? 'checked' : ''; ?>>
                                    <i class="fas fa-hospital-user"></i> Plano de Saúde
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group" id="plano_saude_group" style="display: <?php echo $paciente['tipo_consulta'] === 'plano' ? 'block' : 'none'; ?>">
                            <label>Plano de Saúde:</label>
                            <select name="plano_saude" class="form-control">
                                <option value="">Selecione o plano...</option>
                                <option value="unimed" <?php echo $paciente['plano_saude'] === 'unimed' ? 'selected' : ''; ?>>Unimed</option>
                                <option value="imas" <?php echo $paciente['plano_saude'] === 'imas' ? 'selected' : ''; ?>>IMAS</option>
                                <option value="plamhuv" <?php echo $paciente['plano_saude'] === 'plamhuv' ? 'selected' : ''; ?>>Plamhuv</option>
                                <option value="plan_minas" <?php echo $paciente['plano_saude'] === 'plan_minas' ? 'selected' : ''; ?>>Plan Minas</option>
                                <option value="zelo" <?php echo $paciente['plano_saude'] === 'zelo' ? 'selected' : ''; ?>>Zelo</option>
                                <option value="primicias" <?php echo $paciente['plano_saude'] === 'primicias' ? 'selected' : ''; ?>>Primícias</option>
                                <option value="outro" <?php echo !in_array($paciente['plano_saude'], ['unimed', 'imas', 'plamhuv', 'plan_minas', 'zelo', 'primicias']) && !empty($paciente['plano_saude']) ? 'selected' : ''; ?>>Outro</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="outro_plano_group" style="display: <?php echo !in_array($paciente['plano_saude'], ['unimed', 'imas', 'plamhuv', 'plan_minas', 'zelo', 'primicias']) && !empty($paciente['plano_saude']) ? 'block' : 'none'; ?>">
                            <label>Especifique o Plano:</label>
                            <input type="text" name="outro_plano" class="form-control" 
                                   value="<?php echo !in_array($paciente['plano_saude'], ['unimed', 'imas', 'plamhuv', 'plan_minas', 'zelo', 'primicias']) ? htmlspecialchars($paciente['plano_saude']) : ''; ?>"
                                   placeholder="Digite o nome do plano">
                        </div>
                    </div>

                    <!-- Endereço -->
                    <div class="form-section">
                        <h3><i class="fas fa-map-marker-alt"></i> Endereço</h3>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>CEP:</label>
                                <input type="text" name="cep" id="cep" 
                                       value="<?php echo htmlspecialchars($paciente['cep']); ?>" 
                                       class="form-control" maxlength="9" 
                                       placeholder="00000-000" required>
                                <div id="loading-cep" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i> Buscando CEP...
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Rua:</label>
                                <input type="text" name="rua" id="rua" 
                                       value="<?php echo htmlspecialchars($paciente['rua']); ?>" 
                                       class="form-control" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Número:</label>
                                <input type="text" name="numero" id="numero" 
                                       value="<?php echo htmlspecialchars($paciente['numero']); ?>" 
                                       class="form-control" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Complemento:</label>
                                <input type="text" name="complemento" id="complemento" 
                                       value="<?php echo htmlspecialchars($paciente['complemento']); ?>" 
                                       class="form-control">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Cidade:</label>
                                <input type="text" name="cidade" id="cidade" 
                                       value="<?php echo htmlspecialchars($paciente['cidade']); ?>" 
                                       class="form-control" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Estado:</label>
                                <input type="text" name="estado" id="estado" 
                                       value="<?php echo htmlspecialchars($paciente['estado']); ?>" 
                                       class="form-control" required maxlength="2">
                            </div>
                        </div>
                    </div>

                    <!-- Contato -->
                    <div class="form-section">
                        <h3><i class="fas fa-phone"></i> Contato</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Telefone:</label>
                                <input type="text" name="telefone" 
                                       value="<?php echo htmlspecialchars($paciente['telefone']); ?>" 
                                       class="form-control telefone-mask" required>
                            </div>
                            <div class="form-group">
                                <label>Email:</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($paciente['email']); ?>" 
                                       class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Foto -->
                    <div class="form-section">
                        <h3><i class="fas fa-camera"></i> Foto</h3>
                        <div class="preview-container">
                            <div id="placeholder" class="foto-placeholder" style="display: <?php echo $paciente['foto'] ? 'none' : 'flex'; ?>">
                                <i class="fas fa-camera"></i>
                                <p>Clique para tirar uma foto</p>
                            </div>
                            <video id="cameraPreview" style="display: none;"></video>
                            <canvas id="canvas" style="display: none;"></canvas>
                            <img id="fotoPreview" 
                                 src="<?php echo $paciente['foto'] ? 'get_foto.php?id=' . $paciente['id'] . '&t=' . time() : ''; ?>" 
                                 style="display: <?php echo $paciente['foto'] ? 'block' : 'none'; ?>"
                                 alt="Foto do paciente">
                        </div>
                        <input type="file" id="foto" name="foto" accept="image/*" style="display: none;">
                        <input type="hidden" id="fotoBase64" name="foto_data">
                        <div class="foto-actions">
                            <button type="button" id="iniciarCameraBtn" class="btn btn-primary" style="display: <?php echo $paciente['foto'] ? 'none' : 'block'; ?>">
                                <i class="fas fa-camera"></i> Iniciar Câmera
                            </button>
                            <button type="button" id="tirarFotoBtn" class="btn btn-success" style="display: none;">
                                <i class="fas fa-camera"></i> Tirar Foto
                            </button>
                            <button type="button" id="escolherArquivoBtn" class="btn btn-secondary">
                                <i class="fas fa-upload"></i> Escolher Arquivo
                            </button>
                            <button type="button" id="novaFotoBtn" class="btn btn-primary" style="display: <?php echo $paciente['foto'] ? 'block' : 'none'; ?>">
                                <i class="fas fa-sync"></i> Tirar Nova Foto
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Foto Atual</label>
                        <?php if($paciente['foto']): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($paciente['foto']); ?>" 
                                 class="paciente-foto-edit" alt="Foto do paciente">
                        <?php else: ?>
                            <img src="assets/img/default-user.png" class="paciente-foto-edit" alt="Foto padrão">
                        <?php endif; ?>
                        <input type="file" name="nova_foto" class="form-control" accept="image/*">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                        <a href="visualizar_paciente.php?id=<?php echo $paciente['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Máscaras
            $('.cpf-mask').mask('000.000.000-00');
            $('.telefone-mask').mask('(00) 00000-0000');
            $('.cep-mask').mask('00000-000');

            // Controle de exibição dos campos de plano de saúde
            $('input[name="tipo_consulta"]').change(function() {
                const planoSaudeCampos = $('#plano_saude_group');
                const outroPlanoCampo = $('#outro_plano_group');
                
                if ($(this).val() === 'plano') {
                    planoSaudeCampos.slideDown();
                } else {
                    planoSaudeCampos.slideUp();
                    $('#plano_saude').val('');
                    outroPlanoCampo.slideUp();
                    $('input[name="outro_plano"]').val('');
                }

                // Adiciona/remove classe selected do radio button
                $('.radio-option').removeClass('selected');
                $(this).closest('.radio-option').addClass('selected');
            });

            // Controle do campo "Outros"
            $('#plano_saude').change(function() {
                const outroPlanoCampo = $('#outro_plano_group');
                if ($(this).val() === 'outro') {
                    outroPlanoCampo.slideDown();
                } else {
                    outroPlanoCampo.slideUp();
                    $('input[name="outro_plano"]').val('');
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Elementos do DOM
            const fotoInput = document.getElementById('foto');
            const fotoPreview = document.getElementById('fotoPreview');
            const cameraPreview = document.getElementById('cameraPreview');
            const canvas = document.getElementById('canvas');
            const placeholder = document.getElementById('placeholder');
            const tirarFotoBtn = document.getElementById('tirarFotoBtn');
            const iniciarCameraBtn = document.getElementById('iniciarCameraBtn');
            const escolherArquivoBtn = document.getElementById('escolherArquivoBtn');
            const novaFotoBtn = document.getElementById('novaFotoBtn');
            const fotoBase64Input = document.getElementById('fotoBase64');

            let stream = null;
            let usandoCameraFrontal = true;

            // Função para verificar suporte à câmera
            async function verificarCamera() {
                try {
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    const cameras = devices.filter(device => device.kind === 'videoinput');
                    return cameras.length > 0;
                } catch (err) {
                    console.error('Erro ao verificar câmeras:', err);
                    return false;
                }
            }

            // Função para iniciar a câmera
            async function iniciarCamera() {
                try {
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                    }

                    const constraints = {
                        video: {
                            facingMode: { exact: usandoCameraFrontal ? 'user' : 'environment' },
                            width: { ideal: 1280 },
                            height: { ideal: 720 }
                        }
                    };

                    try {
                        stream = await navigator.mediaDevices.getUserMedia(constraints);
                    } catch (err) {
                        stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                facingMode: usandoCameraFrontal ? 'user' : 'environment'
                            }
                        });
                    }

                    cameraPreview.srcObject = stream;
                    await cameraPreview.play();

                    // Mostrar/esconder elementos
                    cameraPreview.style.display = 'block';
                    placeholder.style.display = 'none';
                    fotoPreview.style.display = 'none';
                    tirarFotoBtn.style.display = 'block';
                    iniciarCameraBtn.style.display = 'none';
                    novaFotoBtn.style.display = 'none';

                    // Adicionar botão de alternar câmera se necessário
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    const cameras = devices.filter(device => device.kind === 'videoinput');
                    
                    if (cameras.length > 1) {
                        const existingBtn = document.querySelector('.btn-alternar-camera');
                        if (existingBtn) {
                            existingBtn.remove();
                        }

                        const alternarBtn = document.createElement('button');
                        alternarBtn.className = 'btn btn-secondary btn-alternar-camera';
                        alternarBtn.innerHTML = '<i class="fas fa-sync"></i> Alternar Câmera';
                        alternarBtn.type = 'button';
                        alternarBtn.onclick = function(e) {
                            e.preventDefault();
                            alternarCamera();
                        };
                        document.querySelector('.foto-actions').insertBefore(alternarBtn, tirarFotoBtn);
                    }
                } catch (err) {
                    console.error('Erro ao acessar câmera:', err);
                    alert('Erro ao acessar a câmera. Verifique as permissões e tente novamente.');
                }
            }

            // Função para alternar câmera
            async function alternarCamera() {
                usandoCameraFrontal = !usandoCameraFrontal;
                await iniciarCamera();
            }

            // Função para capturar foto
            function capturarFoto() {
                const context = canvas.getContext('2d');
                canvas.width = cameraPreview.videoWidth;
                canvas.height = cameraPreview.videoHeight;
                context.drawImage(cameraPreview, 0, 0, canvas.width, canvas.height);
                
                const foto = canvas.toDataURL('image/jpeg', 0.9);
                fotoPreview.src = foto;
                fotoBase64Input.value = foto;
                
                fotoPreview.style.display = 'block';
                cameraPreview.style.display = 'none';
                tirarFotoBtn.style.display = 'none';
                novaFotoBtn.style.display = 'block';
                
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
            }

            // Event Listeners
            iniciarCameraBtn.addEventListener('click', async () => {
                const temCamera = await verificarCamera();
                if (temCamera) {
                    iniciarCamera();
                } else {
                    alert('Não foi possível acessar a câmera. Verifique as permissões do navegador e tente novamente.');
                }
            });

            tirarFotoBtn.addEventListener('click', capturarFoto);

            novaFotoBtn.addEventListener('click', () => {
                fotoPreview.style.display = 'none';
                iniciarCameraBtn.style.display = 'block';
                novaFotoBtn.style.display = 'none';
                fotoBase64Input.value = '';
                iniciarCamera();
            });

            escolherArquivoBtn.addEventListener('click', () => {
                fotoInput.click();
            });

            fotoInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        fotoPreview.src = e.target.result;
                        fotoPreview.style.display = 'block';
                        placeholder.style.display = 'none';
                        cameraPreview.style.display = 'none';
                        tirarFotoBtn.style.display = 'none';
                        novaFotoBtn.style.display = 'block';
                        iniciarCameraBtn.style.display = 'none';
                        fotoBase64Input.value = e.target.result;
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });

            // Limpar recursos ao sair da página
            window.addEventListener('beforeunload', () => {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
            });

            // Função para buscar CEP
            function buscarCEP(cep) {
                // Remove tudo que não é número
                cep = cep.replace(/\D/g, '');
                
                if (cep.length !== 8) {
                    return;
                }

                // Mostra loading
                document.getElementById('loading-cep').style.display = 'block';

                // Faz a consulta
                fetch(`https://viacep.com.br/ws/${cep}/json`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.erro) {
                            throw new Error('CEP não encontrado');
                        }
                        
                        // Preenche os campos
                        document.getElementById('rua').value = data.logradouro || '';
                        document.getElementById('cidade').value = data.localidade || '';
                        document.getElementById('estado').value = data.uf || '';
                        
                        // Foca no campo número
                        document.getElementById('numero').focus();
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert('Erro ao buscar CEP. Por favor, tente novamente.');
                        
                        // Limpa os campos em caso de erro
                        document.getElementById('rua').value = '';
                        document.getElementById('cidade').value = '';
                        document.getElementById('estado').value = '';
                    })
                    .finally(() => {
                        // Esconde loading
                        document.getElementById('loading-cep').style.display = 'none';
                    });
            }

            // Formata o CEP enquanto digita
            const cepInput = document.getElementById('cep');
            
            cepInput.addEventListener('input', function() {
                let cep = this.value.replace(/\D/g, '');
                
                if (cep.length > 8) {
                    cep = cep.slice(0, 8);
                }
                
                if (cep.length > 5) {
                    cep = cep.slice(0, 5) + '-' + cep.slice(5);
                }
                
                this.value = cep;
                
                if (cep.length === 9) { // 8 números + 1 hífen
                    buscarCEP(cep);
                }
            });

            // Trata evento de colar CEP
            cepInput.addEventListener('paste', function(e) {
                e.preventDefault();
                let paste = (e.clipboardData || window.clipboardData).getData('text');
                paste = paste.replace(/\D/g, '').slice(0, 8);
                
                if (paste.length > 5) {
                    paste = paste.slice(0, 5) + '-' + paste.slice(5);
                }
                
                this.value = paste;
                
                if (paste.length === 8) {
                    buscarCEP(paste);
                }
            });
        });

        // Controle dos campos de plano de saúde
        document.addEventListener('DOMContentLoaded', function() {
            const tipoConsultaRadios = document.querySelectorAll('input[name="tipo_consulta"]');
            const planoSaudeGroup = document.getElementById('plano_saude_group');
            const outroPlanoGroup = document.getElementById('outro_plano_group');
            const planoSaudeSelect = document.querySelector('select[name="plano_saude"]');

            // Função para controlar a visibilidade dos campos
            function togglePlanoSaudeFields(showPlano) {
                planoSaudeGroup.style.display = showPlano ? 'block' : 'none';
                if (!showPlano) {
                    planoSaudeSelect.value = '';
                    outroPlanoGroup.style.display = 'none';
                    outroPlanoGroup.querySelector('input').value = '';
                }
            }

            // Event listener para os radio buttons
            tipoConsultaRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    togglePlanoSaudeFields(this.value === 'plano');
                });
            });

            // Event listener para o select de plano de saúde
            planoSaudeSelect.addEventListener('change', function() {
                const outroPlanoInput = outroPlanoGroup.querySelector('input');
                const isOutro = this.value === 'outro';
                
                outroPlanoGroup.style.display = isOutro ? 'block' : 'none';
                if (!isOutro) {
                    outroPlanoInput.value = '';
                }
            });
        });

        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const tipoConsulta = document.querySelector('input[name="tipo_consulta"]:checked');
            
            if (tipoConsulta && tipoConsulta.value === 'plano') {
                const planoSaude = document.querySelector('select[name="plano_saude"]').value;
                if (!planoSaude) {
                    e.preventDefault();
                    alert('Por favor, selecione o plano de saúde');
                    return;
                }
                if (planoSaude === 'outro') {
                    const outroPlano = document.querySelector('input[name="outro_plano"]').value.trim();
                    if (!outroPlano) {
                        e.preventDefault();
                        alert('Por favor, especifique o plano de saúde');
                        return;
                    }
                }
            }
        });
    </script>
</body>
</html> 