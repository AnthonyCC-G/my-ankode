// PAGE VEILLE - Gestion complete avec API REST

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== VARIABLES GLOBALES =====
    let currentPage = 1;
    let totalPages = 1;
    let currentFilter = 'all'; // 'all' ou 'favorites'
    let currentSearchKeyword = '';
    
    // ===== GESTION ACCORDEON FAVORIS =====
    const favoritesToggle = document.getElementById('favorites-toggle');
    const favoritesAccordion = document.getElementById('favorites-accordion');
    const veilleGrid = document.querySelector('.veille-grid');

    if (favoritesToggle && favoritesAccordion && veilleGrid) {
        favoritesToggle.addEventListener('click', function() {
            // Toggle accordéon
            this.classList.toggle('active');
            favoritesAccordion.classList.toggle('open');
            
        // Toggle grille étendue
        veilleGrid.classList.toggle('favorites-expanded');
    });
}
    
    // ===== CHARGEMENT INITIAL =====
    loadArticles(currentPage);
    loadFavoritesSidebar();
    
    // ===== GESTION RECHERCHE =====
    const searchInput = document.getElementById('search-input');
    const searchBtn = document.getElementById('search-btn');
    const resetBtn = document.getElementById('reset-btn');
    
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            const keyword = searchInput.value.trim();
            if (keyword) {
                searchArticles(keyword);
            }
        });
    }
    
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const keyword = this.value.trim();
                if (keyword) {
                    searchArticles(keyword);
                }
            }
        });
    }
    
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            searchInput.value = '';
            currentSearchKeyword = '';
            currentPage = 1;
            loadArticles(currentPage);
            showFeedback('Recherche reinitalisee', 'info');
        });
    }
   
    
    // ===== FONCTION : CHARGER LES ARTICLES =====
    async function loadArticles(page) {
        try {
            showLoading();
            
            const response = await fetch(`/api/articles?page=${page}`);
            const data = await response.json();
            
            if (response.ok) {
                displayArticles(data.articles);
                updateTitle('Tous les articles');
            } else {
                showError('Erreur lors du chargement des articles');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showError('Impossible de charger les articles');
        }
    }
    
    // ===== FONCTION : RECHERCHER LES ARTICLES =====
    async function searchArticles(keyword) {
        try {
            showLoading();
            currentSearchKeyword = keyword;
            
            const response = await fetch(`/api/articles/search?q=${encodeURIComponent(keyword)}`);
            const data = await response.json();
            
            if (response.ok) {
                displayArticles(data.articles);
                updateTitle(`Resultats pour "${keyword}"`);
                showFeedback(`${data.count} article(s) trouve(s)`, 'success');
            } else {
                showError('Erreur lors de la recherche');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showError('Impossible de rechercher les articles');
        }
    }
    
    // ===== FONCTION : CHARGER LES FAVORIS =====
    async function loadFavoritesArticles() {
        try {
            showLoading();
            
            const response = await fetch('/api/articles/favorites');
            const data = await response.json();
            
            if (response.ok) {
                displayArticles(data.favorites);
                updateTitle('Mes favoris');
                showFeedback(`${data.count} favori(s)`, 'info');
            } else {
                showError('Erreur lors du chargement des favoris');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showError('Impossible de charger les favoris');
        }
    }
    
    // ===== FONCTION : CHARGER LA SIDEBAR FAVORIS =====
    async function loadFavoritesSidebar() {
        try {
            const response = await fetch('/api/articles/favorites');
            const data = await response.json();
            
            const favoritesList = document.getElementById('favorites-list');
            
            if (response.ok && data.favorites.length > 0) {
                favoritesList.innerHTML = '';
                data.favorites.slice(0, 10).forEach(article => {
                    const item = document.createElement('div');
                    item.className = 'favorite-item';
                    
                    // Structure avec le lien et le bouton côte à côte
                    item.innerHTML = `
                        <a href="${article.url}" target="_blank" class="favorite-link">${article.title}</a>
                        <button class="btn-unfavorite" data-id="${article.id}" title="Retirer des favoris">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    `;
                    
                    favoritesList.appendChild(item);
                });
                
                addUnfavoriteListeners();
                
            } else {
                favoritesList.innerHTML = '<p class="favorites-empty">Aucun favori</p>';
            }
        } catch (error) {
            console.error('Erreur chargement favoris sidebar:', error);
        }
    }


    // ===== FONCTION : EVENT LISTENERS POUR DEFAVORIS SIDEBAR =====
    function addUnfavoriteListeners() {
        document.querySelectorAll('.btn-unfavorite').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.stopPropagation(); // Empeche le clic de se propager
                const articleId = this.dataset.id;
                await removeFavoriteFromSidebar(articleId);
            });
        });
    }

    // ===== FONCTION : RETIRER UN FAVORI DEPUIS LA SIDEBAR =====
    async function removeFavoriteFromSidebar(articleId) {
        try {
            const response = await fetch(`/api/articles/${articleId}/favorite`, {
                method: 'DELETE'
            });
            
            if (response.ok) {
                // Recharger la sidebar favoris
                loadFavoritesSidebar();
                
                // Si on est dans la vue favoris, recharger aussi
                if (currentFilter === 'favorites') {
                    loadFavoritesArticles();
                }
                
                // Mettre a jour le bouton dans le carrousel si l'article est visible
                const btn = document.querySelector(`.btn-favorite[data-id="${articleId}"]`);
                if (btn) {
                    btn.classList.remove('active');
                    const svg = btn.querySelector('svg');
                    svg.setAttribute('fill', 'none');
                }
                
                showFeedback('Article retire des favoris', 'success');
            }
        } catch (error) {
            console.error('Erreur retrait favori:', error);
            showFeedback('Erreur lors du retrait', 'error');
        }
    }
    
    // ===== FONCTION : AFFICHER LES ARTICLES =====
    function displayArticles(articles) {
        const container = document.getElementById('articles-container');
        
        if (!container) return;
        
        if (articles.length === 0) {
            container.innerHTML = '<div class="articles-loading"><p>Aucun article trouve</p></div>';
            return;
        }
        
        const grid = document.createElement('div');
        grid.className = 'articles-grid';
        
        articles.forEach(article => {
            const card = createArticleCard(article);
            grid.appendChild(card);
        });
        
        container.innerHTML = '';
        container.appendChild(grid);
        
        addArticleEventListeners();
        
        // Mettre a jour les boutons carrousel apres le rendu
        requestAnimationFrame(updateCarouselButtonsIfExists);
    }
    
    // ===== FONCTION : CREER UNE CARD =====
    function createArticleCard(article) {
        const card = document.createElement('div');
        card.className = `article-card ${article.isRead ? 'read' : ''}`;
        card.dataset.articleId = article.id;
        
        card.innerHTML = `
            <h4 class="article-title">${article.title}</h4>
            <p class="article-source">${article.source} - ${article.publishedAt}</p>
            <p class="article-description">${article.description || 'Pas de description'}</p>
            <div class="article-actions">
                <button class="btn-read" data-id="${article.id}" title="Marquer comme ${article.isRead ? 'non lu' : 'lu'}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </button>
                <button class="btn-favorite ${article.isFavorite ? 'active' : ''}" data-id="${article.id}" title="${article.isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="${article.isFavorite ? 'currentColor' : 'none'}" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
                    </svg>
                </button>
                <button class="btn-link" data-url="${article.url}" title="Ouvrir l'article">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </button>
            </div>
        `;
        
        return card;
    }
    
    // ===== FONCTION : AJOUTER LES EVENT LISTENERS =====
    function addArticleEventListeners() {
        // Bouton "Marquer comme lu"
        document.querySelectorAll('.btn-read').forEach(btn => {
            btn.addEventListener('click', async function() {
                const articleId = this.dataset.id;
                await toggleReadStatus(articleId);
            });
        });
        
        // Bouton "Favoris"
        document.querySelectorAll('.btn-favorite').forEach(btn => {
            btn.addEventListener('click', async function() {
                const articleId = this.dataset.id;
                const isCurrentlyFavorite = this.classList.contains('active');
                await toggleFavorite(articleId, isCurrentlyFavorite);
            });
        });
        
        // Bouton "Ouvrir le lien"
        document.querySelectorAll('.btn-link').forEach(btn => {
            btn.addEventListener('click', function() {
                const url = this.dataset.url;
                window.open(url, '_blank');
            });
        });
    }
    
    // ===== FONCTION : TOGGLE READ STATUS =====
    async function toggleReadStatus(articleId) {
        try {
            const response = await fetch(`/api/articles/${articleId}/mark-read`, {
                method: 'PATCH'
            });
            
            if (response.ok) {
                const card = document.querySelector(`[data-article-id="${articleId}"]`);
                if (card) {
                    card.classList.toggle('read');
                }
            }
        } catch (error) {
            console.error('Erreur toggle read:', error);
        }
    }
    
    // ===== FONCTION : TOGGLE FAVORITE =====
    async function toggleFavorite(articleId, isCurrentlyFavorite) {
        try {
            const method = isCurrentlyFavorite ? 'DELETE' : 'POST';
            const response = await fetch(`/api/articles/${articleId}/favorite`, {
                method: method
            });
            
            if (response.ok) {
                const btn = document.querySelector(`.btn-favorite[data-id="${articleId}"]`);
                const svg = btn.querySelector('svg');
                
                btn.classList.toggle('active');
                const isFavorite = btn.classList.contains('active');
                svg.setAttribute('fill', isFavorite ? 'currentColor' : 'none');
                
                // Recharger la sidebar favoris
                loadFavoritesSidebar();
                
                // Si on est dans la vue favoris, recharger
                if (currentFilter === 'favorites') {
                    loadFavoritesArticles();
                }
            }
        } catch (error) {
            console.error('Erreur toggle favorite:', error);
        }
    }
    
    // ===== FONCTION : METTRE A JOUR LE TITRE =====
    function updateTitle(title) {
        const titleElement = document.getElementById('articles-title');
        if (titleElement) {
            titleElement.textContent = title;
        }
    }
    
    
    // ===== FONCTION : AFFICHER LE LOADING =====
    function showLoading() {
        const container = document.getElementById('articles-container');
        if (container) {
            container.innerHTML = `
                <div class="articles-loading">
                    <div class="spinner"></div>
                    <p>Chargement des articles...</p>
                </div>
            `;
        }
    }
    
    // ===== FONCTION : AFFICHER UN MESSAGE =====
    function showFeedback(message, type = 'info') {
        const feedback = document.getElementById('search-feedback');
        if (!feedback) return;
        
        const alertClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-danger' : 'alert-info';
        
        feedback.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
        
        setTimeout(() => {
            feedback.innerHTML = '';
        }, 3000);
    }
    
    // ===== FONCTION : AFFICHER UNE ERREUR =====
    function showError(message) {
        const container = document.getElementById('articles-container');
        if (container) {
            container.innerHTML = `
                <div class="articles-loading">
                    <p style="color: var(--red-500);">${message}</p>
                </div>
            `;
        }
    }
    
    // ===== FONCTION UTILITAIRE : METTRE A JOUR BOUTONS CARROUSEL =====
    function updateCarouselButtonsIfExists() {
        const carouselPrev = document.getElementById('carousel-prev');
        const carouselNext = document.getElementById('carousel-next');
        const articlesContainer = document.getElementById('articles-container');
        const carouselWrapper = document.querySelector('.carousel-wrapper');
        
        if (carouselPrev && carouselNext && articlesContainer) {
            const scrollLeft = articlesContainer.scrollLeft;
            const maxScroll = articlesContainer.scrollWidth - articlesContainer.clientWidth;
            
            // Desactiver boutons
            carouselPrev.disabled = scrollLeft <= 0;
            carouselNext.disabled = scrollLeft >= maxScroll - 1;
            
            // Gerer gradients lateraux
            if (carouselWrapper) {
                // Gradient gauche visible si on peut scroller a gauche
                if (scrollLeft > 10) {
                    carouselWrapper.style.setProperty('--gradient-left-opacity', '1');
                } else {
                    carouselWrapper.style.setProperty('--gradient-left-opacity', '0');
                }
                
                // Gradient droit visible si on peut scroller a droite
                if (scrollLeft < maxScroll - 10) {
                    carouselWrapper.style.setProperty('--gradient-right-opacity', '1');
                } else {
                    carouselWrapper.style.setProperty('--gradient-right-opacity', '0');
                }
            }
        }
    }


    // ===== GESTION CARROUSEL : NAVIGATION AVEC FLECHES =====
    const carouselPrev = document.getElementById('carousel-prev');
    const carouselNext = document.getElementById('carousel-next');
    const articlesContainer = document.getElementById('articles-container');

    if (carouselPrev && carouselNext && articlesContainer) {
        
        // Fonction pour scroller vers la gauche
        carouselPrev.addEventListener('click', function() {
            const scrollAmount = 420;
            articlesContainer.scrollBy({
                left: -scrollAmount,
                behavior: 'smooth'
            });
        });
        
        // Fonction pour scroller vers la droite
        carouselNext.addEventListener('click', function() {
            const scrollAmount = 420;
            articlesContainer.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });

        // Convertir scroll vertical (molette) en scroll horizontal
        articlesContainer.addEventListener('wheel', function(e) {
            // Empecher le scroll vertical de la page
            e.preventDefault();
            
            // Convertir deltaY (vertical) en scroll horizontal
            articlesContainer.scrollBy({
                left: e.deltaY,
                behavior: 'auto' // Pas smooth pour la molette
            });
        }, { passive: false }); // passive:false pour permettre preventDefault

        });


        
        // Fonction pour activer/desactiver les boutons selon la position
        function updateCarouselButtons() {
            const scrollLeft = articlesContainer.scrollLeft;
            const maxScroll = articlesContainer.scrollWidth - articlesContainer.clientWidth;
            
            carouselPrev.disabled = scrollLeft <= 0;
            carouselNext.disabled = scrollLeft >= maxScroll - 1;
        }
        
        // Ecouter le scroll pour mettre a jour les boutons
        articlesContainer.addEventListener('scroll', updateCarouselButtons);

        // Mettre a jour aussi les gradients lors du scroll
        articlesContainer.addEventListener('scroll', function() {
            requestAnimationFrame(updateCarouselButtonsIfExists);
        });

    }
    
}); // FIN du DOMContentLoaded

