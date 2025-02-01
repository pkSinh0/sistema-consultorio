<?php
session_start();
require_once 'config/database.php';

$gd_available = extension_loaded('gd');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Função para capitalizar nome
    function formatarNome($nome) {
        $nome = mb_convert_case($nome, MB_CASE_TITLE, 'UTF-8');
        return $nome;
    }
    
    $nome = formatarNome($_POST['nome']);
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $rg = $_POST['rg'];
    $data_nascimento = null;
    if (!empty($_POST['data_nascimento'])) {
        $data = str_replace('/', '-', $_POST['data_nascimento']);
        $data_nascimento = date('Y-m-d', strtotime($data));
        
        // Validar se a data é válida
        if ($data_nascimento === false || $data_nascimento === '-0001-11-30') {
            $erro = "Data de nascimento inválida";
            error_log("Erro na data: " . $_POST['data_nascimento']);
        }
    }
    $tipo_consulta = $_POST['tipo_consulta'];
    $plano_saude = isset($_POST['plano_saude']) ? $_POST['plano_saude'] : null;
    $outro_plano = isset($_POST['outro_plano']) ? $_POST['outro_plano'] : null;
    
    // Processar foto
    $foto = null;
    $foto_tipo = null;
    
    if (!empty($_POST['fotoBase64'])) {
        $foto_data = $_POST['fotoBase64'];
        if (preg_match('/^data:image\/(\w+);base64,/', $foto_data, $tipo)) {
            $foto_data = substr($foto_data, strpos($foto_data, ',') + 1);
            $foto_tipo = 'image/' . strtolower($tipo[1]);
            $foto = base64_decode($foto_data);
            
            // Debug para verificar os dados da foto
            error_log("Cadastro - Tamanho da foto: " . strlen($foto) . " bytes");
            error_log("Cadastro - Tipo da foto: " . $foto_tipo);
            
            // Verificar se a decodificação foi bem sucedida
            if ($foto === false) {
                error_log("Erro na decodificação da foto base64");
                $foto = null;
                $foto_tipo = null;
            }
        }
    } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto = file_get_contents($_FILES['foto']['tmp_name']);
        $foto_tipo = $_FILES['foto']['type'];
        
        // Debug
        error_log("Cadastro - Foto upload recebida: " . strlen($foto) . " bytes");
        error_log("Cadastro - Tipo da foto upload: " . $foto_tipo);
    }
    
    $query = "INSERT INTO pacientes (
        nome, cpf, rg, data_nascimento, tipo_consulta, plano_saude, outro_plano,
        rua, numero, complemento, cidade, estado, cep, telefone, email, foto, foto_tipo
    ) VALUES (
        :nome, :cpf, :rg, :data_nascimento, :tipo_consulta, :plano_saude, :outro_plano,
        :rua, :numero, :complemento, :cidade, :estado, :cep, :telefone, :email, :foto, :foto_tipo
    )";
    
    try {
        $stmt = $db->prepare($query);
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
        $stmt->bindParam(':foto', $foto, PDO::PARAM_LOB);
        $stmt->bindParam(':foto_tipo', $foto_tipo);
        
        if ($stmt->execute()) {
            $id = $db->lastInsertId();
            header("Location: visualizar_paciente.php?id=" . $id . "&sucesso=Paciente cadastrado com sucesso!");
            exit();
        }
    } catch (PDOException $e) {
        $erro = "Erro ao cadastrar paciente: " . $e->getMessage();
        error_log($erro);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Novo Paciente - Consultório Oftalmológico</title>
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
                <h2><i class="fas fa-user-plus"></i> Cadastro de Novo Paciente</h2>
            </div>

            <?php if (isset($mensagem)): ?>
                <div class="alert alert-success"><?php echo $mensagem; ?></div>
            <?php endif; ?>
            
            <?php if (isset($erro)): ?>
                <div class="alert alert-danger"><?php echo $erro; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" class="cadastro-form">
                    <div class="form-grid">
                        <!-- Informações Pessoais -->
                        <div class="form-section">
                            <h3><i class="fas fa-user"></i> Informações Pessoais</h3>
                            
                            <div class="form-group">
                                <label><i class="fas fa-signature"></i> Nome Completo:</label>
                                <input type="text" name="nome" required class="form-control">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-id-card"></i> CPF:</label>
                                    <input type="text" name="cpf" class="form-control cpf-mask" required>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-id-badge"></i> RG:</label>
                                    <input type="text" name="rg" class="form-control">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Data de Nascimento:</label>
                                <input type="text" 
                                       name="data_nascimento" 
                                       id="data_nascimento" 
                                       class="form-control" 
                                       placeholder="DD/MM/AAAA"
                                       maxlength="10"
                                       inputmode="numeric"
                                       autocomplete="off"
                                       required>
                            </div>
                        </div>

                        <!-- Tipo de Consulta -->
                        <div class="form-section">
                            <h3><i class="fas fa-clipboard-list"></i> Tipo de Consulta</h3>
                            
                            <div class="form-group">
                                <label>Tipo de Consulta:</label>
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="tipo_consulta" value="particular">
                                        <i class="fas fa-user"></i> Particular
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="tipo_consulta" value="plano">
                                        <i class="fas fa-hospital-user"></i> Plano de Saúde
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group" id="plano_saude_group" style="display: none;">
                                <label>Plano de Saúde:</label>
                                <select name="plano_saude" class="form-control">
                                    <option value="">Selecione o plano...</option>
                                    <option value="unimed">Unimed</option>
                                    <option value="imas">IMAS</option>
                                    <option value="plamhuv">Plamhuv</option>
                                    <option value="plan_minas">Plan Minas</option>
                                    <option value="zelo">Zelo</option>
                                    <option value="primicias">Primícias</option>
                                    <option value="outro">Outro</option>
                                </select>
                            </div>
                            
                            <div class="form-group" id="outro_plano_group" style="display: none;">
                                <label>Especifique o Plano:</label>
                                <input type="text" name="outro_plano" class="form-control" placeholder="Digite o nome do plano">
                            </div>
                        </div>

                        <!-- Endereço -->
                        <div class="form-section">
                            <h3><i class="fas fa-map-marker-alt"></i> Endereço</h3>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>CEP:</label>
                                    <input type="text" name="cep" id="cep" class="form-control cep-mask" maxlength="9" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Rua:</label>
                                    <input type="text" name="rua" id="rua" class="form-control" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Número:</label>
                                    <input type="text" name="numero" id="numero" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Complemento:</label>
                                    <input type="text" name="complemento" id="complemento" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Cidade:</label>
                                    <input type="text" name="cidade" id="cidade" class="form-control" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Estado:</label>
                                    <select name="estado" id="estado" class="form-control" required>
                                        <option value="">Selecione...</option>
                                        <option value="AC">Acre</option>
                                        <option value="AL">Alagoas</option>
                                        <option value="AP">Amapá</option>
                                        <option value="AM">Amazonas</option>
                                        <option value="BA">Bahia</option>
                                        <option value="CE">Ceará</option>
                                        <option value="DF">Distrito Federal</option>
                                        <option value="ES">Espírito Santo</option>
                                        <option value="GO">Goiás</option>
                                        <option value="MA">Maranhão</option>
                                        <option value="MT">Mato Grosso</option>
                                        <option value="MS">Mato Grosso do Sul</option>
                                        <option value="MG">Minas Gerais</option>
                                        <option value="PA">Pará</option>
                                        <option value="PB">Paraíba</option>
                                        <option value="PR">Paraná</option>
                                        <option value="PE">Pernambuco</option>
                                        <option value="PI">Piauí</option>
                                        <option value="RJ">Rio de Janeiro</option>
                                        <option value="RN">Rio Grande do Norte</option>
                                        <option value="RS">Rio Grande do Sul</option>
                                        <option value="RO">Rondônia</option>
                                        <option value="RR">Roraima</option>
                                        <option value="SC">Santa Catarina</option>
                                        <option value="SP">São Paulo</option>
                                        <option value="SE">Sergipe</option>
                                        <option value="TO">Tocantins</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Contato -->
                        <div class="form-section">
                            <h3><i class="fas fa-phone"></i> Contato</h3>
                            
                            <div class="form-group">
                                <label>Telefone:</label>
                                <input type="text" name="telefone" class="form-control telefone-mask" required>
                            </div>
                            
                            <div class="form-group">
                                <label>E-mail:</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                        </div>

                        <!-- Foto -->
                        <div class="form-section">
                            <h3><i class="fas fa-camera"></i> Foto do Paciente</h3>
                            
                            <div class="form-group">
                                <label><i class="fas fa-camera"></i> Foto do Paciente:</label>
                                <div class="foto-upload-container">
                                    <div class="preview-container">
                                        <img id="fotoPreview" src="#" alt="Preview da foto" style="display: none;">
                                        <video id="cameraPreview" style="display: none;" playsinline autoplay></video>
                                        <canvas id="canvas" style="display: none;"></canvas>
                                        <div id="placeholder" class="foto-placeholder">
                                            <i class="fas fa-camera"></i>
                                            <p>Clique para tirar uma foto ou fazer upload</p>
                                        </div>
                                    </div>
                                    <div class="foto-actions">
                                        <input type="file" id="foto" name="foto" accept="image/*" style="display: none;">
                                        <button type="button" id="tirarFotoBtn" class="btn btn-primary" style="display: none;">
                                            <i class="fas fa-camera"></i> Capturar Foto
                                        </button>
                                        <button type="button" id="iniciarCameraBtn" class="btn btn-primary">
                                            <i class="fas fa-video"></i> Abrir Câmera
                                        </button>
                                        <button type="button" id="escolherArquivoBtn" class="btn btn-secondary">
                                            <i class="fas fa-upload"></i> Upload de Foto
                                        </button>
                                        <button type="button" id="novaFotoBtn" class="btn btn-warning" style="display: none;">
                                            <i class="fas fa-redo"></i> Nova Foto
                                        </button>
                                    </div>
                                    <input type="hidden" id="fotoBase64" name="fotoBase64" value="">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Cadastrar Paciente
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Limpar Formulário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
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
                    // Se falhar com 'exact', tentar sem
                    console.log('Tentando configuração alternativa...');
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: usandoCameraFrontal ? 'user' : 'environment'
                        }
                    });
                }

                cameraPreview.srcObject = stream;
                await cameraPreview.play();

                // Mostrar/esconder elementos relevantes
                cameraPreview.style.display = 'block';
                placeholder.style.display = 'none';
                tirarFotoBtn.style.display = 'block';
                iniciarCameraBtn.style.display = 'none';

                // Verificar câmeras disponíveis
                const devices = await navigator.mediaDevices.enumerateDevices();
                const cameras = devices.filter(device => device.kind === 'videoinput');
                
                // Remover botão existente se houver
                const existingBtn = document.querySelector('.btn-alternar-camera');
                if (existingBtn) {
                    existingBtn.remove();
                }

                // Adicionar botão de alternar se houver mais de uma câmera
                if (cameras.length > 1) {
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
                console.error('Erro detalhado ao acessar câmera:', err);
                let mensagem = 'Erro ao acessar a câmera. ';
                
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    mensagem += 'Permissão negada. Por favor, permita o acesso à câmera nas configurações.';
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    mensagem += 'Nenhuma câmera encontrada no dispositivo.';
                } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                    mensagem += 'A câmera pode estar sendo usada por outro aplicativo.';
                } else if (err.name === 'OverconstrainedError' || err.name === 'ConstraintNotSatisfiedError') {
                    mensagem += 'Não foi possível encontrar uma câmera compatível.';
                } else {
                    mensagem += err.message;
                }
                
                alert(mensagem);
                console.log('Detalhes do erro:', {
                    name: err.name,
                    message: err.message,
                    constraint: err.constraint
                });
            }
        }

        // Função para alternar entre câmeras
        async function alternarCamera() {
            usandoCameraFrontal = !usandoCameraFrontal;
            await iniciarCamera();
        }

        // Função para capturar foto
        function capturarFoto() {
            const context = canvas.getContext('2d');
            
            // Ajustar dimensões do canvas para corresponder ao vídeo
            canvas.width = cameraPreview.videoWidth;
            canvas.height = cameraPreview.videoHeight;
            
            // Capturar a imagem
            context.drawImage(cameraPreview, 0, 0, canvas.width, canvas.height);
            
            // Converter para JPEG com qualidade 0.9
            const foto = canvas.toDataURL('image/jpeg', 0.9);
            
            // Atualizar preview
            fotoPreview.src = foto;
            fotoPreview.style.display = 'block';
            
            // Salvar dados da foto
            fotoBase64Input.value = foto;
            
            // Log para debug
            console.log("Tamanho da foto base64:", foto.length);
            
            // Mostrar/esconder elementos
            cameraPreview.style.display = 'none';
            tirarFotoBtn.style.display = 'none';
            novaFotoBtn.style.display = 'block';
            
            // Parar a câmera
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
            placeholder.style.display = 'flex';
            iniciarCameraBtn.style.display = 'block';
            novaFotoBtn.style.display = 'none';
            fotoBase64Input.value = '';
        });

        // Limpar recursos ao sair da página
        window.addEventListener('beforeunload', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });

        // Função para preencher endereço pelo CEP
        function buscarCep(cep) {
            // Remover caracteres não numéricos
            cep = cep.replace(/\D/g, '');
            
            if (cep.length !== 8) {
                return;
            }

            // Mostrar indicador de carregamento
            document.getElementById('rua').value = 'Carregando...';
            document.getElementById('cidade').value = 'Carregando...';
            document.getElementById('estado').value = '';

            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (data.erro) {
                        alert('CEP não encontrado.');
                        limparEndereco();
                        return;
                    }

                    // Preencher os campos
                    document.getElementById('rua').value = data.logradouro;
                    document.getElementById('cidade').value = data.localidade;
                    document.getElementById('estado').value = data.uf;

                    // Focar no campo número após preenchimento
                    document.getElementById('numero').focus();
                })
                .catch(error => {
                    console.error('Erro ao buscar CEP:', error);
                    alert('Erro ao buscar CEP. Tente novamente.');
                    limparEndereco();
                });
        }

        function limparEndereco() {
            document.getElementById('rua').value = '';
            document.getElementById('cidade').value = '';
            document.getElementById('estado').value = '';
        }

        // Adicionar evento ao campo CEP
        document.getElementById('cep').addEventListener('blur', function() {
            const cep = this.value;
            if (cep) {
                buscarCep(cep);
            }
        });

        // Máscara para o CEP
        document.getElementById('cep').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 8) {
                value = value.substr(0, 8);
            }
            if (value.length > 5) {
                this.value = value.substr(0, 5) + '-' + value.substr(5);
            } else {
                this.value = value;
            }
        });

        // Substitua o código de formatação da data por este
        const dataNascimento = document.getElementById('data_nascimento');
        let oldValue = '';
        let oldCursor = 0;

        dataNascimento.addEventListener('input', function(e) {
            let cursorPos = this.selectionStart;
            let value = this.value.replace(/\D/g, '');
            let newValue = '';
            
            // Se estiver apagando, apenas remove o caractere
            if (this.value.length < oldValue.length) {
                oldValue = this.value;
                return;
            }
            
            // Formata a data
            if (value.length > 0) {
                // Dia
                newValue = value.substr(0, 2);
                
                // Mês
                if (value.length > 2) {
                    newValue += '/' + value.substr(2, 2);
                    
                    // Ano
                    if (value.length > 4) {
                        newValue += '/' + value.substr(4, 4);
                    }
                }
            }
            
            // Atualiza o valor mantendo a posição do cursor
            this.value = newValue;
            
            // Ajusta a posição do cursor
            if (cursorPos === 2 && newValue.length > 2) cursorPos += 1;
            if (cursorPos === 5 && newValue.length > 5) cursorPos += 1;
            
            // Se adicionou um número antes de uma barra, move o cursor para depois da barra
            if (oldValue.charAt(cursorPos - 1) === '/' && newValue.length > oldValue.length) {
                cursorPos += 1;
            }
            
            // Limita o cursor à posição máxima
            if (cursorPos > newValue.length) {
                cursorPos = newValue.length;
            }
            
            this.setSelectionRange(cursorPos, cursorPos);
            oldValue = newValue;
            oldCursor = cursorPos;
        });

        // Validação ao perder o foco
        dataNascimento.addEventListener('blur', function() {
            const value = this.value;
            if (value.length === 10) {
                const [dia, mes, ano] = value.split('/').map(Number);
                const data = new Date(ano, mes - 1, dia);
                const hoje = new Date();
                
                // Verificar se é uma data válida
                if (data.getDate() !== dia || data.getMonth() + 1 !== mes || data.getFullYear() !== ano) {
                    alert('Data inválida');
                    this.value = '';
                    return;
                }
                
                // Verificar se não é uma data futura
                if (data > hoje) {
                    alert('A data não pode ser futura');
                    this.value = '';
                    return;
                }
                
                // Verificar idade máxima razoável (150 anos)
                const idadeMaxima = new Date();
                idadeMaxima.setFullYear(hoje.getFullYear() - 150);
                if (data < idadeMaxima) {
                    alert('Data muito antiga');
                    this.value = '';
                    return;
                }
            }
        });

        // Configurar o input para teclado numérico em dispositivos móveis
        dataNascimento.setAttribute('inputmode', 'numeric');

        // Permitir apenas números e teclas de controle
        dataNascimento.addEventListener('keydown', function(e) {
            // Permite: backspace, delete, tab, escape, enter
            if ([8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
                // Permite: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true) ||
                // Permite: home, end, left, right
                (e.keyCode >= 35 && e.keyCode <= 39)) {
                return;
            }
            // Garante que é um número e impede o evento keypress
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });

        // Modifique também o evento de upload de arquivo
        fotoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Atualizar preview
                    fotoPreview.src = e.target.result;
                    fotoPreview.style.display = 'block';
                    placeholder.style.display = 'none';
                    
                    // Salvar dados da foto
                    fotoBase64Input.value = e.target.result;
                    
                    // Log para debug
                    console.log("Tamanho da foto upload:", e.target.result.length);
                    
                    // Atualizar interface
                    cameraPreview.style.display = 'none';
                    tirarFotoBtn.style.display = 'none';
                    novaFotoBtn.style.display = 'block';
                    iniciarCameraBtn.style.display = 'none';
                };
                reader.readAsDataURL(this.files[0]);
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
            outroPlanoGroup.style.display = 'none';
            
            // Resetar campos quando esconder
            if (!showPlano) {
                planoSaudeSelect.value = '';
                if (outroPlanoGroup.querySelector('input')) {
                    outroPlanoGroup.querySelector('input').value = '';
                }
            }
            
            // Ajustar required
            planoSaudeSelect.required = showPlano;
            if (outroPlanoGroup.querySelector('input')) {
                outroPlanoGroup.querySelector('input').required = false;
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
            if (outroPlanoInput) {
                outroPlanoInput.required = isOutro;
                if (!isOutro) {
                    outroPlanoInput.value = '';
                }
            }
        });

        // Inicializar estado dos campos baseado no valor inicial
        const selectedTipo = document.querySelector('input[name="tipo_consulta"]:checked');
        if (selectedTipo) {
            togglePlanoSaudeFields(selectedTipo.value === 'plano');
        }
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