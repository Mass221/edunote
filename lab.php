<?php
// lab.php - Studio Laboratoire en Direct (Tour de Contrôle Démonstration Soutenance)
require_once __DIR__ . '/config.php';

$db = load_db();
$isVuln = is_vulnerable_mode();

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-8">

    <!-- EN-TÊTE DU LABORATOIRE -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white rounded-2xl p-6 sm:p-8 shadow-xl">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <span class="text-xs font-black uppercase tracking-widest text-indigo-400 bg-indigo-900/60 px-3 py-1 rounded-full">
                    UIDT · L2 Informatique · Groupe de 7
                </span>
                <h1 class="text-2xl font-black mt-2">Studio Laboratoire – Banc d'essai Sécurité XML</h1>
                <p class="text-xs text-indigo-200 mt-1 max-w-2xl">
                    Tour de contrôle pour la soutenance. Permet de déclencher et d'expliquer chaque attaque du diaporama en un clic, 
                    de modifier le XML en direct et de visualiser l'état de la base.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="toggle_mode.php" class="bg-white/10 hover:bg-white/20 text-white font-bold text-xs px-4 py-2 rounded-xl border border-white/20 transition flex items-center gap-1.5">
                    <span><?= $isVuln ? '⚠️' : '🛡️' ?></span> <?= $isVuln ? 'Mode Vulnérable Actif' : 'Mode Sécurisé Actif' ?>
                </a>
                <a href="reset.php" class="bg-rose-600 hover:bg-rose-500 text-white font-black text-xs px-4 py-2 rounded-xl shadow transition">
                    🔄 Reset Base (08/20)
                </a>
            </div>
        </div>
    </div>

    <!-- LES 3 SCÉNARIOS MAJEURS DU DIAPORAMA -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- SC-01 : FALSIFICATION RELEVÉ SIGNÉ -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full">
                        Diapo 7 · SC-01
                    </span>
                    <span class="text-2xl">✍️</span>
                </div>
                <h3 class="text-lg font-black text-slate-900 mt-3">Falsification de Relevé (XML-DSig)</h3>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                    On modifie la note de Mamadou Sow de <strong>08/20</strong> à <strong>18/20</strong> dans le XML signé.
                </p>
                <div class="mt-3 text-[11px] text-slate-600 space-y-1 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                    <div><strong>Vulnérable :</strong> Note 18 enregistrée en base.</div>
                    <div><strong>Sécurisé :</strong> Rejet d'intégrité SHA-256.</div>
                </div>
            </div>
            <a href="notes.php" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2.5 px-4 rounded-xl text-center shadow transition block">
                Tester sur Notes.php →
            </a>
        </div>

        <!-- SC-02 : USURPATION IDENTITÉ SAML -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full">
                        Diapo 10 · SC-02
                    </span>
                    <span class="text-2xl">🎭</span>
                </div>
                <h3 class="text-lg font-black text-slate-900 mt-3">Usurpation d'Identité SAML</h3>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                    On intercepte l'assertion d'un étudiant et on remplace <code>role=student</code> par <code>teacher</code>.
                </p>
                <div class="mt-3 text-[11px] text-slate-600 space-y-1 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                    <div><strong>Vulnérable :</strong> Élévation de privilèges totale.</div>
                    <div><strong>Sécurisé :</strong> Rejet 403 (signature IdP invalide).</div>
                </div>
            </div>
            <a href="idp.php?email=etudiant@univ.com&intercept=1" class="w-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs py-2.5 px-4 rounded-xl text-center shadow transition block">
                Tester l'interception SAML →
            </a>
        </div>

        <!-- SC-03 : INJECTION XXE -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-rose-600 bg-rose-50 px-2.5 py-1 rounded-full">
                        Diapo 12 · SC-03
                    </span>
                    <span class="text-2xl">💥</span>
                </div>
                <h3 class="text-lg font-black text-slate-900 mt-3">Attaque XXE (External Entity)</h3>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                    On déclare <code>&lt;!ENTITY xxe SYSTEM "file://..."&gt;</code> pour forcer le serveur à lire un fichier secret.
                </p>
                <div class="mt-3 text-[11px] text-slate-600 space-y-1 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                    <div><strong>Vulnérable :</strong> Contenu du fichier divulgué.</div>
                    <div><strong>Sécurisé :</strong> DTD et entités désactivées.</div>
                </div>
            </div>
            <a href="idp.php?email=etudiant@univ.com&intercept=1" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-2.5 px-4 rounded-xl text-center shadow transition block">
                Injecter XXE en direct →
            </a>
        </div>

    </div>

    <!-- ÉTAT DE LA BASE DE DONNÉES EN DIRECT -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-black text-slate-900 text-base">État des Notes en Base (Persistance EF-08)</h3>
            <span class="text-xs text-slate-400 font-mono">Fichier : data/db.json</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <?php foreach ($db['grades'] as $g): ?>
            <div class="p-4 rounded-xl border <?= $g['student_id'] === 'ETUD-001' ? 'bg-amber-50 border-amber-300' : 'bg-slate-50 border-slate-200' ?>">
                <div class="text-xs font-bold text-slate-900"><?= htmlspecialchars($g['name']) ?></div>
                <div class="text-[11px] text-slate-500"><?= htmlspecialchars($g['student_id']) ?></div>
                <div class="mt-2 text-xl font-mono font-black <?= $g['grade'] >= 10 ? 'text-emerald-600' : 'text-red-600' ?>">
                    <?= number_format($g['grade'], 1) ?> / 20
                </div>
                <div class="text-[10px] text-slate-500 mt-1"><?= htmlspecialchars($g['comment']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
