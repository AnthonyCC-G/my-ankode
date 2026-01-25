// ===== 1. VARIABLES GLOBALES =====
let competences = []; // Liste de toutes les competences de l'utilisateur
let isEditMode = false; // Mode edition ou creation
let editingCompetenceId = null; // ID de la competence en cours d'edition (null = mode creation)
let currentLevel = 1; // Niveau selectionne (1-5 etoiles)

// ===== 2. INITIALISATION AU CHARGEMENT DU DOM =====
document.addEventListener('DOMContentLoaded', function() {
    
    // Charger les competences au demarrage
    loadCompetences();
    
    // Initialiser les evenements globaux
    initEventListeners();
    
    // Initialiser les etoiles cliquables
    initStarsInteraction();
    
});

// ===== 3. INITIALISATION DES EVENT LISTENERS =====
function initEventListeners() {
    // Formulaire de creation/edition
    const competenceForm = document.getElementById('competence-form');
    if (competenceForm) {
        competenceForm.addEventListener('submit', handleSubmitCompetence);
    }
    
    // Bouton Annuler
    const btnCancel = document.getElementById('btn-cancel');
    if (btnCancel) {
        btnCancel.addEventListener('click', handleCancelForm);
    }
    
    // Bouton "Nouvelle competence" (dans le header Config)
    const btnNewCompetence = document.getElementById('btn-new-competence');
    if (btnNewCompetence) {
        btnNewCompetence.addEventListener('click', () => {
            resetForm();
        });
    }
    
}

// ===== 4. INITIALISATION DES ETOILES CLIQUABLES (Niveau 1-5) =====
function initStarsInteraction() {
    const starsContainer = document.getElementById('stars-container');
    
    if (!starsContainer) {
        return;
    }
    
    const stars = starsContainer.querySelectorAll('.star');
    
    stars.forEach((star, index) => {
        const value = index + 1;
        
        // Hover : Afficher preview du niveau
        star.addEventListener('mouseenter', () => {
            updateStarsDisplay(value, true);
        });
        
        // Clic : Selectionner le niveau
        star.addEventListener('click', () => {
            currentLevel = value;
            document.getElementById('competence-level').value = value;
            updateStarsDisplay(value, false);
            updateLevelDescription(value);
        });
    });
    
    // Mouseleave : Revenir au niveau selectionne
    starsContainer.addEventListener('mouseleave', () => {
        updateStarsDisplay(currentLevel, false);
    });
    
    // Initialiser au niveau 1 par defaut
    updateStarsDisplay(1, false);
    updateLevelDescription(1);
    
}

// ===== 5. CHARGEMENT DES COMPETENCES (API GET) =====
async function loadCompetences() {
    try {
        
        // Appel API GET /api/competences
        const data = await API.get('/api/competences');
        competences = data;
        
        
        // Afficher les competences dans le DOM
        displayCompetences();
        updateCounter();
        toggleEmptyState();
        
    } catch (error) {
        showFlashMessage('Impossible de charger les competences.', 'error');
    }
}

// ===== 6. AFFICHAGE DES COMPETENCES (DOM MANIPULATION) =====
function displayCompetences() {
    const container = document.getElementById('competences-cards');
    
    if (!container) {
        return;
    }
    
    // Vider le container
    container.innerHTML = '';
    
    // Creer une card pour chaque competence
    competences.forEach(competence => {
        const card = createCompetenceCard(competence);
        container.appendChild(card);
    });
}

function createCompetenceCard(competence) {
    const card = document.createElement('div');
    card.className = 'competence-card';
    card.dataset.id = competence.id;
    
    // Si c'est la competence en cours d'edition, ajouter classe "editing"
    if (editingCompetenceId === competence.id) {
        card.classList.add('editing');
    }
    
    // Etoiles d'affichage (non cliquables)
    const starsHtml = generateStarsDisplay(competence.level);
    
    // Date formatee
    const date = new Date(competence.createdAt);
    const formattedDate = date.toLocaleDateString('fr-FR', { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric' 
    });
    
    // Construire le HTML de la card
    card.innerHTML = `
        <div class="card-header">
            <h3 class="card-title">${escapeHtml(competence.name)}</h3>
            <div class="card-stars">${starsHtml}</div>
        </div>
        
        <div class="card-body">
            ${competence.notes ? `
                <p class="card-notes">${escapeHtml(competence.notes)}</p>
            ` : ''}
            
            ${competence.projectsLinks || competence.snippetsLinks ? `
                <div class="card-links">
                    ${competence.projectsLinks ? `
                        <div class="card-link-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span>Projets : ${escapeHtml(competence.projectsLinks)}</span>
                        </div>
                    ` : ''}
                    ${competence.snippetsLinks ? `
                        <div class="card-link-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                            <span>Snippets : ${escapeHtml(competence.snippetsLinks)}</span>
                        </div>
                    ` : ''}
                </div>
            ` : ''}
        </div>
        
        <div class="card-footer">
            <span class="card-date">Creee le ${formattedDate}</span>
            <div class="card-actions">
                <button class="btn-edit" data-id="${competence.id}" title="Editer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>
                <button class="btn-delete" data-id="${competence.id}" title="Supprimer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>
    `;
    
    // Attacher les event listeners aux boutons
    const btnEdit = card.querySelector('.btn-edit');
    const btnDelete = card.querySelector('.btn-delete');
    
    btnEdit.addEventListener('click', (e) => {
        e.stopPropagation();
        handleEditCompetence(competence.id);
    });
    
    btnDelete.addEventListener('click', (e) => {
        e.stopPropagation();
        handleDeleteCompetence(competence.id);
    });
    
    // Clic sur la card complete = edition
    card.addEventListener('click', () => {
        handleEditCompetence(competence.id);
    });
    
    return card;
}

// ===== 7. CREATION D'UNE COMPETENCE (API POST) =====
async function handleSubmitCompetence(e) {
    e.preventDefault();
    
    
    // Recuperer les valeurs du formulaire
    const data = {
        name: document.getElementById('competence-name').value.trim(),
        level: parseInt(document.getElementById('competence-level').value),
        notes: document.getElementById('competence-notes').value.trim() || null,
        projectsLinks: document.getElementById('competence-projects').value.trim() || null,
        snippetsLinks: document.getElementById('competence-snippets').value.trim() || null
    };
    
    // Validation basique cote client
    if (!data.name) {
        showFlashMessage('Le nom de la competence est requis', 'error');
        return;
    }
    
    if (data.level < 1 || data.level > 5) {
        showFlashMessage('Le niveau doit etre entre 1 et 5', 'error');
        return;
    }
    
    try {
        const btnSubmit = document.getElementById('btn-submit');
        const btnSubmitText = document.getElementById('btn-submit-text');
        
        btnSubmit.disabled = true;
        btnSubmitText.textContent = isEditMode ? 'Modification...' : 'Creation...';
        
        if (isEditMode && editingCompetenceId) {
            // Mode EDITION : PUT
            await API.put(`/api/competences/${editingCompetenceId}`, data);
            showFlashMessage('Competence modifiee avec succes', 'success');
        } else {
            // Mode CREATION : POST
            await API.post('/api/competences', data);
            showFlashMessage('Competence creee avec succes', 'success');
        }
        
        // Recharger les competences
        await loadCompetences();
        
        // Reinitialiser le formulaire
        resetForm();
        
    } catch (error) {
        showFlashMessage('Erreur lors de la sauvegarde : ' + error.message, 'error');
    } finally {
        const btnSubmit = document.getElementById('btn-submit');
        const btnSubmitText = document.getElementById('btn-submit-text');
        
        btnSubmit.disabled = false;
        btnSubmitText.textContent = isEditMode ? 'Modifier la competence' : 'Creer la competence';
    }
}

// ===== 8. EDITION D'UNE COMPETENCE (Remplir le formulaire) =====
function handleEditCompetence(id) {
    const competence = competences.find(c => c.id === id);
    
    if (!competence) {
        return;
    }
    
    
    // Passer en mode edition
    isEditMode = true;
    editingCompetenceId = id;
    
    // Remplir le formulaire avec les donnees de la competence
    document.getElementById('competence-id').value = competence.id;
    document.getElementById('competence-name').value = competence.name;
    document.getElementById('competence-level').value = competence.level;
    document.getElementById('competence-notes').value = competence.notes || '';
    document.getElementById('competence-projects').value = competence.projectsLinks || '';
    document.getElementById('competence-snippets').value = competence.snippetsLinks || '';
    
    // Mettre a jour les etoiles
    currentLevel = competence.level;
    updateStarsDisplay(currentLevel, false);
    updateLevelDescription(currentLevel);
    
    // Changer le texte du bouton submit
    document.getElementById('btn-submit-text').textContent = 'Modifier la competence';
    
    // Re-render pour afficher classe "editing" sur la card
    displayCompetences();
    
    // Scroll vers le formulaire
    document.getElementById('competence-form').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
    });
}

// ===== 9. SUPPRESSION D'UNE COMPETENCE (API DELETE) =====
async function handleDeleteCompetence(id) {
    const competence = competences.find(c => c.id === id);
    
    if (!competence) {
        return;
    }
    
    // Confirmation utilisateur
    const confirmed = confirm(
        `Etes-vous sur de vouloir supprimer la competence "${competence.name}" ?\n\nCette action est irreversible.`
    );
    
    if (!confirmed) {
        return;
    }
    
    try {
        
        // Appel API DELETE
        await API.delete(`/api/competences/${id}`);
        
        showFlashMessage('Competence supprimee avec succes', 'success');
        
        // Si on etait en train d'editer cette competence, reinitialiser le formulaire
        if (editingCompetenceId === id) {
            resetForm();
        }
        
        // Recharger les competences
        await loadCompetences();
        
    } catch (error) {
        showFlashMessage('Erreur lors de la suppression : ' + error.message, 'error');
    }
}

// ===== 10. REINITIALISATION DU FORMULAIRE =====
function resetForm() {
    
    // Reinitialiser les variables d'etat
    isEditMode = false;
    editingCompetenceId = null;
    currentLevel = 1;
    
    // Vider le formulaire
    document.getElementById('competence-form').reset();
    document.getElementById('competence-id').value = '';
    document.getElementById('competence-level').value = 1;
    
    // Reinitialiser les etoiles au niveau 1
    updateStarsDisplay(1, false);
    updateLevelDescription(1);
    
    // Changer le texte du bouton submit
    document.getElementById('btn-submit-text').textContent = 'Creer la competence';
    
    // Re-render pour retirer classe "editing" des cards
    displayCompetences();
}

function handleCancelForm() {
    resetForm();
}

// ===== 11. HELPERS - GESTION DES ETOILES =====
function updateStarsDisplay(level, isHover) {
    const stars = document.querySelectorAll('#stars-container .star');
    
    stars.forEach((star, index) => {
        const value = index + 1;
        
        // Retirer toutes les classes
        star.classList.remove('active', 'hover');
        
        // Ajouter la classe appropriee
        if (value <= level) {
            star.classList.add(isHover ? 'hover' : 'active');
        }
    });
}

function updateLevelDescription(level) {
    const descriptions = {
        1: 'Niveau 1/5 - Debutant (notions de base)',
        2: 'Niveau 2/5 - Intermediaire (peut realiser des taches simples)',
        3: 'Niveau 3/5 - Competent (autonome sur les taches courantes)',
        4: 'Niveau 4/5 - Avance (maitrise approfondie)',
        5: 'Niveau 5/5 - Expert (reference sur le sujet)'
    };
    
    const descElement = document.getElementById('level-description');
    if (descElement) {
        descElement.textContent = descriptions[level];
    }
}

function generateStarsDisplay(level) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        stars += i <= level ? '★' : '☆';
    }
    return stars;
}

// ===== 12. HELPERS - GESTION DE L'INTERFACE =====
function updateCounter() {
    const counter = document.getElementById('list-counter');
    if (!counter) return;
    
    const count = competences.length;
    counter.textContent = `${count} competence${count > 1 ? 's' : ''}`;
}

function toggleEmptyState() {
    const emptyState = document.getElementById('empty-state');
    const cardsContainer = document.getElementById('competences-cards');
    
    if (!emptyState || !cardsContainer) return;
    
    if (competences.length === 0) {
        emptyState.style.display = 'flex';
        cardsContainer.style.display = 'none';
    } else {
        emptyState.style.display = 'none';
        cardsContainer.style.display = 'flex';
    }
}

function showFlashMessage(message, type = 'success') {
    const container = document.getElementById('list-flash-messages');
    
    if (!container) {
        return;
    }
    
    // Effacer le message precedent
    container.innerHTML = '';
    
    // Creer le nouveau message (span simple)
    const flash = document.createElement('span');
    flash.className = `flash-${type}`; // flash-success ou flash-error
    flash.textContent = message;
    
    container.appendChild(flash);
    
    // Suppression automatique apres 5 secondes
    setTimeout(() => {
        flash.remove();
    }, 5000);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
