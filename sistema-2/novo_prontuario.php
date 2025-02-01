<?php
session_start();
require_once 'config/database.php';

// Verificar se o usuário é médico ou admin
if (!isset($_SESSION['usuario_tipo']) || ($_SESSION['usuario_tipo'] !== 'medico' && $_SESSION['usuario_tipo'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

// ... resto do código ...
?> 