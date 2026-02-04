<?php
// /var/www/rede/config.php
ini_set('display_errors', 0); // Desativado para segurança
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_name('vnstat_panel_session');
    session_start();
}
define('SESSION_NAME', 'vnstat_panel_session');
?>