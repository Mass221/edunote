<?php
// includes/header_portal.php - En-tête Institutionnel 100% Réaliste (UIDT)
require_once __DIR__ . '/../config.php';
$currentUser = get_current_user_session();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail Pédagogique Numérique – Université Iba Der Thiam de Thiès</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 flex flex-col font-sans">

    <!-- BANDEAU OFFICIEL RÉPUBLIQUE DU SÉNÉGAL / MINISTÈRE -->
    <div class="bg-slate-900 text-slate-300 text-[11px] py-1 px-4 border-b border-slate-800">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>🇸🇳</span>
                <span class="font-medium">République du Sénégal · Ministère de l'Enseignement Supérieur, de la Recherche et de l'Innovation</span>
            </div>
            <div class="hidden sm:flex items-center gap-4 text-slate-400">
                <a href="admin.php" class="hover:text-slate-200 transition">Administration DSI</a>
                <span>•</span>
                <a href="/attacker/" target="_blank" class="text-indigo-400 hover:text-indigo-300 font-bold transition flex items-center gap-1">
                    <span>🕵️</span> Console Pentest
                </a>
            </div>
        </div>
    </div>

    <!-- EN-TÊTE PRINCIPAL INSTITUTIONNEL DE L'UNIVERSITÉ -->
    <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 h-20 flex items-center justify-between">
            <div class="flex items-center gap-4 sm:gap-6">
                <a href="index.php" class="flex items-center gap-3 group">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-700 to-indigo-900 text-white flex items-center justify-center font-black text-2xl shadow-md">
                        🏛️
                    </div>
                    <div>
                        <div class="font-black text-base sm:text-lg text-slate-900 leading-tight tracking-tight">
                            UNIVERSITÉ IBA DER THIAM DE THIÈS
                        </div>
                        <div class="text-[11px] text-blue-700 font-bold tracking-wider uppercase mt-0.5">
                            Portail de Gestion Pédagogique & Délibérations (EduNotes)
                        </div>
                    </div>
                </a>
            </div>

            <!-- MENU & NAVIGATION -->
            <div class="flex items-center gap-3">
                <nav class="hidden md:flex items-center gap-1 mr-4 border-r border-slate-200 pr-4">
                    <a href="index.php" class="px-3 py-2 rounded-lg text-xs font-semibold text-slate-600 hover:text-blue-700 hover:bg-slate-50 transition">
                        Accueil
                    </a>
                    <a href="dashboard.php" class="px-3 py-2 rounded-lg text-xs font-semibold text-slate-600 hover:text-blue-700 hover:bg-slate-50 transition">
                        Tableau de bord
                    </a>
                    <a href="notes.php" class="px-3 py-2 rounded-lg text-xs font-semibold text-slate-600 hover:text-blue-700 hover:bg-slate-50 transition">
                        Relevés & Notes
                    </a>
                </nav>

                <?php if ($currentUser): ?>
                    <div class="flex items-center gap-3">
                        <div class="text-right hidden sm:block">
                            <div class="text-xs font-bold text-slate-900 leading-tight"><?= htmlspecialchars($currentUser['name']) ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-blue-600">
                                <?= $currentUser['role'] === 'teacher' ? 'Corps Enseignant' : 'Étudiant (L2 Info)' ?>
                            </div>
                        </div>
                        <a href="index.php?action=logout" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3 py-1.5 rounded-lg border border-slate-200 transition">
                            Déconnexion
                        </a>
                    </div>
                <?php else: ?>
                    <a href="index.php" class="text-xs bg-blue-700 hover:bg-blue-800 text-white font-bold px-4 py-2 rounded-lg shadow-sm transition">
                        Authentification ENT
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- NOTIFICATION FLASH (SI PRÉSENTE) -->
    <?php if (!empty($_SESSION['flash_message'])): ?>
        <?php 
            $fType = $_SESSION['flash_type'] ?? 'info';
            $bg = $fType === 'success' ? 'bg-emerald-50 border-emerald-500 text-emerald-900' : 
                 ($fType === 'danger' || $fType === 'error' ? 'bg-red-50 border-red-500 text-red-900' : 'bg-blue-50 border-blue-500 text-blue-900');
        ?>
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="border-l-4 p-4 rounded-r-lg shadow-sm <?= $bg ?> flex items-center justify-between text-xs font-medium">
                <div><?= htmlspecialchars($_SESSION['flash_message']) ?></div>
                <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 font-bold ml-4">✕</button>
            </div>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <!-- CONTENU PRINCIPAL DU PORTAIL -->
    <main class="flex-grow max-w-7xl mx-auto px-4 py-8 w-full">
