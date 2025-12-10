<?php
// logout.php
require_once 'includes/functions.php';

// Destruir sessão
session_destroy();

// Redirecionar para página inicial
header('Location: index.php');
exit();
?>