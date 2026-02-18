/**
 * delete-account.js
 * Gestion de la suppression de compte utilisateur
 */

// Attendre que le DOM soit charge
document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================
    // 1. SELECTION DES ELEMENTS
    // ========================================
    
    const btnDeleteAccount = document.getElementById('btn-delete-account');
    const modal = document.getElementById('delete-account-modal');
    const btnClose = modal?.querySelector('.delete-modal__close');
    const btnCancel = modal?.querySelector('.delete-modal__cancel');
    const overlay = modal?.querySelector('.delete-modal__overlay');
    const form = document.getElementById('delete-account-form');
    const errorMessage = document.getElementById('delete-error-message');
    const submitBtn = document.getElementById('delete-submit-btn');

    // Si un element manque, arreter le script
    if (!btnDeleteAccount || !modal || !form) {
        console.error(' Elements manquants pour la suppression de compte');
        return;
    }
    
    // ========================================
    // 2. FONCTIONS D'OUVERTURE/FERMETURE
    // ========================================
    
    /**
     * Ouvrir le modal
     */
    function openModal() {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Bloque le scroll de la page
    }
    
    /**
     * Fermer le modal
     */
    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = ''; // Reactive le scroll
        
        // Reinitialiser le formulaire
        form.reset();
        hideError();
    }
    
    /**
     * Afficher un message d'erreur
     */
    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.style.display = 'block';
    }
    
    /**
     * Cacher le message d'erreur
     */
    function hideError() {
        errorMessage.style.display = 'none';
        errorMessage.textContent = '';
    }
    
    // ========================================
    // 3. EVENT LISTENERS D'OUVERTURE/FERMETURE
    // ========================================
    
    // Ouvrir le modal au clic sur le bouton rouge
    btnDeleteAccount.addEventListener('click', function(e) {
        e.preventDefault();
        openModal();
    });
    
    // Fermer le modal avec la croix
    if (btnClose) {
        btnClose.addEventListener('click', closeModal);
    }
    
    // Fermer le modal avec le bouton "Annuler"
    if (btnCancel) {
        btnCancel.addEventListener('click', closeModal);
    }
    
    // Fermer le modal en cliquant sur l'overlay
    if (overlay) {
        overlay.addEventListener('click', closeModal);
    }
    
    // Fermer le modal avec la touche Echap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
    
    // ========================================
    // 4. SOUMISSION DU FORMULAIRE
    // ========================================
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault(); // Empeche le rechargement de la page

        // Recuperer le mot de passe
        const password = document.getElementById('delete-password').value;
        const checkbox = document.getElementById('delete-confirm-checkbox').checked;
        
        // Validation cote client
        if (!password) {
            showError('Le mot de passe est obligatoire');
            return;
        }
        
        if (!checkbox) {
            showError('Vous devez confirmer que vous comprenez les consequences');
            return;
        }
        
        // Desactiver le bouton pendant la requete
        submitBtn.disabled = true;
        submitBtn.textContent = 'Suppression en cours...';
        hideError();
        
        try {
            // ========================================
            // 5. REQUETE DELETE VERS L'API
            // ========================================

            const response = await fetch('/api/user/delete', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    password: password
                })
            });

            const data = await response.json();

            // ========================================
            // 6. TRAITEMENT DE LA REPONSE
            // ========================================
            
            if (response.ok && data.success) {
                // Succes : compte supprime

                // Afficher un message de confirmation
                alert('Votre compte a ete supprime avec succes. Vous allez etre redirige vers la page d\'authentification.');
                
                // Redirection vers /auth
                window.location.href = '/auth';
                
            } else {
                // Erreur retournee par l'API
                console.error('Erreur API:', data.error);
                showError(data.error || 'Une erreur est survenue lors de la suppression');
                
                // Reactiver le bouton
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirmer la suppression';
            }
            
        } catch (error) {
            // Erreur reseau ou autre
            console.error('Erreur reseau:', error);
            showError('Erreur de connexion. Veuillez reessayer.');
            
            // Reactiver le bouton
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirmer la suppression';
        }
    });
});