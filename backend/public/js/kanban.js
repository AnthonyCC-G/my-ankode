/* ============================================
   KANBAN MY-ANKODE - JavaScript API
   ============================================ */

// === CONFIGURATION ===
const API_BASE_URL = 'http://localhost:8000/api';
const PROJECT_ID = 10; // ID du projet "Site E-commerce"

// === ÉLÉMENTS DOM ===
const taskForm = document.getElementById('taskForm');
const taskInput = document.getElementById('nouvelletache');
const taskLists = {
    todo: document.getElementById('tasks-todo'),
    in_progress: document.getElementById('tasks-in-progress'),
    done: document.getElementById('tasks-done')
};

// === CHARGEMENT INITIAL ===
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Kanban chargé, récupération des tâches...');
    loadTasks();
    
    // Event listener formulaire
    taskForm.addEventListener('submit', handleAddTask);
});

// === FONCTION 1 : CHARGER LES TÂCHES ===
async function loadTasks() {
    try {
        const response = await fetch(`${API_BASE_URL}/projects/${PROJECT_ID}/tasks`);
        
        if (!response.ok) {
            throw new Error(`Erreur API: ${response.status}`);
        }
        
        const tasks = await response.json();
        console.log('✅ Tâches récupérées:', tasks);
        
        displayTasks(tasks);
        
    } catch (error) {
        console.error('❌ Erreur chargement tâches:', error);
        alert('Impossible de charger les tâches. Vérifiez que l\'API fonctionne.');
    }
}

// === FONCTION 2 : AFFICHER LES TÂCHES ===
function displayTasks(tasks) {
    // Vider les colonnes
    taskLists.todo.innerHTML = '';
    taskLists.in_progress.innerHTML = '';
    taskLists.done.innerHTML = '';
    
    // Trier par position
    tasks.sort((a, b) => a.position - b.position);
    
    // Afficher chaque tâche dans la bonne colonne
    tasks.forEach(task => {
        const taskCard = createTaskCard(task);
        
        // Ajouter dans la colonne correspondante
        if (taskLists[task.status]) {
            taskLists[task.status].appendChild(taskCard);
        }
    });
    
    console.log(`📊 Affichage: ${tasks.length} tâches réparties`);
}

// === FONCTION 3 : CRÉER UNE CARTE DE TÂCHE ===
function createTaskCard(task) {
    const li = document.createElement('li');
    li.className = 'task-card';
    li.dataset.taskId = task.id;
    
    // Titre de la tâche
    const title = document.createElement('div');
    title.className = 'task-title';
    title.textContent = task.title;
    
    // Description (si elle existe)
    if (task.description) {
        const desc = document.createElement('div');
        desc.className = 'task-description';
        desc.textContent = task.description;
        desc.style.fontSize = '12px';
        desc.style.color = '#666';
        desc.style.marginTop = '5px';
        li.appendChild(desc);
    }
    
    // Boutons de déplacement
    const actions = document.createElement('div');
    actions.className = 'task-actions';
    
    // Bouton "← Précédent"
    if (task.status !== 'todo') {
        const btnPrev = createMoveButton('←', task, getPreviousStatus(task.status));
        actions.appendChild(btnPrev);
    }
    
    // Bouton "Suivant →"
    if (task.status !== 'done') {
        const btnNext = createMoveButton('→', task, getNextStatus(task.status));
        actions.appendChild(btnNext);
    }
    
    li.appendChild(title);
    li.appendChild(actions);
    
    return li;
}

// === FONCTION 4 : CRÉER UN BOUTON DE DÉPLACEMENT ===
function createMoveButton(label, task, newStatus) {
    const button = document.createElement('button');
    button.className = 'btn-move';
    button.textContent = label;
    button.onclick = () => moveTask(task.id, newStatus);
    return button;
}

// === FONCTION 5 : DÉPLACER UNE TÂCHE ===
async function moveTask(taskId, newStatus) {
    try {
        console.log(`🔄 Déplacement tâche ${taskId} vers ${newStatus}...`);
        
        const response = await fetch(`${API_BASE_URL}/tasks/${taskId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ status: newStatus })
        });
        
        if (!response.ok) {
            throw new Error(`Erreur API: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('✅ Tâche déplacée:', result);
        
        // Recharger toutes les tâches pour mettre à jour l'affichage
        loadTasks();
        
    } catch (error) {
        console.error('❌ Erreur déplacement tâche:', error);
        alert('Impossible de déplacer la tâche.');
    }
}

// === FONCTION 6 : AJOUTER UNE NOUVELLE TÂCHE ===
async function handleAddTask(event) {
    event.preventDefault(); // Empêcher rechargement page
    
    const title = taskInput.value.trim();
    
    if (!title) {
        alert('Veuillez saisir un titre de tâche.');
        return;
    }
    
    try {
        console.log(`➕ Création tâche: "${title}"...`);
        
        const response = await fetch(`${API_BASE_URL}/projects/${PROJECT_ID}/tasks`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                title: title,
                description: '',
                status: 'todo',
                position: 999 // Position à la fin
            })
        });
        
        if (!response.ok) {
            throw new Error(`Erreur API: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('✅ Tâche créée:', result);
        
        // Vider le champ
        taskInput.value = '';
        
        // Recharger les tâches
        loadTasks();
        
    } catch (error) {
        console.error('❌ Erreur création tâche:', error);
        alert('Impossible de créer la tâche.');
    }
}

// === FONCTIONS UTILITAIRES ===

// Obtenir le statut précédent
function getPreviousStatus(currentStatus) {
    const statusOrder = ['todo', 'in_progress', 'done'];
    const index = statusOrder.indexOf(currentStatus);
    return index > 0 ? statusOrder[index - 1] : null;
}

// Obtenir le statut suivant
function getNextStatus(currentStatus) {
    const statusOrder = ['todo', 'in_progress', 'done'];
    const index = statusOrder.indexOf(currentStatus);
    return index < statusOrder.length - 1 ? statusOrder[index + 1] : null;
}
