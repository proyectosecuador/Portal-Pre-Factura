<?php
// mail/config.php
// Configuración para envío de correos con PHPMailer

// Configuración SMTP
$smtp_config = [
    'host' => 'smtp.office365.com',
    'port' => 587,
    'auth' => true,
    'username' => 'proyectosecuador@ransa.net',
    'password' => 'Didacta_123',
    'secure' => 'tls',
    'from_email' => 'proyectosecuador@ransa.net',
    'from_name' => 'Ransa - Operador Logístico'
];

// Configuración de la aplicación
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host . '/Portal-Pre%20Factura';

$app_config = [
    'nombre_sistema' => 'RANSA Archivo',
    'url_base' => $base_url,
    'logo_url' => $base_url . '/img/logo.png',
    'from_email' => $smtp_config['from_email'],
    'from_name' => $smtp_config['from_name']
];
?>