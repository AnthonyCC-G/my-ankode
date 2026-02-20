/**
 * Module de gestion des appels API avec protection CSRF
 * Centralise tous les appels API de l'application
 * avec gestion automatique du token CSRF pour la sécurité
 */

const API = {
    /**
     * Recupere le token CSRF - Double source (defense en profondeur)
     * Priorite 1 : Hidden input dans le formulaire (methode classique)
     * Priorite 2 : Meta tag dans le <head> (methode API/SPA)
     * @returns {string|null} Le token CSRF ou null si absent
     */
    getCsrfToken() {
        // Source 1 : Hidden input dans le formulaire actif
        const hiddenInput = document.querySelector('input[name="_csrf_token"]');
        if (hiddenInput && hiddenInput.value) {
            return hiddenInput.value;
        }
        
        // Source 2 : Meta tag (fallback pour les pages sans formulaire)
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag && metaTag.content) {
            return metaTag.content;
        }
        
        console.error('[API] Token CSRF manquant dans le DOM');
        console.error('[API] Verifier la presence du hidden input ou du meta tag CSRF');
        return null;
    },

    /**
     * Génère les headers par défaut avec CSRF
     * @returns {Object} Headers HTTP
     */
    getHeaders() {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        const csrfToken = this.getCsrfToken();
        if (csrfToken) {
            headers['X-CSRF-Token'] = csrfToken;
        }

        return headers;
    },

    /**
     * Gère les erreurs HTTP et les transforme en erreurs exploitables
     * @param {Response} response - Réponse fetch
     * @returns {Promise<Object>} Données JSON
     * @throws {Error} Si la réponse n'est pas OK
     */
    async handleResponse(response) {
        // Tenter de parser la réponse JSON
        const data = await response.json().catch(() => ({}));
        
        if (!response.ok) {
            // Construire un message d'erreur clair
            const errorMessage = data.error || data.message || `Erreur HTTP ${response.status}`;
            throw new Error(errorMessage);
        }
        
        return data;
    },

    /**
     * Requête GET (lecture seule, pas de CSRF nécessaire)
     * @param {string} url - URL de la ressource
     * @returns {Promise<Object>} Données JSON
     */
    async get(url) {
        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            return await this.handleResponse(response);
        } catch (error) {
            console.error(`[API GET] ${url}`, error);
            throw error;
        }
    },

    /**
     * Requête POST (création) avec protection CSRF
     * @param {string} url - URL de la ressource
     * @param {Object} data - Données à envoyer
     * @returns {Promise<Object>} Données JSON
     */
    async post(url, data) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify(data)
            });
            return await this.handleResponse(response);
        } catch (error) {
            console.error(`[API POST] ${url}`, error);
            throw error;
        }
    },

    /**
     * Requête PUT (mise à jour complète) avec protection CSRF
     * @param {string} url - URL de la ressource
     * @param {Object} data - Données à envoyer
     * @returns {Promise<Object>} Données JSON
     */
    async put(url, data) {
        try {
            const response = await fetch(url, {
                method: 'PUT',
                headers: this.getHeaders(),
                body: JSON.stringify(data)
            });
            return await this.handleResponse(response);
        } catch (error) {
            console.error(`[API PUT] ${url}`, error);
            throw error;
        }
    },

    /**
     * Requête PATCH (mise à jour partielle) avec protection CSRF
     * @param {string} url - URL de la ressource
     * @param {Object} data - Données à envoyer (optionnel)
     * @returns {Promise<Object>} Données JSON
     */
    async patch(url, data = {}) {
        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: this.getHeaders(),
                body: JSON.stringify(data)
            });
            return await this.handleResponse(response);
        } catch (error) {
            console.error(`[API PATCH] ${url}`, error);
            throw error;
        }
    },

    /**
     * Requête DELETE (suppression) avec protection CSRF
     * @param {string} url - URL de la ressource
     * @returns {Promise<Object>} Données JSON
     */
    async delete(url) {
        try {
            const response = await fetch(url, {
                method: 'DELETE',
                headers: this.getHeaders()
            });
            return await this.handleResponse(response);
        } catch (error) {
            console.error(`[API DELETE] ${url}`, error);
            throw error;
        }
    }
};

// Export pour utilisation dans les autres modules (si besoin)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = API;
}