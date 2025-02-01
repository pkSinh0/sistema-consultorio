<?php
// Configurações básicas
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar apenas o requisito essencial do MySQL
$requirements = array(
    'MySQL/MariaDB' => extension_loaded('pdo_mysql')
);

$allRequirementsMet = true;
foreach ($requirements as $requirement => $met) {
    if (!$met) {
        $allRequirementsMet = false;
        break;
    }
}

if (!$allRequirementsMet) {
    echo "<h1>Verificação de Sistema</h1>";
    foreach ($requirements as $requirement => $met) {
        echo "$requirement: " . ($met ? "✓" : "✗") . "<br>";
    }
    echo "<p>É necessário ter o MySQL/MariaDB instalado e configurado.</p>";
    exit;
}

// Redirecionar para login
header("Location: login.php");
exit();
?> 