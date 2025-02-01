<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['erro' => 'Não autorizado']);
    exit();
}

function formatarNome($nome) {
    // Converter para minúsculas e dividir em palavras
    $palavras = explode(' ', mb_strtolower($nome, 'UTF-8'));
    
    // Lista de preposições que devem permanecer em minúsculo
    $preposicoes = ['de', 'da', 'do', 'dos', 'das', 'e'];
    
    // Processar cada palavra
    $palavras_formatadas = array_map(function($palavra) use ($preposicoes) {
        // Se for preposição, manter em minúsculo
        if (in_array($palavra, $preposicoes)) {
            return $palavra;
        }
        
        // Caso contrário, capitalizar primeira letra
        return mb_strtoupper(mb_substr($palavra, 0, 1, 'UTF-8'), 'UTF-8') . 
               mb_substr($palavra, 1, null, 'UTF-8');
    }, $palavras);
    
    // Juntar as palavras novamente
    return implode(' ', $palavras_formatadas);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Iniciar transação
        $db->beginTransaction();
        
        // Formatar o nome
        $_POST['nome'] = formatarNome($_POST['nome']);
        
        // Validar dados obrigatórios
        if (empty($_POST['nome']) || empty($_POST['cpf']) || empty($_POST['telefone']) || empty($_POST['tipo_consulta'])) {
            throw new Exception("Todos os campos são obrigatórios");
        }
        
        // Formatar CPF
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
        
        // Verificar se CPF já existe
        $stmt = $db->prepare("SELECT id FROM pacientes WHERE cpf = ?");
        $stmt->execute([$cpf]);
        if ($stmt->fetch()) {
            throw new Exception("CPF já cadastrado");
        }
        
        // Tratar o plano de saúde
        $plano_saude = null;
        if ($_POST['tipo_consulta'] === 'plano') {
            if (empty($_POST['plano_saude'])) {
                throw new Exception("Por favor, selecione o plano de saúde");
            }
            $plano_saude = $_POST['plano_saude'];
        }
        
        // Inserir o novo paciente
        $query = "INSERT INTO pacientes (
            nome, 
            cpf, 
            telefone, 
            tipo_consulta,
            plano_saude,
            data_nascimento,
            data_cadastro
        ) VALUES (
            :nome,
            :cpf,
            :telefone,
            :tipo_consulta,
            :plano_saude,
            :data_nascimento,
            NOW()
        )";
        
        $stmt = $db->prepare($query);
        
        $data_nascimento = date('Y-m-d'); // Data atual como padrão
        
        $stmt->bindParam(':nome', $_POST['nome']);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->bindParam(':telefone', $_POST['telefone']);
        $stmt->bindParam(':tipo_consulta', $_POST['tipo_consulta']);
        $stmt->bindParam(':plano_saude', $plano_saude);
        $stmt->bindParam(':data_nascimento', $data_nascimento);
        
        if (!$stmt->execute()) {
            throw new Exception("Erro ao cadastrar paciente");
        }
        
        $paciente_id = $db->lastInsertId();
        
        // Confirmar transação
        $db->commit();
        
        // Formatar CPF para exibição
        $cpf_formatado = substr($cpf, 0, 3) . '.' . 
                        substr($cpf, 3, 3) . '.' . 
                        substr($cpf, 6, 3) . '-' . 
                        substr($cpf, 9, 2);
        
        echo json_encode([
            'sucesso' => true,
            'paciente_id' => $paciente_id,
            'nome' => $_POST['nome'],
            'cpf' => $cpf_formatado,
            'tipo_consulta' => $_POST['tipo_consulta'],
            'data_nascimento' => $data_nascimento,
            'plano_saude' => $plano_saude
        ]);
        
    } catch (Exception $e) {
        // Reverter transação em caso de erro
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo json_encode(['erro' => $e->getMessage()]);
    }
}
?> 