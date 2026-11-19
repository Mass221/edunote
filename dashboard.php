<?php
// dashboard.php - Tableau de Bord Officiel EduNotes (UIDT)
require_once __DIR__ . '/config.php';

$authError = null;
$xxePayloadDetected = null;

// 1. Réception et traitement de l'assertion SAML POSTée
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
        log_security_event('SAML_AUTH_REJECTED', 'BLOCKED', $result['message']);
        $authError = $result;
    } else {
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

// 2. Si une erreur de sécurité survient (Rejet 403 officiel)
if ($authError) {
    http_response_code(403);
    require_once __DIR__ . '/includes/header_portal.php';
    ?>
    <div class="max-w-2xl mx-auto my-12">
        <div class="bg-white rounded-3xl border border-red-200 shadow-xl overflow-hidden text-center p-8 sm:p-10 space-y-6">
            <div class="w-16 h-16 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center text-3xl mx-auto">
                ⛔
            </div>

            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-red-700 bg-red-50 px-3 py-1 rounded-full border border-red-200">
                    Sécurité des Données · Erreur 403
                </span>
                <h1 class="text-2xl font-black text-slate-900 mt-3">Accès Refusé – Signature Invalide</h1>
                <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                    Le serveur EduNotes n'a pas pu authentifier l'assertion SAML 2.0. Le document a été altéré en transit ou la signature cryptographique ne correspond pas à la clé publique de l'IdP.
                </p>
            </div>

            <div class="bg-slate-50 border border-slate-200 p-4 rounded-xl text-left text-xs font-mono text-slate-700 space-y-1">
                <div><strong>Cause du rejet :</strong> <?= htmlspecialchars($authError['message']) ?></div>
                <?php if (isset($authError['claimed_digest'])): ?>
                <div><strong>Digest revendiqué :</strong> <?= htmlspecialchars($authError['claimed_digest']) ?></div>
                <div><strong>Digest recalculé :</strong> <?= htmlspecialchars($authError['calculated_digest']) ?></div>
                <?php endif; ?>
            </div>

            <div class="pt-2">
                <a href="index.php" class="bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-6 py-3 rounded-xl transition">
                    ← Retourner au portail d'accueil
                </a>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer_portal.php';
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

require_once __DIR__ . '/includes/header_portal.php';
?>

<div class="max-w-5xl mx-auto space-y-8">

    <!-- ALERTE PÉDAGOGIQUE SI L'ÉTUDIANT A RÉUSSI L'USURPATION -->
    <?php if (!empty($currentUser['is_impostor'])): ?>
    <div class="bg-amber-500 text-slate-950 p-6 rounded-2xl shadow-xl border-2 border-amber-600">
        <div class="flex items-start gap-4">
            <span class="text-3xl">🚨</span>
            <div>
                <span class="bg-slate-950 text-amber-400 font-black text-[10px] uppercase px-2 py-0.5 rounded">
                    IMPACT DE L'ATTAQUE 1 CONSTATÉ SUR LA VICTIME
                </span>
                <h3 class="text-lg font-black mt-1">Élévation de Privilèges Réussie !</h3>
                <p class="text-xs text-slate-950 mt-1 leading-relaxed">
                    L'étudiant <strong>Mamadou Sow</strong> a accédé à l'Espace Enseignant avec les droits complets sur les délibérations, car le serveur en mode non durci a lu le rôle sans valider la signature de l'IdP.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ALERTE PÉDAGOGIQUE SI UNE FUITE XXE EST SURVENUE -->
    <?php if ($lastXxe): ?>
    <div class="bg-slate-900 text-white p-6 rounded-2xl shadow-xl border border-slate-800">
        <div class="flex items-start gap-4">
            <span class="text-3xl">💥</span>
            <div>
                <span class="bg-rose-600 text-white font-black text-[10px] uppercase px-2 py-0.5 rounded">
                    IMPACT DE L'ATTAQUE 2 (XXE)
                </span>
                <h3 class="text-lg font-black mt-1">Fichier Serveur Exfiltré dans la Réponse !</h3>
                <pre class="mt-3 bg-black text-emerald-400 p-4 rounded-xl font-mono text-xs overflow-x-auto border border-slate-800"><?= htmlspecialchars($lastXxe) ?></pre>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- EN-TÊTE DU TABLEAU DE BORD OFFICIEL -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="text-xs font-black uppercase tracking-wider text-blue-700">
                <?= $isTeacher ? 'Espace Enseignant · Délibérations' : 'Espace Étudiant · Licence 2 Informatique' ?>
            </div>
            <h1 class="text-2xl font-black text-slate-900 mt-1">
                Bienvenue, <?= htmlspecialchars($currentUser['name']) ?>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Identifiant : <span class="font-mono"><?= htmlspecialchars($currentUser['email']) ?></span> · Rôle accordé : <span class="font-bold text-slate-800"><?= htmlspecialchars($currentUser['role']) ?></span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="notes.php" class="bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow transition">
                <?= $isTeacher ? 'Gérer les délibérations' : 'Consulter mes notes et relevé' ?> →
            </a>
        </div>
    </div>

    <!-- CARNET DE NOTES ACADÉMIQUE -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="font-black text-slate-900 text-base">Carnet d'Évaluation – Sécurité Informatique</h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    <?= $isTeacher ? 'Affichage complet des notes de la promotion (Droits Enseignant)' : 'Vos résultats académiques personnels' ?>
                </p>
            </div>
            <span class="text-xs bg-slate-100 font-mono text-slate-600 px-3 py-1 rounded-full border border-slate-200">
                Semestre 3 · 2026/2027
            </span>
        </div>

        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-bold border-b border-slate-200">
                <tr>
                    <th class="p-4">N° Étudiant</th>
                    <th class="p-4">Nom et Prénom</th>
                    <th class="p-4">Unité d'Enseignement</th>
                    <th class="p-4 text-center">Note / 20</th>
                    <th class="p-4">Appréciation</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php 
                foreach ($db['grades'] as $g): 
                    if ($isStudent && $g['student_id'] !== $currentUser['student_id'] && empty($currentUser['is_impostor'])) {
                        continue;
                    }
                ?>
                <tr class="hover:bg-slate-50 transition <?= $g['student_id'] === 'ETUD-001' ? 'bg-amber-50/50' : '' ?>">
                    <td class="p-4 font-mono font-bold text-slate-600"><?= htmlspecialchars($g['student_id']) ?></td>
                    <td class="p-4 font-bold text-slate-900"><?= htmlspecialchars($g['name']) ?></td>
                    <td class="p-4 text-slate-600"><?= htmlspecialchars($g['course']) ?></td>
                    <td class="p-4 text-center">
                        <span class="font-mono font-black text-sm px-3 py-1 rounded-full border <?= $g['grade'] >= 10 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
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

<?php require_once __DIR__ . '/includes/footer_portal.php'; ?>
