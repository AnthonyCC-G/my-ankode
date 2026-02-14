/**
 * DESKTOP-ONLY.JS - Affichage message desktop
 * Détecte le retour en mode desktop mais SANS redirection auto
 */

(function checkDesktopReturn() {
    //  PAS de redirection automatique au chargement
    // L'utilisateur voit le message et clique sur le bouton
    
    // ÉCOUTE DES CHANGEMENTS de taille
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Si retour en desktop, proposer de retourner (sans forcer)
            if (window.innerWidth >= 768) {
                // Option 1 : Afficher un bouton (si pas déjà présent)
                const existingBtn = document.getElementById('auto-redirect-btn');
                if (!existingBtn) {
                    const btn = document.createElement('button');
                    btn.id = 'auto-redirect-btn';
                    btn.textContent = ' Retourner à la version desktop';
                    btn.className = 'btn btn-primary mt-3';
                    btn.onclick = function() {
                        const lastPage = localStorage.getItem('lastDesktopPage') || '/dashboard';
                        window.location.href = lastPage;
                    };
                    document.querySelector('.container').appendChild(btn);
                }
            }
        }, 250);
    });
})();