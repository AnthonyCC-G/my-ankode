/**
 *DESKTOP-ONLY.JS - Détection du retour en mode desktop
 * Redirige vers la page d'origine si l'écran redevient desktop
 */

(function checkDesktopReturn() {
    // Détecte si on repasse en desktop
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth >= 768) {
                console.log('[DESKTOP-ONLY] Écran desktop détecté - Retour au Kanban');
                // Redirige vers la page d'origine (ou dashboard par défaut)
                const referrer = document.referrer;
                if (referrer && referrer.includes('/kanban')) {
                    window.location.href = '/kanban';
                } else {
                    window.location.href = '/dashboard';
                }
            }
        }, 250);
    });
})();

console.log('[DESKTOP-ONLY] Script chargé - Détection du retour desktop active');