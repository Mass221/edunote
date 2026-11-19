<?php
// notes.php - Page Officielle des Notes & Téléversement de Relevé (UIDT)
require_once __DIR__ . '/config.php';

$currentUser = get_current_user_session();
if (!$currentUser) {
    header("Location: index.php");
    exit;
}

$db = load_db();
$isTeacher = ($currentUser['role'] === 'teacher');
$isStudent = ($currentUser['role'] === 'student');

// 1. TÉLÉCHARGEMENT DU RELEVÉ XML SIGNÉ OFFICIEL (XML-DSig)
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

// 2. TRAITEMENT DU TÉLÉVERSEMENT DE RELEVÉ XML
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

require_once __DIR__ . '/includes/header_portal.php';
?>

<div class="max-w-5xl mx-auto space-y-8">

    <!-- EN-TÊTE DE LA PAGE -->
    <div class="flex items-center justify-between flex-wrap gap-4 border-b border-slate-200 pb-6">
        <div>
            <span class="text-[11px] font-black uppercase tracking-wider text-blue-700 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">
                Service de la Scolarité Centrale · UIDT
            </span>
            <h1 class="text-2xl font-black text-slate-900 mt-2">Relevés de Notes & Délibérations</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Certification numérique et intégrité conformes aux spécifications W3C XML-DSig.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="notes.php?download=xml&student_id=ETUD-001" class="bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow transition flex items-center gap-1.5">
                <span>📥</span> Télécharger Relevé Officiel Signé (XML)
            </a>
        </div>
    </div>

    <!-- RÉSULTAT DU TÉLÉVERSEMENT (SI EFFECTUÉ) -->
    <?php if ($importResult): ?>
        <?php if ($importResult['success']): ?>
            <div class="bg-emerald-50 border border-emerald-400 p-6 rounded-2xl shadow-sm text-xs text-emerald-900 space-y-2">
                <div class="flex items-center gap-2 font-bold text-sm text-emerald-950">
                    <span>✅</span> Relevé importé et validé avec succès
                </div>
                <p><?= htmlspecialchars($importResult['message']) ?></p>
                <div class="font-mono bg-white p-2.5 rounded border border-emerald-200 mt-2">
                    Note enregistrée en base : <strong><?= number_format($importResult['grade'], 1) ?> / 20</strong> pour <?= htmlspecialchars($importResult['student_id']) ?>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-red-50 border border-red-400 p-6 rounded-2xl shadow-sm text-xs text-red-900 space-y-2">
                <div class="flex items-center gap-2 font-bold text-sm text-red-950">
                    <span>❌</span> Échec d'intégrité – Fichier rejeté
                </div>
                <p><?= htmlspecialchars($importResult['message']) ?></p>
                <?php if (isset($importResult['claimed_digest'])): ?>
                <div class="font-mono bg-white p-2.5 rounded border border-red-200 mt-2 space-y-1">
                    <div>Digest attendu : <?= htmlspecialchars($importResult['claimed_digest']) ?></div>
                    <div>Digest recalculé : <?= htmlspecialchars($importResult['calculated_digest']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- TABLEAU DES NOTES EN BASE -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-black text-slate-900 text-base">Notes Délibérées en Base de Données</h2>
            <span class="text-xs text-slate-400">UE Sécurité Informatique</span>
        </div>

        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-bold border-b border-slate-200">
                <tr>
                    <th class="p-4">N° Étudiant</th>
                    <th class="p-4">Nom et Prénom</th>
                    <th class="p-4">Matière</th>
                    <th class="p-4 text-center">Note Persistée</th>
                    <th class="p-4">Relevé Numérique</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($db['grades'] as $g): ?>
                <tr class="<?= $g['student_id'] === 'ETUD-001' ? 'bg-amber-50/50' : '' ?>">
                    <td class="p-4 font-mono font-bold text-slate-600"><?= htmlspecialchars($g['student_id']) ?></td>
                    <td class="p-4 font-bold text-slate-900"><?= htmlspecialchars($g['name']) ?></td>
                    <td class="p-4 text-slate-600"><?= htmlspecialchars($g['course']) ?></td>
                    <td class="p-4 text-center">
                        <span class="text-sm font-mono font-black px-3 py-1 rounded-full border <?= $g['grade'] >= 10 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
                            <?= number_format($g['grade'], 1) ?> / 20
                        </span>
                    </td>
                    <td class="p-4">
                        <a href="notes.php?download=xml&student_id=<?= urlencode($g['student_id']) ?>" class="text-blue-700 hover:underline font-semibold flex items-center gap-1">
                            <span>📄</span> Télécharger .xml signé
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- FORMULAIRE INSTITUTIONNEL D'IMPORT DE FICHIER XML -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <div>
            <h3 class="text-base font-black text-slate-900">
                Téléverser un Fichier de Délibération (XML Signé)
            </h3>
            <p class="text-xs text-slate-500 mt-1">
                Ce module permet d'importer une délibération ou un relevé officiel exporté depuis le logiciel de scolarité. Le fichier doit être signé numériquement par l'émetteur (enseignant ou scolarité centrale).
            </p>
        </div>

        <form action="notes.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="import_transcript" value="1">

            <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center bg-slate-50 hover:bg-slate-100 transition">
                <input type="file" name="xml_file" id="xml_file" accept=".xml" class="hidden" onchange="document.getElementById('fileName').innerText = this.files[0].name">
                <label for="xml_file" class="cursor-pointer space-y-2 block">
                    <div class="text-3xl">📁</div>
                    <div class="text-xs font-bold text-slate-700">Sélectionner un fichier relevé XML sur votre ordinateur</div>
                    <div id="fileName" class="text-xs font-mono text-blue-700 font-bold">Aucun fichier sélectionné</div>
                </label>
            </div>

            <div class="flex items-center justify-between flex-wrap gap-4 pt-2">
                <div class="text-xs text-slate-400">
                    Format accepté : XML conforme au schéma XML-DSig RSA-SHA256
                </div>
                <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase px-6 py-3 rounded-xl shadow transition">
                    Valider et Intégrer le Relevé →
                </button>
            </div>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer_portal.php'; ?>
