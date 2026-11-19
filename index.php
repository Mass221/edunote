<?php
// index.php - PAGE 1 : Accueil et Authentification (Classique & SSO SAML 2.0)
require_once __DIR__ . '/config.php';

// Déconnexion
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['user']);
    $_SESSION['flash_message'] = "Vous avez été déconnecté avec succès.";
    $_SESSION['flash_type'] = "info";
    header("Location: index.php");
    exit;
}

// Connexion classique locale
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
        $loginError = "Identifiants invalides (essayez etudiant@univ.com / pass ou prof@univ.com / pass).";
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-8">

    <!-- PRÉSENTATION & OBJECTIFS DU PROJET -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200">
        <div class="flex items-start gap-4">
            <div class="text-4xl p-3 bg-blue-50 text-blue-600 rounded-2xl hidden sm:block">🏛️</div>
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Plateforme Académique EduNotes</h1>
                <p class="text-slate-600 text-sm mt-1 leading-relaxed">
                    Banc d'essai de démonstration pour le cours de <strong>Sécurité XML</strong> (L2 Informatique · UIDT). 
                    Cette plateforme illustre l'authentification unique <strong>SAML 2.0</strong>, la signature numérique <strong>XML-DSig</strong>, 
                    et l'impact des vulnérabilités applicatives (usurpation d'identité et injection <strong>XXE</strong>).
                </p>
                <div class="mt-4 flex flex-wrap gap-2 text-xs">
                    <span class="bg-indigo-50 text-indigo-700 font-bold px-3 py-1 rounded-full border border-indigo-100">
                        🔑 SSO SAML 2.0
                    </span>
                    <span class="bg-amber-50 text-amber-700 font-bold px-3 py-1 rounded-full border border-amber-100">
                        ✍️ Signature XML-DSig (SHA-256)
                    </span>
                    <span class="bg-rose-50 text-rose-700 font-bold px-3 py-1 rounded-full border border-rose-100">
                        💥 Vulnérabilité XXE (XML External Entity)
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- SE CONNECTER VIA SSO SAML (LE SCÉNARIO DU MEMBRE DU GROUPE) -->
    <div class="bg-gradient-to-br from-blue-900 to-indigo-950 rounded-2xl p-6 sm:p-8 text-white shadow-xl">
        <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
            <div>
                <span class="text-xs font-black uppercase tracking-widest text-blue-300 bg-blue-800/60 px-3 py-1 rounded-full">
                    Méthode Officielle
                </span>
                <h2 class="text-xl font-bold mt-2">Connexion SSO SAML 2.0 (IdP Universitaire)</h2>
                <p class="text-blue-200 text-xs mt-1">
                    Le Fournisseur d'Identité génère une assertion XML signée avec votre rôle officiel.
                </p>
            </div>
            <div class="text-3xl">🔐</div>
        </div>

        <form action="idp.php" method="GET" class="space-y-4">
            <div class="bg-white/10 p-4 rounded-xl border border-white/15">
                <label class="block text-xs font-bold uppercase tracking-wider text-blue-200 mb-2">
                    Sélectionner l'utilisateur à authentifier auprès de l'IdP :
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3 bg-white/10 hover:bg-white/15 rounded-lg border border-white/10 cursor-pointer transition">
                        <input type="radio" name="email" value="etudiant@univ.com" checked class="w-4 h-4 text-blue-500">
                        <div>
                            <div class="font-bold text-sm">🎓 Mamadou Sow</div>
                            <div class="text-[11px] text-blue-300">etudiant@univ.com (Rôle: student)</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 bg-white/10 hover:bg-white/15 rounded-lg border border-white/10 cursor-pointer transition">
                        <input type="radio" name="email" value="prof@univ.com" class="w-4 h-4 text-blue-500">
                        <div>
                            <div class="font-bold text-sm">👨‍🏫 Dr. Abdoulaye Diop</div>
                            <div class="text-[11px] text-blue-300">prof@univ.com (Rôle: teacher)</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- OPTION D'INTERCEPTION EN DIRECT DANS LE NAVIGATEUR (POUR LA SOUTENANCE) -->
            <div class="flex items-center gap-2.5 bg-black/20 p-3 rounded-lg border border-white/10">
                <input type="checkbox" name="intercept" id="intercept" value="1" checked class="w-4 h-4 text-indigo-500 rounded focus:ring-0 cursor-pointer">
                <label for="intercept" class="text-xs text-blue-100 cursor-pointer">
                    <strong class="text-white">Interception en direct (MitM simulé) :</strong> 
                    Afficher l'assertion SAML XML dans un éditeur modifiable avant de l'envoyer au serveur. 
                    <span class="text-blue-300 block text-[11px]">Idéal en amphi si vous n'avez pas Burp Suite configuré !</span>
                </label>
            </div>

            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-400 text-white font-extrabold py-3.5 px-6 rounded-xl shadow-lg transition transform hover:scale-[1.01] flex items-center justify-center gap-2 text-sm">
                <span>🚀</span> Se connecter via SSO SAML (IdP Universitaire)
            </button>
        </form>
    </div>

    <!-- GUIDE DES DÉMONSTRATIONS EN LIVE -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-amber-500 font-black text-xs uppercase tracking-wider mb-1">Attaque 1 (Diapo 10)</div>
            <h3 class="font-bold text-slate-900 text-sm">Usurpation SAML</h3>
            <p class="text-slate-500 text-xs mt-1">
                L'étudiant modifie son assertion SAML : <code>role = teacher</code>. En mode vulnérable, la signature n'est pas vérifiée : accès enseignant total !
            </p>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-rose-500 font-black text-xs uppercase tracking-wider mb-1">Attaque 2 (Diapo 12)</div>
            <h3 class="font-bold text-slate-900 text-sm">Injection XXE</h3>
            <p class="text-slate-500 text-xs mt-1">
                On injecte une entité externe <code>&lt;!ENTITY xxe SYSTEM "..."&gt;</code> dans le flux XML pour forcer le serveur à lire un fichier confidentiel.
            </p>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-blue-500 font-black text-xs uppercase tracking-wider mb-1">Attaque 3 (Diapo 7)</div>
            <h3 class="font-bold text-slate-900 text-sm">Falsification de Relevé</h3>
            <p class="text-slate-500 text-xs mt-1">
                Sur <code>notes.php</code>, on modifie la note <strong>08/20</strong> en <strong>18/20</strong> dans le relevé signé. Rejeté en mode sécurisé via SHA-256.
            </p>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
