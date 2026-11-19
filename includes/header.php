<?php
// includes/header.php - En-tête HTML commun avec bandeau de sécurité persistant
require_once __DIR__ . '/../config.php';
$isVuln = is_vulnerable_mode();
$currentUser = get_current_user_session();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduNotes – Banc d'essai Sécurité XML (UIDT)</title>
    <!-- Tailwind CSS pour un rendu visuel moderne et adapté au vidéoprojecteur -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse-slow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.85; }
        }
        .animate-pulse-slow {
            animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800 flex flex-col font-sans">

    <!-- 1. BANDEAU DE SÉCURITÉ PERSISTANT HAUTE VISIBILITÉ (ENF-03) -->
    <?php if ($isVuln): ?>
    <div class="bg-red-600 text-white shadow-md border-b-4 border-red-800 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 py-2.5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="text-2xl animate-bounce">⚠️</span>
                <div>
                    <span class="font-black text-sm uppercase tracking-wider bg-red-800 px-2.5 py-0.5 rounded text-white mr-2">
                        MODE VULNÉRABLE
                    </span>
                    <span class="text-sm font-medium text-red-100">
                        Signatures XML & SAML <strong>non vérifiées</strong>. DTD & résolution d'entités externes <strong>activées (XXE)</strong>.
                    </span>
                </div>
            </div>
            <a href="toggle_mode.php" class="bg-white text-red-700 hover:bg-red-50 font-bold text-xs uppercase px-4 py-1.5 rounded-full shadow transition transform hover:scale-105 flex items-center gap-1.5">
                <span>🛡️</span> Basculer en Mode Sécurisé
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-emerald-600 text-white shadow-md border-b-4 border-emerald-800 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 py-2.5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🛡️</span>
                <div>
                    <span class="font-black text-sm uppercase tracking-wider bg-emerald-800 px-2.5 py-0.5 rounded text-white mr-2">
                        MODE SÉCURISÉ
                    </span>
                    <span class="text-sm font-medium text-emerald-100">
                        Vérification <strong>XML-DSig (SHA-256 / RSA)</strong> stricte. DTD <strong>bloquées (disallow-doctype-decl)</strong>.
                    </span>
                </div>
            </div>
            <a href="toggle_mode.php" class="bg-white text-emerald-800 hover:bg-emerald-50 font-bold text-xs uppercase px-4 py-1.5 rounded-full shadow transition transform hover:scale-105 flex items-center gap-1.5">
                <span>⚠️</span> Basculer en Mode Vulnérable
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- 2. BARRE DE NAVIGATION PRINCIPALE -->
    <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="index.php" class="flex items-center gap-2.5 group">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black text-xl shadow-md group-hover:bg-blue-700 transition">
                        🎓
                    </div>
                    <div>
                        <div class="font-black text-lg text-slate-900 leading-tight">EduNotes</div>
                        <div class="text-[11px] text-slate-500 font-semibold tracking-wide uppercase">Banc d'essai Sécurité XML · UIDT</div>
                    </div>
                </a>

                <nav class="hidden md:flex items-center gap-1">
                    <a href="index.php" class="px-3 py-1.5 rounded-lg text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                        Accueil
                    </a>
                    <a href="dashboard.php" class="px-3 py-1.5 rounded-lg text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                        Tableau de bord
                    </a>
                    <a href="notes.php" class="px-3 py-1.5 rounded-lg text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                        Notes & Relevés
                    </a>
                    <a href="lab.php" class="px-3 py-1.5 rounded-lg text-sm font-bold text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 transition flex items-center gap-1">
                        <span>🧪</span> Studio Live
                    </a>
                </nav>
            </div>

            <!-- BOUTON RESET RAPIDE & BADGE UTILISATEUR -->
            <div class="flex items-center gap-3">
                <a href="reset.php" title="Restaurer la note de Mamadou Sow à 08/20 et réinitialiser les sessions" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 font-bold px-3 py-1.5 rounded-lg shadow-sm transition flex items-center gap-1">
                    <span>🔄</span> <span class="hidden sm:inline">Réinitialiser</span> (08/20)
                </a>

                <?php if ($currentUser): ?>
                    <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                        <div class="text-right hidden sm:block">
                            <div class="text-xs font-bold text-slate-900 leading-tight"><?= htmlspecialchars($currentUser['name'] ?? 'Utilisateur') ?></div>
                            <div class="text-[10px] font-black uppercase tracking-wider <?= $currentUser['role'] === 'teacher' ? 'text-amber-600' : 'text-blue-600' ?>">
                                <?= $currentUser['role'] === 'teacher' ? '👨‍🏫 Enseignant' : '🎓 Étudiant' ?>
                            </div>
                        </div>
                        <a href="index.php?action=logout" class="text-xs text-red-600 hover:text-red-800 font-bold p-1.5 hover:bg-red-50 rounded-lg transition" title="Déconnexion">
                            ✕
                        </a>
                    </div>
                <?php else: ?>
                    <a href="index.php" class="text-xs bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-1.5 rounded-lg shadow transition">
                        Connexion
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- 3. MESSAGES FLASH DE NOTIFICATION -->
    <?php if (!empty($_SESSION['flash_message'])): ?>
        <?php 
            $fType = $_SESSION['flash_type'] ?? 'info';
            $bgClass = $fType === 'success' ? 'bg-emerald-50 border-emerald-500 text-emerald-800' : 
                      ($fType === 'danger' || $fType === 'error' ? 'bg-red-50 border-red-500 text-red-800' : 'bg-blue-50 border-blue-500 text-blue-800');
        ?>
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="border-l-4 p-4 rounded-r-lg shadow-sm <?= $bgClass ?> flex items-center justify-between">
                <div class="font-medium text-sm"><?= htmlspecialchars($_SESSION['flash_message']) ?></div>
                <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 font-bold ml-4">✕</button>
            </div>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <!-- CONTENU PRINCIPAL -->
    <main class="flex-grow max-w-7xl mx-auto px-4 py-6 w-full">
