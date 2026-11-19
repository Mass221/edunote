#!/usr/bin/env bash
# start.sh - Démarrage en 1 commande de la plateforme EduNotes (UIDT)

set -e

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
cd "$DIR"

echo "======================================================================"
echo "   🎓 EduNotes – Banc d'essai Sécurité XML (UIDT L2 Informatique)"
echo "======================================================================"
echo "Groupe de 7 · Projet Démonstration en Direct (Soutenance)"
echo ""

# Vérifier PHP
if ! command -v php &> /dev/null; then
    echo "❌ Erreur : PHP n'est pas installé sur ce système."
    exit 1
fi

# Initialiser la base si nécessaire
if [ ! -f "data/db.json" ]; then
    echo "📦 Initialisation de la base de données..."
    cp data/db_backup.json data/db.json
fi

PORT=8000
echo "🚀 Démarrage du serveur PHP sur http://localhost:$PORT ..."
echo ""
echo "👉 Ouvrez votre navigateur sur : http://localhost:$PORT"
echo "👉 Page Laboratoire / Studio :   http://localhost:$PORT/lab.php"
echo ""
echo "Appuyez sur Ctrl+C pour arrêter le serveur."
echo "======================================================================"

php -S 0.0.0.0:$PORT
