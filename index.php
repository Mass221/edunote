<?php
// index.php - Portail Officiel EduNotes (Université Iba Der Thiam de Thiès)
require_once __DIR__ . '/config.php';

// Déconnexion
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['user']);
    $_SESSION['flash_message'] = "Vous avez été déconnecté avec succès du portail universitaire.";
    $_SESSION['flash_type'] = "info";
    header("Location: index.php");
    exit;
}

// Connexion locale
$loginError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_local'])) {
    $email = trim($_POST['email'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    $db = load_db();
    $found = null;
    foreach ($db['users'] as $u) {
        if ($u['email'] === $email && $u['password'] === $pass) {
            $found = $u;
            break;
        }
    }
    if ($found) {
        $_SESSION['user'] = $found;
        header("Location: dashboard.php");
        exit;
    } else {
        $loginError = "Identifiants incorrects. Veuillez utiliser vos identifiants institutionnels.";
    }
}

require_once __DIR__ . '/includes/header_portal.php';
?>

<div class="max-w-4xl mx-auto space-y-8">

    <!-- BANNIÈRE DE BIENVENUE INSTITUTIONNELLE -->
    <div class="bg-gradient-to-r from-blue-900 to-indigo-950 text-white rounded-3xl p-8 sm:p-10 shadow-xl relative overflow-hidden">
        <div class="relative z-10 max-w-2xl space-y-3">
            <span class="text-xs font-black uppercase tracking-widest text-blue-300 bg-blue-800/60 px-3.5 py-1 rounded-full border border-blue-400/20">
                Année Universitaire 2026 – 2027
            </span>
            <h1 class="text-3xl font-black tracking-tight leading-tight">
                Portail Pédagogique & Carnet de Notes Numérique
            </h1>
            <p class="text-blue-100 text-xs sm:text-sm leading-relaxed">
                Bienvenue sur la plateforme officielle de consultation des résultats académiques et de délibération des notes de l'<strong>Université Iba Der Thiam de Thiès</strong>.
            </p>
        </div>
        <div class="absolute right-6 -bottom-6 text-9xl opacity-10 select-none hidden md:block">
            🎓
        </div>
    </div>

    <!-- FORMULAIRE DE CONNEXION ENT OFFICIEL & BOUTON SSO SAML -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">

        <!-- BOÎTE DE CONNEXION SSO OFFICIELLE -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Authentification Centralisée</span>
                <h2 class="text-xl font-black text-slate-900 mt-1">Connexion SSO Universitaire</h2>
                <p class="text-xs text-slate-500 mt-1">
                    Connectez-vous via le Fournisseur d'Identité (IdP) officiel avec vos identifiants universitaires (SAML 2.0).
                </p>
            </div>

            <form action="idp.php" method="GET" class="space-y-4">
                <div class="space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Compte institutionnel :
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-blue-500 bg-slate-50 cursor-pointer transition">
                            <input type="radio" name="email" value="etudiant@univ.com" checked class="w-4 h-4 text-blue-600">
                            <div>
                                <div class="font-bold text-xs text-slate-900">Mamadou Sow (Étudiant L2 Info)</div>
                                <div class="text-[11px] text-slate-500 font-mono">etudiant@univ.com · N° ETUD-001</div>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-blue-500 bg-slate-50 cursor-pointer transition">
                            <input type="radio" name="email" value="prof@univ.com" class="w-4 h-4 text-blue-600">
                            <div>
                                <div class="font-bold text-xs text-slate-900">Dr. Abdoulaye Diop (Enseignant)</div>
                                <div class="text-[11px] text-slate-500 font-mono">prof@univ.com · Sécurité Informatique</div>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-700 hover:bg-blue-800 text-white font-black text-xs uppercase tracking-wider py-3.5 px-6 rounded-xl shadow-md transition transform hover:scale-[1.01] flex items-center justify-center gap-2">
                    <span>🔐</span> Se connecter via l'IdP Universitaire (SAML 2.0)
                </button>
            </form>
        </div>

        <!-- INFORMATIONS ET COMPTES PÉDAGOGIQUES -->
        <div class="space-y-4">
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3">
                <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                    <span>🏛️</span> À propos d'EduNotes
                </h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    EduNotes centralise la saisie des notes par le corps professoral et permet aux étudiants de consulter et télécharger leurs relevés officiels signés numériquement via le standard <strong>XML-DSig</strong>.
                </p>
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>UFR Sciences & Technologies</span>
                    <span class="font-mono text-slate-400">UIDT - Thiès</span>
                </div>
            </div>

            <!-- ENCADRÉ SOUTENANCE DISCRET -->
            <div class="bg-slate-100/80 rounded-2xl p-5 border border-slate-200 text-xs text-slate-600 space-y-2">
                <div class="font-bold text-slate-800 flex items-center justify-between">
                    <span>🎯 Démonstrateur Cybersécurité</span>
                    <a href="/attacker/" target="_blank" class="text-indigo-600 font-bold hover:underline">
                        Ouvrir la Console Attaquant ↗
                    </a>
                </div>
                <p class="text-[11px] text-slate-500">
                    Pour simuler l'attaque de l'homme du milieu ou l'injection XXE, ouvrez la <strong>Console de l'Attaquant</strong> dans un onglet séparé.
                </p>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer_portal.php'; ?>
