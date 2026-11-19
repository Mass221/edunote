<?php
// toggle_mode.php - Commutateur global Mode Vulnérable <-> Mode Sécurisé
require_once __DIR__ . '/config.php';

$current = is_vulnerable_mode();
$newMode = !$current;
set_vulnerable_mode($newMode);

if ($newMode) {
    $_SESSION['flash_message'] = "⚠️ MODE VULNÉRABLE ACTIVÉ : Les signatures SAML et XML ne sont plus vérifiées. Le parseur résout les entités externes (XXE).";
    $_SESSION['flash_type'] = "danger";
} else {
    $_SESSION['flash_message'] = "🛡️ MODE SÉCURISÉ ACTIVÉ : Vérification cryptographique XML-DSig active (SHA-256 + RSA). DTD et XXE neutralisées.";
    $_SESSION['flash_type'] = "success";
}

$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: $redirect");
exit;
