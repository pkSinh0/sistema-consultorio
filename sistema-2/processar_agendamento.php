<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Iniciar transação
        $db->beginTransaction();
        
        // Formatar a data e hora corretamente
        $data_consulta = $_POST['data'];
        $horario = $_POST['horario'];
        
        // Verificar se o horário ainda está disponível
        $query = "SELECT 1 FROM agendamentos 
                 WHERE data_consulta = :data 
                 AND horario = :horario 
                 AND status != 'cancelado'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':data', $data_consulta);
        $stmt->bindParam(':horario', $horario);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            throw new Exception("Horário já foi agendado");
        }
        
        // Buscar informações do paciente
        $query = "SELECT nome, tipo_consulta FROM pacientes WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_POST['paciente_id']);
        $stmt->execute();
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$paciente) {
            throw new Exception("Paciente não encontrado");
        }
        
        // Calcular o valor baseado no tipo de atendimento e tipo de consulta
        $tipo_atendimento = $_POST['tipo_atendimento'];
        $valor = 0;

        switch($tipo_atendimento) {
            case 'consulta':
                // Verificar se é particular ou plano
                $valor = ($paciente['tipo_consulta'] === 'particular') ? 350.00 : 250.00;
                break;
            case 'retorno':
                $valor = 0.00;
                break;
            case 'mapeamento':
                $valor = 150.00;
                break;
            default:
                $valor = 0.00;
        }
        
        // Inserir agendamento
        $query = "INSERT INTO agendamentos (
            paciente_id,
            data_consulta,
            horario,
            tipo_atendimento,
            valor,
            status,
            medico_id
        ) VALUES (
            :paciente_id,
            :data_consulta,
            :horario,
            :tipo_atendimento,
            :valor,
            'agendado',
            :medico_id
        )";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':paciente_id', $_POST['paciente_id']);
        $stmt->bindParam(':data_consulta', $data_consulta);
        $stmt->bindParam(':horario', $horario);
        $stmt->bindParam(':tipo_atendimento', $tipo_atendimento);
        $stmt->bindParam(':valor', $valor);
        $stmt->bindParam(':medico_id', $_SESSION['usuario_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Erro ao salvar agendamento");
        }
        
        // Confirmar transação
        $db->commit();
        
        // Redirecionar para a agenda na data do agendamento
        $mensagem = "Agendamento realizado com sucesso para " . htmlspecialchars($paciente['nome']);
        header("Location: agenda.php?data=" . urlencode($data_consulta) . "&sucesso=" . urlencode($mensagem));
        exit();
        
    } catch (Exception $e) {
        // Reverter transação em caso de erro
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        header("Location: pacientes.php?erro=" . urlencode($e->getMessage()));
        exit();
    }
}

// Atualize o array de horários (se existir validação de horários)
$horarios_validos = [
    '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
    '13:00', '13:30', // Novos horários
    '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'
];
?> 