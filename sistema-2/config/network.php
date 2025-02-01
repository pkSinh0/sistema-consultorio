<?php
// Obter o IP do servidor na rede local
function getServerIP() {
    if (isset($_SERVER['SERVER_ADDR'])) {
        return $_SERVER['SERVER_ADDR'];
    }
    return 'localhost';
}

// URL base do sistema
$baseUrl = 'http://' . getServerIP() . '/sistema-2/';
define('BASE_URL', $baseUrl);
?> 