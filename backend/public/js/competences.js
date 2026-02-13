// ========================================
// COMPETENCES PAGE - GESTION COMPLETE
// MY-ANKODE - Module de gestion des compétences
// Fonctionnalités : CRUD, affichage détails, calcul auto niveau
// ========================================

// =============================================
// 1️⃣ VARIABLES GLOBALES
// =============================================
let competences = [];
let allProjects = [];
let allSnippets = [];
let selectedCompetenceId = null;
let currentCompetenceData = null;

// =============================================
// 2️⃣ ELEMENTS DOM
// =============================================

// Bloc 3 : Liste cards
const cardsContainer = document.getElementById('cards-container');
const emptyState = document.getElementById('empty-state');
const listCounter = document.getElementById('list-counter');

// Bloc 4 : États
const detailEmpty = document.getElementById('detail-empty');
const detailViewRead = document.getElementById('detail-view-read');
const detailViewEdit = document.getElementById('detail-view-edit');

// Mode LECTURE
const detailTitleRead = document.getElementById('detail-title-read');
const detailDescriptionRead = document.getElementById('detail-description-read');
const detailStarsRead = document.getElementById('detail-stars-read');
const detailProjectsRead = document.getElementById('detail-projects-read');
const detailSnippetsRead = document.getElementById('detail-snippets-read');
const detailExternalProjectsRead = document.getElementById('detail-external-projects-read');
const detailExternalSnippetsRead = document.getElementById('detail-external-snippets-read');
const btnEditDetail = document.getElementById('btn-edit-detail');
const btnDeleteDetail = document.getElementById('btn-delete-detail');

// Mode ÉDITION
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

// Formulaire création (Bloc 2)
const competenceForm = document.getElementById('competence-form');
const competenceName = document.getElementById('competence-name');
const competenceDescription = document.getElementById('competence-description');
const btnSubmitText = document.getElementById('btn-submit-text');

// Messages flash
const formFlashMessages = document.getElementById('form-flash-messages');
const detailFlashMessages = document.getElementById('detail-flash-messages');

// =============================================
// 3️⃣ INITIALISATION AU CHARGEMENT
// =============================================
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 [Competences] Initialisation...');
    initAutoScroll();
    loadInitialData();
    initEventListeners();
});

// =============================================
// 4️⃣ ANIMATION AUTO-SCROLL BLOC INTRO
// =============================================
function initAutoScroll() {
    const introContent = document.querySelector('.bloc-intro .scrollable');
    
    if (!introContent) {
        console.warn('⚠️ [AutoScroll] Element .bloc-intro .scrollable introuvable');
        return;
    }
    
    let scrollDirection = 1;
    let scrollSpeed = 0;
    let isScrolling = false;
    
    function autoScroll() {
        const maxScroll = introContent.scrollHeight - introContent.clientHeight;
        
        if (maxScroll <= 0 || !isScrolling) {
            requestAnimationFrame(autoScroll);
            return;
        }
        
        introContent.scrollTop += scrollDirection * scrollSpeed;
        
        if (introContent.scrollTop >= maxScroll) {
            scrollDirection = -1;
        } else if (introContent.scrollTop <= 0) {
            scrollDirection = 1;
        }
        
        requestAnimationFrame(autoScroll);
    }
    
    autoScroll();
    console.log('✅ [AutoScroll] Prêt (activé au survol uniquement)');
    
    introContent.addEventListener('mouseenter', () => {
        scrollSpeed = 0.3;
        isScrolling = true;
        console.log('▶️ [AutoScroll] Démarré');
    });
    
    introContent.addEventListener('mouseleave', () => {
        scrollSpeed = 0;
        isScrolling = false;
        console.log('⏸️ [AutoScroll] Pausé');
    });
}

// =============================================
// 5️⃣ CHARGEMENT INITIAL DES DONNÉES
// =============================================
async function loadInitialData() {
    console.log('📡 [API] Chargement données initiales...');
    
    try {
        const [competencesData, projectsData, snippetsData] = await Promise.all([
            API.get('/api/competences'),
            API.get('/api/projects'),
            API.get('/api/snippets')
        ]);
        
        competences = competencesData;
        allProjects = projectsData;
        allSnippets = snippetsData;
        
        console.log(`✅ [API] ${competences.length} compétences chargées`);
        console.log(`✅ [API] ${allProjects.length} projets disponibles`);
        console.log(`✅ [API] ${allSnippets.length} snippets disponibles`);
        
        renderCompetenceCards();
        
    } catch (error) {
        console.error('❌ [API] Erreur chargement:', error);
        showFlashMessage(formFlashMessages, 'Erreur lors du chargement des données', 'error');
    }
}

// =============================================
// 6️⃣ AFFICHAGE DES CARDS (BLOC 3)
// =============================================
function renderCompetenceCards() {
    console.log(`🎨 [Render] Affichage de ${competences.length} cards`);
    
    if (competences.length === 0) {
        emptyState.style.display = 'flex';
        cardsContainer.innerHTML = '';
        listCounter.textContent = '0 compétence';
        return;
    }
    
    emptyState.style.display = 'none';
    listCounter.textContent = `${competences.length} compétence${competences.length > 1 ? 's' : ''}`;
    
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
    
    document.querySelectorAll('.competence-card').forEach(card => {
        card.addEventListener('click', () => {
            const id = parseInt(card.dataset.id);
            selectCompetence(id);
        });
    });
    
    console.log('✅ [Render] Cards affichées');
}

// =============================================
// 7️⃣ AFFICHAGE DES ÉTOILES (avec demi-étoiles)
// =============================================
function renderStars(level) {
    let stars = '';
    
    for (let i = 1; i <= 5; i++) {
        if (level >= i) {
            // Étoile pleine
            stars += '<svg class="star-filled" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        } else if (level >= i - 0.5) {
            // Demi-étoile
            stars += '<svg class="star-half" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><defs><linearGradient id="half-fill-' + i + '"><stop offset="50%" stop-color="var(--accent)"/><stop offset="50%" stop-color="var(--border-color)"/></linearGradient></defs><path fill="url(#half-fill-' + i + ')" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        } else {
            // Étoile vide
            stars += '<svg class="star-empty" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        }
    }
    
    return stars;
}

// =============================================
// 8️⃣ SÉLECTION COMPÉTENCE → MODE LECTURE
// =============================================
function selectCompetence(id) {
    console.log(`👆 [Selection] Compétence ID ${id}`);
    
    selectedCompetenceId = id;
    const competence = competences.find(c => c.id === id);
    
    if (!competence) {
        console.error(`❌ [Selection] Compétence ${id} introuvable`);
        return;
    }
    
    currentCompetenceData = JSON.parse(JSON.stringify(competence));
    
    document.querySelectorAll('.competence-card').forEach(card => {
        card.classList.remove('active');
    });
    document.querySelector(`[data-id="${id}"]`)?.classList.add('active');
    
    showReadMode(competence);
}

// =============================================
// 9️⃣ AFFICHAGE MODE LECTURE
// =============================================
function showReadMode(competence) {
    console.log('📖 [Mode] Passage en mode LECTURE');
    
    detailEmpty.style.display = 'none';
    detailViewEdit.style.display = 'none';
    detailViewRead.style.display = 'block';
    
    detailTitleRead.textContent = competence.name;
    detailDescriptionRead.textContent = competence.description || 'Pas de description';
    detailStarsRead.innerHTML = `${renderStars(competence.level)} <span class="detail-level">${competence.level.toFixed(1)}/5</span>`;
    
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
    
    console.log('✅ [Mode] Mode LECTURE affiché');
}

// =============================================
// 🔟 PASSAGE EN MODE ÉDITION
// =============================================
function showEditMode() {
    console.log('✏️ [Mode] Passage en mode ÉDITION');
    
    if (!currentCompetenceData) {
        console.error('❌ [Edit] Aucune compétence sélectionnée');
        return;
    }
    
    detailViewRead.style.display = 'none';
    detailViewEdit.style.display = 'block';
    
    editTitle.value = currentCompetenceData.name;
    editDescription.value = currentCompetenceData.description || '';
    
    populateProjectsSelect();
    populateSnippetsSelect();
    populateExternalList(editExternalProjectsList, currentCompetenceData.externalProjects);
    populateExternalList(editExternalSnippetsList, currentCompetenceData.externalSnippets);
    
    console.log('✅ [Mode] Mode ÉDITION affiché');
}

// =============================================
// 1️⃣1️⃣ REMPLIR SELECT + LISTE PROJETS
// =============================================
function populateProjectsSelect() {
    const linkedProjectIds = (currentCompetenceData.projects || []).map(p => p.id);
    const availableProjects = allProjects.filter(p => !linkedProjectIds.includes(p.id));
    
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
    
    editProjectsLinkedList.innerHTML = (currentCompetenceData.projects || []).map(project => 
        createLinkedItem(project.id, project.name, '+1 étoile', 'project')
    ).join('');
    
    editProjectsLinkedList.querySelectorAll('.btn-remove-linked').forEach(btn => {
        btn.addEventListener('click', () => removeLinkedItem('project', btn.dataset.id));
    });
    
    console.log(`📋 [Select] ${availableProjects.length} projets disponibles`);
}

// =============================================
// 1️⃣2️⃣ REMPLIR SELECT + LISTE SNIPPETS
// =============================================
function populateSnippetsSelect() {
    const linkedSnippetIds = currentCompetenceData.snippetsIds || [];
    const availableSnippets = allSnippets.filter(s => !linkedSnippetIds.includes(s.id));
    
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
    
    const linkedSnippets = allSnippets.filter(s => linkedSnippetIds.includes(s.id));
    editSnippetsLinkedList.innerHTML = linkedSnippets.map(snippet => 
        createLinkedItem(snippet.id, snippet.title, '+0.5 étoile', 'snippet')
    ).join('');
    
    editSnippetsLinkedList.querySelectorAll('.btn-remove-linked').forEach(btn => {
        btn.addEventListener('click', () => removeLinkedItem('snippet', btn.dataset.id));
    });
    
    console.log(`📋 [Select] ${availableSnippets.length} snippets disponibles`);
}

// =============================================
// 1️⃣3️⃣ CRÉER <LI> ITEM LIÉ (projet/snippet MY-ANKODE)
// =============================================
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

// =============================================
// 1️⃣4️⃣ AJOUTER PROJET LIÉ
// =============================================
function addLinkedProject() {
    const selectedId = parseInt(editProjectsSelect.value);
    
    if (!selectedId) {
        console.warn('⚠️ [Add] Aucun projet sélectionné');
        return;
    }
    
    const project = allProjects.find(p => p.id === selectedId);
    if (!project) {
        console.error('❌ [Add] Projet introuvable');
        return;
    }
    
    if (!currentCompetenceData.projects) {
        currentCompetenceData.projects = [];
    }
    currentCompetenceData.projects.push(project);
    
    populateProjectsSelect();
    
    console.log(`✅ [Add] Projet "${project.name}" ajouté`);
}

// =============================================
// 1️⃣5️⃣ AJOUTER SNIPPET LIÉ
// =============================================
function addLinkedSnippet() {
    const selectedId = editSnippetsSelect.value;
    
    if (!selectedId) {
        console.warn('⚠️ [Add] Aucun snippet sélectionné');
        return;
    }
    
    const snippet = allSnippets.find(s => s.id === selectedId);
    if (!snippet) {
        console.error('❌ [Add] Snippet introuvable');
        return;
    }
    
    if (!currentCompetenceData.snippetsIds) {
        currentCompetenceData.snippetsIds = [];
    }
    currentCompetenceData.snippetsIds.push(snippet.id);
    
    populateSnippetsSelect();
    
    console.log(`✅ [Add] Snippet "${snippet.title}" ajouté`);
}

// =============================================
// 1️⃣6️⃣ RETIRER ITEM LIÉ
// =============================================
function removeLinkedItem(type, id) {
    console.log(`🗑️ [Remove] Retrait ${type} ID ${id}`);
    
    if (type === 'project') {
        const projectId = parseInt(id);
        currentCompetenceData.projects = (currentCompetenceData.projects || []).filter(p => p.id !== projectId);
        populateProjectsSelect();
    } else if (type === 'snippet') {
        currentCompetenceData.snippetsIds = (currentCompetenceData.snippetsIds || []).filter(sid => sid !== id);
        populateSnippetsSelect();
    }
    
    console.log(`✅ [Remove] ${type} retiré`);
}

// =============================================
// 1️⃣7️⃣ REMPLIR LISTES EXTERNES
// =============================================
function populateExternalList(listElement, itemsString) {
    const items = parseExternalItems(itemsString);
    
    listElement.innerHTML = items.map(item => createExternalListItem(item)).join('');
    
    listElement.querySelectorAll('.btn-remove-item').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.target.closest('li').remove();
        });
    });
}

// =============================================
// 1️⃣8️⃣ CRÉER <LI> ITEM EXTERNE
// =============================================
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

// =============================================
// 1️⃣9️⃣ AJOUTER LIGNE EXTERNE
// =============================================
function addExternalItem(listElement) {
    const li = document.createElement('li');
    li.innerHTML = createExternalListItem();
    listElement.appendChild(li);
    
    li.querySelector('input').focus();
    
    li.querySelector('.btn-remove-item').addEventListener('click', () => {
        li.remove();
    });
    
    console.log('➕ [Edit] Ligne externe ajoutée');
}

// =============================================
// 2️⃣0️⃣ ENREGISTRER MODIFICATIONS
// =============================================
async function saveCompetence() {
    console.log('💾 [Save] Enregistrement...');
    
    const data = {
        name: editTitle.value.trim(),
        description: editDescription.value.trim() || null,
        projectIds: (currentCompetenceData.projects || []).map(p => p.id),
        snippetsIds: currentCompetenceData.snippetsIds || [],
        externalProjects: collectExternalItems(editExternalProjectsList),
        externalSnippets: collectExternalItems(editExternalSnippetsList)
    };
    
    if (!data.name) {
        showFlashMessage(detailFlashMessages, 'Le titre est obligatoire', 'error');
        return;
    }
    
    try {
        const result = await API.put(`/api/competences/${selectedCompetenceId}`, data);
        
        showFlashMessage(detailFlashMessages, result.message || 'Compétence mise à jour !', 'success');
        
        await loadInitialData();
        
        const updatedComp = competences.find(c => c.id === selectedCompetenceId);
        if (updatedComp) {
            currentCompetenceData = JSON.parse(JSON.stringify(updatedComp));
            showReadMode(updatedComp);
        }
        
        console.log('✅ [Save] Compétence mise à jour');
        
    } catch (error) {
        console.error('❌ [Save] Erreur:', error);
        showFlashMessage(detailFlashMessages, error.message || 'Erreur lors de la sauvegarde', 'error');
    }
}

// =============================================
// 2️⃣1️⃣ ANNULER ÉDITION
// =============================================
function cancelEdit() {
    console.log('❌ [Edit] Annulation');
    
    if (currentCompetenceData) {
        const original = competences.find(c => c.id === selectedCompetenceId);
        if (original) {
            currentCompetenceData = JSON.parse(JSON.stringify(original));
            showReadMode(original);
        }
    }
}

// =============================================
// 2️⃣2️⃣ SUPPRIMER COMPÉTENCE
// =============================================
async function deleteCompetence() {
    if (!confirm('Supprimer cette compétence définitivement ?')) return;
    
    console.log(`🗑️ [Delete] Suppression compétence ${selectedCompetenceId}`);
    
    try {
        const result = await API.delete(`/api/competences/${selectedCompetenceId}`);
        showFlashMessage(detailFlashMessages, result.message || 'Compétence supprimée !', 'success');
        
        detailViewRead.style.display = 'none';
        detailEmpty.style.display = 'flex';
        
        await loadInitialData();
        
        console.log('✅ [Delete] Compétence supprimée');
        
    } catch (error) {
        console.error('❌ [Delete] Erreur:', error);
        showFlashMessage(detailFlashMessages, error.message || 'Erreur lors de la suppression', 'error');
    }
}

// =============================================
// 2️⃣3️⃣ CRÉER NOUVELLE COMPÉTENCE (BLOC 2)
// =============================================
async function createCompetence(e) {
    e.preventDefault();
    console.log('➕ [Create] Création compétence...');
    
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
        
        competenceForm.reset();
        
        await loadInitialData();
        
        console.log('✅ [Create] Compétence créée');
        
    } catch (error) {
        console.error('❌ [Create] Erreur:', error);
        showFlashMessage(formFlashMessages, error.message || 'Erreur lors de la création', 'error');
    }
}

// =============================================
// 2️⃣4️⃣ EVENT LISTENERS
// =============================================
function initEventListeners() {
    competenceForm.addEventListener('submit', createCompetence);
    
    btnEditDetail.addEventListener('click', showEditMode);
    
    btnSaveDetail.addEventListener('click', saveCompetence);
    btnCancelDetail.addEventListener('click', cancelEdit);
    
    btnAddProjectLinked.addEventListener('click', addLinkedProject);
    btnAddSnippetLinked.addEventListener('click', addLinkedSnippet);
    
    btnAddExternalProject.addEventListener('click', () => addExternalItem(editExternalProjectsList));
    btnAddExternalSnippet.addEventListener('click', () => addExternalItem(editExternalSnippetsList));
    
    btnDeleteDetail.addEventListener('click', deleteCompetence);
    
    console.log('✅ [Events] Listeners initialisés');
}

// =============================================
// 2️⃣5️⃣ UTILITAIRES
// =============================================

function parseExternalItems(text) {
    if (!text || text.trim() === '') return [];
    return text.split('\n').map(line => line.trim()).filter(line => line !== '');
}

function collectExternalItems(listElement) {
    const items = [];
    listElement.querySelectorAll('input[type="text"]').forEach(input => {
        const value = input.value.trim();
        if (value) items.push(value);
    });
    return items.join('\n');
}

function showFlashMessage(container, message, type) {
    container.innerHTML = `<div class="flash-message flash-${type}">${escapeHtml(message)}</div>`;
    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

console.log('✅ [Competences] Module chargé');