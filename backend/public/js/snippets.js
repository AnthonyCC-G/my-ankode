/**
 * SNIPPETS.JS - Gestion des snippets de code
 * Layout flexbox avec états FOCUS/REDUCED (inspiré de kanban.js)
 */

// ===== 1. DÉTECTION MOBILE CÔTÉ CLIENT =====
(function checkMobileDevice() {
    const isMobile = window.innerWidth < 768;
    
    if (isMobile) {
        console.log('[SNIPPETS] Appareil mobile détecté - Redirection vers /desktop-only');
        localStorage.setItem('lastDesktopPage', '/snippets');
        window.location.href = '/desktop-only';
        return;
    }
    
    console.log('[SNIPPETS] Appareil desktop détecté - Chargement de la page Snippets');
    
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth < 768) {
                console.log('[SNIPPETS] Fenêtre réduite en mobile - Redirection');
                localStorage.setItem('lastDesktopPage', '/snippets');
                window.location.href = '/desktop-only';
            }
        }, 250);
    });
})();

// ===== 2. VARIABLES GLOBALES =====
let snippets = [];
let isEditMode = false;
let editingSnippetId = null;

// ===== 3. INITIALISATION AU CHARGEMENT DU DOM =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('[SNIPPETS] DOM chargé - Initialisation de la page Snippets');
    
    // Charger les snippets depuis l'API
    loadSnippets();
    
    // Initialiser les event listeners
    initEventListeners();
    
    console.log('[SNIPPETS] Script snippets.js chargé avec succès ✅');
});

// ===== 4. INITIALISATION DES EVENT LISTENERS =====
function initEventListeners() {
    // Formulaire de création/édition
    const snippetForm = document.getElementById('snippet-form');
    if (snippetForm) {
        snippetForm.addEventListener('submit', handleSubmitSnippet);
    }
    
    // Bouton Annuler
    const btnCancel = document.getElementById('btn-cancel-snippet');
    if (btnCancel) {
        btnCancel.addEventListener('click', handleCancelForm);
    }
    
    // Bouton "Nouveau snippet" (dans le header du bloc config focus)
    const btnNewSnippetExpanded = document.getElementById('btn-new-snippet-expanded');
    if (btnNewSnippetExpanded) {
        btnNewSnippetExpanded.addEventListener('click', () => {
            console.log('[SNIPPETS] Bouton "Nouveau snippet" cliqué - Reset formulaire');
            resetForm();
        });
    }
    
    // Clic sur bloc reduced pour basculer en focus
    const configBlockReduced = document.querySelector('#config-block .block-content--reduced');
    if (configBlockReduced) {
        configBlockReduced.addEventListener('click', () => {
            console.log('[SNIPPETS] Clic sur Config reduced → Focus Config');
            focusConfigBlock();
        });
    }
    
    const listBlockReduced = document.querySelector('#list-block .block-content--reduced');
    if (listBlockReduced) {
        listBlockReduced.addEventListener('click', () => {
            console.log('[SNIPPETS] Clic sur Liste reduced → Focus Liste');
            focusListBlock();
        });
    }
    
    console.log('[SNIPPETS] Event listeners initialisés ✅');
}

// ===== 5. CHARGEMENT DES SNIPPETS (API GET) =====
async function loadSnippets() {
    try {
        console.log('[SNIPPETS] Chargement des snippets...');
        
        const data = await API.get('/api/snippets');
        snippets = data;
        
        console.log(`[SNIPPETS] ${snippets.length} snippet(s) chargé(s) ✅`, snippets);
        
        // Trier les snippets par langage alphabétique puis par titre
        snippets.sort((a, b) => {
            const langA = (a.language || '').toLowerCase();
            const langB = (b.language || '').toLowerCase();
            
            if (langA !== langB) {
                return langA.localeCompare(langB);
            }
            
            const titleA = (a.title || '').toLowerCase();
            const titleB = (b.title || '').toLowerCase();
            return titleA.localeCompare(titleB);
        });
        
        // Afficher les snippets dans les différentes zones
        displaySnippetsList();
        displaySnippetsAside();
        
    } catch (error) {
        console.error('[SNIPPETS] Erreur chargement snippets:', error);
        showFlashMessage('Impossible de charger les snippets.', 'error');
    }
}

// ===== 6. AFFICHAGE LISTE DES SNIPPETS (BLOC LISTE) =====
function displaySnippetsList() {
    const container = document.getElementById('snippets-container');
    
    if (!container) {
        console.error('[SNIPPETS] Élément #snippets-container introuvable');
        return;
    }
    
    // Si aucun snippet, afficher message vide
    if (snippets.length === 0) {
        container.innerHTML = `
            <p class="empty-state">
                Aucun snippet pour le moment. Crée ton premier snippet ci-dessus !
            </p>
        `;
        return;
    }
    
    // Vider le container
    container.innerHTML = '';
    
    // Créer une card pour chaque snippet
    snippets.forEach(snippet => {
        const card = createSnippetCard(snippet);
        container.appendChild(card);
    });
    
    console.log(`[SNIPPETS] ${snippets.length} card(s) affichée(s) dans la liste ✅`);
}

// ===== 7. CRÉATION D'UNE CARD SNIPPET =====
function createSnippetCard(snippet) {
    const card = document.createElement('div');
    card.className = 'snippet-card';
    card.dataset.snippetId = snippet.id;
    
    // Formater la description (ou message par défaut)
    const descriptionText = snippet.description && snippet.description.trim() !== '' 
        ? escapeHtml(snippet.description)
        : '<span class="snippet-card-description-empty">Aucune description</span>';
    
    card.innerHTML = `
        <div class="snippet-card-header">
            <div class="snippet-card-info">
                <h3 class="snippet-card-title">${escapeHtml(snippet.title)}</h3>
                <span class="snippet-card-language">${escapeHtml(snippet.language || 'Autre')}</span>
            </div>
            <div class="snippet-card-expand-icon" title="Déplier/Replier">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
            <div class="snippet-card-actions">
                <button class="btn-edit-snippet" data-snippet-id="${snippet.id}" title="Éditer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>
                <button class="btn-delete-snippet" data-snippet-id="${snippet.id}" title="Supprimer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>
        <div class="snippet-card-body collapsed">
            <div class="snippet-card-code">
                <span class="snippet-card-code-label">Code</span>
                <div class="snippet-card-code-content">
                    <code>${escapeHtml(snippet.code)}</code>
                </div>
            </div>
            <div class="snippet-card-description">
                <span class="snippet-card-description-label">Description</span>
                <div class="snippet-card-description-content">
                    ${descriptionText}
                </div>
            </div>
        </div>
    `;
    
    // Event listeners
    const btnEdit = card.querySelector('.btn-edit-snippet');
    const btnDelete = card.querySelector('.btn-delete-snippet');
    const expandIcon = card.querySelector('.snippet-card-expand-icon');
    
    btnEdit.addEventListener('click', (e) => {
        e.stopPropagation();
        handleEditSnippet(snippet.id);
    });
    
    btnDelete.addEventListener('click', (e) => {
        e.stopPropagation();
        handleDeleteSnippet(snippet.id);
    });
    
    // Clic sur la flèche pour déplier/replier
    expandIcon.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleCardExpand(card);
    });
    
    return card;
}

// ===== 7bis. TOGGLE ACCORDÉON CARD =====
function toggleCardExpand(card) {
    const body = card.querySelector('.snippet-card-body');
    
    if (body.classList.contains('collapsed')) {
        // Expand
        body.classList.remove('collapsed');
        body.classList.add('expanded');
        card.classList.add('expanded');
        console.log('[SNIPPETS] Card expanded');
    } else {
        // Collapse
        body.classList.remove('expanded');
        body.classList.add('collapsed');
        card.classList.remove('expanded');
        console.log('[SNIPPETS] Card collapsed');
    }
}

// ===== 8. AFFICHAGE ASIDE RAPIDE =====
function displaySnippetsAside() {
    const asideList = document.getElementById('aside-list');
    
    if (!asideList) {
        console.error('[SNIPPETS] Élément #aside-list introuvable');
        return;
    }
    
    // Si aucun snippet, afficher message vide
    if (snippets.length === 0) {
        asideList.innerHTML = '<li class="aside-empty">Aucun snippet</li>';
        return;
    }
    
    // Vider la liste
    asideList.innerHTML = '';
    
    // Créer une mini-card pour chaque snippet (même ordre que la liste)
    snippets.forEach(snippet => {
        const item = document.createElement('li');
        item.className = 'aside-item';
        item.dataset.snippetId = snippet.id;
        
        // Juste le titre
        item.innerHTML = `
            <span class="aside-item-title">${escapeHtml(snippet.title)}</span>
        `;
        
        // Au clic, scroller vers le snippet dans la liste + highlight
        item.addEventListener('click', () => scrollToSnippet(snippet.id));
        
        asideList.appendChild(item);
    });
    
    console.log(`[SNIPPETS] ${snippets.length} mini-card(s) affichée(s) dans l'aside ✅`);
}

// ===== 9. SCROLL VERS UN SNIPPET (depuis l'aside) + HIGHLIGHT =====
function scrollToSnippet(snippetId) {
    console.log(`[SNIPPETS] Scroll vers snippet ID: ${snippetId}`);
    
    // Ouvrir le bloc liste si fermé
    focusListBlock();
    
    // Attendre que le bloc soit ouvert avant de scroller
    setTimeout(() => {
        // Trouver la card correspondante
        const card = document.querySelector(`.snippet-card[data-snippet-id="${snippetId}"]`);
        
        if (card) {
            // Trouver le container scrollable
            const container = document.getElementById('snippets-container');
            
            if (container) {
                // Calculer la position de la card relative au container
                const cardOffsetTop = card.offsetTop;
                const containerScrollTop = container.scrollTop;
                const containerOffsetTop = container.offsetTop;
                
                // Scroller DANS le container (pas le viewport global)
                container.scrollTo({
                    top: cardOffsetTop - 20, // 20px de marge
                    behavior: 'smooth'
                });
                
                // Animation de highlight temporaire
                card.style.transform = 'scale(1.02)';
                card.style.boxShadow = '0 0 20px rgba(0, 194, 209, 0.6)';
                
                setTimeout(() => {
                    card.style.transform = '';
                    card.style.boxShadow = '';
                }, 1000);
            }
        }
    }, 100); // Petit délai pour laisser le temps au bloc de s'ouvrir
}

// ===== 10. CRÉATION OU MODIFICATION D'UN SNIPPET (API POST/PUT) =====
async function handleSubmitSnippet(event) {
    event.preventDefault();
    
    const title = document.getElementById('snippet-title').value.trim();
    const language = document.getElementById('snippet-language').value.trim();
    const code = document.getElementById('snippet-code').value.trim();
    const description = document.getElementById('snippet-description').value.trim();
    
    // Validation côté client
    if (!title) {
        showFlashMessage('Le titre est obligatoire', 'error');
        return;
    }
    
    if (!language) {
        showFlashMessage('Le langage est obligatoire', 'error');
        return;
    }
    
    if (!code) {
        showFlashMessage('Le code est obligatoire', 'error');
        return;
    }
    
    const snippetData = {
        title,
        language,
        code,
        description: description || ''
    };
    
    try {
        if (isEditMode && editingSnippetId) {
            // Mode édition : PUT
            console.log(`[SNIPPETS] Mise à jour du snippet ID: ${editingSnippetId}`, snippetData);
            
            const result = await API.put(`/api/snippets/${editingSnippetId}`, snippetData);
            
            console.log('[SNIPPETS] Snippet mis à jour avec succès ✅', result);
            showFlashMessage('Snippet modifié avec succès !', 'success');
            
        } else {
            // Mode création : POST
            console.log('[SNIPPETS] Création d\'un nouveau snippet', snippetData);
            
            const result = await API.post('/api/snippets', snippetData);
            
            console.log('[SNIPPETS] Snippet créé avec succès ✅', result);
            showFlashMessage('Snippet créé avec succès !', 'success');
        }
        
        // Recharger la liste des snippets
        await loadSnippets();
        
        // Réinitialiser le formulaire
        resetForm();
        
        // Fermer le bloc config et ouvrir la liste
        focusListBlock();
        
    } catch (error) {
        console.error('[SNIPPETS] Erreur lors de la sauvegarde du snippet:', error);
        showFlashMessage(error.message || 'Impossible de sauvegarder le snippet', 'error');
    }
}

// ===== 11. ÉDITION D'UN SNIPPET =====
function handleEditSnippet(snippetId) {
    console.log(`[SNIPPETS] Édition du snippet ID: ${snippetId}`);
    
    // Trouver le snippet
    const snippet = snippets.find(s => s.id === snippetId);
    
    if (!snippet) {
        console.error('[SNIPPETS] Snippet introuvable');
        showFlashMessage('Snippet introuvable', 'error');
        return;
    }
    
    // Passer en mode édition
    isEditMode = true;
    editingSnippetId = snippetId;
    
    // Remplir le formulaire avec les données du snippet
    document.getElementById('snippet-id').value = snippet.id;
    document.getElementById('snippet-title').value = snippet.title;
    document.getElementById('snippet-language').value = snippet.language || '';
    document.getElementById('snippet-code').value = snippet.code;
    document.getElementById('snippet-description').value = snippet.description || '';
    
    // Changer le texte du bouton submit
    document.getElementById('btn-save-snippet').textContent = 'Modifier';
    
    // Ouvrir le bloc config
    focusConfigBlock();
}

// ===== 12. SUPPRESSION D'UN SNIPPET (API DELETE) =====
async function handleDeleteSnippet(snippetId) {
    console.log(`[SNIPPETS] Tentative de suppression du snippet ID: ${snippetId}`);
    
    // Trouver le snippet pour afficher son titre dans la confirmation
    const snippet = snippets.find(s => s.id === snippetId);
    const snippetTitle = snippet ? snippet.title : 'ce snippet';
    
    // Confirmation utilisateur
    const confirmed = confirm(`Êtes-vous sûr de vouloir supprimer "${snippetTitle}" ?\n\nCette action est irréversible.`);
    
    if (!confirmed) {
        console.log('[SNIPPETS] Suppression annulée par l\'utilisateur');
        return;
    }
    
    try {
        console.log(`[SNIPPETS] Suppression du snippet ID: ${snippetId}`);
        
        await API.delete(`/api/snippets/${snippetId}`);
        
        console.log('[SNIPPETS] Snippet supprimé avec succès ✅');
        showFlashMessage('Snippet supprimé avec succès !', 'success');
        
        // Recharger la liste des snippets
        await loadSnippets();
        
    } catch (error) {
        console.error('[SNIPPETS] Erreur lors de la suppression:', error);
        showFlashMessage(error.message || 'Impossible de supprimer le snippet', 'error');
    }
}

// ===== 13. ANNULATION DU FORMULAIRE =====
function handleCancelForm() {
    console.log('[SNIPPETS] Annulation du formulaire');
    
    resetForm();
    
    // Si on était en mode édition, retourner à la liste
    if (isEditMode) {
        focusListBlock();
    }
}

// ===== 14. RÉINITIALISATION DU FORMULAIRE =====
function resetForm() {
    const form = document.getElementById('snippet-form');
    if (form) {
        form.reset();
    }
    
    document.getElementById('snippet-id').value = '';
    document.getElementById('btn-save-snippet').textContent = 'Enregistrer';
    
    isEditMode = false;
    editingSnippetId = null;
    
    console.log('[SNIPPETS] Formulaire réinitialisé');
}

// ===== 15. GESTION DE L'ACCORDÉON - FOCUS/REDUCED (comme Kanban) =====
function focusConfigBlock() {
    console.log('[SNIPPETS] Focus sur Config');
    
    const configBlock = document.getElementById('config-block');
    const listBlock = document.getElementById('list-block');
    
    if (!configBlock || !listBlock) return;
    
    // Config : passer en FOCUS
    configBlock.classList.remove('reduced');
    configBlock.querySelector('.block-content--focus').style.display = 'flex';
    configBlock.querySelector('.block-content--reduced').style.display = 'none';
    
    // Liste : passer en REDUCED
    listBlock.classList.add('reduced');
    listBlock.classList.remove('focus');
    listBlock.querySelector('.block-content--focus').style.display = 'none';
    listBlock.querySelector('.block-content--reduced').style.display = 'flex';
}

function focusListBlock() {
    console.log('[SNIPPETS] Focus sur Liste');
    
    const configBlock = document.getElementById('config-block');
    const listBlock = document.getElementById('list-block');
    
    if (!configBlock || !listBlock) return;
    
    // Config : passer en REDUCED
    configBlock.classList.add('reduced');
    configBlock.querySelector('.block-content--focus').style.display = 'none';
    configBlock.querySelector('.block-content--reduced').style.display = 'flex';
    
    // Liste : passer en FOCUS
    listBlock.classList.remove('reduced');
    listBlock.classList.add('focus');
    listBlock.querySelector('.block-content--focus').style.display = 'flex';
    listBlock.querySelector('.block-content--reduced').style.display = 'none';
}

// ===== 16. AFFICHAGE DES MESSAGES FLASH =====
function showFlashMessage(message, type = 'success') {
    const flashContainer = document.getElementById('config-flash-messages');
    
    if (!flashContainer) {
        console.error('[SNIPPETS] Élément #config-flash-messages introuvable');
        return;
    }
    
    // Classe CSS selon le type
    const className = type === 'success' ? 'flash-success' : 'flash-error';
    
    flashContainer.innerHTML = `<span class="${className}">${escapeHtml(message)}</span>`;
    
    console.log(`[SNIPPETS] Message flash affiché: ${message} (${type})`);
    
    // Effacer le message après 5 secondes
    setTimeout(() => {
        flashContainer.innerHTML = '';
    }, 5000);
}

// ===== 17. UTILITAIRES =====

// Échapper les caractères HTML pour éviter XSS
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
