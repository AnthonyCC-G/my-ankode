<?php

/**
 * KERNEL.PHP - Noyau central de l'application Symfony
 * 
 * Responsabilités :
 * - Bootstrap de l'application Symfony (point d'entrée)
 * - Chargement de tous les bundles (FrameworkBundle, DoctrineBundle, SecurityBundle, etc.)
 * - Configuration du container de services (injection de dépendances)
 * - Gestion du cycle de vie requête/réponse HTTP
 * - Chargement des configurations (config/packages/*.yaml)
 * - Enregistrement des routes (config/routes.yaml)
 * 
 * Architecture :
 * - Étend BaseKernel de Symfony
 * - Utilise MicroKernelTrait pour configuration simplifiée
 * - MicroKernelTrait = configuration automatique via conventions (depuis Symfony 4+)
 * - Pas de méthodes à définir : tout est automatique via le trait
 * 
 * Fonctionnement interne (via MicroKernelTrait) :
 * 1. registerBundles() : charge config/bundles.php
 * 2. configureContainer() : charge config/packages/*.yaml
 * 3. configureRoutes() : charge config/routes.yaml
 * 
 * Appelé par :
 * - public/index.php (point d'entrée web)
 * - bin/console (point d'entrée CLI)
 * 
 */

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}