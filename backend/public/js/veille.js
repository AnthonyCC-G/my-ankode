// PAGE VEILLE - Gestion Accordeon Favoris

document.addEventListener('DOMContentLoaded', function() {
    
    // Gestion accordeon favoris
    const favoritesToggle = document.getElementById('favorites-toggle');
    const favoritesAccordion = document.getElementById('favorites-accordion');
    
    if (favoritesToggle && favoritesAccordion) {
        favoritesToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            favoritesAccordion.classList.toggle('open');
        });
    }
    
    // TODO: Ajouter ici ton code pour charger les articles, gerer la recherche, etc.
    
});