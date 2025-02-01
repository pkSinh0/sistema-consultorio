<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'medico') {
    header("Location: login.php");
    exit();
}

$prontuario_id = isset($_GET['id']) ? $_GET['id'] : null;
$paciente_id = isset($_GET['paciente_id']) ? $_GET['paciente_id'] : null;

if (!$prontuario_id || !$paciente_id) {
    header("Location: prontuario.php?id=" . $paciente_id);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Buscar dados do paciente
$query = "SELECT * FROM pacientes WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $paciente_id);
$stmt->execute();
$paciente = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar dados do prontuário
$query = "SELECT * FROM prontuarios WHERE id = :id AND paciente_id = :paciente_id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $prontuario_id);
$stmt->bindValue(':paciente_id', $paciente_id);
$stmt->execute();
$prontuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prontuario) {
    header("Location: prontuario.php?id=" . $paciente_id . "&erro=" . urlencode("Prontuário não encontrado."));
    exit();
}

// Se for um POST, atualizar o prontuário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = "UPDATE prontuarios SET 
        acuidade_od = :acuidade_od,
        acuidade_oe = :acuidade_oe,
        tonometria_od = :tonometria_od,
        tonometria_oe = :tonometria_oe,
        biomicroscopia = :biomicroscopia,
        fundoscopia = :fundoscopia,
        conduta = :conduta,
        observacoes = :observacoes
        WHERE id = :id AND paciente_id = :paciente_id";
    
    try {
        $stmt = $db->prepare($query);
        
        $stmt->bindValue(':acuidade_od', $_POST['acuidade_od']);
        $stmt->bindValue(':acuidade_oe', $_POST['acuidade_oe']);
        $stmt->bindValue(':tonometria_od', $_POST['tonometria_od']);
        $stmt->bindValue(':tonometria_oe', $_POST['tonometria_oe']);
        $stmt->bindValue(':biomicroscopia', $_POST['biomicroscopia']);
        $stmt->bindValue(':fundoscopia', $_POST['fundoscopia']);
        $stmt->bindValue(':conduta', $_POST['conduta']);
        $stmt->bindValue(':observacoes', $_POST['observacoes']);
        $stmt->bindValue(':id', $prontuario_id);
        $stmt->bindValue(':paciente_id', $paciente_id);
        
        if ($stmt->execute()) {
            header("Location: prontuario.php?id=" . $paciente_id . "&sucesso=" . urlencode("Prontuário atualizado com sucesso!"));
            exit();
        }
    } catch (PDOException $e) {
        $erro = "Erro ao atualizar prontuário: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Prontuário - <?php echo $paciente['nome']; ?></title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-edit"></i> Editar Prontuário</h2>
            </div>

            <div class="prontuario-container">
                <?php if (isset($erro)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $erro; ?>
                    </div>
                <?php endif; ?>

                <div class="paciente-info-card">
                    <h3><?php echo htmlspecialchars($paciente['nome']); ?></h3>
                    <p>Data da Consulta: <?php echo date('d/m/Y', strtotime($prontuario['data_consulta'])); ?></p>
                </div>

                <form method="POST" class="prontuario-form">
                    <!-- Acuidade Visual -->
                    <div class="form-section">
                        <h4><i class="fas fa-eye"></i> Acuidade Visual</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Olho Direito (OD):</label>
                                <input type="text" name="acuidade_od" class="form-control" 
                                       value="<?php echo htmlspecialchars($prontuario['acuidade_od']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Olho Esquerdo (OE):</label>
                                <input type="text" name="acuidade_oe" class="form-control" 
                                       value="<?php echo htmlspecialchars($prontuario['acuidade_oe']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Tonometria -->
                    <div class="form-section">
                        <h4><i class="fas fa-compress-arrows-alt"></i> Tonometria</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>OD (mmHg):</label>
                                <input type="text" name="tonometria_od" class="form-control" 
                                       value="<?php echo htmlspecialchars($prontuario['tonometria_od']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>OE (mmHg):</label>
                                <input type="text" name="tonometria_oe" class="form-control" 
                                       value="<?php echo htmlspecialchars($prontuario['tonometria_oe']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Biomicroscopia -->
                    <div class="form-section">
                        <h4><i class="fas fa-microscope"></i> Biomicroscopia</h4>
                        <div class="form-group">
                            <textarea name="biomicroscopia" class="form-control" rows="4"><?php echo htmlspecialchars($prontuario['biomicroscopia']); ?></textarea>
                        </div>
                    </div>

                    <!-- Fundoscopia -->
                    <div class="form-section">
                        <h4><i class="fas fa-search"></i> Fundoscopia</h4>
                        <div class="form-group">
                            <textarea name="fundoscopia" class="form-control" rows="4"><?php echo htmlspecialchars($prontuario['fundoscopia']); ?></textarea>
                        </div>
                    </div>

                    <!-- Conduta -->
                    <div class="form-section">
                        <h4><i class="fas fa-clipboard-list"></i> Conduta</h4>
                        <div class="form-group">
                            <textarea name="conduta" class="form-control" rows="4" required><?php echo htmlspecialchars($prontuario['conduta']); ?></textarea>
                        </div>
                    </div>

                    <!-- Observações -->
                    <div class="form-section">
                        <h4><i class="fas fa-comment-medical"></i> Observações</h4>
                        <div class="form-group">
                            <textarea name="observacoes" class="form-control" rows="4"><?php echo htmlspecialchars($prontuario['observacoes']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="prontuario.php?id=<?php echo $paciente_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 