<?php
session_start();
require_once 'config/database.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Criar conexão com o banco
$database = new Database();
$db = $database->getConnection();

// Buscar lista de pacientes
$query = "SELECT id, nome, cpf, telefone, tipo_consulta, foto FROM pacientes ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Pacientes</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="content">
            <h2><i class="fas fa-users"></i> Lista de Pacientes</h2>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Telefone</th>
                        <th>Tipo</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td class="foto-cell">
                            <?php if($row['foto']): ?>
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($row['foto']); ?>" 
                                     class="paciente-foto" 
                                     alt="Foto do paciente"
                                     onclick="mostrarFotoAmpliada(this.src)">
                            <?php else: ?>
                                <img src="assets/img/default-user.png" 
                                     class="paciente-foto" 
                                     alt="Foto padrão">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['nome']); ?></td>
                        <td><?php echo htmlspecialchars($row['cpf']); ?></td>
                        <td><?php echo htmlspecialchars($row['telefone']); ?></td>
                        <td><?php echo htmlspecialchars($row['tipo_consulta']); ?></td>
                        <td class="acoes">
                            <a href="visualizar_paciente.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i> Visualizar
                            </a>
                            <a href="editar_paciente.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin'): ?>
                                <button onclick="confirmarExclusao(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nome']); ?>')" 
                                        class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i> Excluir
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Modal para visualização ampliada -->
            <div id="modalFoto" class="modal-foto" onclick="this.style.display='none'">
                <img id="fotoAmpliada" src="" alt="Foto ampliada">
            </div>
        </div>
    </div>

    <script>
    function mostrarFotoAmpliada(src) {
        document.getElementById('fotoAmpliada').src = src;
        document.getElementById('modalFoto').style.display = 'block';
    }

    function confirmarExclusao(pacienteId, pacienteNome) {
        if (confirm(`Tem certeza que deseja excluir o paciente ${pacienteNome}?\nEsta ação não pode ser desfeita.`)) {
            const formData = new FormData();
            formData.append('paciente_id', pacienteId);
            
            fetch('excluir_paciente.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    alert('Paciente excluído com sucesso!');
                    window.location.reload();
                } else {
                    alert(data.erro || 'Erro ao excluir paciente');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao excluir paciente');
            });
        }
    }
    </script>
</body>
</html> 