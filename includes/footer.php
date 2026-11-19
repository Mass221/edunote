    </main>

    <!-- PIED DE PAGE -->
    <footer class="bg-white border-t border-slate-200 mt-auto py-6 text-slate-500 text-xs">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800">EduNotes · Plateforme Sécurité XML</span>
                <span>—</span>
                <span>Projet Académique Licence 2 Informatique (UIDT)</span>
            </div>
            <div class="flex items-center gap-4 text-slate-400">
                <span>Groupe de 7</span>
                <span>•</span>
                <span>Dév Web 2 & Sécurité des Données</span>
                <span>•</span>
                <span class="font-mono text-[11px] bg-slate-100 px-2 py-0.5 rounded border border-slate-200 text-slate-600">
                    Mode : <?= is_vulnerable_mode() ? '⚠️ Vulnérable' : '🛡️ Sécurisé' ?>
                </span>
            </div>
        </div>
    </footer>

</body>
</html>
