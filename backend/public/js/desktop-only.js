/**
 * DESKTOP-ONLY.JS - Détection du retour en mode desktop
 * Redirige vers la page d'origine si l'écran redevient desktop
 */

    (function checkDesktopReturn() {
        console.log('[DESKTOP-ONLY] Script chargé - Détection du retour desktop active');
        
        // VÉRIFICATION IMMÉDIATE au chargement
        if (window.innerWidth >= 768) {
            console.log('[DESKTOP-ONLY] Écran desktop détecté au chargement - Retour à la page d\'origine');
            const lastPage = localStorage.getItem('lastDesktopPage') || '/dashboard';
            console.log('[DESKTOP-ONLY] Redirection vers:', lastPage);
            window.location.href = lastPage;
            return; // Stopper l'exécution si on redirige
        }
        
        // ÉCOUTE DES CHANGEMENTS de taille
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth >= 768) {
                    console.log('[DESKTOP-ONLY] Écran desktop détecté après resize - Retour à la page d\'origine');
                    const lastPage = localStorage.getItem('lastDesktopPage') || '/dashboard';
                    console.log('[DESKTOP-ONLY] Redirection vers:', lastPage);
                    window.location.href = lastPage;
                }
            }, 250);
        });
    })();