/* ========================================
   ADMIN DASHBOARD - CHARGEMENT DES STATISTIQUES
   ======================================== */

// ============================================================
// SECTION 0 : DÉTECTION MOBILE (Protection Desktop Only)
// ============================================================

(function checkMobileDevice() {
    const isMobile = window.innerWidth < 768;

    if (isMobile) {
        localStorage.setItem('lastDesktopPage', '/admin/dashboard');
        window.location.href = '/desktop-only';
        return;
    }

    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth < 768) {
                localStorage.setItem('lastDesktopPage', '/admin/dashboard');
                window.location.href = '/desktop-only';
            }
        }, 250);
    });
})();

// ============================================================
// SECTION 1 : CHARGEMENT DES STATISTIQUES AU DEMARRAGE
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    loadAllStats();
});

// ============================================================
// SECTION 2 : FONCTION PRINCIPALE DE CHARGEMENT
// ============================================================

/**
 * Charge toutes les statistiques en parallele
 */
function loadAllStats() {
    // Lancer tous les appels API en parallèle
    Promise.all([
        fetchStat('/admin/dashboard/api/stats/users', 'total-users'),
        fetchStat('/admin/dashboard/api/stats/projects', 'total-projects'),
        fetchStat('/admin/dashboard/api/stats/tasks', 'total-tasks'),
        fetchStat('/admin/dashboard/api/stats/articles', 'total-articles'),
        fetchStat('/admin/dashboard/api/stats/snippets', 'total-snippets'),
        fetchStat('/admin/dashboard/api/stats/competences', 'total-competences')
    ])
    .then(() => {
        console.log('Toutes les statistiques chargées avec succès');
    })
    .catch((error) => {
        console.error('Erreur lors du chargement des statistiques:', error);
        showErrorMessage('Impossible de charger certaines statistiques');
    });
}

// ============================================================
// SECTION 3 : APPEL API GENERIQUE
// ============================================================

/**
 * Recupere une statistique depuis l'API et met a jour l'element HTML
 * @param {string} url - URL de l'endpoint API
 * @param {string} elementId - ID de l'element HTML a mettre a jour
 */
async function fetchStat(url, elementId) {
    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`Erreur HTTP : ${response.status}`);
        }

        const data = await response.json();
        
        // Extraire la valeur (premiere cle de l'objet)
        const statValue = Object.values(data)[0];
        
        // Mettre a jour l'element HTML
        updateStatElement(elementId, statValue);
        
    } catch (error) {
        console.error(`Erreur pour ${url}:`, error);
        updateStatElement(elementId, 'Erreur', true);
    }
}

// ============================================================
// SECTION 4 : MISE A JOUR DE L'AFFICHAGE
// ============================================================

/**
 * Met a jour un element de statistique avec animation
 * @param {string} elementId - ID de l'element
 * @param {number|string} value - Valeur a afficher
 * @param {boolean} isError - Si c'est une erreur
 */
function updateStatElement(elementId, value, isError = false) {
    const element = document.getElementById(elementId);
    
    if (!element) {
        console.error(`Element ${elementId} introuvable`);
        return;
    }

    // Remplacer le spinner par la valeur avec animation
    element.innerHTML = isError 
        ? '<span class="text-danger">❌</span>' 
        : `<span class="fade-in">${value}</span>`;
    
    // Ajouter l'animation CSS
    if (!isError) {
        element.querySelector('span').style.animation = 'fadeIn 0.5s ease-in';
    }
}

// ============================================================
// SECTION 5 : GESTION DES MESSAGES
// ============================================================

/**
 * Affiche un message d'erreur a l'utilisateur
 * @param {string} message - Message a afficher
 */
function showErrorMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-warning alert-dismissible fade show';
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Inserer au debut du container
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
}

// ============================================================
// SECTION 6 : ANIMATION CSS INLINE
// ============================================================

// Ajout de l'animation fade-in
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.8); }
        to { opacity: 1; transform: scale(1); }
    }
`;
document.head.appendChild(style);