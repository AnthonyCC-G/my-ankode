/**
 * DESKTOP-ONLY.JS - Détection du retour en mode desktop
 * Redirige vers la page d'origine si l'écran redevient desktop
 */

    (function checkDesktopReturn() {
        // VÉRIFICATION IMMÉDIATE au chargement
        if (window.innerWidth >= 768) {
            const lastPage = localStorage.getItem('lastDesktopPage') || '/dashboard';
            window.location.href = lastPage;
            return; // Stopper l'exécution si on redirige
        }
        
        // ÉCOUTE DES CHANGEMENTS de taille
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth >= 768) {
                    const lastPage = localStorage.getItem('lastDesktopPage') || '/dashboard';
                    window.location.href = lastPage;
                }
            }, 250);
        });
    })();