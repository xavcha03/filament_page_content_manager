#!/bin/bash

# Script de configuration pour workbench
# Ce script configure l'environnement de test

echo "🚀 Configuration de workbench pour tester le package..."

# Vérifier si ddev est disponible
if ! command -v ddev &> /dev/null; then
    echo "❌ ddev n'est pas installé. Veuillez l'installer d'abord."
    exit 1
fi

echo "📦 Installation des dépendances (sans le package media-library pour l'instant)..."
# Note: Le package media-library sera requis mais peut être ignoré temporairement
# pour les tests de base. Vous devrez l'ajouter manuellement si nécessaire.

echo "🔧 Configuration de l'environnement..."
ddev exec "cd /var/www/html && php artisan key:generate --force" || echo "⚠️  Clé déjà générée"

echo "📊 Exécution des migrations..."
ddev exec "cd /var/www/html && php artisan migrate --force"

echo "👤 Création d'un utilisateur Filament..."
echo "⚠️  Vous devrez créer un utilisateur manuellement avec: ddev exec 'php artisan make:filament-user'"

echo "✅ Configuration terminée!"
echo ""
echo "📝 Prochaines étapes:"
echo "1. Créer un utilisateur: ddev exec 'php artisan make:filament-user'"
echo "2. Accéder à l'admin: https://xavcha-pages.ddev.site/admin"
echo "3. Tester les routes API: https://xavcha-pages.ddev.site/api/pages"



