<?php
// logout.php

require 'config.php';

// Inicia a sessão para poder destruí-la
session_name(SESSION_NAME);
session_start();

// Limpa todas as variáveis da sessão
$_SESSION = array();

// Destrói a sessão
session_destroy();

// Redireciona para a página de login
header('Location: login.php');
exit;
?>