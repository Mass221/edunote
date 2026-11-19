<?php
// idp.php - Serveur SSO Central (IdP Universitaire - UIDT)
require_once __DIR__ . '/config.php';

$email = trim($_GET['email'] ?? 'etudiant@univ.com');

$db = load_db();
$targetUser = null;
foreach ($db['users'] as $u) {
    if ($u['email'] === $email) {
        $targetUser = $u;
        break;
    }
}

if (!$targetUser) {
    header("Location: index.php");
    exit;
}

// Génération de l'assertion SAML signée avec la clé privée de l'IdP
$samlXml = generate_saml_assertion($targetUser);
$samlBase64 = base64_encode($samlXml);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serveur d'Authentification Central (IdP) – UIDT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-slate-200 p-8 space-y-6 text-center">
        <div class="w-16 h-16 bg-blue-700 text-white rounded-2xl flex items-center justify-center text-3xl mx-auto shadow-md">
            🏛️
        </div>

        <div>
            <span class="text-[10px] font-black uppercase tracking-widest text-blue-700 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">
                Fournisseur d'Identité (IdP Universitaire)
            </span>
            <h1 class="text-xl font-black text-slate-900 mt-3">Authentification Réussie</h1>
            <p class="text-xs text-slate-500 mt-1">
                Assertion SAML 2.0 générée et signée numériquement pour <strong><?= htmlspecialchars($targetUser['name']) ?></strong> (<?= htmlspecialchars($targetUser['email']) ?>).
            </p>
        </div>

        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-left text-xs space-y-1 font-mono">
            <div><strong>Rôle émis :</strong> <span class="text-blue-600 font-bold"><?= htmlspecialchars($targetUser['role']) ?></span></div>
            <div><strong>Algorithme :</strong> RSA-SHA256 (C14N Exclusive)</div>
            <div><strong>Destination :</strong> EduNotes (Service Provider)</div>
        </div>

        <!-- FORMULAIRE AUTOMATIQUE SAML HTTP-POST BINDING -->
        <form id="samlForm" method="POST" action="dashboard.php" class="space-y-3">
            <input type="hidden" name="SAMLResponse" value="<?= $samlBase64 ?>">
            <input type="hidden" name="saml_xml" value="<?= htmlspecialchars($samlXml) ?>">

            <button type="submit" class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs py-3 px-4 rounded-xl shadow transition">
                Transférer l'Assertion vers EduNotes →
            </button>
        </form>

        <!-- ROUTAGE ALTERNATIF VERS LA CONSOLE ATTAQUANT (SI PAS DE BURP SUITE) -->
        <div class="pt-4 border-t border-slate-100">
            <a href="attacker/index.php" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center justify-center gap-1">
                <span>🕵️</span> Intercepter dans la Console Attaquant (MitM) →
            </a>
        </div>
    </div>

    <script>
        // Si aucun paramètre manuel, soumettre après un court délai pour laisser le temps de voir
        // ou permettre l'interception Burp Suite
    </script>
</body>
</html>
