// ============================================
// THEME SWITCHER - Dark/Light Mode
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const themeSwitcherDesktop = document.getElementById('theme-switcher');
    const themeSwitcherMobile = document.getElementById('theme-switcher-mobile');
    const character = document.getElementById('character');
    const body = document.body;
    
    // Chemins des images du personnage (desktop uniquement)
    const images = {
        happy: '/images/character-happy.png',
        hiding: '/images/character-scary.png'
    };
    
    // Charger le thème sauvegardé (par défaut : dark)
    const savedTheme = localStorage.getItem('theme') || 'dark';
    applyTheme(savedTheme);
    
    // ============================================
    // DESKTOP : Écouter le clic sur l'ampoule
    // ============================================
    if (themeSwitcherDesktop) {
        themeSwitcherDesktop.addEventListener('click', function() {
            toggleTheme();
        });
    }
    
    // ============================================
    // MOBILE : Écouter le clic sur le bouton bottom nav
    // ============================================
    if (themeSwitcherMobile) {
        themeSwitcherMobile.addEventListener('click', function() {
            toggleTheme();
        });
    }
    
    // ============================================
    // FONCTION : Toggle entre dark et light
    // ============================================
    function toggleTheme() {
        const currentTheme = body.classList.contains('dark-mode') ? 'dark' : 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        applyTheme(newTheme);
        localStorage.setItem('theme', newTheme);
    }
    
    // ============================================
    // FONCTION : Appliquer le thème
    // ============================================
    function applyTheme(theme) {
        if (theme === 'dark') {
            body.classList.add('dark-mode');
            
            // Change le personnage UNIQUEMENT si il existe (desktop)
            if (character) {
                character.src = images.happy; // Personnage SOURIT en dark mode (sa religion !)
            }
        } else {
            body.classList.remove('dark-mode');
            
            // Change le personnage UNIQUEMENT si il existe (desktop)
            if (character) {
                character.src = images.hiding; // Personnage se cache les yeux en light mode (ça fait mal !)
            }
        }
    }
});