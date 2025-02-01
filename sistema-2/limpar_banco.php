<?php
session_start();
require_once 'config/database.php';

// Verificar se é administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Desabilitar verificação de chave estrangeira temporariamente
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // Iniciar transação
    $db->beginTransaction();

    try {
        // Apagar prontuários primeiro
        $query = "DELETE FROM prontuarios";
        $db->exec($query);

        // Apagar agendamentos
        $query = "DELETE FROM agendamentos";
        $db->exec($query);

        // Apagar dias sem atendimento
        $query = "DELETE FROM dias_sem_atendimento";
        $db->exec($query);

        // Finalmente, apagar pacientes
        $query = "DELETE FROM pacientes";
        $db->exec($query);

        // Resetar os auto_increment
        $db->exec("ALTER TABLE prontuarios AUTO_INCREMENT = 1");
        $db->exec("ALTER TABLE agendamentos AUTO_INCREMENT = 1");
        $db->exec("ALTER TABLE dias_sem_atendimento AUTO_INCREMENT = 1");
        $db->exec("ALTER TABLE pacientes AUTO_INCREMENT = 1");

        // Confirmar todas as alterações
        $db->commit();

        // Reabilitar verificação de chave estrangeira
        $db->exec('SET FOREIGN_KEY_CHECKS = 1');

        header("Location: pacientes.php?mensagem=" . urlencode("Banco de dados limpo com sucesso!"));
    } catch (Exception $e) {
        // Em caso de erro, desfazer todas as alterações
        $db->rollBack();
        
        // Reabilitar verificação de chave estrangeira mesmo em caso de erro
        $db->exec('SET FOREIGN_KEY_CHECKS = 1');
        
        throw $e;
    }
} catch (Exception $e) {
    header("Location: pacientes.php?erro=" . urlencode("Erro ao limpar banco de dados: " . $e->getMessage()));
}
exit();
?> 