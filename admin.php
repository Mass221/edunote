<?php
// admin.php - Console d'Administration Système & Durcissement (DSI UIDT)
require_once __DIR__ . '/config.php';

$db = load_db();
$isVuln = is_vulnerable_mode();

require_once __DIR__ . '/includes/header_portal.php';
?>

<div class="max-w-5xl mx-auto space-y-8">

    <!-- EN-TÊTE DSI -->
    <div class="bg-slate-900 text-white p-6 sm:p-8 rounded-2xl shadow-lg border border-slate-800">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <span class="text-[11px] font-black uppercase tracking-wider text-blue-400 bg-blue-900/50 px-3 py-1 rounded-full border border-blue-700/50">
                    Direction des Systèmes d'Information · UIDT
                </span>
                <h1 class="text-2xl font-black mt-2">Console d'Administration & Durcissement Système</h1>
                <p class="text-xs text-slate-300 mt-1 max-w-2xl">
                    Configuration des politiques de sécurité du serveur d'application, paramétrage des parseurs XML et supervision des accès.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="reset.php" class="bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow transition flex items-center gap-1.5">
                    <span>🔄</span> Réinitialiser la Base (08/20)
                </a>
            </div>
        </div>
    </div>

    <!-- COMMUTATEUR DE DURCISSEMENT SYSTÈME (EF-07) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-lg font-black text-slate-900">
                    Politique de Sécurité et de Durcissement XML (Parseur & SAML)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Détermine si les défenses W3C et les recommandations de l'OWASP sont appliquées par le moteur PHP.
                </p>
            </div>

            <div>
                <span class="font-mono text-xs font-black uppercase px-3 py-1.5 rounded-full border <?= $isVuln ? 'bg-red-50 text-red-700 border-red-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?>">
                    <?= $isVuln ? '⚠️ État : Mode Non Durci (Vulnérable)' : '🛡️ État : Mode Durci (Sécurisé)' ?>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- CARTE MODE VULNÉRABLE -->
            <div class="p-5 rounded-xl border-2 <?= $isVuln ? 'border-red-500 bg-red-50/40' : 'border-slate-200 bg-slate-50 opacity-60' ?>">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-sm text-slate-900">Mode 1 : Non Durci (Vulnérable)</span>
                    <span class="text-xl">⚠️</span>
                </div>
                <ul class="text-xs text-slate-600 mt-3 space-y-1.5 list-disc list-inside">
                    <li>Signatures SAML et XML-DSig <strong>ignorées</strong>.</li>
                    <li>Résolution des entités externes <strong>activée (XXE)</strong>.</li>
                    <li>Permet d'illustrer la compromission en direct.</li>
                </ul>
            </div>

            <!-- CARTE MODE SÉCURISÉ -->
            <div class="p-5 rounded-xl border-2 <?= !$isVuln ? 'border-emerald-500 bg-emerald-50/40' : 'border-slate-200 bg-slate-50 opacity-60' ?>">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-sm text-slate-900">Mode 2 : Durci (Sécurisé - OWASP)</span>
                    <span class="text-xl">🛡️</span>
                </div>
                <ul class="text-xs text-slate-600 mt-3 space-y-1.5 list-disc list-inside">
                    <li>Contrôle cryptographique <strong>SHA-256 et X.509</strong> strict.</li>
                    <li>Déclaration DTD et entités externes <strong>bloquées (Rejet XXE)</strong>.</li>
                    <li>Rejet immédiat (400 / 403) en cas d'altération.</li>
                </ul>
            </div>
        </div>

        <div class="pt-2 flex items-center justify-between border-t border-slate-100 flex-wrap gap-4">
            <span class="text-xs text-slate-500">
                Bascule instantanée en mémoire sans nécessiter de redémarrage du serveur.
            </span>
            <a href="toggle_mode.php" class="bg-blue-700 hover:bg-blue-800 text-white font-black text-xs uppercase tracking-wider px-6 py-3 rounded-xl shadow-md transition flex items-center gap-2">
                <span>🔄</span> Basculer la Politique de Sécurité (<?= $isVuln ? 'Activer le Durcissement' : 'Désactiver le Durcissement' ?>)
            </a>
        </div>
    </div>

    <!-- JOURNAL D'AUDIT SYSTÈME ET LOGS D'ACCÈS (EF-09) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-black text-slate-900 text-base">Journal des Événements & Alertes Sécurité (EF-09)</h3>
                <p class="text-xs text-slate-500 mt-0.5">Traces d'audit enregistrées lors de chaque tentative de connexion et d'import</p>
            </div>
            <span class="text-xs bg-slate-100 text-slate-600 font-mono px-3 py-1 rounded-full border border-slate-200">
                <?= count($db['logs'] ?? []) ?> traces
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-3">Horodatage</th>
                        <th class="p-3">Événement</th>
                        <th class="p-3">Politique Serveur</th>
                        <th class="p-3">Résultat</th>
                        <th class="p-3">Détails de l'Audit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php 
                    $recentLogs = array_reverse(array_slice($db['logs'] ?? [], -15));
                    if (empty($recentLogs)):
                    ?>
                    <tr>
                        <td colspan="5" class="p-4 text-center text-slate-400 italic">Aucun événement enregistré.</td>
                    </tr>
                    <?php else: foreach ($recentLogs as $log): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="p-3 text-slate-500"><?= htmlspecialchars($log['timestamp']) ?></td>
                        <td class="p-3 font-bold text-slate-800"><?= htmlspecialchars($log['event']) ?></td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-black <?= $log['mode'] === 'VULNERABLE' ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' ?>">
                                <?= htmlspecialchars($log['mode']) ?>
                            </span>
                        </td>
                        <td class="p-3 font-bold <?= $log['status'] === 'SUCCESS' ? 'text-emerald-600' : ($log['status'] === 'BLOCKED' ? 'text-red-600' : 'text-amber-600') ?>">
                            <?= htmlspecialchars($log['status']) ?>
                        </td>
                        <td class="p-3 text-slate-600"><?= htmlspecialchars($log['details']) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer_portal.php'; ?>
