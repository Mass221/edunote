<?php
// dashboard.php - PAGE 3 : Tableau de Bord & Traitement SAML (Failles SAML & XXE)
require_once __DIR__ . '/config.php';

$authError = null;
$xxePayloadDetected = null;

// 1. Traitement d'une assertion SAML POSTée (depuis idp.php ou Burp Suite)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['SAMLResponse']) || isset($_POST['saml_xml']))) {
    $rawXml = '';
    if (!empty($_POST['saml_xml'])) {
        $rawXml = trim($_POST['saml_xml']);
    } elseif (!empty($_POST['SAMLResponse'])) {
        $rawXml = base64_decode($_POST['SAMLResponse']);
    }

    $isVuln = is_vulnerable_mode();
    $result = validate_saml_assertion($rawXml, $isVuln);

    if (!$result['success']) {
        // Enregistrement de l'attaque bloquée dans le journal
        log_security_event('SAML_AUTH_REJECTED', 'BLOCKED', $result['message']);
        $authError = $result;
    } else {
        // Authentification réussie
        $sessionUser = [
            'email' => $result['email'],
            'role' => $result['role'],
            'name' => $result['name'],
            'student_id' => $result['student_id'] ?? '',
            'is_impostor' => ($result['email'] === 'etudiant@univ.com' && $result['role'] === 'teacher'),
        ];
        $_SESSION['user'] = $sessionUser;
        if (!empty($result['xxe_content'])) {
            $xxePayloadDetected = $result['xxe_content'];
            $_SESSION['last_xxe'] = $xxePayloadDetected;
        }

        log_security_event(
            $sessionUser['is_impostor'] ? 'PRIVILEGE_ESCALATION_SUCCESS' : 'USER_LOGIN',
            $sessionUser['is_impostor'] ? 'VULNERABLE' : 'SUCCESS',
            $sessionUser['is_impostor'] 
                ? "USURPATION RÉUSSIE : L'étudiant {$result['email']} a obtenu le rôle teacher !"
                : "Connexion réussie de {$result['name']} avec le rôle {$result['role']}."
        );
    }
}

// 2. Si un rejet de sécurité 403 s'est produit (Mode Sécurisé actif)
if ($authError) {
    http_response_code(403);
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="max-w-3xl mx-auto my-8">
        <div class="bg-white rounded-2xl border-2 border-red-500 shadow-2xl overflow-hidden">
            <div class="bg-red-600 text-white p-6 flex items-center gap-4">
                <span class="text-4xl">⛔</span>
                <div>
                    <h1 class="text-xl font-black">ACCÈS REFUSÉ – ERREUR 403 FORBIDDEN</h1>
                    <p class="text-red-100 text-xs mt-0.5 font-medium">Contrôle de sécurité du Service Provider EduNotes</p>
                </div>
            </div>

            <div class="p-6 sm:p-8 space-y-6">
                <div class="bg-red-50 border-l-4 border-red-600 p-4 rounded-r-lg">
                    <div class="font-bold text-red-900 text-sm">
                        <?= htmlspecialchars($authError['message']) ?>
                    </div>
                    <?php if (isset($authError['claimed_digest'])): ?>
                    <div class="mt-3 font-mono text-xs text-red-700 bg-white p-3 rounded border border-red-200 space-y-1">
                        <div><strong>Digest attendu (signé) :</strong> <?= htmlspecialchars($authError['claimed_digest']) ?></div>
                        <div><strong>Digest recalculé (C14N) :</strong> <?= htmlspecialchars($authError['calculated_digest']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs text-slate-600 space-y-2">
                    <p class="font-bold text-slate-800">💡 Ce que cette défense démontre au jury :</p>
                    <p>
                        En <strong>Mode Sécurisé</strong>, le serveur EduNotes recalcule l'empreinte SHA-256 canonique (C14N) du document 
                        et vérifie la signature cryptographique X.509 de l'IdP. Toute altération en transit (comme le passage de 
                        <code>role=student</code> à <code>role=teacher</code>) est immédiatement bloquée.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <a href="index.php" class="bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs px-5 py-2.5 rounded-lg transition">
                        ← Retour à l'accueil
                    </a>
                    <a href="toggle_mode.php" class="bg-red-600 hover:bg-red-700 text-white font-bold text-xs px-5 py-2.5 rounded-lg transition">
                        ⚠️ Rejouer l'attaque en Mode Vulnérable
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// 3. Vérification de session
$currentUser = get_current_user_session();
if (!$currentUser) {
    header("Location: index.php");
    exit;
}

$db = load_db();
$isTeacher = ($currentUser['role'] === 'teacher');
$isStudent = ($currentUser['role'] === 'student');
$lastXxe = $_SESSION['last_xxe'] ?? null;

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-8">

    <!-- 1. ALERTE SPÉCIALE EN CAS D'USURPATION DE RÔLE RÉUSSIE (ATTAQUE 1) -->
    <?php if (!empty($currentUser['is_impostor'])): ?>
    <div class="bg-amber-500 text-slate-950 p-6 rounded-2xl shadow-xl border-4 border-amber-600 animate-pulse-slow">
        <div class="flex items-start gap-4">
            <span class="text-4xl">🚨</span>
            <div>
                <span class="bg-slate-950 text-amber-400 font-black text-xs uppercase px-2.5 py-1 rounded">
                    ATTAQUE 1 RÉUSSIE · USURPATION SAML
                </span>
                <h2 class="text-xl font-black mt-2 leading-tight">
                    Élévation de Privilèges : L'étudiant a obtenu le rôle Enseignant !
                </h2>
                <p class="text-slate-900 text-xs mt-1.5 leading-relaxed">
                    L'assertion SAML de <strong>Mamadou Sow (etudiant@univ.com)</strong> a été modifiée en transit avec <code>role=teacher</code>. 
                    Comme le serveur tourne en <strong>Mode Vulnérable</strong>, la signature n'a pas été contrôlée. 
                    L'étudiant a désormais accès au carnet de notes de tous les élèves de l'université !
                </p>
                <div class="mt-4 flex gap-2">
                    <a href="notes.php" class="bg-slate-950 text-white font-bold text-xs px-4 py-2 rounded-lg hover:bg-slate-800 transition">
                        Voir les notes de tous les étudiants →
                    </a>
                    <a href="toggle_mode.php" class="bg-white text-slate-950 font-bold text-xs px-4 py-2 rounded-lg hover:bg-slate-100 transition">
                        🛡️ Activer la Défense (Mode Sécurisé)
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 2. ALERTE SPÉCIALE EN CAS D'ATTAQUE XXE RÉUSSIE (ATTAQUE 2) -->
    <?php if ($lastXxe): ?>
    <div class="bg-rose-950 text-white p-6 rounded-2xl shadow-xl border-4 border-rose-600">
        <div class="flex items-start gap-4">
            <span class="text-4xl">💥</span>
            <div>
                <span class="bg-rose-600 text-white font-black text-xs uppercase px-2.5 py-1 rounded">
                    ATTAQUE 2 RÉUSSIE · EXFILTRATION XXE
                </span>
                <h2 class="text-xl font-black mt-2 leading-tight">
                    Fuite de Fichier Système / Confidentiel réussie !
                </h2>
                <p class="text-rose-200 text-xs mt-1.5">
                    Le parseur XML a résolu l'entité externe <code>&lt;!ENTITY xxe SYSTEM "..."&gt;</code>. Voici le contenu extrait du serveur :
                </p>
                <pre class="mt-3 bg-black text-emerald-400 p-4 rounded-xl font-mono text-xs overflow-x-auto border border-rose-800 shadow-inner leading-relaxed"><?= htmlspecialchars($lastXxe) ?></pre>
                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="mt-3 text-xs text-rose-300 hover:text-white underline">
                    Fermer cette notification
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 3. EN-TÊTE DU TABLEAU DE BORD SELON LE RÔLE -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="text-xs font-black uppercase tracking-wider <?= $isTeacher ? 'text-amber-600' : 'text-blue-600' ?>">
                <?= $isTeacher ? 'Espace Pédagogique (Enseignant)' : 'Espace Étudiant' ?>
            </div>
            <h1 class="text-2xl font-black text-slate-900 mt-1">
                Bienvenue, <?= htmlspecialchars($currentUser['name']) ?> !
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Compte : <strong><?= htmlspecialchars($currentUser['email']) ?></strong> • Rôle actif : <span class="font-mono font-bold"><?= htmlspecialchars($currentUser['role']) ?></span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="notes.php" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow transition flex items-center gap-1.5">
                <span>📊</span> <?= $isTeacher ? 'Gérer les notes' : 'Mes notes et relevé' ?>
            </a>
            <a href="index.php?action=logout" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-3 py-2.5 rounded-xl transition">
                Déconnexion
            </a>
        </div>
    </div>

    <!-- 4. APERÇU DES NOTES EN BASE -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-black text-slate-900 text-base">Carnet de Notes Officiel (Sécurité Informatique)</h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    <?= $isTeacher ? 'Accès complet en lecture et écriture sur la promotion L2' : 'Consultation personnelle de vos résultats' ?>
                </p>
            </div>
            <span class="text-xs bg-slate-100 font-mono text-slate-600 px-3 py-1 rounded-full border border-slate-200">
                <?= count($db['grades']) ?> notes enregistrées
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-4">Numéro Étudiant</th>
                        <th class="p-4">Nom et Prénom</th>
                        <th class="p-4">Matière</th>
                        <th class="p-4 text-center">Note / 20</th>
                        <th class="p-4">Observation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php 
                    foreach ($db['grades'] as $g): 
                        // L'étudiant ne voit que ses notes s'il n'est pas enseignant
                        if ($isStudent && $g['student_id'] !== $currentUser['student_id'] && empty($currentUser['is_impostor'])) {
                            continue;
                        }
                        $gradeBadge = $g['grade'] >= 10 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200';
                    ?>
                    <tr class="hover:bg-slate-50 transition <?= ($g['student_id'] === 'ETUD-001') ? 'bg-amber-50/50' : '' ?>">
                        <td class="p-4 font-mono font-bold text-slate-600"><?= htmlspecialchars($g['student_id']) ?></td>
                        <td class="p-4 font-bold text-slate-900">
                            <?= htmlspecialchars($g['name']) ?>
                            <?php if ($g['student_id'] === 'ETUD-001'): ?>
                                <span class="ml-2 text-[10px] bg-amber-200 text-amber-900 px-2 py-0.5 rounded font-black">Cible Démo</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-slate-600"><?= htmlspecialchars($g['course']) ?></td>
                        <td class="p-4 text-center">
                            <span class="font-mono font-black text-sm px-3 py-1 rounded-full border <?= $gradeBadge ?>">
                                <?= number_format($g['grade'], 1) ?> / 20
                            </span>
                        </td>
                        <td class="p-4 text-slate-500"><?= htmlspecialchars($g['comment']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5. JOURNAL D'AUDIT DES ÉVÉNEMENTS DE SÉCURITÉ (EF-09) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-black text-slate-900 text-base flex items-center gap-2">
                <span>📋</span> Journal d'Audit & Événements de Sécurité (EF-09)
            </h3>
            <span class="text-xs text-slate-400">Traces en direct des attaques et rejets</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-bold">
                    <tr>
                        <th class="p-2.5">Horodatage</th>
                        <th class="p-2.5">Événement</th>
                        <th class="p-2.5">Mode</th>
                        <th class="p-2.5">Statut</th>
                        <th class="p-2.5">Détails</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php 
                    $recentLogs = array_reverse(array_slice($db['logs'] ?? [], -10));
                    if (empty($recentLogs)):
                    ?>
                    <tr>
                        <td colspan="5" class="p-4 text-center text-slate-400 italic">Aucun événement enregistré.</td>
                    </tr>
                    <?php else: foreach ($recentLogs as $log): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="p-2.5 text-slate-500"><?= htmlspecialchars($log['timestamp']) ?></td>
                        <td class="p-2.5 font-bold text-slate-800"><?= htmlspecialchars($log['event']) ?></td>
                        <td class="p-2.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-black <?= $log['mode'] === 'VULNERABLE' ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' ?>">
                                <?= htmlspecialchars($log['mode']) ?>
                            </span>
                        </td>
                        <td class="p-2.5 font-bold <?= $log['status'] === 'SUCCESS' ? 'text-emerald-600' : ($log['status'] === 'BLOCKED' ? 'text-red-600' : 'text-amber-600') ?>">
                            <?= htmlspecialchars($log['status']) ?>
                        </td>
                        <td class="p-2.5 text-slate-600"><?= htmlspecialchars($log['details']) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
