<?php

/**
 * INDEX.PHP - Point d'entrée unique de l'application web (Front Controller)
 * 
 * Responsabilités :
 * - Premier fichier exécuté par le serveur web (Apache/Nginx) pour CHAQUE requête HTTP
 * - Charge l'autoloader Composer (vendor/autoload_runtime.php)
 * - Instancie le Kernel Symfony avec l'environnement et le mode debug
 * - Délègue la gestion de la requête HTTP au Kernel
 * - Retourne la réponse HTTP au serveur web
 * 
 * Architecture :
 * - Pattern Front Controller : un seul point d'entrée pour toutes les URLs
 * - Symfony Runtime Component : gère le cycle de vie de l'application
 * - Retourne une closure (fonction anonyme) qui crée le Kernel
 * - Le Runtime Component appelle cette closure pour chaque requête
 * 
 * Workflow d'une requête HTTP :
 * 1. Serveur web (Apache/Nginx) reçoit requête → exécute public/index.php
 * 2. index.php charge autoloader Composer
 * 3. index.php retourne closure au Runtime Component
 * 4. Runtime Component exécute la closure → crée Kernel
 * 5. Kernel traite la requête → génère Response
 * 6. Runtime Component envoie Response au serveur web
 * 7. Serveur web renvoie Response au navigateur
 * 
 * Variables d'environnement (injectées par Runtime) :
 * - APP_ENV : environnement (dev, prod, test)
 * - APP_DEBUG : mode debug activé (true/false)
 * 
 * Fichier .htaccess (Apache) ou config Nginx :
 * - Toutes les URLs sont redirigées vers public/index.php
 * - Sauf les fichiers statiques (CSS, JS, images)
 * 
 * Note pour la certification DWWM :
 * - Ce pattern Front Controller est une compétence clé (CP1 - Maquetter une application)
 * - Démontre la compréhension du routing et du cycle requête/réponse HTTP
 * - Fichier ultra simple = bonne architecture (séparation des responsabilités)
 * 
 * ATTENTION SÉCURITÉ :
 * - Ce fichier DOIT être dans public/ (pas à la racine du projet)
 * - vendor/, src/, config/ ne doivent PAS être accessibles via HTTP
 * - Document Root du serveur web = /path/to/projet/public/
 */

use App\Kernel;

// ===== 1. CHARGEMENT DE L'AUTOLOADER COMPOSER + RUNTIME =====

// require_once : inclusion unique du fichier d'autoloading
// dirname(__DIR__) : remonte d'un dossier (public/ → racine projet)
// vendor/autoload_runtime.php : autoloader Composer + Symfony Runtime Component
// 
// Ce fichier fait 2 choses :
// 1. Charge l'autoloader Composer (PSR-4) pour trouver les classes automatiquement
// 2. Initialise le Symfony Runtime Component qui gère le cycle de vie de l'app
require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// ===== 2. RETOUR D'UNE CLOSURE POUR INSTANCIER LE KERNEL =====

// Retourne une fonction anonyme (closure) au Runtime Component
// $context : tableau fourni par le Runtime avec variables d'environnement
//   - $context['APP_ENV'] : 'dev', 'prod', ou 'test' (depuis .env ou .env.local)
//   - $context['APP_DEBUG'] : true/false (mode debug activé ou non)
// 
// Pourquoi une closure et pas directement new Kernel() ?
// → Le Runtime Component a besoin de contrôler QUAND créer le Kernel
// → Permet au Runtime de gérer les signaux système, les erreurs fatales, etc.
return function (array $context) {
    // Instanciation du Kernel Symfony avec :
    // - Environnement (dev = cache désactivé, prod = cache activé)
    // - Debug (true = messages d'erreur détaillés, false = erreurs génériques)
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};