// backend/public/js/veille.js
// JavaScript vanilla pour la veille technologique complete

document.addEventListener('DOMContentLoaded', function() {
    // Active la classe pour le layout fixe (SANS scroll de page)
    document.body.classList.add('veille-page');
    
    let currentPage = 1;
    let currentKeyword = '';
    let viewMode = 'all'; // 'all' ou 'favorites'
    
    // Elements DOM
    const searchInput = document.getElementById('search-input');
    const searchBtn = document.getElementById('search-btn');
    const resetBtn = document.getElementById('reset-btn');
    const showFavoritesBtn = document.getElementById('show-favorites-btn');
    const showAllBtn = document.getElementById('show-all-btn');
    
    // Charge les articles au demarrage
    loadArticles(currentPage);
    loadFavorites();
    
    // Event listeners
    searchBtn.addEventListener('click', handleSearch);
    resetBtn.addEventListener('click', handleReset);
    showFavoritesBtn.addEventListener('click', () => switchView('favorites'));
    showAllBtn.addEventListener('click', () => switchView('all'));
    
    // Recherche en appuyant sur Entree
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            handleSearch();
        }
    });
    
    /**
     * Gere la recherche par mot-cle
     */
    function handleSearch() {
        const keyword = searchInput.value.trim();
        
        if (keyword.length === 0) {
            showError('Veuillez saisir un mot-cle');
            return;
        }
        
        currentKeyword = keyword;
        searchArticles(keyword);
    }
    
    /**
     * Reinitialise la recherche
     */
    function handleReset() {
        searchInput.value = '';
        currentKeyword = '';
        currentPage = 1;
        loadArticles(currentPage);
    }
    
    /**
     * Bascule entre vue tous les articles et favoris
     */
    function switchView(mode) {
        viewMode = mode;
        
        if (mode === 'favorites') {
            loadFavorites();
            showFavoritesBtn.classList.add('active');
            showAllBtn.classList.remove('active');
        } else {
            if (currentKeyword) {
                searchArticles(currentKeyword);
            } else {
                loadArticles(currentPage);
            }
            showAllBtn.classList.add('active');
            showFavoritesBtn.classList.remove('active');
        }
    }
    
    /**
     * Charge les articles depuis l'API
     */
    function loadArticles(page) {
        showLoader();
        
        fetch(`/api/articles?page=${page}`)
            .then(response => {
                if (!response.ok) throw new Error('Erreur chargement');
                return response.json();
            })
            .then(data => {
                displayArticles(data.articles, 'Tous les articles');
                displayPagination(data.pagination);
                currentPage = page;
            })
            .catch(error => {
                console.error('Erreur:', error);
                displayError('Impossible de charger les articles');
            });
    }
    
    /**
     * Recherche des articles par mot-cle
     */
    function searchArticles(keyword) {
        showLoader();
        
        fetch(`/api/articles/search?q=${encodeURIComponent(keyword)}`)
            .then(response => {
                if (!response.ok) throw new Error('Erreur recherche');
                return response.json();
            })
            .then(data => {
                const title = `Resultats pour "${keyword}" (${data.count} article${data.count > 1 ? 's' : ''})`;
                displayArticles(data.articles, title);
                document.getElementById('pagination-container').innerHTML = ''; // Pas de pagination en recherche
            })
            .catch(error => {
                console.error('Erreur:', error);
                displayError('Erreur lors de la recherche');
            });
    }
    
    /**
     * Charge les articles favoris
     */
    function loadFavorites() {
        fetch('/api/articles/favorites')
            .then(response => {
                if (!response.ok) throw new Error('Erreur favoris');
                return response.json();
            })
            .then(data => {
                const favContainer = document.getElementById('favorites-sidebar');
                if (data.count === 0) {
                    favContainer.innerHTML = '<p class="text-white-50 small">Aucun favori</p>';
                } else {
                    favContainer.innerHTML = data.favorites.map(article => `
                        <div class="favorite-item mb-2">
                            <a href="${escapeHtml(article.url)}" target="_blank" class="text-decoration-none small">
                                ${escapeHtml(article.title)}
                            </a>
                        </div>
                    `).join('');
                }
                
                // Affiche aussi dans la zone principale si on est en mode favoris
                if (viewMode === 'favorites') {
                    const title = `Mes favoris (${data.count})`;
                    displayArticles(data.favorites, title);
                    document.getElementById('pagination-container').innerHTML = '';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
    }
    
    /**
     * Affiche les articles dans le DOM
     */
    function displayArticles(articles, title = 'Articles') {
        const container = document.getElementById('articles-container');
        const titleElement = document.getElementById('articles-title');
        
        titleElement.textContent = title;
        
        if (articles.length === 0) {
            container.innerHTML = `
                <div class="alert alert-info">
                    <p>Aucun article trouve.</p>
                </div>
            `;
            return;
        }
        
        let html = '<div class="row">';
        
        articles.forEach(article => {
            const readClass = article.isRead ? 'read-article' : '';
            const favoriteIcon = article.isFavorite ? '❤️' : '🤍';
            const readIcon = article.isRead ? '✓' : '○';
            
            html += `
                <div class="col-md-6 mb-3">
                    <div class="card article-card h-100 ${readClass}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0 flex-grow-1">
                                    <a href="${escapeHtml(article.url)}" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                                        ${escapeHtml(article.title)}
                                    </a>
                                </h5>
                                <div class="ms-2 article-actions">
                                    <button class="btn btn-sm btn-link p-0 me-2" 
                                            onclick="toggleFavorite('${article.id}', ${article.isFavorite})" 
                                            title="${article.isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'}">
                                        <span style="font-size: 1.2rem;">${favoriteIcon}</span>
                                    </button>
                                    <button class="btn btn-sm btn-link p-0" 
                                            onclick="toggleRead('${article.id}')" 
                                            title="Marquer comme ${article.isRead ? 'non lu' : 'lu'}">
                                        <span style="font-size: 1.2rem;">${readIcon}</span>
                                    </button>
                                </div>
                            </div>
                            <p class="card-text text-muted small mb-2">
                                <span class="badge bg-secondary">${escapeHtml(article.source)}</span>
                                <span class="ms-2">${article.publishedAt}</span>
                            </p>
                            <p class="card-text">${escapeHtml(article.description)}</p>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
        
        // Scroll en haut du container après chargement
        container.scrollTop = 0;
    }
    
    /**
     * Toggle favori - CORRIGE : utilise POST pour ajouter, DELETE pour retirer
     */
    window.toggleFavorite = function(articleId, isFavorite) {
        const method = isFavorite ? 'DELETE' : 'POST';
        
        fetch(`/api/articles/${articleId}/favorite`, {
            method: method,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recharge la vue actuelle
                if (viewMode === 'favorites') {
                    loadFavorites();
                } else if (currentKeyword) {
                    searchArticles(currentKeyword);
                } else {
                    loadArticles(currentPage);
                }
                loadFavorites(); // Met a jour sidebar
            }
        })
        .catch(error => console.error('Erreur toggle favori:', error));
    };
    
    /**
     * Toggle lu/non lu
     */
    window.toggleRead = function(articleId) {
        fetch(`/api/articles/${articleId}/mark-read`, {
            method: 'PATCH',
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recharge la vue actuelle
                if (viewMode === 'favorites') {
                    loadFavorites();
                } else if (currentKeyword) {
                    searchArticles(currentKeyword);
                } else {
                    loadArticles(currentPage);
                }
            }
        })
        .catch(error => console.error('Erreur toggle read:', error));
    };
    
    /**
     * Affiche la pagination
     */
    function displayPagination(pagination) {
        const container = document.getElementById('pagination-container');
        
        if (pagination.totalPages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let html = `
            <nav aria-label="Navigation articles">
                <ul class="pagination justify-content-center mb-2">
        `;
        
        // Bouton Precedent
        if (pagination.currentPage > 1) {
            html += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.currentPage - 1}">Precedent</a>
                </li>
            `;
        } else {
            html += `<li class="page-item disabled"><span class="page-link">Precedent</span></li>`;
        }
        
        // Numeros de page (affiche max 5 pages)
        let startPage = Math.max(1, pagination.currentPage - 2);
        let endPage = Math.min(pagination.totalPages, pagination.currentPage + 2);
        
        if (startPage > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
            if (startPage > 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === pagination.currentPage) {
                html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            }
        }
        
        if (endPage < pagination.totalPages) {
            if (endPage < pagination.totalPages - 1) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.totalPages}">${pagination.totalPages}</a></li>`;
        }
        
        // Bouton Suivant
        if (pagination.currentPage < pagination.totalPages) {
            html += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.currentPage + 1}">Suivant</a>
                </li>
            `;
        } else {
            html += `<li class="page-item disabled"><span class="page-link">Suivant</span></li>`;
        }
        
        html += `
                </ul>
            </nav>
            <p class="text-center text-muted small mb-0">
                Page ${pagination.currentPage} sur ${pagination.totalPages} 
                (${pagination.totalArticles} articles)
            </p>
        `;
        
        container.innerHTML = html;
        
        // Event listeners pagination
        container.querySelectorAll('a[data-page]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.getAttribute('data-page'));
                loadArticles(page);
                // Scroll en haut du container articles (pas de la page)
                document.getElementById('articles-container').scrollTop = 0;
            });
        });
    }
    
    /**
     * Affiche un loader
     */
    function showLoader() {
        const container = document.getElementById('articles-container');
        container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-3">Chargement...</p>
            </div>
        `;
    }
    
    /**
     * Affiche une erreur
     */
    function displayError(message) {
        const container = document.getElementById('articles-container');
        container.innerHTML = `
            <div class="alert alert-danger">
                <strong>Erreur :</strong> ${escapeHtml(message)}
            </div>
        `;
    }
    
    /**
     * Affiche un message temporaire
     */
    function showError(message) {
        const container = document.getElementById('search-feedback');
        container.innerHTML = `<div class="alert alert-warning mt-2">${escapeHtml(message)}</div>`;
        setTimeout(() => container.innerHTML = '', 3000);
    }
    
    /**
     * Echappe HTML pour securite XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
