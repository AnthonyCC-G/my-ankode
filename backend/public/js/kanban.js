/**
 * KANBAN.JS - Gestion du Kanban (Projets + Tâches)
 * Version MVP - JS Vanilla ES6+ avec Fetch API
 * Projet : MY-ANKODE - Certification DWWM
 * Auteur : Anthony
 * 
 * Fonctionnalités :
 * - CRUD Projets (Create, Read, Delete)
 * - CRUD Tâches (Create, Read, Update status, Delete)
 * - Fetch API avec gestion d'erreurs (try/catch)
 * - Manipulation DOM dynamique (innerHTML / createElement)
 * - Toggle focus entre blocs Projets et Tâches
 */

// ===== 1. DÉTECTION MOBILE CÔTÉ CLIENT (Double sécurité avec le controller) =====
(function checkMobileDevice() {
    const isMobile = window.innerWidth < 768;
    
    if (isMobile) {
        console.log('[KANBAN] Appareil mobile détecté - Redirection vers /desktop-only');
        window.location.href = '/desktop-only';
        return;
    }
    
    console.log('[KANBAN] Appareil desktop détecté - Chargement du Kanban');
    
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth < 768) {
                console.log('[KANBAN] Fenêtre réduite en mobile - Redirection');
                window.location.href = '/desktop-only';
            }
        }, 250);
    });
})();

// ===== 2. VARIABLES GLOBALES =====
let currentProjectId = null; // ID du projet actuellement sélectionné
let projects = []; // Liste de tous les projets de l'utilisateur
let tasks = []; // Liste des tâches du projet sélectionné

// ===== 3. INITIALISATION AU CHARGEMENT DU DOM =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('[KANBAN] DOM chargé - Initialisation du Kanban');
    
    // Charger les projets au démarrage
    loadProjects();
    
    // Initialiser les événements globaux
    initEventListeners();
    
    console.log('[KANBAN] Script kanban.js chargé avec succès ✅');
});

// ===== 4. INITIALISATION DES EVENT LISTENERS =====
function initEventListeners() {
    // Bouton "Nouveau projet"
    const btnNewProject = document.getElementById('btn-new-project');
    if (btnNewProject) {
        btnNewProject.addEventListener('click', showProjectForm);
    }
    
    // Bouton "Annuler" formulaire projet
    const btnCancelProject = document.getElementById('btn-cancel-project');
    if (btnCancelProject) {
        btnCancelProject.addEventListener('click', hideProjectForm);
    }
    
    // Soumission formulaire projet
    const projectForm = document.getElementById('project-form');
    if (projectForm) {
        projectForm.addEventListener('submit', handleCreateProject);
    }
    
    // ===== NOUVEAU : Clic sur TOUTE la zone réduite du bloc Projets ===== 
    const projectsBlockReduced = document.querySelector('#projects-block .block-content--reduced');
    if (projectsBlockReduced) {
        projectsBlockReduced.addEventListener('click', switchToProjectsBlock);
        // Ajouter un style visuel pour montrer que c'est cliquable
        projectsBlockReduced.style.cursor = 'pointer';
    }
    
    // ===== NOUVEAU : Clic sur TOUTE la zone réduite du bloc Tâches =====
    const tasksBlockReduced = document.querySelector('#tasks-block .block-content--reduced');
    if (tasksBlockReduced) {
        tasksBlockReduced.addEventListener('click', function() {
            // Si un projet est sélectionné, basculer vers Tâches
            if (currentProjectId) {
                switchToTasksBlock();
            } else {
                // Sinon, afficher un message
                showError('Sélectionne d\'abord un projet dans la liste ci-dessus.');
            }
        });
        // Ajouter un style visuel pour montrer que c'est cliquable
        tasksBlockReduced.style.cursor = 'pointer';
    }
    
    // Bouton "Nouvelle tâche" (colonne TODO)
    const btnNewTaskTodo = document.getElementById('btn-new-task-todo');
    if (btnNewTaskTodo) {
        btnNewTaskTodo.addEventListener('click', showTaskForm);
    }
    
    console.log('[KANBAN] Event listeners initialisés ✅');
}

// ===== 5. CHARGEMENT DES PROJETS (FETCH API) =====
async function loadProjects() {
    try {
        console.log('[KANBAN] Chargement des projets...');
        
        // Appel API GET /api/projects
        const response = await fetch('/api/projects');
        
        // Gestion des erreurs HTTP
        if (!response.ok) {
            throw new Error(`Erreur HTTP ${response.status}`);
        }
        
        // Parsing de la réponse JSON
        projects = await response.json();
        
        console.log(`[KANBAN] ${projects.length} projet(s) chargé(s) ✅`, projects);
        
        // Affichage des projets dans le DOM
        displayProjects();
        
    } catch (error) {
        console.error('[KANBAN] Erreur chargement projets:', error);
        showError('Impossible de charger les projets. Veuillez rafraîchir la page.');
    }
}

// ===== 6. AFFICHAGE DES PROJETS (DOM MANIPULATION) =====
function displayProjects() {
    const projectsList = document.getElementById('projects-list');
    
    if (!projectsList) {
        console.error('[KANBAN] Élément #projects-list introuvable');
        return;
    }
    
    // Si aucun projet, afficher message vide
    if (projects.length === 0) {
        projectsList.innerHTML = `
            <p class="empty-state">
                Aucun projet pour le moment. Crée ton premier projet pour commencer !
            </p>
        `;
        return;
    }
    
    // Vider la liste
    projectsList.innerHTML = '';
    
    // Créer une card pour chaque projet
    projects.forEach(project => {
        const projectCard = createProjectCard(project);
        projectsList.appendChild(projectCard);
    });
}

// ===== 7. CRÉATION D'UNE CARD PROJET (createElement) =====
function createProjectCard(project) {
    // Créer l'élément div.project-card
    const card = document.createElement('div');
    card.className = 'project-card';
    card.dataset.projectId = project.id; // Stocke l'ID dans un data-attribute
    
    // Ajouter la classe "selected" si c'est le projet actuel
    if (project.id === currentProjectId) {
        card.classList.add('selected');
    }
    
    // Créer le contenu de la card
    const title = document.createElement('h3');
    title.className = 'project-card-title';
    title.textContent = project.name;
    
    const description = document.createElement('p');
    description.className = 'project-card-description';
    description.textContent = project.description || 'Pas de description';
    
    // Actions (bouton supprimer)
    const actions = document.createElement('div');
    actions.className = 'project-card-actions';
    
    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'btn-icon btn-icon--delete';
    deleteBtn.title = 'Supprimer le projet';
    deleteBtn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
    `;
    
    // Event : Supprimer projet
    deleteBtn.addEventListener('click', (e) => {
        e.stopPropagation(); // Empêche la sélection du projet lors du clic sur supprimer
        handleDeleteProject(project.id);
    });
    
    actions.appendChild(deleteBtn);
    
    // Event : Sélectionner projet (clic sur la card)
    card.addEventListener('click', () => {
        selectProject(project.id);
    });
    
    // Assembler la card
    card.appendChild(title);
    card.appendChild(description);
    card.appendChild(actions);
    
    return card;
}

// ===== 8. SÉLECTION D'UN PROJET =====
function selectProject(projectId) {
    console.log(`[KANBAN] Sélection du projet ID: ${projectId}`);
    
    currentProjectId = projectId;
    
    // Mettre à jour la classe "selected" sur les cards
    document.querySelectorAll('.project-card').forEach(card => {
        card.classList.remove('selected');
        if (parseInt(card.dataset.projectId) === projectId) {
            card.classList.add('selected');
        }
    });
    
    // Mettre à jour le nom du projet dans le bloc réduit
    const currentProjectName = document.getElementById('current-project-name');
    const project = projects.find(p => p.id === projectId);
    if (currentProjectName && project) {
        currentProjectName.textContent = project.name;
    }
    
    // Basculer vers le bloc Tâches
    switchToTasksBlock();
    
    // Charger les tâches de ce projet
    loadTasks(projectId);
}

// ===== 9. TOGGLE FOCUS : BASCULER VERS BLOC PROJETS =====
function switchToProjectsBlock() {
    console.log('[KANBAN] Basculer vers bloc Projets');
    
    const projectsBlock = document.getElementById('projects-block');
    const tasksBlock = document.getElementById('tasks-block');
    
    // Projets : FOCUS (déployé)
    projectsBlock.classList.remove('reduced');
    projectsBlock.querySelector('.block-content--focus').style.display = 'flex';
    projectsBlock.querySelector('.block-content--reduced').style.display = 'none';
    
    // Tâches : REDUCED (réduit)
    tasksBlock.classList.remove('focus');
    tasksBlock.classList.add('reduced');
    tasksBlock.querySelector('.block-content--focus').style.display = 'none';
    tasksBlock.querySelector('.block-content--reduced').style.display = 'flex';
}

// ===== 10. TOGGLE FOCUS : BASCULER VERS BLOC TÂCHES =====
function switchToTasksBlock() {
    console.log('[KANBAN] Basculer vers bloc Tâches');
    
    const projectsBlock = document.getElementById('projects-block');
    const tasksBlock = document.getElementById('tasks-block');
    
    // Projets : REDUCED (réduit)
    projectsBlock.classList.add('reduced');
    projectsBlock.querySelector('.block-content--focus').style.display = 'none';
    projectsBlock.querySelector('.block-content--reduced').style.display = 'flex';
    
    // Tâches : FOCUS (déployé)
    tasksBlock.classList.remove('reduced');
    tasksBlock.classList.add('focus');
    tasksBlock.querySelector('.block-content--focus').style.display = 'flex';
    tasksBlock.querySelector('.block-content--reduced').style.display = 'none';
}

// ===== 11. AFFICHER FORMULAIRE PROJET =====
function showProjectForm() {
    const formContainer = document.getElementById('project-form-container');
    if (formContainer) {
        formContainer.style.display = 'block';
        // Focus sur le champ titre
        document.getElementById('project-title')?.focus();
    }
}

// ===== 12. MASQUER FORMULAIRE PROJET =====
function hideProjectForm() {
    const formContainer = document.getElementById('project-form-container');
    const form = document.getElementById('project-form');
    
    if (formContainer) {
        formContainer.style.display = 'none';
    }
    
    if (form) {
        form.reset(); // Réinitialiser les champs
    }
}

// ===== 13. CRÉATION D'UN PROJET (FETCH POST) =====
async function handleCreateProject(event) {
    event.preventDefault(); // Empêche le rechargement de la page
    
    const titleInput = document.getElementById('project-title');
    const descriptionInput = document.getElementById('project-description');
    
    const projectData = {
        name: titleInput.value.trim(),
        description: descriptionInput.value.trim() || null
    };
    
    try {
        console.log('[KANBAN] Création du projet:', projectData);
        
        // Appel API POST /api/projects
        const response = await fetch('/api/projects', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(projectData)
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Erreur lors de la création');
        }
        
        const result = await response.json();
        console.log('[KANBAN] Projet créé avec succès ✅', result);
        
        // Masquer le formulaire
        hideProjectForm();
        
        // Recharger la liste des projets
        await loadProjects();
        
        // Message de succès
        showSuccess('Projet créé avec succès !');
        
    } catch (error) {
        console.error('[KANBAN] Erreur création projet:', error);
        showError(error.message || 'Impossible de créer le projet');
    }
}

// ===== 14. SUPPRESSION D'UN PROJET (FETCH DELETE) =====
async function handleDeleteProject(projectId) {
    // Demander confirmation
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce projet et toutes ses tâches ?')) {
        return;
    }
    
    try {
        console.log(`[KANBAN] Suppression du projet ID: ${projectId}`);
        
        // Appel API DELETE /api/projects/{id}
        const response = await fetch(`/api/projects/${projectId}`, {
            method: 'DELETE'
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Erreur lors de la suppression');
        }
        
        console.log('[KANBAN] Projet supprimé avec succès ✅');
        
        // Si c'était le projet sélectionné, réinitialiser
        if (currentProjectId === projectId) {
            currentProjectId = null;
            clearTasksDisplay();
        }
        
        // Recharger la liste des projets
        await loadProjects();
        
        // Message de succès
        showSuccess('Projet supprimé avec succès !');
        
    } catch (error) {
        console.error('[KANBAN] Erreur suppression projet:', error);
        showError(error.message || 'Impossible de supprimer le projet');
    }
}

// ===== 15. CHARGEMENT DES TÂCHES D'UN PROJET (FETCH API) =====
async function loadTasks(projectId) {
    try {
        console.log(`[KANBAN] Chargement des tâches du projet ID: ${projectId}`);
        
        // Appel API GET /api/projects/{id}/tasks
        const response = await fetch(`/api/projects/${projectId}/tasks`);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP ${response.status}`);
        }
        
        tasks = await response.json();
        
        console.log(`[KANBAN] ${tasks.length} tâche(s) chargée(s) ✅`, tasks);
        
        // Afficher les tâches dans les colonnes
        displayTasks();
        
        // Afficher le board Kanban
        document.getElementById('tasks-empty-state').style.display = 'none';
        document.getElementById('kanban-board').style.display = 'grid';
        
        // Mettre à jour le titre
        const project = projects.find(p => p.id === projectId);
        const tasksTitle = document.getElementById('tasks-title');
        if (tasksTitle && project) {
            tasksTitle.textContent = `Tâches de ${project.name}`;
        }
        
    } catch (error) {
        console.error('[KANBAN] Erreur chargement tâches:', error);
        showError('Impossible de charger les tâches.');
    }
}

// ===== 16. AFFICHAGE DES TÂCHES DANS LES 3 COLONNES =====
function displayTasks() {
    // Vider les 3 colonnes
    const todoContainer = document.getElementById('tasks-todo');
    const inProgressContainer = document.getElementById('tasks-in-progress');
    const doneContainer = document.getElementById('tasks-done');
    
    if (!todoContainer || !inProgressContainer || !doneContainer) {
        console.error('[KANBAN] Conteneurs de tâches introuvables');
        return;
    }
    
    todoContainer.innerHTML = '';
    inProgressContainer.innerHTML = '';
    doneContainer.innerHTML = '';
    
    // Trier les tâches par status et position
    const tasksByStatus = {
        todo: tasks.filter(t => t.status === 'todo').sort((a, b) => a.position - b.position),
        in_progress: tasks.filter(t => t.status === 'in_progress').sort((a, b) => a.position - b.position),
        done: tasks.filter(t => t.status === 'done').sort((a, b) => a.position - b.position)
    };
    
    // Afficher les tâches dans chaque colonne
    tasksByStatus.todo.forEach(task => {
        todoContainer.appendChild(createTaskCard(task));
    });
    
    tasksByStatus.in_progress.forEach(task => {
        inProgressContainer.appendChild(createTaskCard(task));
    });
    
    tasksByStatus.done.forEach(task => {
        doneContainer.appendChild(createTaskCard(task));
    });
    
    // Messages si colonnes vides
    if (tasksByStatus.todo.length === 0) {
        todoContainer.innerHTML = '<p class="empty-state">Aucune tâche</p>';
    }
    if (tasksByStatus.in_progress.length === 0) {
        inProgressContainer.innerHTML = '<p class="empty-state">Aucune tâche</p>';
    }
    if (tasksByStatus.done.length === 0) {
        doneContainer.innerHTML = '<p class="empty-state">Aucune tâche</p>';
    }
}

// ===== 17. CRÉATION D'UNE CARD TÂCHE (createElement) =====
function createTaskCard(task) {
    const card = document.createElement('div');
    card.className = 'task-card';
    card.dataset.taskId = task.id;
    
    // Header : Titre + Boutons actions
    const header = document.createElement('div');
    header.className = 'task-card-header';
    
    const title = document.createElement('h4');
    title.className = 'task-card-title';
    title.textContent = task.title;
    
    const actions = document.createElement('div');
    actions.className = 'task-card-actions';
    
    // Bouton Modifier (crayon) - TODO : À implémenter
    const editBtn = document.createElement('button');
    editBtn.className = 'task-btn task-btn--edit';
    editBtn.title = 'Modifier';
    editBtn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
    `;
    editBtn.addEventListener('click', () => {
        alert('Fonctionnalité "Modifier" à implémenter (bonus post-MVP)');
    });
    
    // BOUTON CONTEXTUEL selon le statut actuel
    let actionBtn = null;
    
    if (task.status === 'todo') {
        // Bouton "Démarrer" (passer en IN_PROGRESS)
        actionBtn = document.createElement('button');
        actionBtn.className = 'task-btn task-btn--start';
        actionBtn.title = 'Démarrer la tâche';
        actionBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        `;
        actionBtn.addEventListener('click', () => {
            handleUpdateTaskStatus(task.id, 'in_progress');
        });
    } 
    else if (task.status === 'in_progress') {
        // Bouton "Terminer" (passer en DONE)
        actionBtn = document.createElement('button');
        actionBtn.className = 'task-btn task-btn--complete';
        actionBtn.title = 'Marquer comme terminé';
        actionBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        `;
        actionBtn.addEventListener('click', () => {
            handleUpdateTaskStatus(task.id, 'done');
        });
    }
    // Si status = 'done' : pas de bouton d'action (tâche terminée)
    
    // Bouton Supprimer (poubelle) - Toujours présent
    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'task-btn task-btn--delete';
    deleteBtn.title = 'Supprimer';
    deleteBtn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
    `;
    deleteBtn.addEventListener('click', () => {
        handleDeleteTask(task.id);
    });
    
    // Assembler les boutons d'actions
    actions.appendChild(editBtn);
    if (actionBtn) {
        actions.appendChild(actionBtn); // Ajouter le bouton contextuel (Démarrer ou Terminer)
    }
    actions.appendChild(deleteBtn);
    
    header.appendChild(title);
    header.appendChild(actions);
    
    // Description (tronquée)
    const description = document.createElement('p');
    description.className = 'task-card-description';
    description.textContent = task.description || 'Pas de description';
    
    // Clic pour déplier (accordéon)
    description.addEventListener('click', () => {
        description.classList.toggle('expanded');
    });
    
    card.appendChild(header);
    card.appendChild(description);
    
    return card;
}

// ===== 18. AFFICHER FORMULAIRE TÂCHE =====
function showTaskForm() {
    const formContainer = document.getElementById('task-form-todo');
    if (formContainer) {
        formContainer.style.display = 'block';
        // Focus sur le champ titre
        formContainer.querySelector('input[name="title"]')?.focus();
    }
    
    // Ajouter événement submit au formulaire
    const form = formContainer.querySelector('.task-form');
    if (form && !form.dataset.listenerAdded) {
        form.addEventListener('submit', handleCreateTask);
        form.dataset.listenerAdded = 'true';
    }
    
    // Bouton Annuler
    const btnCancel = formContainer.querySelector('.btn-cancel-task');
    if (btnCancel && !btnCancel.dataset.listenerAdded) {
        btnCancel.addEventListener('click', hideTaskForm);
        btnCancel.dataset.listenerAdded = 'true';
    }
}

// ===== 19. MASQUER FORMULAIRE TÂCHE =====
function hideTaskForm() {
    const formContainer = document.getElementById('task-form-todo');
    const form = formContainer?.querySelector('.task-form');
    
    if (formContainer) {
        formContainer.style.display = 'none';
    }
    
    if (form) {
        form.reset();
    }
}

// ===== 20. CRÉATION D'UNE TÂCHE (FETCH POST) =====
async function handleCreateTask(event) {
    event.preventDefault();
    
    if (!currentProjectId) {
        showError('Aucun projet sélectionné');
        return;
    }
    
    const form = event.target;
    const titleInput = form.querySelector('input[name="title"]');
    const descriptionInput = form.querySelector('textarea[name="description"]');
    
    const taskData = {
        title: titleInput.value.trim(),
        description: descriptionInput.value.trim() || null,
        status: 'todo',
        position: tasks.filter(t => t.status === 'todo').length + 1 // Position = dernière position + 1
    };
    
    try {
        console.log('[KANBAN] Création de la tâche:', taskData);
        
        // Appel API POST /api/projects/{id}/tasks
        const response = await fetch(`/api/projects/${currentProjectId}/tasks`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(taskData)
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Erreur lors de la création');
        }
        
        const result = await response.json();
        console.log('[KANBAN] Tâche créée avec succès ✅', result);
        
        // Masquer le formulaire
        hideTaskForm();
        
        // Recharger les tâches
        await loadTasks(currentProjectId);
        
        // Message de succès
        showSuccess('Tâche créée avec succès !');
        
    } catch (error) {
        console.error('[KANBAN] Erreur création tâche:', error);
        showError(error.message || 'Impossible de créer la tâche');
    }
}

// ===== 21. MISE À JOUR DU STATUT D'UNE TÂCHE (FETCH PATCH) =====
async function handleUpdateTaskStatus(taskId, newStatus) {
    try {
        console.log(`[KANBAN] Changement status tâche ID:${taskId} vers ${newStatus}`);
        
        // Appel API PATCH /api/tasks/{id}/status
        const response = await fetch(`/api/tasks/${taskId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ status: newStatus })
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Erreur lors de la mise à jour');
        }
        
        console.log('[KANBAN] Statut mis à jour avec succès ✅');
        
        // Recharger les tâches pour mettre à jour l'affichage
        await loadTasks(currentProjectId);
        
        showSuccess('Tâche mise à jour !');
        
    } catch (error) {
        console.error('[KANBAN] Erreur mise à jour statut:', error);
        showError(error.message || 'Impossible de mettre à jour la tâche');
    }
}

// ===== 22. SUPPRESSION D'UNE TÂCHE (FETCH DELETE) =====
async function handleDeleteTask(taskId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
        return;
    }
    
    try {
        console.log(`[KANBAN] Suppression de la tâche ID: ${taskId}`);
        
        // Appel API DELETE /api/tasks/{id}
        const response = await fetch(`/api/tasks/${taskId}`, {
            method: 'DELETE'
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Erreur lors de la suppression');
        }
        
        console.log('[KANBAN] Tâche supprimée avec succès ✅');
        
        // Recharger les tâches
        await loadTasks(currentProjectId);
        
        showSuccess('Tâche supprimée avec succès !');
        
    } catch (error) {
        console.error('[KANBAN] Erreur suppression tâche:', error);
        showError(error.message || 'Impossible de supprimer la tâche');
    }
}

// ===== 23. VIDER L'AFFICHAGE DES TÂCHES =====
function clearTasksDisplay() {
    document.getElementById('tasks-empty-state').style.display = 'block';
    document.getElementById('kanban-board').style.display = 'none';
    document.getElementById('tasks-title').textContent = 'Tâches';
    
    document.getElementById('tasks-todo').innerHTML = '';
    document.getElementById('tasks-in-progress').innerHTML = '';
    document.getElementById('tasks-done').innerHTML = '';
}

// ===== 24. MESSAGES DE SUCCÈS =====
function showSuccess(message) {
    // TODO : Implémenter un système de toast/notifications
    console.log(`[KANBAN] ✅ ${message}`);
    // Pour le MVP, on utilise console.log
    // Post-MVP : Créer un composant toast comme dans la Veille
}

// ===== 25. MESSAGES D'ERREUR =====
function showError(message) {
    // TODO : Implémenter un système de toast/notifications
    console.error(`[KANBAN] ❌ ${message}`);
    alert(`Erreur : ${message}`);
    // Pour le MVP, on utilise alert
    // Post-MVP : Créer un composant toast comme dans la Veille
}