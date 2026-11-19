<?php
// tests/test_simple.php - Suite de tests automatisés des 3 scénarios (Modes Vulnérable & Sécurisé)
require_once __DIR__ . '/../config.php';

$passed = 0;
$failed = 0;

function assert_test($description, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "  ✅ PASS: $description\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: $description\n";
        $failed++;
    }
}

echo "\n======================================================================\n";
echo "🧪 Lancement des Tests Automatisés – EduNotes (Sécurité XML)\n";
echo "======================================================================\n\n";

$samplesDir = __DIR__ . '/../samples';
$studentXml = file_get_contents("$samplesDir/assertion_student.xml");
$tamperedXml = file_get_contents("$samplesDir/assertion_tampered.xml");
$xxeXml = file_get_contents("$samplesDir/assertion_xxe.xml");
$releve08Xml = file_get_contents("$samplesDir/releve_08.xml");
$releve18Xml = file_get_contents("$samplesDir/releve_18.xml");

// -------------------------------------------------------------
echo "1. Tests de l'Attaque 1 : Usurpation d'Identité SAML (SC-02)\n";
// -------------------------------------------------------------
// A. Assertion normale en mode sécurisé
$resNormalSec = validate_saml_assertion($studentXml, false);
assert_test("Assertion normale validée en Mode Sécurisé", $resNormalSec['success'] === true && $resNormalSec['role'] === 'student');

// B. Assertion modifiée (role=teacher) en mode vulnérable
$resTamperedVuln = validate_saml_assertion($tamperedXml, true);
assert_test("Usurpation réussie en Mode Vulnérable (role=teacher accepté aveuglément)", $resTamperedVuln['success'] === true && $resTamperedVuln['role'] === 'teacher');

// C. Assertion modifiée (role=teacher) en mode sécurisé
$resTamperedSec = validate_saml_assertion($tamperedXml, false);
assert_test("Usurpation bloquée en Mode Sécurisé (Rejet 403 / Échec d'intégrité)", $resTamperedSec['success'] === false && in_array($resTamperedSec['error_type'], ['INTEGRITY_FAILED', 'SIGNATURE_INVALID']));

// -------------------------------------------------------------
echo "\n2. Tests de l'Attaque 2 : Injection XXE (SC-03)\n";
// -------------------------------------------------------------
// A. XXE en mode vulnérable (résolution de secret.txt)
$resXxeVuln = validate_saml_assertion($xxeXml, true);
assert_test("XXE réussi en Mode Vulnérable (Fichier secret exfiltré)", $resXxeVuln['success'] === true && !empty($resXxeVuln['xxe_content']) && strpos($resXxeVuln['xxe_content'], 'FLAG{') !== false);

// B. XXE en mode sécurisé (blocage de la DTD)
$resXxeSec = validate_saml_assertion($xxeXml, false);
assert_test("XXE bloqué en Mode Sécurisé (DTD formellement interdite)", $resXxeSec['success'] === false && $resXxeSec['error_type'] === 'XXE_BLOCKED');

// -------------------------------------------------------------
echo "\n3. Tests de l'Attaque 3 : Falsification de Relevé Signé (SC-01)\n";
// -------------------------------------------------------------
// A. Relevé officiel 08/20 en mode sécurisé
$resRel08Sec = verify_imported_transcript($releve08Xml, false);
assert_test("Relevé officiel 08/20 validé en Mode Sécurisé", $resRel08Sec['success'] === true && $resRel08Sec['grade'] == 8.0);

// B. Relevé falsifié 18/20 en mode vulnérable
$resRel18Vuln = verify_imported_transcript($releve18Xml, true);
assert_test("Relevé falsifié 18/20 accepté en Mode Vulnérable", $resRel18Vuln['success'] === true && $resRel18Vuln['grade'] == 18.0);

// C. Relevé falsifié 18/20 en mode sécurisé
$resRel18Sec = verify_imported_transcript($releve18Xml, false);
assert_test("Relevé falsifié 18/20 rejeté en Mode Sécurisé (Intégrité SHA-256 non conforme)", $resRel18Sec['success'] === false && $resRel18Sec['error_type'] === 'TAMPERED_NOTE');

// -------------------------------------------------------------
echo "\n4. Test de la Réinitialisation en 1 clic (EF-11)\n";
// -------------------------------------------------------------
update_student_grade('ETUD-001', 18.0);
$dbCheck1 = load_db();
$gradeCheck1 = null;
foreach ($dbCheck1['grades'] as $g) {
    if ($g['student_id'] === 'ETUD-001') $gradeCheck1 = $g['grade'];
}
assert_test("Modification physique de la note à 18/20 dans db.json", $gradeCheck1 == 18.0);

reset_db();
$dbCheck2 = load_db();
$gradeCheck2 = null;
foreach ($dbCheck2['grades'] as $g) {
    if ($g['student_id'] === 'ETUD-001') $gradeCheck2 = $g['grade'];
}
assert_test("Retour automatique de la note à 08/20 après reset", $gradeCheck2 == 8.0);

echo "\n======================================================================\n";
echo "📊 Résultat : $passed passés, $failed échoués sur " . ($passed + $failed) . " tests.\n";
echo "======================================================================\n\n";

exit($failed > 0 ? 1 : 0);
