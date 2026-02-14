// ========================================
// COMPETENCES PAGE - GESTION COMPLETE + ACCORDEON
// MY-ANKODE - Module de gestion des compétences
// ========================================
// Fonctionnalités :
// - CRUD compétences (Create, Read, Update, Delete)
// - Système d'accordéon pour optimiser l'espace écran
// - Calcul automatique du niveau en étoiles (projets + snippets)
// - Liaison avec projets Kanban et snippets de code
// - Gestion projets/snippets externes
// - Auto-scroll dans le bloc introduction
// ========================================

// =============================================
// VARIABLES GLOBALES
// =============================================

// Données chargées depuis l'API
let competences = [];      // Liste de toutes les compétences
let allProjects = [];      // Liste de tous les projets Kanban disponibles
let allSnippets = [];      // Liste de tous les snippets disponibles

// État de sélection actuelle
let selectedCompetenceId = null;    // ID de la compétence sélectionnée
let currentCompetenceData = null;   // Copie de travail des données en édition

// =============================================
// ELEMENTS DOM
// =============================================

// Grid principale
const competencesGrid = document.getElementById('competences-grid');

// Blocs principaux
const blocIntro = document.getElementById('bloc-intro');
const blocForm = document.getElementById('bloc-form');
const blocList = document.getElementById('bloc-list');
const blocDetail = document.getElementById('bloc-detail');

// Headers cliquables pour accordéon (Blocs 1 et 2)
const introToggle = document.getElementById('intro-toggle');
const formToggle = document.getElementById('form-toggle');

// Zones accordéon (contenu extensible)
const introAccordion = document.getElementById('intro-accordion');
const formAccordion = document.getElementById('form-accordion');

// Sidebar Bloc 4 (étoile cliquable)
const blocSidebarToggle = document.getElementById('bloc-sidebar-toggle');

// Bloc 3 : Liste des compétences
const cardsContainer = document.getElementById('cards-container');
const emptyState = document.getElementById('empty-state');
const listCounter = document.getElementById('list-counter');

// Bloc 4 : Différents états d'affichage
const detailEmpty = document.getElementById('detail-empty');
const detailViewRead = document.getElementById('detail-view-read');
const detailViewEdit = document.getElementById('detail-view-edit');

// Bloc 4 : Éléments du mode LECTURE
const detailTitleRead = document.getElementById('detail-title-read');
const detailDescriptionRead = document.getElementById('detail-description-read');
const detailStarsRead = document.getElementById('detail-stars-read');
const detailProjectsRead = document.getElementById('detail-projects-read');
const detailSnippetsRead = document.getElementById('detail-snippets-read');
const detailExternalProjectsRead = document.getElementById('detail-external-projects-read');
const detailExternalSnippetsRead = document.getElementById('detail-external-snippets-read');
const btnEditDetail = document.getElementById('btn-edit-detail');
const btnDeleteDetail = document.getElementById('btn-delete-detail');

// Bloc 4 : Éléments du mode ÉDITION
const editTitle = document.getElementById('edit-title');
const editDescription = document.getElementById('edit-description');
const editProjectsSelect = document.getElementById('edit-projects-select');
const editSnippetsSelect = document.getElementById('edit-snippets-select');
const editProjectsLinkedList = document.getElementById('edit-projects-linked-list');
const editSnippetsLinkedList = document.getElementById('edit-snippets-linked-list');
const btnAddProjectLinked = document.getElementById('btn-add-project-linked');
const btnAddSnippetLinked = document.getElementById('btn-add-snippet-linked');
const editExternalProjectsList = document.getElementById('edit-external-projects-list');
const editExternalSnippetsList = document.getElementById('edit-external-snippets-list');
const btnAddExternalProject = document.getElementById('btn-add-external-project');
const btnAddExternalSnippet = document.getElementById('btn-add-external-snippet');
const btnSaveDetail = document.getElementById('btn-save-detail');
const btnCancelDetail = document.getElementById('btn-cancel-detail');

// Bloc 2 : Formulaire de création
const competenceForm = document.getElementById('competence-form');
const competenceName = document.getElementById('competence-name');
const competenceDescription = document.getElementById('competence-description');
const btnSubmitText = document.getElementById('btn-submit-text');

// Zones de messages flash
const formFlashMessages = document.getElementById('form-flash-messages');
const detailFlashMessages = document.getElementById('detail-flash-messages');

// =============================================
// INITIALISATION AU CHARGEMENT DE LA PAGE
// =============================================

document.addEventListener('DOMContentLoaded', () => {
    initAutoScroll();
    initAccordion();
    loadInitialData();
    initEventListeners();
});

// =============================================
// SYSTEME ACCORDEON
// =============================================
// Pattern inspiré de la page Veille
// Gestion des blocs extensibles/réduits pour optimiser l'espace écran
// =============================================

/**
 * Initialise le système d'accordéon avec les états par défaut
 * 
 * États initiaux :
 * - Bloc 1 (Introduction) : collapsed (réduit, header visible seulement)
 * - Bloc 2 (Formulaire) : expanded (ouvert par défaut)
 * - Bloc 3 (Liste) : wide (affichage 2 colonnes)
 * - Bloc 4 (Détail) : collapsed (sidebar avec étoile)
 * - Grid : list-mode (colonnes 1fr 2fr 60px)
 */
function initAccordion() {
    // Bloc 1 : Introduction réduite par défaut
    blocIntro.classList.add('collapsed');
    blocIntro.classList.remove('expanded');
    
    // Bloc 2 : Formulaire ouvert par défaut pour faciliter la création
    blocForm.classList.add('expanded');
    blocForm.classList.remove('collapsed');
    
    // Bloc 3 : Liste en mode 2 colonnes (wide)
    blocList.classList.add('wide');
    blocList.classList.remove('narrow');
    
    // Bloc 4 : Détail réduit en sidebar
    blocDetail.classList.add('collapsed');
    blocDetail.classList.remove('expanded');
    
    // Grid principale en mode liste
    competencesGrid.classList.add('list-mode');
    competencesGrid.classList.remove('detail-mode');
    
    // Attachement des event listeners pour les toggles
    introToggle.addEventListener('click', () => toggleColumnBloc('intro'));
    formToggle.addEventListener('click', () => toggleColumnBloc('form'));
    
    // Event listener pour étendre le Bloc 4 via la sidebar
    blocSidebarToggle.addEventListener('click', expandDetailBloc);

    // Event listener pour RÉDUIRE le Bloc 4 via le header
    // Mode LECTURE
    const detailHeaderRead = document.querySelector('#detail-view-read .detail-header');
    if (detailHeaderRead) {
        detailHeaderRead.style.cursor = 'pointer';
        detailHeaderRead.addEventListener('click', (e) => {
            // Ne pas déclencher si on clique sur les boutons Éditer/Supprimer
            if (!e.target.closest('.btn-edit') && 
                !e.target.closest('.btn-delete') &&
                !e.target.closest('.detail-actions')) {
                collapseDetailBloc();
            }
        });
    }
    
    // Mode ÉDITION
    const detailHeaderEdit = document.querySelector('#detail-view-edit .detail-header');
    if (detailHeaderEdit) {
        detailHeaderEdit.style.cursor = 'pointer';
        detailHeaderEdit.addEventListener('click', (e) => {
            // Ne pas déclencher si on clique sur les boutons Enregistrer/Annuler
            if (!e.target.closest('.btn-save') && 
                !e.target.closest('.btn-cancel') &&
                !e.target.closest('.detail-actions')) {
                collapseDetailBloc();
            }
        });
    }

}

/**
 * Toggle entre Bloc 1 (Introduction) et Bloc 2 (Formulaire)
 * Fonctionne en mode radio : un seul bloc peut être étendu à la fois
 * 
 * @param {string} bloc - 'intro' pour Bloc 1, 'form' pour Bloc 2
 */
function toggleColumnBloc(bloc) {
    if (bloc === 'intro') {
        // Si on clique sur Intro
        if (blocIntro.classList.contains('collapsed')) {
            // Ouvrir Intro, fermer Form
            blocIntro.classList.remove('collapsed');
            blocIntro.classList.add('expanded');
            blocForm.classList.remove('expanded');
            blocForm.classList.add('collapsed');
        } else {
            // Fermer Intro, ouvrir Form
            blocIntro.classList.remove('expanded');
            blocIntro.classList.add('collapsed');
            blocForm.classList.remove('collapsed');
            blocForm.classList.add('expanded');
        }
    } else if (bloc === 'form') {
        // Si on clique sur Form
        if (blocForm.classList.contains('collapsed')) {
            // Ouvrir Form, fermer Intro
            blocForm.classList.remove('collapsed');
            blocForm.classList.add('expanded');
            blocIntro.classList.remove('expanded');
            blocIntro.classList.add('collapsed');
        } else {
            // Fermer Form, ouvrir Intro
            blocForm.classList.remove('expanded');
            blocForm.classList.add('collapsed');
            blocIntro.classList.remove('collapsed');
            blocIntro.classList.add('expanded');
        }
    }
}

/**
 * Étend le Bloc 4 (Détail) pour afficher les informations de la compétence
 * Réduit automatiquement le Bloc 3 (Liste) en 1 colonne pour faire de la place
 * Change la grid en mode detail-mode (colonnes 1fr 1fr 1.5fr)
 */
function expandDetailBloc() {
    // Bloc 4 passe en mode expanded (panneau complet)
    blocDetail.classList.remove('collapsed');
    blocDetail.classList.add('expanded');
    
    // Bloc 3 passe en mode narrow (1 seule colonne de cards)
    blocList.classList.remove('wide');
    blocList.classList.add('narrow');
    
    // Grid passe en mode detail (colonnes recalculées)
    competencesGrid.classList.remove('list-mode');
    competencesGrid.classList.add('detail-mode');
}

/**
 * Réduit le Bloc 4 (Détail) en sidebar (juste l'icône étoile)
 * Étend automatiquement le Bloc 3 (Liste) en 2 colonnes
 * Restaure la grid en mode list-mode (colonnes 1fr 2fr 60px)
 */
function collapseDetailBloc() {
    // Bloc 4 passe en mode collapsed (sidebar avec étoile)
    blocDetail.classList.remove('expanded');
    blocDetail.classList.add('collapsed');
    
    // Bloc 3 passe en mode wide (2 colonnes de cards)
    blocList.classList.remove('narrow');
    blocList.classList.add('wide');
    
    // Grid passe en mode list (colonnes par défaut)
    competencesGrid.classList.remove('detail-mode');
    competencesGrid.classList.add('list-mode');
}

// =============================================
// ANIMATION AUTO-SCROLL BLOC INTRODUCTION
// =============================================
// Scroll automatique du contenu au survol de la souris
// Actif uniquement si le Bloc Introduction est en mode expanded
// =============================================

/**
 * Initialise l'animation de scroll automatique pour le Bloc Introduction
 * Le scroll démarre au survol de la souris et s'arrête lorsqu'elle sort
 * Actif uniquement si le bloc est en mode expanded
 */
function initAutoScroll() {
    const introContent = introAccordion;
    
    if (!introContent) {
        console.error('[AutoScroll] Element #intro-accordion introuvable');
        return;
    }
    
    let scrollDirection = 1;    // 1 = vers le bas, -1 = vers le haut
    let scrollSpeed = 0;        // Vitesse actuelle (0 = arrêté)
    let isScrolling = false;    // État du scroll
    
    /**
     * Boucle d'animation requestAnimationFrame
     * Gère le scroll continu avec inversion de direction aux extrémités
     */
    function autoScroll() {
        const maxScroll = introContent.scrollHeight - introContent.clientHeight;
        
        // Si pas de contenu scrollable ou scroll désactivé, continuer la boucle sans action
        if (maxScroll <= 0 || !isScrolling) {
            requestAnimationFrame(autoScroll);
            return;
        }
        
        // Appliquer le scroll
        introContent.scrollTop += scrollDirection * scrollSpeed;
        
        // Inverser la direction si on atteint une extrémité
        if (introContent.scrollTop >= maxScroll) {
            scrollDirection = -1;  // Remonter
        } else if (introContent.scrollTop <= 0) {
            scrollDirection = 1;   // Redescendre
        }
        
        requestAnimationFrame(autoScroll);
    }
    
    // Démarrer la boucle d'animation
    autoScroll();
    
    // Activer le scroll au survol (uniquement si le bloc est expanded)
    blocIntro.addEventListener('mouseenter', () => {
        // Vérifier que le bloc est ouvert avant d'activer le scroll
        if (blocIntro.classList.contains('expanded')) {
            scrollSpeed = 0.3;
            isScrolling = true;
        }
    });
    
    // Désactiver le scroll lorsque la souris sort
    blocIntro.addEventListener('mouseleave', () => {
        scrollSpeed = 0;
        isScrolling = false;
    });
}

// =============================================
// CHARGEMENT DES DONNEES INITIALES
// =============================================
// Charge toutes les données nécessaires depuis l'API
// au chargement de la page
// =============================================

/**
 * Charge en parallèle toutes les données depuis l'API
 * - Liste des compétences
 * - Liste des projets Kanban disponibles
 * - Liste des snippets disponibles
 * 
 * Appelle ensuite renderCompetenceCards() pour afficher les cards
 */
async function loadInitialData() {
    try {
        // Requêtes parallèles pour optimiser le temps de chargement
        const [competencesData, projectsData, snippetsData] = await Promise.all([
            API.get('/api/competences'),
            API.get('/api/projects'),
            API.get('/api/snippets')
        ]);
        
        // Stockage dans les variables globales
        competences = competencesData;
        allProjects = projectsData;
        allSnippets = snippetsData;
        
        // Affichage des cards
        renderCompetenceCards();
        
    } catch (error) {
        console.error('[API] Erreur chargement données initiales:', error);
        showFlashMessage(formFlashMessages, 'Erreur lors du chargement des données', 'error');
    }
}

// =============================================
// AFFICHAGE DES CARDS COMPETENCES (BLOC 3)
// =============================================

/**
 * Génère et affiche les cards de toutes les compétences dans le Bloc 3
 * Gère également l'état vide et le compteur
 * Attache les event listeners de sélection sur chaque card
 */
function renderCompetenceCards() {
    // Si aucune compétence, afficher l'état vide
    if (competences.length === 0) {
        emptyState.style.display = 'flex';
        cardsContainer.innerHTML = '';
        listCounter.textContent = '0 compétence';
        return;
    }
    
    // Cacher l'état vide et mettre à jour le compteur
    emptyState.style.display = 'none';
    listCounter.textContent = `${competences.length} compétence${competences.length > 1 ? 's' : ''}`;
    
    // Générer le HTML de toutes les cards
    cardsContainer.innerHTML = competences.map(comp => `
        <div class="competence-card" data-id="${comp.id}">
            <h3 class="card-title">${escapeHtml(comp.name)}</h3>
            <p class="card-description">${escapeHtml(comp.description || 'Pas de description')}</p>
            <div class="card-stars">
                ${renderStars(comp.level)}
                <span class="card-level">${comp.level.toFixed(1)}/5</span>
            </div>
        </div>
    `).join('');
    
    // Attacher les event listeners de sélection
    document.querySelectorAll('.competence-card').forEach(card => {
        card.addEventListener('click', () => {
            const id = parseInt(card.dataset.id);
            selectCompetence(id);
        });
    });
}

// =============================================
// AFFICHAGE DES ETOILES
// =============================================
// Génère le HTML des étoiles avec support des demi-étoiles
// Utilise un gradient SVG pour les demi-étoiles
// =============================================

/**
 * Génère le HTML SVG pour afficher le niveau en étoiles
 * Supporte les demi-étoiles via gradient linéaire
 * 
 * @param {number} level - Niveau entre 0 et 5 (peut contenir des décimales)
 * @returns {string} HTML des 5 étoiles (pleines, vides ou demi)
 */
function renderStars(level) {
    let stars = '';
    
    for (let i = 1; i <= 5; i++) {
        if (level >= i) {
            // Étoile pleine (dorée)
            stars += '<svg class="star-filled" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        } else if (level >= i - 0.5) {
            // Demi-étoile (gradient 50% doré / 50% gris)
            stars += '<svg class="star-half" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><defs><linearGradient id="half-fill-' + i + '"><stop offset="50%" stop-color="var(--accent)"/><stop offset="50%" stop-color="var(--border-color)"/></linearGradient></defs><path fill="url(#half-fill-' + i + ')" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        } else {
            // Étoile vide (grise)
            stars += '<svg class="star-empty" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        }
    }
    
    return stars;
}

// =============================================
// SELECTION D'UNE COMPETENCE
// =============================================
// Appelé au clic sur une card
// Étend automatiquement le Bloc 4 et affiche le mode lecture
// =============================================

/**
 * Sélectionne une compétence et affiche ses détails
 * - Marque la card comme active
 * - Étend automatiquement le Bloc 4 (Détail)
 * - Affiche le mode lecture avec toutes les informations
 * 
 * @param {number} id - ID de la compétence à sélectionner
 */
function selectCompetence(id) {
    selectedCompetenceId = id;
    const competence = competences.find(c => c.id === id);
    
    if (!competence) {
        console.error(`[Selection] Compétence ID ${id} introuvable`);
        return;
    }
    
    // Créer une copie de travail pour l'édition
    currentCompetenceData = JSON.parse(JSON.stringify(competence));
    
    // Mettre à jour l'état visuel des cards (retirer .active de toutes, ajouter sur la sélectionnée)
    document.querySelectorAll('.competence-card').forEach(card => {
        card.classList.remove('active');
    });
    document.querySelector(`[data-id="${id}"]`)?.classList.add('active');
    
    // Étendre automatiquement le Bloc 4 pour afficher les détails
    expandDetailBloc();
    
    // Afficher le mode lecture
    showReadMode(competence);
}

// =============================================
// MODE LECTURE (BLOC 4)
// =============================================
// Affiche toutes les informations d'une compétence en lecture seule
// =============================================

/**
 * Affiche les détails d'une compétence en mode lecture
 * Masque les autres états (empty, edit) et affiche le mode lecture
 * 
 * @param {Object} competence - Objet compétence à afficher
 */
function showReadMode(competence) {
    // Masquer les autres états
    detailEmpty.style.display = 'none';
    detailViewEdit.style.display = 'none';
    detailViewRead.style.display = 'block';
    
    // Remplir les informations générales
    detailTitleRead.textContent = competence.name;
    detailDescriptionRead.textContent = competence.description || 'Pas de description';
    detailStarsRead.innerHTML = `${renderStars(competence.level)} <span class="detail-level">${competence.level.toFixed(1)}/5</span>`;
    
    // Afficher les projets MY-ANKODE liés
    if (competence.projects && competence.projects.length > 0) {
        detailProjectsRead.innerHTML = `
            <div class="linked-items-list">
                ${competence.projects.map(p => `
                    <div class="linked-item">
                        <div class="linked-item-title">${escapeHtml(p.name)}</div>
                        <div class="linked-item-meta">+1 étoile</div>
                    </div>
                `).join('')}
            </div>
        `;
    } else {
        detailProjectsRead.innerHTML = '<p>Aucun projet lié</p>';
    }
    
    // Afficher les snippets MY-ANKODE liés
    if (competence.snippets && competence.snippets.length > 0) {
        detailSnippetsRead.innerHTML = `
            <div class="linked-items-list">
                ${competence.snippets.map(s => `
                    <div class="linked-item">
                        <div class="linked-item-title">${escapeHtml(s.title)}</div>
                        <div class="linked-item-meta">+0.5 étoile</div>
                    </div>
                `).join('')}
            </div>
        `;
    } else {
        detailSnippetsRead.innerHTML = '<p>Aucun snippet lié</p>';
    }
    
    // Afficher les projets externes
    const externalProjects = parseExternalItems(competence.externalProjects);
    if (externalProjects.length > 0) {
        detailExternalProjectsRead.innerHTML = `
            <ul class="linked-items-list">
                ${externalProjects.map(p => `<li class="linked-item"><div class="linked-item-title">${escapeHtml(p)}</div><div class="linked-item-meta">+1 étoile</div></li>`).join('')}
            </ul>
        `;
    } else {
        detailExternalProjectsRead.innerHTML = '<p>Aucun projet externe</p>';
    }
    
    // Afficher les snippets externes
    const externalSnippets = parseExternalItems(competence.externalSnippets);
    if (externalSnippets.length > 0) {
        detailExternalSnippetsRead.innerHTML = `
            <ul class="linked-items-list">
                ${externalSnippets.map(s => `<li class="linked-item"><div class="linked-item-title">${escapeHtml(s)}</div><div class="linked-item-meta">+0.5 étoile</div></li>`).join('')}
            </ul>
        `;
    } else {
        detailExternalSnippetsRead.innerHTML = '<p>Aucun snippet externe</p>';
    }
}

// =============================================
// MODE EDITION (BLOC 4)
// =============================================
// Permet de modifier une compétence existante
// =============================================

/**
 * Bascule en mode édition pour la compétence sélectionnée
 * Pré-remplit les champs avec les données actuelles
 * Peuple les listes de projets/snippets disponibles
 */
function showEditMode() {
    if (!currentCompetenceData) {
        console.error('[Edit] Aucune compétence sélectionnée');
        return;
    }
    
    // Masquer le mode lecture, afficher le mode édition
    detailViewRead.style.display = 'none';
    detailViewEdit.style.display = 'block';
    
    // Pré-remplir les champs
    editTitle.value = currentCompetenceData.name;
    editDescription.value = currentCompetenceData.description || '';
    
    // Peupler les listes déroulantes et les items liés
    populateProjectsSelect();
    populateSnippetsSelect();
    populateExternalList(editExternalProjectsList, currentCompetenceData.externalProjects);
    populateExternalList(editExternalSnippetsList, currentCompetenceData.externalSnippets);
}

// =============================================
// GESTION DES PROJETS LIES
// =============================================

/**
 * Remplit le select des projets disponibles et la liste des projets déjà liés
 * Filtre les projets déjà liés pour ne proposer que ceux disponibles
 * Désactive le bouton d'ajout si tous les projets sont déjà liés
 */
function populateProjectsSelect() {
    // Récupérer les IDs des projets déjà liés
    const linkedProjectIds = (currentCompetenceData.projects || []).map(p => p.id);
    
    // Filtrer pour ne garder que les projets non liés
    const availableProjects = allProjects.filter(p => !linkedProjectIds.includes(p.id));
    
    // Remplir le select
    if (availableProjects.length === 0) {
        editProjectsSelect.innerHTML = '<option value="">Tous les projets sont déjà liés</option>';
        btnAddProjectLinked.disabled = true;
    } else {
        editProjectsSelect.innerHTML = '<option value="">Sélectionner un projet...</option>' +
            availableProjects.map(project => 
                `<option value="${project.id}">${escapeHtml(project.name)}</option>`
            ).join('');
        btnAddProjectLinked.disabled = false;
    }
    
    // Afficher la liste des projets déjà liés
    editProjectsLinkedList.innerHTML = (currentCompetenceData.projects || []).map(project => 
        createLinkedItem(project.id, project.name, '+1 étoile', 'project')
    ).join('');
    
    // Attacher les event listeners sur les boutons de suppression
    editProjectsLinkedList.querySelectorAll('.btn-remove-linked').forEach(btn => {
        btn.addEventListener('click', () => removeLinkedItem('project', btn.dataset.id));
    });
}

// =============================================
// GESTION DES SNIPPETS LIES
// =============================================

/**
 * Remplit le select des snippets disponibles et la liste des snippets déjà liés
 * Filtre les snippets déjà liés pour ne proposer que ceux disponibles
 * Désactive le bouton d'ajout si tous les snippets sont déjà liés
 */
function populateSnippetsSelect() {
    // Récupérer les IDs des snippets déjà liés
    const linkedSnippetIds = currentCompetenceData.snippetsIds || [];
    
    // Filtrer pour ne garder que les snippets non liés
    const availableSnippets = allSnippets.filter(s => !linkedSnippetIds.includes(s.id));
    
    // Remplir le select
    if (availableSnippets.length === 0) {
        editSnippetsSelect.innerHTML = '<option value="">Tous les snippets sont déjà liés</option>';
        btnAddSnippetLinked.disabled = true;
    } else {
        editSnippetsSelect.innerHTML = '<option value="">Sélectionner un snippet...</option>' +
            availableSnippets.map(snippet => 
                `<option value="${snippet.id}">${escapeHtml(snippet.title)}</option>`
            ).join('');
        btnAddSnippetLinked.disabled = false;
    }
    
    // Afficher la liste des snippets déjà liés
    const linkedSnippets = allSnippets.filter(s => linkedSnippetIds.includes(s.id));
    editSnippetsLinkedList.innerHTML = linkedSnippets.map(snippet => 
        createLinkedItem(snippet.id, snippet.title, '+0.5 étoile', 'snippet')
    ).join('');
    
    // Attacher les event listeners sur les boutons de suppression
    editSnippetsLinkedList.querySelectorAll('.btn-remove-linked').forEach(btn => {
        btn.addEventListener('click', () => removeLinkedItem('snippet', btn.dataset.id));
    });
}

/**
 * Génère le HTML d'un item lié (projet ou snippet MY-ANKODE)
 * 
 * @param {number} id - ID du projet/snippet
 * @param {string} name - Nom à afficher
 * @param {string} bonus - Texte du bonus (ex: "+1 étoile")
 * @param {string} type - 'project' ou 'snippet'
 * @returns {string} HTML du <li>
 */
function createLinkedItem(id, name, bonus, type) {
    return `
        <li>
            <div class="linked-item-info">
                <div class="linked-item-name">${escapeHtml(name)}</div>
                <div class="linked-item-bonus">${bonus}</div>
            </div>
            <button type="button" class="btn-remove-linked" data-id="${id}" data-type="${type}" title="Retirer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </li>
    `;
}

/**
 * Ajoute un projet MY-ANKODE à la compétence en cours d'édition
 * Récupère la valeur du select, ajoute le projet à currentCompetenceData
 * et rafraîchit les selects
 */
function addLinkedProject() {
    const selectedId = parseInt(editProjectsSelect.value);
    
    if (!selectedId) {
        return;
    }
    
    const project = allProjects.find(p => p.id === selectedId);
    if (!project) {
        console.error('[Add] Projet introuvable');
        return;
    }
    
    // Ajouter le projet à la liste liée
    if (!currentCompetenceData.projects) {
        currentCompetenceData.projects = [];
    }
    currentCompetenceData.projects.push(project);
    
    // Rafraîchir l'affichage
    populateProjectsSelect();
}

/**
 * Ajoute un snippet MY-ANKODE à la compétence en cours d'édition
 * Récupère la valeur du select, ajoute le snippet ID à currentCompetenceData
 * et rafraîchit les selects
 */
function addLinkedSnippet() {
    const selectedId = editSnippetsSelect.value;
    
    if (!selectedId) {
        return;
    }
    
    const snippet = allSnippets.find(s => s.id === selectedId);
    if (!snippet) {
        console.error('[Add] Snippet introuvable');
        return;
    }
    
    // Ajouter le snippet ID à la liste liée
    if (!currentCompetenceData.snippetsIds) {
        currentCompetenceData.snippetsIds = [];
    }
    currentCompetenceData.snippetsIds.push(snippet.id);
    
    // Rafraîchir l'affichage
    populateSnippetsSelect();
}

/**
 * Retire un projet ou snippet lié de la compétence en cours d'édition
 * 
 * @param {string} type - 'project' ou 'snippet'
 * @param {string} id - ID de l'élément à retirer
 */
function removeLinkedItem(type, id) {
    if (type === 'project') {
        const projectId = parseInt(id);
        currentCompetenceData.projects = (currentCompetenceData.projects || []).filter(p => p.id !== projectId);
        populateProjectsSelect();
    } else if (type === 'snippet') {
        currentCompetenceData.snippetsIds = (currentCompetenceData.snippetsIds || []).filter(sid => sid !== id);
        populateSnippetsSelect();
    }
}

// =============================================
// GESTION DES ITEMS EXTERNES
// =============================================

/**
 * Peuple une liste éditable d'items externes (projets ou snippets hors MY-ANKODE)
 * Parse la chaîne stockée en BDD et génère les <li> éditables
 * 
 * @param {HTMLElement} listElement - <ul> où insérer les items
 * @param {string} itemsString - Chaîne multi-lignes des items
 */
function populateExternalList(listElement, itemsString) {
    const items = parseExternalItems(itemsString);
    
    listElement.innerHTML = items.map(item => createExternalListItem(item)).join('');
    
    // Attacher les event listeners de suppression
    listElement.querySelectorAll('.btn-remove-item').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.target.closest('li').remove();
        });
    });
}

/**
 * Génère le HTML d'un item externe éditable
 * 
 * @param {string} value - Valeur pré-remplie (nom du projet/snippet)
 * @returns {string} HTML du <li> avec input text
 */
function createExternalListItem(value = '') {
    const randomId = 'item-' + Math.random().toString(36).substr(2, 9);
    return `
        <li>
            <input type="text" value="${escapeHtml(value)}" placeholder="Nom du projet/snippet..." maxlength="200" data-id="${randomId}">
            <button type="button" class="btn-remove-item" title="Supprimer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </li>
    `;
}

/**
 * Ajoute une ligne vide pour saisir un nouvel item externe
 * Focus automatiquement sur l'input créé
 * 
 * @param {HTMLElement} listElement - <ul> où ajouter la ligne
 */
function addExternalItem(listElement) {
    const li = document.createElement('li');
    li.innerHTML = createExternalListItem();
    listElement.appendChild(li);
    
    // Focus sur le nouvel input
    li.querySelector('input').focus();
    
    // Attacher l'event listener de suppression
    li.querySelector('.btn-remove-item').addEventListener('click', () => {
        li.remove();
    });
}

// =============================================
// SAUVEGARDE DES MODIFICATIONS
// =============================================

/**
 * Enregistre les modifications de la compétence via l'API
 * Collecte toutes les données du formulaire d'édition
 * Recharge les données et retourne en mode lecture après succès
 */
async function saveCompetence() {
    // Collecter les données du formulaire
    const data = {
        name: editTitle.value.trim(),
        description: editDescription.value.trim() || null,
        projectIds: (currentCompetenceData.projects || []).map(p => p.id),
        snippetsIds: currentCompetenceData.snippetsIds || [],
        externalProjects: collectExternalItems(editExternalProjectsList),
        externalSnippets: collectExternalItems(editExternalSnippetsList)
    };
    
    // Validation basique
    if (!data.name) {
        showFlashMessage(detailFlashMessages, 'Le titre est obligatoire', 'error');
        return;
    }
    
    try {
        // Appel API PUT
        const result = await API.put(`/api/competences/${selectedCompetenceId}`, data);
        
        showFlashMessage(detailFlashMessages, result.message || 'Compétence mise à jour !', 'success');
        
        // Recharger toutes les données pour avoir les étoiles recalculées
        await loadInitialData();
        
        // Retourner en mode lecture avec les nouvelles données
        const updatedComp = competences.find(c => c.id === selectedCompetenceId);
        if (updatedComp) {
            currentCompetenceData = JSON.parse(JSON.stringify(updatedComp));
            showReadMode(updatedComp);
        }
        
    } catch (error) {
        console.error('[Save] Erreur sauvegarde:', error);
        showFlashMessage(detailFlashMessages, error.message || 'Erreur lors de la sauvegarde', 'error');
    }
}

/**
 * Annule les modifications en cours et retourne en mode lecture
 * Restaure les données originales depuis la liste des compétences
 */
function cancelEdit() {
    if (currentCompetenceData) {
        const original = competences.find(c => c.id === selectedCompetenceId);
        if (original) {
            currentCompetenceData = JSON.parse(JSON.stringify(original));
            showReadMode(original);
        }
    }
}

// =============================================
// SUPPRESSION D'UNE COMPETENCE
// =============================================

/**
 * Supprime la compétence sélectionnée après confirmation
 * Réduit automatiquement le Bloc 4 après suppression
 * Recharge les données et affiche l'état vide
 */
async function deleteCompetence() {
    if (!confirm('Supprimer cette compétence définitivement ?')) return;
    
    try {
        const result = await API.delete(`/api/competences/${selectedCompetenceId}`);
        showFlashMessage(detailFlashMessages, result.message || 'Compétence supprimée !', 'success');
        
        // Masquer les détails et afficher l'état vide
        detailViewRead.style.display = 'none';
        detailEmpty.style.display = 'flex';
        
        // Réduire le Bloc 4 après suppression
        collapseDetailBloc();
        
        // Recharger les données
        await loadInitialData();
        
    } catch (error) {
        console.error('[Delete] Erreur suppression:', error);
        showFlashMessage(detailFlashMessages, error.message || 'Erreur lors de la suppression', 'error');
    }
}

// =============================================
// CREATION NOUVELLE COMPETENCE (BLOC 2)
// =============================================

/**
 * Crée une nouvelle compétence depuis le formulaire du Bloc 2
 * Réinitialise le formulaire après succès
 * Recharge les données pour afficher la nouvelle compétence
 * 
 * @param {Event} e - Event du formulaire
 */
async function createCompetence(e) {
    e.preventDefault();
    
    const data = {
        name: competenceName.value.trim(),
        description: competenceDescription.value.trim() || null
    };
    
    if (!data.name) {
        showFlashMessage(formFlashMessages, 'Le titre est obligatoire', 'error');
        return;
    }
    
    try {
        const result = await API.post('/api/competences', data);
        showFlashMessage(formFlashMessages, result.message || 'Compétence créée !', 'success');
        
        // Réinitialiser le formulaire
        competenceForm.reset();
        
        // Recharger les données
        await loadInitialData();
        
    } catch (error) {
        console.error('[Create] Erreur création:', error);
        showFlashMessage(formFlashMessages, error.message || 'Erreur lors de la création', 'error');
    }
}

// =============================================
// EVENT LISTENERS GLOBAUX
// =============================================

/**
 * Attache tous les event listeners de la page
 * Appelé une seule fois au chargement du DOM
 */
function initEventListeners() {
    // Formulaire de création (Bloc 2)
    competenceForm.addEventListener('submit', createCompetence);
    
    // Boutons du mode lecture
    btnEditDetail.addEventListener('click', showEditMode);
    btnDeleteDetail.addEventListener('click', deleteCompetence);
    
    // Boutons du mode édition
    btnSaveDetail.addEventListener('click', saveCompetence);
    btnCancelDetail.addEventListener('click', cancelEdit);
    
    // Ajout de projets/snippets liés
    btnAddProjectLinked.addEventListener('click', addLinkedProject);
    btnAddSnippetLinked.addEventListener('click', addLinkedSnippet);
    
    // Ajout d'items externes
    btnAddExternalProject.addEventListener('click', () => addExternalItem(editExternalProjectsList));
    btnAddExternalSnippet.addEventListener('click', () => addExternalItem(editExternalSnippetsList));
}

// =============================================
// UTILITAIRES
// =============================================

/**
 * Parse une chaîne multi-lignes en tableau d'items
 * Chaque ligne non vide devient un item
 * 
 * @param {string} text - Chaîne multi-lignes (format: un item par ligne)
 * @returns {Array<string>} Tableau d'items nettoyés
 */
function parseExternalItems(text) {
    if (!text || text.trim() === '') return [];
    return text.split('\n').map(line => line.trim()).filter(line => line !== '');
}

/**
 * Collecte tous les items d'une liste externe éditable
 * Parcourt tous les inputs text et retourne une chaîne multi-lignes
 * 
 * @param {HTMLElement} listElement - <ul> contenant les inputs
 * @returns {string} Chaîne multi-lignes des items (un par ligne)
 */
function collectExternalItems(listElement) {
    const items = [];
    listElement.querySelectorAll('input[type="text"]').forEach(input => {
        const value = input.value.trim();
        if (value) items.push(value);
    });
    return items.join('\n');
}

/**
 * Affiche un message flash temporaire (5 secondes)
 * 
 * @param {HTMLElement} container - Element où afficher le message
 * @param {string} message - Texte du message
 * @param {string} type - 'success' ou 'error'
 */
function showFlashMessage(container, message, type) {
    container.innerHTML = `<div class="flash-message flash-${type}">${escapeHtml(message)}</div>`;
    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}

/**
 * Échappe les caractères HTML pour éviter les injections XSS
 * 
 * @param {string} text - Texte à échapper
 * @returns {string} Texte échappé et sécurisé
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}