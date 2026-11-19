<?php
// reset.php - Réinitialise les notes et les comptes en 1 clic
require_once __DIR__ . '/config.php';

reset_db();
unset($_SESSION['user']);

$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: $redirect");
exit;
