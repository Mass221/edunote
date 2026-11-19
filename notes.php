<?php
// notes.php - PAGE 4 : Consultation des notes & Import/Export de relevé signé (Faille 08 -> 18)
require_once __DIR__ . '/config.php';

$currentUser = get_current_user_session();
if (!$currentUser) {
    header("Location: index.php");
    exit;
}

$db = load_db();
$isTeacher = ($currentUser['role'] === 'teacher');
$isStudent = ($currentUser['role'] === 'student');

// 1. TÉLÉCHARGEMENT D'UN RELEVÉ XML SIGNÉ OFFICIEL (XML-DSig)
if (isset($_GET['download']) && $_GET['download'] === 'xml') {
    $targetStudentId = $_GET['student_id'] ?? ($isStudent ? $currentUser['student_id'] : 'ETUD-001');
    $studentData = null;
    $gradeData = null;

    foreach ($db['users'] as $u) {
        if (($u['student_id'] ?? '') === $targetStudentId) {
            $studentData = $u;
            break;
        }
    }
    foreach ($db['grades'] as $g) {
        if ($g['student_id'] === $targetStudentId) {
            $gradeData = $g;
            break;
        }
    }

    if ($studentData && $gradeData) {
        $signedXml = generate_signed_transcript($studentData, $gradeData);
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="releve_' . $targetStudentId . '_signe.xml"');
        echo $signedXml;
        exit;
    }
}

// 2. TRAITEMENT DE L'IMPORT D'UN RELEVÉ XML (SC-01 : FALSIFICATION DE NOTE 08 -> 18)
$importResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_transcript'])) {
    $xmlContent = '';
    if (!empty($_FILES['xml_file']['tmp_name'])) {
        $xmlContent = file_get_contents($_FILES['xml_file']['tmp_name']);
    } elseif (!empty($_POST['xml_text'])) {
        $xmlContent = trim($_POST['xml_text']);
    }

    if (!empty($xmlContent)) {
        $isVuln = is_vulnerable_mode();
        $importResult = verify_imported_transcript($xmlContent, $isVuln);

        if ($importResult['success']) {
            // Mise à jour de la note en base !
            $studentId = $importResult['student_id'] ?: 'ETUD-001';
            $newGrade = $importResult['grade'];
            update_student_grade($studentId, $newGrade);
            $db = load_db(); // recharger la base
            log_security_event('TRANSCRIPT_IMPORT_SUCCESS', 'SUCCESS', $importResult['message']);
        } else {
            log_security_event('TRANSCRIPT_TAMPERING_REJECTED', 'BLOCKED', $importResult['message']);
        }
    }
}

// Générer le relevé officiel de Mamadou Sow pour l'éditeur
$mamadouUser = ['name' => 'Mamadou Sow', 'email' => 'etudiant@univ.com', 'student_id' => 'ETUD-001'];
$mamadouGrade = null;
foreach ($db['grades'] as $g) {
    if ($g['student_id'] === 'ETUD-001') {
        $mamadouGrade = $g;
        break;
    }
}
$sampleSignedXml = generate_signed_transcript($mamadouUser, $mamadouGrade ?: ['grade' => 8.0, 'course' => 'Sécurité Informatique', 'coefficient' => 2, 'comment' => 'Insuffisant']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-8">

    <!-- EN-TÊTE DE LA PAGE -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <span class="text-xs font-black uppercase tracking-wider text-slate-500">Service Scolarité UIDT</span>
            <h1 class="text-2xl font-black text-slate-900 mt-1">Relevés de Notes & Signature Numérique</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Authentification et intégrité garanties par la norme W3C <strong>XML-DSig</strong>.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="notes.php?download=xml&student_id=ETUD-001" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2 rounded-xl shadow transition flex items-center gap-1.5">
                <span>📥</span> Télécharger Relevé Signé (Mamadou Sow)
            </a>
        </div>
    </div>

    <!-- RÉSULTAT DU DERNIER TEST D'IMPORT -->
    <?php if ($importResult): ?>
        <?php if ($importResult['success']): ?>
            <div class="bg-emerald-50 border-2 border-emerald-500 p-6 rounded-2xl shadow-lg">
                <div class="flex items-start gap-4">
                    <span class="text-3xl">✅</span>
                    <div>
                        <span class="bg-emerald-600 text-white font-black text-[10px] uppercase px-2 py-0.5 rounded">
                            <?= $importResult['is_vulnerable'] ? 'MODE VULNÉRABLE · SIGNATURE IGNORÉE' : 'MODE SÉCURISÉ · SIGNATURE VÉRIFIÉE' ?>
                        </span>
                        <h3 class="text-lg font-black text-emerald-950 mt-1">Relevé de notes accepté !</h3>
                        <p class="text-xs text-emerald-800 mt-1"><?= htmlspecialchars($importResult['message']) ?></p>
                        <div class="mt-3 text-xs bg-white p-3 rounded-lg border border-emerald-200 font-mono text-emerald-900">
                            <strong>Note enregistrée en base :</strong> <?= number_format($importResult['grade'], 1) ?> / 20 pour <?= htmlspecialchars($importResult['student_id']) ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-red-50 border-2 border-red-500 p-6 rounded-2xl shadow-lg">
                <div class="flex items-start gap-4">
                    <span class="text-3xl">❌</span>
                    <div>
                        <span class="bg-red-600 text-white font-black text-[10px] uppercase px-2 py-0.5 rounded">
                            REJET D'INTÉGRITÉ XML-DSIG (MODE SÉCURISÉ)
                        </span>
                        <h3 class="text-lg font-black text-red-950 mt-1">Importation rejetée !</h3>
                        <p class="text-xs text-red-800 mt-1"><?= htmlspecialchars($importResult['message']) ?></p>
                        <?php if (isset($importResult['claimed_digest'])): ?>
                        <div class="mt-3 text-xs bg-white p-3 rounded-lg border border-red-200 font-mono text-red-900 space-y-1">
                            <div><strong>Digest attendu (signé) :</strong> <?= htmlspecialchars($importResult['claimed_digest']) ?></div>
                            <div><strong>Digest recalculé (C14N) :</strong> <?= htmlspecialchars($importResult['calculated_digest']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- TABLEAU DES NOTES ACTUELLES EN BASE -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="font-black text-slate-900 text-base">État Actuel de la Base de Données (data/db.json)</h2>
                <p class="text-xs text-slate-500 mt-0.5">Note persistée en direct après chaque test</p>
            </div>
            <a href="reset.php" class="text-xs bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 font-bold px-3 py-1.5 rounded-lg transition">
                🔄 Remettre Mamadou Sow à 08/20
            </a>
        </div>

        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-bold border-b border-slate-200">
                <tr>
                    <th class="p-4">Étudiant</th>
                    <th class="p-4">Matière</th>
                    <th class="p-4 text-center">Note Persistée</th>
                    <th class="p-4">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($db['grades'] as $g): ?>
                <tr class="<?= $g['student_id'] === 'ETUD-001' ? 'bg-amber-50/70 font-bold' : '' ?>">
                    <td class="p-4">
                        <div class="text-slate-900"><?= htmlspecialchars($g['name']) ?></div>
                        <div class="text-[10px] text-slate-500 font-mono"><?= htmlspecialchars($g['student_id']) ?></div>
                    </td>
                    <td class="p-4 text-slate-600"><?= htmlspecialchars($g['course']) ?></td>
                    <td class="p-4 text-center">
                        <span class="text-sm font-mono font-black px-3 py-1 rounded-full border <?= $g['grade'] >= 10 ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-red-100 text-red-800 border-red-300' ?>">
                            <?= number_format($g['grade'], 1) ?> / 20
                        </span>
                    </td>
                    <td class="p-4">
                        <a href="notes.php?download=xml&student_id=<?= urlencode($g['student_id']) ?>" class="text-blue-600 hover:underline font-semibold">
                            Télécharger XML signé
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- DÉMONSTRATION EN DIRECT SC-01 : FALSIFICATION DE RELEVÉ (DIAPO 7) -->
    <div class="bg-white rounded-2xl border-2 border-indigo-200 shadow-md p-6 sm:p-8 space-y-6">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <span class="bg-indigo-600 text-white font-black text-xs uppercase px-2.5 py-1 rounded">
                    Démonstration SC-01 (Diapo 7)
                </span>
                <h3 class="text-xl font-black text-slate-900 mt-2">
                    Studio d'Import & Falsification de Relevé en Live
                </h3>
                <p class="text-xs text-slate-600 mt-1 max-w-2xl">
                    Le document ci-dessous est le relevé officiel signé numériquement par le professeur avec sa clé privée RSA. 
                    Vous pouvez modifier la note <strong>08.0</strong> en <strong>18.0</strong> directement au clavier, puis cliquer sur 
                    « Importer et Valider » pour observer la différence entre Mode Vulnérable et Mode Sécurisé.
                </p>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="tamperGrade18()" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs px-3.5 py-2 rounded-xl shadow transition flex items-center gap-1.5">
                    <span>⚡</span> Remplacer 08.0 par 18.0
                </button>
                <button type="button" onclick="restoreSignedXml()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-3.5 py-2 rounded-xl border border-slate-300 transition">
                    <span>🔄</span> Restaurer 08.0
                </button>
            </div>
        </div>

        <form action="notes.php" method="POST" class="space-y-4">
            <input type="hidden" name="import_transcript" value="1">
            
            <div>
                <label for="xml_text" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                    Contenu du Relevé XML Signé (Modifiable en direct) :
                </label>
                <textarea id="xml_text" name="xml_text" rows="14" class="w-full font-mono text-xs bg-slate-950 text-amber-300 p-4 rounded-xl border border-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-inner leading-relaxed"><?= htmlspecialchars($sampleSignedXml) ?></textarea>
            </div>

            <div class="flex items-center justify-between flex-wrap gap-4 pt-2">
                <div class="text-xs text-slate-500">
                    Mode actif : <strong><?= is_vulnerable_mode() ? '⚠️ Vulnérable (la note falsifiée sera acceptée)' : '🛡️ Sécurisé (rejet d\'intégrité garanti)' ?></strong>
                </div>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs uppercase px-8 py-3 rounded-xl shadow-lg transition transform hover:scale-[1.02] flex items-center gap-2">
                    <span>📥</span> Importer et Valider le Relevé en Direct
                </button>
            </div>
        </form>
    </div>

</div>

<script>
    const originalSampleXml = <?= json_encode($sampleSignedXml) ?>;

    function tamperGrade18() {
        let textarea = document.getElementById('xml_text');
        let val = textarea.value;
        if (val.includes('valeur="8.0"') || val.includes('valeur="08.0"') || val.includes('valeur="8"')) {
            val = val.replace(/valeur="0?8\.?0?"/, 'valeur="18.0"');
            textarea.value = val;
            alert("⚡ Note modifiée de 08 à 18.0 !\nLa balise <ds:SignatureValue> n'a pas été recalculée.");
        } else {
            alert("La note 08 n'a pas été trouvée ou a déjà été modifiée.");
        }
    }

    function restoreSignedXml() {
        document.getElementById('xml_text').value = originalSampleXml;
        alert("🔄 Relevé officiel restauré avec la note originale (08/20).");
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
