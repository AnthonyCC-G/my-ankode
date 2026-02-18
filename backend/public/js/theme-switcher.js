// ============================================
// THEME SWITCHER - Dark/Light Mode
// ============================================


document.addEventListener('DOMContentLoaded', function() {
    // 1. RÉCUPÉRATION DES ÉLÉMENTS DOM
    // Gestion responsive : boutons desktop ET mobile
    const themeSwitcherDesktop = document.getElementById('theme-switcher');
    const themeSwitcherMobile = document.getElementById('theme-switcher-mobile');
    const character = document.getElementById('character');
    const body = document.body;
    
    // 2. CONFIGURATION DES ASSETS
    // Easter egg : la mascotte réagit au changement de thème
    const images = {
        happy: '/images/character-happy.png', // Dark mode 
        hiding: '/images/character-scary.png' // Light mode (ça pique !)
    };
    
    // 3. PERSISTANCE - Chargement du thème sauvegardé
    // Défaut : 'dark' (audience développeurs)
    const savedTheme = localStorage.getItem('theme') || 'dark';
    applyTheme(savedTheme);
    
    // ============================================
    // 4. GESTION D'ÉVÉNEMENTS - DESKTOP
    // ============================================
    if (themeSwitcherDesktop) {
        themeSwitcherDesktop.addEventListener('click', function() {
            toggleTheme();
        });
    }
    
    // ============================================
    // 5. GESTION D'ÉVÉNEMENTS - MOBILE
    // ============================================
    if (themeSwitcherMobile) {
        themeSwitcherMobile.addEventListener('click', function() {
            toggleTheme();
        });
    }
    
    // ============================================
    // 6. FONCTION TOGGLE - Bascule entre thèmes
    // ============================================
    function toggleTheme() {
        const currentTheme = body.classList.contains('dark-mode') ? 'dark' : 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        applyTheme(newTheme);
        localStorage.setItem('theme', newTheme);
    }
    
    // ============================================
    // 7. FONCTION APPLY - Application du thème
    // ============================================
    function applyTheme(theme) {
        if (theme === 'dark') {
            // MANIPULATION DOM : ajout classe
            body.classList.add('dark-mode');
            
            // Easter egg desktop uniquement (responsive)
            if (character) {
                character.src = images.happy; // Personnage SOURIT en dark mode
            }
        } else {
            // MANIPULATION DOM : retrait classe
            body.classList.remove('dark-mode');
            
            // Easter egg desktop uniquement
            if (character) {
                character.src = images.hiding; // Personnage se cache les yeux en light mode (ça fait mal !)
            }
        }
    }
});