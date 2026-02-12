// ============================================================================
// PAGE VEILLE - Gestion complète avec API REST
// ============================================================================
// Structure :
//   1. VARIABLES GLOBALES
//   2. INITIALISATION & CHARGEMENT
//   3. GESTION UI DESKTOP (Accordéon Favoris)
//   4. GESTION UI MOBILE (Tabs)
//   5. GESTION FILTRES & RECHERCHE
//   6. CHARGEMENT ARTICLES (API)
//   7. CHARGEMENT FAVORIS (API)
//   8. AFFICHAGE & RENDU
//   9. ACTIONS ARTICLES (Lu/Favori/Lien)
//  10. CARROUSEL (Navigation)
//  11. UTILITAIRES
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================================================
    // 1. VARIABLES GLOBALES
    // ========================================================================
    
    // --- Pagination ---
    let currentPage = 1;
    let totalPages = 1;
    let totalArticles = 0;
    
    // --- Filtres principaux ---
    let currentFilter = 'all'; // 'all' ou 'favorites'
    let currentSearchKeyword = '';
    
    // --- Filtres articles ---
    let allArticles = []; // Cache tous les articles chargés
    let currentSourceFilter = 'all';
    let currentStatusFilter = 'all'; // 'all', 'read', 'unread'
    let currentSortOrder = 'desc'; // 'desc', 'asc'
    
    // --- Filtres favoris ---
    let allFavorites = []; // Cache tous les favoris
    let currentFavSourceFilter = 'all';
    let currentFavStatusFilter = 'all';
    let currentFavSortOrder = 'desc';
    
    // --- Sources disponibles (chargées depuis API) ---
    let availableSources = [];
    
    
    // ========================================================================
    // 2. INITIALISATION & CHARGEMENT
    // ========================================================================
    
    // --- Chargement initial au démarrage de la page ---
    loadArticles(currentPage);
    loadFavoritesSidebar();
    loadAvailableSources();
    
    
    // ========================================================================
    // 3. GESTION UI DESKTOP - Accordéon Favoris
    // ========================================================================
    
    const favoritesToggle = document.getElementById('favorites-toggle');
    const favoritesAccordion = document.getElementById('favorites-accordion');
    const veilleGrid = document.querySelector('.veille-grid');
    
    if (favoritesToggle && favoritesAccordion && veilleGrid) {
        favoritesToggle.addEventListener('click', function() {
            // Désactiver en mobile (≤ 768px)
            if (window.innerWidth <= 768) return;
            
            // Toggle accordéon
            this.classList.toggle('active');
            favoritesAccordion.classList.toggle('open');
            
            // Toggle grille étendue
            veilleGrid.classList.toggle('favorites-expanded');
        });
    }
    
    
    // ========================================================================
    // 4. GESTION UI MOBILE - Tabs (Tous / Favoris)
    // ========================================================================
    
    const tabAll = document.getElementById('tab-all');
    const tabFavorites = document.getElementById('tab-favorites');
    
    if (tabAll && tabFavorites) {
        // --- Tab "Tous" ---
        tabAll.addEventListener('click', function() {
            if (this.classList.contains('active')) return;
            
            // Switch tabs
            tabAll.classList.add('active');
            tabFavorites.classList.remove('active');
            
            // Charger tous les articles
            currentFilter = 'all';
            currentPage = 1;
            currentSearchKeyword = '';
            loadArticles(currentPage);
        });
        
        // --- Tab "Favoris" ---
        tabFavorites.addEventListener('click', function() {
            if (this.classList.contains('active')) return;
            
            // Switch tabs
            tabFavorites.classList.add('active');
            tabAll.classList.remove('active');
            
            // Charger les favoris
            currentFilter = 'favorites';
            loadFavoritesArticles();
        });
    }
    
    
    // ========================================================================
    // 5. GESTION FILTRES & RECHERCHE
    // ========================================================================
    
    // --- 5.1 Select Source (nouveau système) ---
    const sourceFilter = document.getElementById('source-filter');
    if (sourceFilter) {
        sourceFilter.addEventListener('change', function() {
            currentSourceFilter = this.value;
            currentPage = 1;
            loadArticles(currentPage);
        });
    }
    
    // --- 5.2 Boutons Status & Date (ancien système toggle) ---
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const filterType = this.dataset.filterType;
            toggleFilter(filterType, this);
        });
    });
    
    // --- 5.3 Recherche ---
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
            // Réinitialiser la recherche
            searchInput.value = '';
            currentSearchKeyword = '';
            currentPage = 1;
            
            // Réinitialiser les filtres
            currentSourceFilter = 'all';
            currentStatusFilter = 'all';
            currentSortOrder = 'desc';
            
            // Réinitialiser le select visually
            if (sourceFilter) {
                sourceFilter.value = 'all';
            }
            
            // Réinitialiser les boutons
            const statusBtn = document.querySelector('[data-filter-type="status"]');
            const dateBtn = document.querySelector('[data-filter-type="date"]');
            
            if (statusBtn) {
                statusBtn.textContent = 'Tous';
                statusBtn.dataset.currentState = 'all';
            }
            if (dateBtn) {
                const textNode = Array.from(dateBtn.childNodes).find(node => node.nodeType === 3);
                if (textNode) {
                    textNode.textContent = 'Récents';
                }
                dateBtn.dataset.currentState = 'desc';
            }
            
            // Recharger les articles
            loadArticles(currentPage);
            showFeedback('Recherche et filtres réinitialisés', 'info');
        });
    }
    
    
    // ========================================================================
    // 6. CHARGEMENT ARTICLES (API)
    // ========================================================================
    
    /**
     * Charge les articles depuis l'API avec filtres et pagination
     * @param {number} page - Numéro de page
     */
    async function loadArticles(page) {
        try {
            showLoading();
            
            // Construire l'URL avec filtres
            let url = `/api/articles?page=${page}`;
            if (currentSourceFilter !== 'all') {
                url += `&source=${encodeURIComponent(currentSourceFilter)}`;
            }
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (response.ok) {
                allArticles = data.articles;
                
                // NOUVEAU : Stocker les infos de pagination
                currentPage = data.pagination.currentPage;
                totalPages = data.pagination.totalPages;
                totalArticles = data.pagination.totalArticles;
                
                console.log('📊 Pagination:', { currentPage, totalPages, totalArticles });
                
                applyFiltersAndDisplay();
                updatePagination(); // NOUVEAU : Appel fonction pagination
                
                // Titre dynamique selon filtre
                const title = currentSourceFilter !== 'all' 
                    ? `Articles - ${currentSourceFilter}` 
                    : 'Tous les articles';
                updateTitle(title);
            } else {
                showError('Erreur lors du chargement des articles');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showError('Impossible de charger les articles');
        }
    }
    
    /**
     * Recherche d'articles par mot-clé
     * @param {string} keyword - Mot-clé de recherche
     */
    async function searchArticles(keyword) {
        try {
            showLoading();
            currentSearchKeyword = keyword;
            
            const response = await fetch(`/api/articles/search?q=${encodeURIComponent(keyword)}`);
            const data = await response.json();
            
            if (response.ok) {
                displayArticles(data.articles);
                updateTitle(`Résultats pour "${keyword}"`);
                showFeedback(`${data.count} article(s) trouvé(s)`, 'success');
            } else {
                showError('Erreur lors de la recherche');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showError('Impossible de rechercher les articles');
        }
    }
    
    /**
     * Charge les sources disponibles depuis l'API
     */
    async function loadAvailableSources() {
        try {
            const response = await fetch('/api/articles/sources');
            const data = await response.json();
            
            if (response.ok) {
                availableSources = data.sources;
                populateSourceFilter();
            }
        } catch (error) {
            console.error('Erreur chargement sources:', error);
        }
    }
    
    /**
     * Peuple le select des sources avec les données de l'API
     */
    function populateSourceFilter() {
        const sourceSelect = document.getElementById('source-filter');
        
        if (!sourceSelect) return;
        
        sourceSelect.innerHTML = '<option value="all">Toutes les sources</option>';
        
        availableSources.forEach(source => {
            const option = document.createElement('option');
            option.value = source;
            option.textContent = source;
            sourceSelect.appendChild(option);
        });
    }
    
    
    // ========================================================================
    // 7. CHARGEMENT FAVORIS (API)
    // ========================================================================
    
    /**
     * Charge les favoris de l'utilisateur
     */
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
    
    /**
     * Charge la sidebar favoris (desktop) + met à jour les badges (mobile)
     */
    async function loadFavoritesSidebar() {
        try {
            const response = await fetch('/api/articles/favorites');
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error('Erreur chargement favoris');
            }
            
            allFavorites = data.favorites;
            
            // Mettre à jour les compteurs
            const favoritesBadge = document.getElementById('favorites-badge');
            const favoritesCount = document.getElementById('favorites-count');
            const count = allFavorites.length;
            
            if (favoritesBadge) {
                favoritesBadge.textContent = count;
            }
            if (favoritesCount) {
                favoritesCount.textContent = `(${count})`;
            }
            
            // Afficher la liste filtrée
            applyFavoritesFilters();
            
        } catch (error) {
            console.error('Erreur chargement favoris sidebar:', error);
            const favoritesList = document.getElementById('favorites-list');
            if (favoritesList) {
                favoritesList.innerHTML = '<p class="favorites-empty">Erreur de chargement</p>';
            }
        }
    }
    
    /**
     * Retire un article des favoris depuis la sidebar
     * @param {string} articleId - ID de l'article
     */
    async function removeFavoriteFromSidebar(articleId) {
        try {
            const data = await API.delete(`/api/articles/${articleId}/favorite`);
            
            if (data.success) {
                loadFavoritesSidebar();
                
                if (currentFilter === 'favorites') {
                    loadFavoritesArticles();
                }
                
                // Mettre à jour le bouton dans le carrousel
                const btn = document.querySelector(`.btn-favorite[data-id="${articleId}"]`);
                if (btn) {
                    btn.classList.remove('active');
                    const svg = btn.querySelector('svg');
                    svg.setAttribute('fill', 'none');
                }
                
                showFeedback('Article retiré des favoris', 'success');
            }
        } catch (error) {
            console.error('Erreur retrait favori:', error);
            showFeedback('Erreur lors du retrait', 'error');
        }
    }
    
    
    // ========================================================================
    // 8. AFFICHAGE & RENDU
    // ========================================================================
    
    /**
     * Toggle les filtres (Status, Date)
     * NOTE: Le filtre Source est géré par le <select>, pas ici
     * @param {string} filterType - Type de filtre ('status' ou 'date')
     * @param {HTMLElement} button - Bouton cliqué
     */
    function toggleFilter(filterType, button) {
        const target = button.dataset.target; // 'favorites' ou undefined
        
        if (filterType === 'status') {
            const states = ['all', 'read', 'unread'];
            const labels = ['Tous', 'Lus', 'Non lus'];
            
            if (target === 'favorites') {
                const currentIndex = states.indexOf(currentFavStatusFilter);
                const nextIndex = (currentIndex + 1) % states.length;
                currentFavStatusFilter = states[nextIndex];
            } else {
                const currentIndex = states.indexOf(currentStatusFilter);
                const nextIndex = (currentIndex + 1) % states.length;
                currentStatusFilter = states[nextIndex];
            }
            
            const nextIndex = target === 'favorites'
                ? states.indexOf(currentFavStatusFilter)
                : states.indexOf(currentStatusFilter);
            button.textContent = labels[nextIndex];
            button.dataset.currentState = states[nextIndex];
            
        } else if (filterType === 'date') {
            const states = ['desc', 'asc'];
            const labels = ['Récents', 'Anciens'];
            
            if (target === 'favorites') {
                const currentIndex = states.indexOf(currentFavSortOrder);
                const nextIndex = (currentIndex + 1) % states.length;
                currentFavSortOrder = states[nextIndex];
            } else {
                const currentIndex = states.indexOf(currentSortOrder);
                const nextIndex = (currentIndex + 1) % states.length;
                currentSortOrder = states[nextIndex];
            }
            
            const nextIndex = target === 'favorites'
                ? states.indexOf(currentFavSortOrder)
                : states.indexOf(currentSortOrder);
            
            // Garder le SVG et changer le texte
            const textNode = Array.from(button.childNodes).find(node => node.nodeType === 3);
            if (textNode) {
                textNode.textContent = labels[nextIndex];
            }
            button.dataset.currentState = states[nextIndex];
        }
        
        // Appliquer les filtres selon la cible
        if (target === 'favorites') {
            applyFavoritesFilters();
        } else {
            applyFiltersAndDisplay();
        }
    }
    
    /**
     * Applique les filtres côté client et affiche les articles
     */
    function applyFiltersAndDisplay() {
        let filtered = [...allArticles];
        
        // NOTE: Filtre source déjà appliqué côté backend
        // On garde ce code au cas où on charge tous les articles
        if (currentSourceFilter !== 'all') {
            filtered = filtered.filter(article => article.source === currentSourceFilter);
        }
        
        // Filtre statut
        if (currentStatusFilter === 'read') {
            filtered = filtered.filter(article => article.isRead === true);
        } else if (currentStatusFilter === 'unread') {
            filtered = filtered.filter(article => article.isRead === false);
        }
        
        // Tri par date
        filtered.sort((a, b) => {
            const dateA = new Date(a.publishedAt.split('/').reverse().join('-'));
            const dateB = new Date(b.publishedAt.split('/').reverse().join('-'));
            
            if (currentSortOrder === 'desc') {
                return dateB - dateA;
            } else {
                return dateA - dateB;
            }
        });
        
        displayArticles(filtered);
        showFeedback(`${filtered.length} article(s) affiché(s)`, 'info');
    }
    
    /**
     * Applique les filtres sur les favoris
     */
    function applyFavoritesFilters() {
        let filtered = [...allFavorites];
        
        // Filtre source
        if (currentFavSourceFilter !== 'all') {
            filtered = filtered.filter(article => article.source === currentFavSourceFilter);
        }
        
        // Filtre statut
        if (currentFavStatusFilter === 'read') {
            filtered = filtered.filter(article => article.isRead === true);
        } else if (currentFavStatusFilter === 'unread') {
            filtered = filtered.filter(article => article.isRead === false);
        }
        
        // Tri par date
        filtered.sort((a, b) => {
            const dateA = new Date(a.publishedAt.split('/').reverse().join('-'));
            const dateB = new Date(b.publishedAt.split('/').reverse().join('-'));
            
            if (currentFavSortOrder === 'desc') {
                return dateB - dateA;
            } else {
                return dateA - dateB;
            }
        });
        
        displayFavoritesList(filtered);
    }
    
    /**
     * Affiche les articles dans le carrousel
     * @param {Array} articles - Liste des articles à afficher
     */
    function displayArticles(articles) {
        const container = document.getElementById('articles-container');
        
        if (!container) return;
        
        if (articles.length === 0) {
            container.innerHTML = '<div class="articles-loading"><p>Aucun article trouvé</p></div>';
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
        
        // Mettre à jour les boutons carrousel
        requestAnimationFrame(updateCarouselButtonsIfExists);
    }
    
    /**
     * Affiche la liste des favoris dans la sidebar
     * @param {Array} favorites - Liste des favoris
     */
    function displayFavoritesList(favorites) {
        const favoritesList = document.getElementById('favorites-list');
        
        if (!favoritesList) return;
        
        if (favorites.length === 0) {
            favoritesList.innerHTML = '<p class="favorites-empty">Aucun favori</p>';
            return;
        }
        
        favoritesList.innerHTML = '';
        
        favorites.slice(0, 10).forEach(article => {
            const item = document.createElement('div');
            item.className = 'favorite-item';
            
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
    }
    
    /**
     * Crée une card article
     * @param {Object} article - Données de l'article
     * @returns {HTMLElement} Card article
     */
    function createArticleCard(article) {
        const card = document.createElement('div');
        card.className = `article-card ${article.isRead ? 'read' : ''}`;
        card.dataset.articleId = article.id;
        
        card.innerHTML = `
            <h4 class="article-title">${cleanText(article.title)}</h4>
            <p class="article-source">${article.source} - ${article.publishedAt}</p>
            <p class="article-description">${cleanText(article.description) || 'Pas de description'}</p>
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
    
    
    // ========================================================================
    // 9. ACTIONS ARTICLES (Lu / Favori / Lien)
    // ========================================================================
    
    /**
     * Ajoute les event listeners sur les boutons d'action
     */
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
    
    /**
     * Ajoute les event listeners sur les boutons défavoris sidebar
     */
    function addUnfavoriteListeners() {
        document.querySelectorAll('.btn-unfavorite').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.stopPropagation();
                const articleId = this.dataset.id;
                await removeFavoriteFromSidebar(articleId);
            });
        });
    }
    
    /**
     * Toggle le statut lu/non lu d'un article
     * @param {string} articleId - ID de l'article
     */
    async function toggleReadStatus(articleId) {
        try {
            const data = await API.patch(`/api/articles/${articleId}/mark-read`);
            
            if (data.success) {
                const card = document.querySelector(`[data-article-id="${articleId}"]`);
                if (card) {
                    card.classList.toggle('read');
                }
            }
        } catch (error) {
            console.error('Erreur toggle read:', error);
            showFeedback('Erreur lors du marquage', 'error');
        }
    }
    
    /**
     * Toggle favori/non favori
     * @param {string} articleId - ID de l'article
     * @param {boolean} isCurrentlyFavorite - État actuel
     */
    async function toggleFavorite(articleId, isCurrentlyFavorite) {
        try {
            let data;
            if (isCurrentlyFavorite) {
                data = await API.delete(`/api/articles/${articleId}/favorite`);
            } else {
                data = await API.post(`/api/articles/${articleId}/favorite`);
            }
            
            if (data.success) {
                const btn = document.querySelector(`.btn-favorite[data-id="${articleId}"]`);
                const svg = btn.querySelector('svg');
                
                btn.classList.toggle('active');
                const isFavorite = btn.classList.contains('active');
                svg.setAttribute('fill', isFavorite ? 'currentColor' : 'none');
                
                loadFavoritesSidebar();
                
                if (currentFilter === 'favorites') {
                    loadFavoritesArticles();
                }
            }
        } catch (error) {
            console.error('Erreur toggle favorite:', error);
            showFeedback('Erreur lors de la mise à jour', 'error');
        }
    }
    
    
    // ========================================================================
    // 10. CARROUSEL - Navigation
    // ========================================================================
    
    const carouselPrev = document.getElementById('carousel-prev');
    const carouselNext = document.getElementById('carousel-next');
    const articlesContainer = document.getElementById('articles-container');
    
    if (carouselPrev && carouselNext && articlesContainer) {
        
        // Bouton précédent
        carouselPrev.addEventListener('click', function() {
            const scrollAmount = 420;
            articlesContainer.scrollBy({
                left: -scrollAmount,
                behavior: 'smooth'
            });
        });
        
        // Bouton suivant
        carouselNext.addEventListener('click', function() {
            const scrollAmount = 420;
            articlesContainer.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });
        });
        
        // Convertir scroll vertical (molette) en scroll horizontal
        articlesContainer.addEventListener('wheel', function(e) {
            e.preventDefault();
            articlesContainer.scrollBy({
                left: e.deltaY,
                behavior: 'auto'
            });
        }, { passive: false });
        
        // Mise à jour boutons et gradients lors du scroll
        articlesContainer.addEventListener('scroll', function() {
            requestAnimationFrame(updateCarouselButtonsIfExists);
        });
    }
    
    
    // ========================================================================
    // 10.5 PAGINATION
    // ========================================================================
    
    const paginationPrev = document.getElementById('pagination-prev');
    const paginationNext = document.getElementById('pagination-next');
    
    // --- Event listeners pagination ---
    if (paginationPrev && paginationNext) {
        paginationPrev.addEventListener('click', function() {
            if (currentPage > 1) {
                loadArticles(currentPage - 1);
                // Scroll vers le début du carrousel
                if (articlesContainer) {
                    articlesContainer.scrollTo({ left: 0, behavior: 'smooth' });
                }
            }
        });
        
        paginationNext.addEventListener('click', function() {
            if (currentPage < totalPages) {
                loadArticles(currentPage + 1);
                if (articlesContainer) {
                    articlesContainer.scrollTo({ left: 0, behavior: 'smooth' });
                }
            }
        });
    }
    
    /**
     * Met à jour l'affichage de la pagination
     */
    function updatePagination() {
        const paginationContainer = document.getElementById('pagination-container');
        const paginationInfo = document.getElementById('pagination-info');
        const paginationPrev = document.getElementById('pagination-prev');
        const paginationNext = document.getElementById('pagination-next');
        
        
        // Toujours afficher la pagination (même avec 1 page)
        if (paginationContainer) {
            paginationContainer.style.display = 'flex';
        }
        if (paginationInfo) {
            paginationInfo.textContent = `Page ${currentPage} / ${totalPages}`;
        }
        if (paginationPrev) {
            paginationPrev.disabled = currentPage === 1;
        }
        if (paginationNext) {
            paginationNext.disabled = currentPage >= totalPages;
        }
    }
    
    
    // ========================================================================
    // 11. UTILITAIRES
    // ========================================================================
    
    /**
     * Nettoie le texte (sauts de ligne, espaces multiples)
     * @param {string} text - Texte à nettoyer
     * @returns {string} Texte nettoyé
     */
    function cleanText(text) {
        if (!text) return '';
        
        return text
            .replace(/\n+/g, ' ')
            .replace(/\r+/g, ' ')
            .replace(/\s{2,}/g, ' ')
            .trim();
    }
    
    /**
     * Met à jour le titre de la section articles
     * @param {string} title - Nouveau titre
     */
    function updateTitle(title) {
        const titleElement = document.getElementById('articles-title');
        if (titleElement) {
            titleElement.textContent = title;
        }
    }
    
    /**
     * Affiche l'état de chargement
     */
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
    
    /**
     * Affiche un message de feedback
     * @param {string} message - Message à afficher
     * @param {string} type - Type de message ('info', 'success', 'error')
     */
    function showFeedback(message, type = 'info') {
        const feedback = document.getElementById('search-feedback');
        if (!feedback) return;
        
        const alertClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-danger' : 'alert-info';
        
        feedback.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
        
        setTimeout(() => {
            feedback.innerHTML = '';
        }, 3000);
    }
    
    /**
     * Affiche un message d'erreur
     * @param {string} message - Message d'erreur
     */
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
    
    /**
     * Met à jour les boutons et gradients du carrousel
     */
    function updateCarouselButtonsIfExists() {
        const carouselPrev = document.getElementById('carousel-prev');
        const carouselNext = document.getElementById('carousel-next');
        const articlesContainer = document.getElementById('articles-container');
        const carouselWrapper = document.querySelector('.carousel-wrapper');
        
        if (carouselPrev && carouselNext && articlesContainer) {
            const scrollLeft = articlesContainer.scrollLeft;
            const maxScroll = articlesContainer.scrollWidth - articlesContainer.clientWidth;
            
            // Désactiver boutons
            carouselPrev.disabled = scrollLeft <= 0;
            carouselNext.disabled = scrollLeft >= maxScroll - 1;
            
            // Gérer gradients latéraux
            if (carouselWrapper) {
                if (scrollLeft > 10) {
                    carouselWrapper.style.setProperty('--gradient-left-opacity', '1');
                } else {
                    carouselWrapper.style.setProperty('--gradient-left-opacity', '0');
                }
                
                if (scrollLeft < maxScroll - 10) {
                    carouselWrapper.style.setProperty('--gradient-right-opacity', '1');
                } else {
                    carouselWrapper.style.setProperty('--gradient-right-opacity', '0');
                }
            }
        }
    }
    
}); // FIN DOMContentLoaded
