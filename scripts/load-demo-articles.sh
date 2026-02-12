#!/bin/bash

# ============================================================================
# Script de chargement des flux RSS (Windows + Git Bash)
# ============================================================================

echo " Chargement des flux RSS de démonstration..."
echo ""

# Se placer dans le dossier backend
cd backend || exit 1

# --- 1. Korben ---
echo "1/8 - Chargement Korben..."
php bin/console app:fetch-rss https://korben.info/feed Korben

# --- 2. Numerama ---
echo "2/8 - Chargement Numerama..."
php bin/console app:fetch-rss https://www.numerama.com/feed/ Numerama

# --- 3. Frandroid ---
echo "3/8 - Chargement Frandroid..."
php bin/console app:fetch-rss https://www.frandroid.com/feed Frandroid

# --- 4. CSS-Tricks ---
echo "4/8 - Chargement CSS-Tricks..."
php bin/console app:fetch-rss https://css-tricks.com/feed/ CSS-Tricks

# --- 5. Dev.to ---
echo "5/8 - Chargement Dev.to..."
php bin/console app:fetch-rss https://dev.to/feed Dev.to

# --- 6. FreeCodeCamp ---
echo "6/8 - Chargement FreeCodeCamp..."
php bin/console app:fetch-rss https://www.freecodecamp.org/news/rss/ FreeCodeCamp

# --- 7. Smashing Magazine ---
echo "7/8 - Chargement Smashing Magazine..."
php bin/console app:fetch-rss https://www.smashingmagazine.com/feed/ SmashingMagazine

# --- 8. SitePoint ---
echo "8/8 - Chargement SitePoint..."
php bin/console app:fetch-rss https://www.sitepoint.com/feed/ SitePoint

echo ""
echo " Chargement terminé !"
echo ""

# Retour au dossier racine
cd ..

echo " Tous les articles sont chargés ! Recharge la page pour voir la pagination."