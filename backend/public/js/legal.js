// ============================================
// LEGAL PAGE - TABS & FAQ INTERACTIVITY
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    initTabs();
    initFAQ();
});

// ============================================
// TABS MANAGEMENT
// ============================================

function initTabs() {
    // Récupère tous les boutons de tabs
    const tabButtons = document.querySelectorAll('.tab-button');
    
    // Ajoute un écouteur de clic sur chaque bouton
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Récupère l'ID du tab à afficher (stocké dans data-tab)
            const targetTab = this.getAttribute('data-tab');
            
            // Switch vers le tab ciblé
            switchTab(targetTab);
        });
    });
}

function switchTab(tabId) {
    // 1. Désactive tous les boutons de tabs
    const allButtons = document.querySelectorAll('.tab-button');
    allButtons.forEach(btn => btn.classList.remove('active'));
    
    // 2. Cache tous les contenus de tabs
    const allContents = document.querySelectorAll('.tab-content');
    allContents.forEach(content => content.classList.remove('active'));
    
    // 3. Active le bouton cliqué
    const activeButton = document.querySelector(`[data-tab="${tabId}"]`);
    if (activeButton) {
        activeButton.classList.add('active');
    }
    
    // 4. Affiche le contenu correspondant
    const activeContent = document.getElementById(tabId);
    if (activeContent) {
        activeContent.classList.add('active');
    }
}

// ============================================
// FAQ ACCORDEONS MANAGEMENT
// ============================================

function initFAQ() {
    // Récupère toutes les questions de la FAQ
    const faqQuestions = document.querySelectorAll('.faq-question');
    
    // Ajoute un écouteur de clic sur chaque question
    faqQuestions.forEach(question => {
        question.addEventListener('click', function() {
            // Récupère l'item parent (contient question + réponse)
            const faqItem = this.parentElement;
            
            // Toggle l'état ouvert/fermé
            toggleFAQ(faqItem);
        });
    });
}

function toggleFAQ(faqItem) {
    // Vérifie si l'accordéon est déjà ouvert
    const isActive = faqItem.classList.contains('active');
    
    // Fermer les autres accordéons avant d'ouvrir celui-ci
    // (un seul accordéon ouvert à la fois)
    const allFaqItems = document.querySelectorAll('.faq-item');
    allFaqItems.forEach(item => item.classList.remove('active'));
    
    // Si l'item n'était pas déjà ouvert, on l'ouvre
    if (!isActive) {
        faqItem.classList.add('active');
    }
    

}

// ============================================
// BONUS : Support des ancres URL (pour liens directs vers FAQ)
// ============================================

// Si l'URL contient #faq, switch automatiquement vers le tab FAQ
if (window.location.hash === '#faq') {
    switchTab('faq');
}