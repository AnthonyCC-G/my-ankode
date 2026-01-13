// Theme Switcher - Toggle Dark/Light Mode
document.addEventListener('DOMContentLoaded', function() {
    const themeSwitcher = document.getElementById('theme-switcher');
    const character = document.getElementById('character');
    const body = document.body;
    
    // Chemins des images
    const images = {
        happy: '/images/character-happy.png',
        hiding: '/images/character-scary.png'
    };
    
    // Charger le thème sauvegardé (par défaut : dark)
    const savedTheme = localStorage.getItem('theme') || 'dark';
    applyTheme(savedTheme);
    
    // Écouter le clic sur l'ampoule
    themeSwitcher.addEventListener('click', function() {
        const currentTheme = body.classList.contains('dark-mode') ? 'dark' : 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        applyTheme(newTheme);
        localStorage.setItem('theme', newTheme);
    });
    
    // Fonction pour appliquer le thème
    function applyTheme(theme) {
        if (theme === 'dark') {
            body.classList.add('dark-mode');
            character.src = images.happy; // Personnage SOURIT en dark mode (sa religion ! = easter egg)
        } else {
            body.classList.remove('dark-mode');
            character.src = images.hiding; //  Personnage se cache les yeux en light mode (ça fait mal !)
        }
    }
});