<?php
// includes/security.php - Fonctions de sécurité XML : SAML 2.0, XML-DSig, XXE et Cryptographie

$KEYS_DIR = __DIR__ . '/../data/keys';

/**
 * Récupère ou génère une paire de clés RSA 2048 bits
 */
function get_or_create_rsa_keys($prefix = 'idp') {
    global $KEYS_DIR;
    if (!is_dir($KEYS_DIR)) {
        mkdir($KEYS_DIR, 0775, true);
    }
    $privPath = "$KEYS_DIR/{$prefix}_private.pem";
    $pubPath = "$KEYS_DIR/{$prefix}_public.pem";

    if (!file_exists($privPath) || !file_exists($pubPath)) {
        $res = openssl_pkey_new([
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($res, $privKey);
        $details = openssl_pkey_get_details($res);
        $pubKey = $details["key"];

        file_put_contents($privPath, $privKey);
        file_put_contents($pubPath, $pubKey);
    }

    return [
        'private' => file_get_contents($privPath),
        'public' => file_get_contents($pubPath),
    ];
}

/**
 * Émet une assertion SAML 2.0 signée par l'IdP universitaire
 */
function generate_saml_assertion($user) {
    $keys = get_or_create_rsa_keys('idp');
    $now = gmdate("Y-m-d\TH:i:s\Z");
    $issueInstant = $now;
    $assertionId = 'assertion_' . bin2hex(random_bytes(8));
    $nameId = htmlspecialchars($user['email'], ENT_XML1);
    $fullName = htmlspecialchars($user['name'], ENT_XML1);
    $role = htmlspecialchars($user['role'], ENT_XML1);
    $studentId = htmlspecialchars($user['student_id'] ?? 'N/A', ENT_XML1);

    // Bloc Attributs et Sujet à signer
    $payloadXml = '<saml:Subject xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">'
                . "<saml:NameID>$nameId</saml:NameID>"
                . '</saml:Subject>'
                . '<saml:AttributeStatement xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">'
                . '<saml:Attribute Name="Role"><saml:AttributeValue>' . $role . '</saml:AttributeValue></saml:Attribute>'
                . '<saml:Attribute Name="FullName"><saml:AttributeValue>' . $fullName . '</saml:AttributeValue></saml:Attribute>'
                . '<saml:Attribute Name="StudentID"><saml:AttributeValue>' . $studentId . '</saml:AttributeValue></saml:Attribute>'
                . '</saml:AttributeStatement>';

    // Canonicalisation C14N et calcul du Digest SHA-256
    $domTemp = new DOMDocument();
    $domTemp->loadXML("<root>$payloadXml</root>");
    $c14nPayload = $domTemp->documentElement->C14N();
    $digest = base64_encode(hash('sha256', $c14nPayload, true));

    // Signature RSA de l'empreinte
    openssl_sign($digest, $rawSig, $keys['private'], OPENSSL_ALGO_SHA256);
    $signatureValue = base64_encode($rawSig);

    // Construction du document SAML 2.0 complet
    $samlXml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"' . "\n"
        . '               xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"' . "\n"
        . '               xmlns:ds="http://www.w3.org/2000/09/xmldsig#"' . "\n"
        . '               ID="resp_' . bin2hex(random_bytes(6)) . '" Version="2.0" IssueInstant="' . $issueInstant . '">' . "\n"
        . '  <saml:Issuer>https://idp.univ-thies.sn/saml2</saml:Issuer>' . "\n"
        . '  <saml:Assertion ID="' . $assertionId . '" Version="2.0" IssueInstant="' . $issueInstant . '">' . "\n"
        . '    <saml:Issuer>https://idp.univ-thies.sn/saml2</saml:Issuer>' . "\n"
        . '    ' . $payloadXml . "\n"
        . '    <ds:Signature>' . "\n"
        . '      <ds:SignedInfo>' . "\n"
        . '        <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>' . "\n"
        . '        <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>' . "\n"
        . '        <ds:Reference URI="#' . $assertionId . '">' . "\n"
        . '          <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>' . "\n"
        . '          <ds:DigestValue>' . $digest . '</ds:DigestValue>' . "\n"
        . '        </ds:Reference>' . "\n"
        . '      </ds:SignedInfo>' . "\n"
        . '      <ds:SignatureValue>' . $signatureValue . '</ds:SignatureValue>' . "\n"
        . '    </ds:Signature>' . "\n"
        . '  </saml:Assertion>' . "\n"
        . '</samlp:Response>';

    return $samlXml;
}

/**
 * Validation d'une assertion SAML (Mode Vulnérable vs Sécurisé)
 */
function validate_saml_assertion($samlXml, $is_vulnerable = true) {
    // 1. Détection XXE en mode sécurisé
    if (!$is_vulnerable) {
        if (stripos($samlXml, '<!DOCTYPE') !== false || stripos($samlXml, '<!ENTITY') !== false) {
            return [
                'success' => false,
                'error_type' => 'XXE_BLOCKED',
                'message' => '🛡️ REJET SÉCURITÉ : Attaque XXE bloquée ! Déclaration DTD / Entités externes formellement interdites.',
            ];
        }
    }

    // 2. Chargement du document XML avec DOMDocument
    $dom = new DOMDocument();
    if ($is_vulnerable) {
        // En mode vulnérable : résolution des entités externes activée (LIBXML_NOENT | LIBXML_DTDLOAD)
        // Permet l'attaque XXE !
        $loaded = @$dom->loadXML($samlXml, LIBXML_NOENT | LIBXML_DTDLOAD);
    } else {
        // En mode sécurisé : aucun appel réseau, aucune entité externe
        $loaded = @$dom->loadXML($samlXml, LIBXML_NONET);
    }

    if (!$loaded) {
        return [
            'success' => false,
            'error_type' => 'MALFORMED_XML',
            'message' => '❌ XML Malformé ou illisible par le parseur.',
        ];
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
    $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

    // 3. Extraction des données
    $nameIdNodes = $xpath->query('//saml:Subject/saml:NameID');
    $nameId = $nameIdNodes->length > 0 ? trim($nameIdNodes->item(0)->textContent) : '';

    $roleNodes = $xpath->query('//saml:Attribute[@Name="Role"]/saml:AttributeValue');
    $role = $roleNodes->length > 0 ? trim($roleNodes->item(0)->textContent) : '';

    $fullNameNodes = $xpath->query('//saml:Attribute[@Name="FullName"]/saml:AttributeValue');
    $fullName = $fullNameNodes->length > 0 ? trim($fullNameNodes->item(0)->textContent) : '';

    $studentIdNodes = $xpath->query('//saml:Attribute[@Name="StudentID"]/saml:AttributeValue');
    $studentId = $studentIdNodes->length > 0 ? trim($studentIdNodes->item(0)->textContent) : '';

    // Détection d'une exfiltration XXE dans les champs (si le fichier cible a été injecté)
    $xxeContent = null;
    if (strlen($fullName) > 60 || strpos($fullName, 'FLAG{') !== false || strpos($fullName, 'root:') !== false) {
        $xxeContent = $fullName;
    } elseif (strlen($role) > 60 || strpos($role, 'FLAG{') !== false || strpos($role, 'root:') !== false) {
        $xxeContent = $role;
    }

    // 4. Si Mode Vulnérable : On ignore la signature !
    if ($is_vulnerable) {
        return [
            'success' => true,
            'is_vulnerable' => true,
            'email' => $nameId,
            'role' => $role,
            'name' => $fullName,
            'student_id' => $studentId,
            'xxe_content' => $xxeContent,
            'message' => '⚠️ Mode Vulnérable : Signature non vérifiée. Rôle ' . htmlspecialchars($role) . ' accepté aveuglément !',
        ];
    }

    // 5. Si Mode Sécurisé : Vérification cryptographique stricte de la Signature
    $digestNodes = $xpath->query('//ds:DigestValue');
    $sigNodes = $xpath->query('//ds:SignatureValue');

    if ($digestNodes->length === 0 || $sigNodes->length === 0) {
        return [
            'success' => false,
            'error_type' => 'MISSING_SIGNATURE',
            'message' => '🛡️ REJET 403 : Aucune signature XML-DSig trouvée dans l\'assertion SAML.',
        ];
    }

    $claimedDigest = trim($digestNodes->item(0)->textContent);
    $signatureValue = base64_decode(trim($sigNodes->item(0)->textContent));

    // Recalcul du Digest C14N SHA-256 sur le payload réel
    $subjectNode = $xpath->query('//saml:Subject')->item(0);
    $attrNode = $xpath->query('//saml:AttributeStatement')->item(0);

    if (!$subjectNode || !$attrNode) {
        return [
            'success' => false,
            'error_type' => 'INVALID_STRUCTURE',
            'message' => '🛡️ REJET 403 : Structure SAML incomplète.',
        ];
    }

    $domTemp = new DOMDocument();
    $domTemp->loadXML('<root>' . $dom->saveXML($subjectNode) . $dom->saveXML($attrNode) . '</root>');
    $c14nPayload = $domTemp->documentElement->C14N();
    $calculatedDigest = base64_encode(hash('sha256', $c14nPayload, true));

    // A. Contrôle de l'empreinte (Intégrité)
    if ($calculatedDigest !== $claimedDigest) {
        return [
            'success' => false,
            'error_type' => 'INTEGRITY_FAILED',
            'claimed_digest' => $claimedDigest,
            'calculated_digest' => $calculatedDigest,
            'message' => "❌ REJET 403 : Échec d'intégrité SHA-256 ! Le contenu de l'assertion a été modifié en transit (usurpation détectée).",
        ];
    }

    // B. Contrôle de la signature RSA (Authenticité)
    $keys = get_or_create_rsa_keys('idp');
    $verifyResult = openssl_verify($calculatedDigest, $signatureValue, $keys['public'], OPENSSL_ALGO_SHA256);

    if ($verifyResult !== 1) {
        return [
            'success' => false,
            'error_type' => 'SIGNATURE_INVALID',
            'message' => '❌ REJET 403 : Clé de signature invalide ou non reconnue par le Service Provider.',
        ];
    }

    // Tout est vérifié et conforme !
    return [
        'success' => true,
        'is_vulnerable' => false,
        'email' => $nameId,
        'role' => $role,
        'name' => $fullName,
        'student_id' => $studentId,
        'xxe_content' => null,
        'message' => '🛡️ Mode Sécurisé : Signature SAML X.509 vérifiée avec succès. Rôle authentifié.',
    ];
}

/**
 * Génère un relevé de notes XML signé par l'enseignant (XML-DSig)
 */
function generate_signed_transcript($student, $gradeRecord) {
    $keys = get_or_create_rsa_keys('teacher');
    $releveId = 'releve_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $student['student_id']);
    $date = date('Y-m-d H:i:s');
    $gradeVal = number_format($gradeRecord['grade'], 1, '.', '');

    // Nœud de données Releve
    $releveXml = '<Releve id="' . $releveId . '">' . "\n"
        . '  <Etudiant id="' . htmlspecialchars($student['student_id'], ENT_XML1) . '">' . "\n"
        . '    <Nom>' . htmlspecialchars($student['name'], ENT_XML1) . '</Nom>' . "\n"
        . '    <Email>' . htmlspecialchars($student['email'], ENT_XML1) . '</Email>' . "\n"
        . '  </Etudiant>' . "\n"
        . '  <Evaluation>' . "\n"
        . '    <Matiere>' . htmlspecialchars($gradeRecord['course'], ENT_XML1) . '</Matiere>' . "\n"
        . '    <Coefficient>' . htmlspecialchars($gradeRecord['coefficient'], ENT_XML1) . '</Coefficient>' . "\n"
        . '    <Note valeur="' . $gradeVal . '"/>' . "\n"
        . '    <Commentaire>' . htmlspecialchars($gradeRecord['comment'], ENT_XML1) . '</Commentaire>' . "\n"
        . '    <DateSignature>' . $date . '</DateSignature>' . "\n"
        . '  </Evaluation>' . "\n"
        . '</Releve>';

    // Canonicalisation C14N et calcul du Digest SHA-256
    $domTemp = new DOMDocument();
    $domTemp->loadXML($releveXml);
    $c14n = $domTemp->documentElement->C14N();
    $digest = base64_encode(hash('sha256', $c14n, true));

    // Signature RSA de l'empreinte
    openssl_sign($digest, $rawSig, $keys['private'], OPENSSL_ALGO_SHA256);
    $signatureValue = base64_encode($rawSig);

    // Document final avec signature XML-DSig
    $finalXml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<DocumentAcademique xmlns:ds="http://www.w3.org/2000/09/xmldsig#">' . "\n"
        . $releveXml . "\n"
        . '  <ds:Signature>' . "\n"
        . '    <ds:SignedInfo>' . "\n"
        . '      <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>' . "\n"
        . '      <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>' . "\n"
        . '      <ds:Reference URI="#' . $releveId . '">' . "\n"
        . '        <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>' . "\n"
        . '        <ds:DigestValue>' . $digest . '</ds:DigestValue>' . "\n"
        . '      </ds:Reference>' . "\n"
        . '    </ds:SignedInfo>' . "\n"
        . '    <ds:SignatureValue>' . $signatureValue . '</ds:SignatureValue>' . "\n"
        . '  </ds:Signature>' . "\n"
        . '</DocumentAcademique>';

    return $finalXml;
}

/**
 * Vérification d'un relevé de notes importé (SC-01 : Falsification de note 08 -> 18)
 */
function verify_imported_transcript($xmlContent, $is_vulnerable = true) {
    if (!$is_vulnerable) {
        if (stripos($xmlContent, '<!DOCTYPE') !== false || stripos($xmlContent, '<!ENTITY') !== false) {
            return [
                'success' => false,
                'error_type' => 'XXE_BLOCKED',
                'message' => '🛡️ REJET SÉCURITÉ : Attaque XXE bloquée ! DTD interdites en mode sécurisé.',
            ];
        }
    }

    $dom = new DOMDocument();
    if ($is_vulnerable) {
        $loaded = @$dom->loadXML($xmlContent, LIBXML_NOENT | LIBXML_DTDLOAD);
    } else {
        $loaded = @$dom->loadXML($xmlContent, LIBXML_NONET);
    }

    if (!$loaded) {
        return [
            'success' => false,
            'error_type' => 'MALFORMED_XML',
            'message' => '❌ Fichier XML malformé ou non valide.',
        ];
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

    $studentIdNodes = $xpath->query('//Releve/Etudiant/@id');
    $studentId = $studentIdNodes->length > 0 ? trim($studentIdNodes->item(0)->nodeValue) : '';

    $gradeNodes = $xpath->query('//Releve/Evaluation/Note/@valeur');
    $newGrade = $gradeNodes->length > 0 ? floatval($gradeNodes->item(0)->nodeValue) : 0.0;

    $courseNodes = $xpath->query('//Releve/Evaluation/Matiere');
    $course = $courseNodes->length > 0 ? trim($courseNodes->item(0)->textContent) : '';

    // Détection d'exfiltration XXE
    $studentNameNodes = $xpath->query('//Releve/Etudiant/Nom');
    $studentName = $studentNameNodes->length > 0 ? trim($studentNameNodes->item(0)->textContent) : '';
    $xxeContent = null;
    if (strlen($studentName) > 60 || strpos($studentName, 'FLAG{') !== false || strpos($studentName, 'root:') !== false) {
        $xxeContent = $studentName;
    }

    // Si Mode Vulnérable : la signature n'est pas vérifiée !
    if ($is_vulnerable) {
        return [
            'success' => true,
            'is_vulnerable' => true,
            'student_id' => $studentId,
            'grade' => $newGrade,
            'course' => $course,
            'student_name' => $studentName,
            'xxe_content' => $xxeContent,
            'message' => "⚠️ MODE VULNÉRABLE : Signature ignorée ! La note falsifiée ({$newGrade}/20) est acceptée et persistée.",
        ];
    }

    // Si Mode Sécurisé : contrôle d'intégrité
    $releveNode = $xpath->query('//Releve')->item(0);
    $digestNode = $xpath->query('//ds:DigestValue')->item(0);
    $sigNode = $xpath->query('//ds:SignatureValue')->item(0);

    if (!$releveNode || !$digestNode || !$sigNode) {
        return [
            'success' => false,
            'error_type' => 'MISSING_SIGNATURE',
            'message' => '🛡️ REJET SÉCURITÉ : Absence d\'éléments de signature valides.',
        ];
    }

    $claimedDigest = trim($digestNode->textContent);
    $signatureValue = base64_decode(trim($sigNode->textContent));

    // Recalcul du digest C14N (Exclusive Canonicalization W3C xml-exc-c14n)
    $c14nReleve = $releveNode->C14N(true, false);
    $calculatedDigest = base64_encode(hash('sha256', $c14nReleve, true));

    if ($calculatedDigest !== $claimedDigest) {
        return [
            'success' => false,
            'error_type' => 'TAMPERED_NOTE',
            'claimed_digest' => $claimedDigest,
            'calculated_digest' => $calculatedDigest,
            'message' => "❌ REJET D'INTÉGRITÉ (400) : L'empreinte SHA-256 recalculée [$calculatedDigest] ne correspond pas au Digest signé [$claimedDigest]. Relevé falsifié rejeté !",
        ];
    }

    // Vérification de la signature RSA avec la clé publique enseignant
    $keys = get_or_create_rsa_keys('teacher');
    if (openssl_verify($calculatedDigest, $signatureValue, $keys['public'], OPENSSL_ALGO_SHA256) !== 1) {
        return [
            'success' => false,
            'error_type' => 'SIGNATURE_INVALID',
            'message' => '❌ REJET SÉCURITÉ : La signature cryptographique RSA est invalide.',
        ];
    }

    return [
        'success' => true,
        'is_vulnerable' => false,
        'student_id' => $studentId,
        'grade' => $newGrade,
        'course' => $course,
        'student_name' => $studentName,
        'xxe_content' => null,
        'message' => "🛡️ MODE SÉCURISÉ : Relevé authentique vérifié avec succès (SHA-256 et RSA conformes).",
    ];
}
