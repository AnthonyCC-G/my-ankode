/**
 * KANBAN.JS - Gestion du Kanban (Projets + Tâches)
 */

// ===== 1. DÉTECTION MOBILE CÔTÉ CLIENT (Double sécurité avec le controller) =====
(function checkMobileDevice() {
    const isMobile = window.innerWidth < 768;

    if (isMobile) {
        localStorage.setItem('lastDesktopPage', '/kanban');
        window.location.href = '/desktop-only';
        return;
    }

    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth < 768) {
                localStorage.setItem('lastDesktopPage', '/kanban');
                window.location.href = '/desktop-only';
            }
        }, 250);
    });
})();

// ===== 2. VARIABLES GLOBALES =====
let currentProjectId = null; // ID du projet actuellement sélectionné
let projects = []; // Liste de tous les projets de l'utilisateur
let tasks = []; // Liste des tâches du projet sélectionné

// DRAG & DROP : Stocker la tâche en cours de déplacement // MVP
let draggedTask = null; // Tâche actuellement déplacée
let draggedElement = null; // Élément DOM de la carte
let sourceColumn = null; // Colonne d'origine (pour le rollback)

// ===== 3. INITIALISATION AU CHARGEMENT DU DOM =====
document.addEventListener('DOMContentLoaded', function() {
    // Charger les projets au démarrage
    loadProjects();
    
    // Initialiser les événements globaux
    initEventListeners();
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
}

// ===== 5. CHARGEMENT DES PROJETS (FETCH API) =====
async function loadProjects() {
    try {
        // Appel API GET /api/projects
        const response = await fetch('/api/projects');
        
        // Gestion des erreurs HTTP
        if (!response.ok) {
            throw new Error(`Erreur HTTP ${response.status}`);
        }
        
        // Parsing de la réponse JSON
        projects = await response.json();

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
    
    // ===== BOUTON MODIFIER (nouveau) =====
    const editBtn = document.createElement('button');
    editBtn.className = 'btn-icon btn-icon--edit';
    editBtn.title = 'Modifier le projet';
    editBtn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
    `;

    // Event : Modifier projet
    editBtn.addEventListener('click', (e) => {
        e.stopPropagation(); // Empêche la sélection du projet lors du clic sur modifier
        handleEditProject(project);
    });

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
    
    // Assembler les boutons
    actions.appendChild(editBtn);
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

    // ===== 13. CRÉATION D'UN PROJET (API POST avec CSRF) =====
    async function handleCreateProject(event) {
        event.preventDefault();
        
        const titleInput = document.getElementById('project-title');
        const descriptionInput = document.getElementById('project-description');
        
        const projectData = {
            name: titleInput.value.trim(),
            description: descriptionInput.value.trim() || null
        };
        
        try {
            // 🔒 Utilisation de API.post avec CSRF automatique
            const result = await API.post('/api/projects', projectData);

            hideProjectForm();
            await loadProjects();
            showSuccess('Projet créé avec succès !');
            
        } catch (error) {
            console.error('[KANBAN] Erreur création projet:', error);
            showError(error.message || 'Impossible de créer le projet');
        }
    }

    // ===== 14. SUPPRESSION D'UN PROJET (API DELETE avec CSRF) =====
    async function handleDeleteProject(projectId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce projet et toutes ses tâches ?')) {
            return;
        }
        
        try {
            // 🔒 Utilisation de API.delete avec CSRF automatique
            await API.delete(`/api/projects/${projectId}`);

            if (currentProjectId === projectId) {
                currentProjectId = null;
                clearTasksDisplay();
            }
            
            await loadProjects();
            showSuccess('Projet supprimé avec succès !');
            
        } catch (error) {
            console.error('[KANBAN] Erreur suppression projet:', error);
            showError(error.message || 'Impossible de supprimer le projet');
        }
    }

    // ===== 14bis. ÉDITION D'UN PROJET (TRANSFORMATION CARD → FORMULAIRE) =====
function handleEditProject(project) {
    // Récupérer la card du projet
    const projectCard = document.querySelector(`.project-card[data-project-id="${project.id}"]`);
    
    if (!projectCard) {
        console.error('[KANBAN] Card projet introuvable');
        return;
    }
    
    // Ajouter la classe d'édition (border orange)
    projectCard.classList.add('project-card--editing');
    
    // Sauvegarder le contenu original (pour pouvoir annuler)
    const originalContent = projectCard.innerHTML;
    
    // Créer le formulaire d'édition
    projectCard.innerHTML = `
        <form class="project-edit-form" data-project-id="${project.id}">
            <div class="form-group">
                <label for="edit-project-title-${project.id}">Titre du projet</label>
                <input 
                    type="text" 
                    id="edit-project-title-${project.id}" 
                    name="title" 
                    class="form-input form-input--edit" 
                    value="${project.name}" 
                    required
                >
            </div>
            <div class="form-group">
                <label for="edit-project-description-${project.id}">Description</label>
                <textarea 
                    id="edit-project-description-${project.id}" 
                    name="description" 
                    class="form-textarea form-textarea--edit" 
                    rows="3"
                >${project.description || ''}</textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-sm">Sauvegarder</button>
                <button type="button" class="btn btn-secondary btn-sm btn-cancel-edit">Annuler</button>
            </div>
        </form>
    `;
    
    // Event : Soumettre le formulaire
    const form = projectCard.querySelector('.project-edit-form');
    form.addEventListener('submit', (e) => {
        handleUpdateProject(e, project.id);
    });

    // Empêcher la propagation du clic sur le formulaire
    // Sinon, cliquer dans les inputs déclenche selectProject()
    form.addEventListener('click', (e) => {
        e.stopPropagation(); // Empêche le clic de remonter à la card
    });
    
    // Event : Annuler l'édition
    const cancelBtn = projectCard.querySelector('.btn-cancel-edit');
    cancelBtn.addEventListener('click', () => {
        // Restaurer le contenu original
        projectCard.innerHTML = originalContent;
        projectCard.classList.remove('project-card--editing');
        
        // Réattacher les event listeners (recréer la card complète)
        loadProjects();
    });
    
    // Focus sur le champ titre
    document.getElementById(`edit-project-title-${project.id}`)?.focus();
}

    // ===== 14ter. MISE À JOUR D'UN PROJET (API PUT avec CSRF) =====
    async function handleUpdateProject(event, projectId) {
        event.preventDefault();
        
        const form = event.target;
        const titleInput = form.querySelector('input[name="title"]');
        const descriptionInput = form.querySelector('textarea[name="description"]');
        
        const projectData = {
            name: titleInput.value.trim(),
            description: descriptionInput.value.trim() || null
        };
        
        try {
            // Utilisation de API.put avec CSRF automatique
            const result = await API.put(`/api/projects/${projectId}`, projectData);

            await loadProjects();
            showSuccess('Projet modifié avec succès !');
            
            if (currentProjectId === projectId) {
                const currentProjectName = document.getElementById('current-project-name');
                if (currentProjectName) {
                    currentProjectName.textContent = projectData.name;
                }
            }
            
        } catch (error) {
            console.error('[KANBAN] Erreur mise à jour projet:', error);
            showError(error.message || 'Impossible de mettre à jour le projet');
        }
    }

// ===== 15. CHARGEMENT DES TÂCHES D'UN PROJET (FETCH API) =====
async function loadTasks(projectId) {
    try {
        // Appel API GET /api/projects/{id}/tasks
        const response = await fetch(`/api/projects/${projectId}/tasks`);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP ${response.status}`);
        }

        tasks = await response.json();

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
    
    // DRAG & DROP : Transformer les colonnes en drop zones
    setupDropZone(todoContainer, 'todo');
    setupDropZone(inProgressContainer, 'in_progress');
    setupDropZone(doneContainer, 'done');
    
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
    
    // NOUVELLE STRUCTURE : Ajouter une poignée de drag
    const dragHandle = document.createElement('div');
    dragHandle.className = 'task-drag-handle';
    dragHandle.title = 'Glisser pour déplacer';
    dragHandle.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <circle cx="9" cy="5" r="1.5"/>
            <circle cx="9" cy="12" r="1.5"/>
            <circle cx="9" cy="19" r="1.5"/>
            <circle cx="15" cy="5" r="1.5"/>
            <circle cx="15" cy="12" r="1.5"/>
            <circle cx="15" cy="19" r="1.5"/>
        </svg>
    `;
    
    // DRAG & DROP : Le drag se fait UNIQUEMENT sur la poignée
    dragHandle.setAttribute('draggable', 'true');
    dragHandle.addEventListener('dragstart', (e) => handleDragStart(e, task));
    dragHandle.addEventListener('dragend', handleDragEnd);
    
    // Header : Titre + Boutons actions
    const header = document.createElement('div');
    header.className = 'task-card-header';
    
    const title = document.createElement('h4');
    title.className = 'task-card-title';
    title.textContent = task.title;
    
    const actions = document.createElement('div');
    actions.className = 'task-card-actions';
    
    // Bouton Modifier (crayon)
    const editBtn = document.createElement('button');
    editBtn.className = 'task-btn task-btn--edit';
    editBtn.title = 'Modifier';
    editBtn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
    `;
    editBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        handleEditTask(task);
    });
    
    // BOUTON CONTEXTUEL selon le statut actuel
    let actionBtn = null;
    
    if (task.status === 'todo') {
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
    
    // Bouton Supprimer (poubelle)
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
        actions.appendChild(actionBtn);
    }
    actions.appendChild(deleteBtn);
    
    header.appendChild(title);
    header.appendChild(actions);
    
    // Description (tronquée)
    const description = document.createElement('p');
    description.className = 'task-card-description';
    description.textContent = task.description || 'Pas de description';
    
    description.addEventListener('click', () => {
        description.classList.toggle('expanded');
    });
    
    // ASSEMBLAGE FINAL : Poignée + Header + Description
    card.appendChild(dragHandle);
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

    // ===== 20. CRÉATION D'UNE TÂCHE (API POST avec CSRF) =====
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
            position: tasks.filter(t => t.status === 'todo').length + 1
        };
        
        try {
            // 🔒 Utilisation de API.post avec CSRF automatique
            const result = await API.post(`/api/projects/${currentProjectId}/tasks`, taskData);

            hideTaskForm();
            await loadTasks(currentProjectId);
            showSuccess('Tâche créée avec succès !');
            
        } catch (error) {
            console.error('[KANBAN] Erreur création tâche:', error);
            showError(error.message || 'Impossible de créer la tâche');
        }
    }

    // ===== 21. MISE À JOUR DU STATUT D'UNE TÂCHE (API PATCH avec CSRF) =====
    async function handleUpdateTaskStatus(taskId, newStatus) {
        try {
            // 🔒 Utilisation de API.patch avec CSRF automatique
            await API.patch(`/api/tasks/${taskId}/status`, { status: newStatus });

            await loadTasks(currentProjectId);
            showSuccess('Tâche mise à jour !');
            
        } catch (error) {
            console.error('[KANBAN] Erreur mise à jour statut:', error);
            showError(error.message || 'Impossible de mettre à jour la tâche');
        }
    }

    // ===== 22. SUPPRESSION D'UNE TÂCHE (API DELETE avec CSRF) =====
    async function handleDeleteTask(taskId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
            return;
        }
        
        try {
            //  Utilisation de API.delete avec CSRF automatique
            await API.delete(`/api/tasks/${taskId}`);

            await loadTasks(currentProjectId);
            showSuccess('Tâche supprimée avec succès !');
            
        } catch (error) {
            console.error('[KANBAN] Erreur suppression tâche:', error);
            showError(error.message || 'Impossible de supprimer la tâche');
        }
    }

// ===== 22bis. ÉDITION D'UNE TÂCHE (TRANSFORMATION CARD → FORMULAIRE) =====
function handleEditTask(task) {
    // Récupérer la card de la tâche
    const taskCard = document.querySelector(`.task-card[data-task-id="${task.id}"]`);
    
    if (!taskCard) {
        console.error('[KANBAN] Card tâche introuvable');
        return;
    }
    
    // Ajouter la classe d'édition (border orange)
    taskCard.classList.add('task-card--editing');
    
    // Sauvegarder le contenu original (pour pouvoir annuler)
    const originalContent = taskCard.innerHTML;
    
    // Créer le formulaire d'édition
    taskCard.innerHTML = `
        <form class="task-edit-form" data-task-id="${task.id}">
            <div class="form-group">
                <label for="edit-task-title-${task.id}">Titre</label>
                <input 
                    type="text" 
                    id="edit-task-title-${task.id}" 
                    name="title" 
                    class="form-input form-input--edit-task" 
                    value="${task.title}" 
                    required
                >
            </div>
            <div class="form-group">
                <label for="edit-task-description-${task.id}">Description</label>
                <textarea 
                    id="edit-task-description-${task.id}" 
                    name="description" 
                    class="form-textarea form-textarea--edit-task" 
                    rows="3"
                >${task.description || ''}</textarea>
            </div>
            <div class="form-actions form-actions--inline">
                <button type="submit" class="btn btn-primary btn-sm">Sauvegarder</button>
                <button type="button" class="btn btn-secondary btn-sm btn-cancel-edit-task">Annuler</button>
            </div>
        </form>
    `;
    
    // Event : Soumettre le formulaire
    const form = taskCard.querySelector('.task-edit-form');
    form.addEventListener('submit', (e) => {
        handleUpdateTask(e, task.id);
    });
    
    // Event : Annuler l'édition
    const cancelBtn = taskCard.querySelector('.btn-cancel-edit-task');
    cancelBtn.addEventListener('click', () => {
        // Restaurer le contenu original
        taskCard.innerHTML = originalContent;
        taskCard.classList.remove('task-card--editing');
        
        // Recharger les tâches pour réattacher les event listeners
        loadTasks(currentProjectId);
    });
    
    // Empêcher la propagation du clic sur le formulaire
    form.addEventListener('click', (e) => {
        e.stopPropagation();
    });
    
    // Focus sur le champ titre
    document.getElementById(`edit-task-title-${task.id}`)?.focus();
}

    // ===== 22ter. MISE À JOUR D'UNE TÂCHE (API PUT avec CSRF) =====
    async function handleUpdateTask(event, taskId) {
        event.preventDefault();
        
        const form = event.target;
        const titleInput = form.querySelector('input[name="title"]');
        const descriptionInput = form.querySelector('textarea[name="description"]');
        
        const taskData = {
            title: titleInput.value.trim(),
            description: descriptionInput.value.trim() || null
        };
        
        try {
            // Utilisation de API.put avec CSRF automatique
            const result = await API.put(`/api/tasks/${taskId}`, taskData);

            await loadTasks(currentProjectId);
            showSuccess('Tâche modifiée avec succès !');
            
        } catch (error) {
            console.error('[KANBAN] Erreur mise à jour tâche:', error);
            showError(error.message || 'Impossible de mettre à jour la tâche');
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

// ===== 26. DRAG & DROP : DÉBUT DU DÉPLACEMENT =====
function handleDragStart(event, task) {
    // Recevoir directement la tâche en paramètre
    draggedTask = task;
    draggedElement = event.currentTarget.closest('.task-card');
    sourceColumn = draggedElement.closest('.tasks-container').id;
    
    //console.log('[DRAG START] Tâche:', draggedTask.title);
    
    // Style visuel
    draggedElement.style.opacity = '0.5';
    
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/html', draggedElement.innerHTML);
}

// ===== 27. DRAG & DROP : FIN DU DÉPLACEMENT =====
function handleDragEnd(event) {
    // Restaurer l'opacité normale
    draggedElement.style.opacity = '1';
    
    // Retirer les styles de survol sur toutes les colonnes
    document.querySelectorAll('.tasks-container').forEach(container => {
        container.classList.remove('drag-over');
    });
}

// ===== 28. DRAG & DROP : CONFIGURATION D'UNE DROP ZONE =====
function setupDropZone(container, targetStatus) {
    // Événement : Survol de la zone
    container.addEventListener('dragover', (event) => {
        event.preventDefault(); // autoriser le drop
        event.dataTransfer.dropEffect = 'move';
        
        // Style visuel : highlight la colonne survolée
        container.classList.add('drag-over');
    });
    
    // Événement : Sortie de la zone
    container.addEventListener('dragleave', () => {
        container.classList.remove('drag-over');
    });
    
    // Événement : Dépose de la carte
    container.addEventListener('drop', async (event) => {
        event.preventDefault();
        container.classList.remove('drag-over');
        
        // Si on dépose dans la même colonne : rien à faire
        if (sourceColumn === container.id) {
            return;
        }
        
        // Appeler la fonction de mise à jour
        await handleDropTask(draggedTask, targetStatus, container);
    });
}

// ===== 29. DRAG & DROP : GESTION DU DROP (OPTIMISTIC UI + API) =====
async function handleDropTask(task, newStatus, targetContainer) {
    // Vérifier que la tâche existe
    if (!task) {
        console.error('[KANBAN] Tâche introuvable dans le tableau tasks');
        showError('Erreur : tâche introuvable');
        return;
    }
    
    const oldStatus = task.status;
    
    // Si on dépose dans la même colonne : rien à faire
    if (oldStatus === newStatus) {
        return;
    }
    
    // 1 - PRINCIPE : OPTIMISTIC UI : Déplacer visuellement IMMÉDIATEMENT
    task.status = newStatus; // Mettre à jour l'objet local
    displayTasks(); // Rafraîchir l'affichage
    
    try {
        // 2 - APPEL API : Persister le changement en base de données
        await API.patch(`/api/tasks/${task.id}/status`, { status: newStatus });
        
        // 3 - SUCCÈS : Afficher un message
        showSuccess(`Tâche déplacée vers "${getStatusLabel(newStatus)}" !`);
        
    } catch (error) {
        // 4 - ERREUR : ROLLBACK (annuler le déplacement visuel)
        console.error('[KANBAN] Erreur drag & drop:', error);
        
        task.status = oldStatus; // Restaurer l'ancien statut
        displayTasks(); // Rafraîchir pour annuler le déplacement
        
        showError('Impossible de déplacer la tâche. Connexion perdue ?');
    }
}

// ===== 30. HELPER : Convertir le statut en libellé français =====
function getStatusLabel(status) {
    const labels = {
        'todo': 'À faire',
        'in_progress': 'En cours',
        'done': 'Terminé'
    };
    return labels[status] || status;
}