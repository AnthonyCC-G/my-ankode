# Features

Documentation sur les features prévues pour le projet My Ankode.

[← Retour au README principal](../README.md)

---

# Fonctionnalités - MY-ANKODE

## Vue d'ensemble

MY-ANKODE est une **application de productivité pour développeurs juniors** organisée autour de **4 modules complémentaires** qui couvrent l'ensemble du cycle d'apprentissage et de développement :

1. **Veille** : Agrégation RSS pour rester à jour 
2. **Kanban** : Gestion de projets et tâches avec drag & drop
3. **Snippets** : Bibliothèque de code réutilisable avec coloration syntaxique
4. **Compétences** : Suivi de progression des compétences DWWM avec auto-évaluation

Chaque module est conçu pour être **autonome** tout en s'intégrant naturellement dans un workflow de développeur junior.

---

## 1. Module Veille (Technologie RSS)

### Objectif

Permettre aux développeurs juniors de **rester à jour** avec les tendances technologiques sans se disperser, en agrégeant du contenu de qualité provenant de sources fiables.

### Sources RSS intégrées

L'application agrège actuellement **8 sources RSS** de qualité :

**Francophones (actualités tech généralistes)** :
- **Korben.info** : Cybersécurité, logiciels, innovations
- **Numerama** : Culture numérique, sciences, tech
- **Frandroid** : Tech grand public, smartphones, objets connectés

**Anglophones (développement et design)** :
- **Dev.to** : Articles communautaires par et pour les développeurs
- **FreeCodeCamp** : Tutoriels approfondis, apprentissage du code
- **CSS-Tricks** : Astuces CSS, frontend, web design
- **Smashing Magazine** : UX/UI, performances web, accessibilité
- **SitePoint** : Tutoriels web, frameworks, best practices

**Mise à jour des flux** : Commande manuelle `php bin/console app:fetch-rss [URL] [SOURCE]` ou via scripts automatisés (`load-demo-articles.sh`)

**Feature future (post-certification)** : Configuration personnalisée par l'utilisateur
- Interface d'ajout/suppression de flux RSS personnalisés
- Préférences de sources (masquer/afficher certains flux)
- Import de fichiers OPML (standard RSS)

### Fonctionnalités principales

#### 📰 Affichage des articles
- **Liste chronologique** : Articles triés par date de publication (plus récents en premier)
- **Pagination** : 20 articles par page pour une navigation fluide
- **Métadonnées** : Titre, description, source, date de publication
- **Lien externe** : Accès direct à l'article complet sur le site source

#### 🔍 Recherche et filtres
- **Recherche par mots-clés** : Recherche dans le titre, la description et la source
  - Regex insensible à la casse (MongoDB)
  - Résultats limités à 50 articles pour optimiser les performances
- **Filtrage par source** : Sélection d'une source spécifique (Korben.info, Dev.to, ou toutes)
- **Filtrage par statut** : Tous / Lu / Non lu (toggle)
- **Tri chronologique** : Récents d'abord (DESC) ou Anciens d'abord (ASC)

#### ⭐ Gestion personnelle
- **Marquer comme lu** : Suivi des articles déjà consultés (icône œil)
  - État persisté par utilisateur dans MongoDB (`readBy` array)
  - Toggle : clic pour marquer lu/non lu
- **Favoris** : Sauvegarde des articles importants
  - État persisté par utilisateur dans MongoDB (`favoritedBy` array)
  - Liste dédiée des favoris accessible via API

#### 🔄 Mise à jour du flux
- **Commande Symfony** : `php bin/console app:fetch-rss`
  - Exécution manuelle ou via cron job
  - Récupération automatique des derniers articles
  - Déduplication (pas de doublons si article déjà en base)

### Architecture technique

**Base de données** : MongoDB (collection `articles`)

**Raisons du choix MongoDB** :
- Structure de données flexible (champs optionnels selon les sources RSS)
- Performance élevée pour lecture/écriture de flux d'articles
- Isolation des données externes (contenu potentiellement non fiable)

**API REST** :
- `GET /api/articles` : Liste paginée (20/page)
- `GET /api/articles/search?q=keyword` : Recherche
- `GET /api/articles/sources` : Liste des sources disponibles
- `GET /api/articles/favorites` : Articles favoris de l'utilisateur
- `PATCH /api/articles/{id}/mark-read` : Toggle lu/non lu (CSRF)
- `POST /api/articles/{id}/favorite` : Ajouter aux favoris (CSRF)
- `DELETE /api/articles/{id}/favorite` : Retirer des favoris (CSRF)

### Interface utilisateur

**Layout desktop** :
- Grille 2x2 responsive
- Bloc recherche (top-left) avec accordion "Features à venir"
- Bloc favoris (top-right) avec compteur
- Bloc articles (bottom, full-width) avec cartes cliquables

**Responsive mobile** :
- Layout vertical simplifié
- Recherche + filtres en accordéon pour économiser l'espace
- Articles en liste verticale (1 colonne)

**Interactions** :
- Clic sur carte article → Ouvre l'article source dans un nouvel onglet
- Clic sur icône œil → Toggle lu/non lu (sans quitter la page)
- Clic sur icône étoile → Toggle favori (sans quitter la page)
- Feedback visuel immédiat (changement de couleur des icônes)

---

## 2. Module Kanban (Gestion de projets)

### Objectif

Offrir une **vue d'ensemble claire** des projets en cours et faciliter la **priorisation des tâches** via un système Kanban intuitif avec drag & drop.

### Fonctionnalités principales

#### 📁 Gestion des projets
- **Création de projet** : Nom + description optionnelle
- **Liste des projets** : Vue chronologique (plus récents en premier)
- **Édition** : Modification du nom et de la description
- **Suppression** : Avec modal de confirmation (protection contre suppressions accidentelles)
- **Ownership** : Chaque utilisateur voit uniquement ses propres projets

#### ✅ Gestion des tâches (Kanban)
- **Statuts de tâche** : 3 colonnes fixes
  - **To Do** : Tâches à faire
  - **In Progress** : Tâches en cours
  - **Done** : Tâches terminées
- **Création de tâche** : Titre + description optionnelle + projet associé
- **Drag & Drop** : Déplacement fluide entre colonnes
  - Mise à jour automatique du statut en base de données
  - Feedback visuel pendant le drag (opacité, survol de colonne)
- **Édition inline** : Modification rapide du titre/description
- **Suppression** : Modal de confirmation

#### 🎯 Filtrage et organisation
- **Filtre par projet** : Affiche uniquement les tâches d'un projet spécifique
- **Vue "Tous les projets"** : Affiche toutes les tâches (sans filtre)


### Architecture technique

**Base de données** : PostgreSQL (tables `project` et `task`)

**Raisons du choix PostgreSQL** :
- Relations fortes entre projets et tâches (foreign key `project_id`)
- Intégrité référentielle (cascade delete si projet supprimé)
- Transactions ACID pour opérations critiques (changement de statut)

**Entités Doctrine** :
- `Project` : id, name, description, owner (User), createdAt
- `Task` : id, title, description, status (enum), project (relation), owner (User), createdAt

**API REST** :
- `GET /api/projects` : Liste des projets de l'utilisateur
- `POST /api/projects` : Créer un projet (CSRF)
- `PUT /api/projects/{id}` : Modifier un projet (CSRF + Voter)
- `DELETE /api/projects/{id}` : Supprimer un projet (CSRF + Voter)
- `GET /api/tasks` : Liste des tâches de l'utilisateur
- `POST /api/tasks` : Créer une tâche (CSRF)
- `PATCH /api/tasks/{id}` : Modifier une tâche (CSRF + Voter)
- `DELETE /api/tasks/{id}` : Supprimer une tâche (CSRF + Voter)

**Sécurité** :
- `ResourceVoter` : Vérifie l'ownership avant toute action (VIEW, EDIT, DELETE)
- CSRF protection sur toutes les routes POST/PUT/PATCH/DELETE

### Interface utilisateur

**Layout desktop** :
- **Sidebar gauche** : Liste des projets + bouton "Créer projet"
  - Projet actif surligné (cyan)
- **Zone principale** : Board Kanban 3 colonnes
  - Drag & Drop entre colonnes (bibliothèque SortableJS)
  - Cartes tâches avec titre, description
  - Bouton "Nouvelle tâche" dans la colonne à faire

**Responsive mobile** :
- **Redirection vers "Desktop Only"** : Module trop complexe pour petit écran
  - Page explicative : "Kanban nécessite un écran plus large"
  - Bouton retour Dashboard + Déconnexion

**Interactions** :
- Clic sur projet → Charge les tâches de ce projet
- Drag & Drop tâche → Change le statut automatiquement (API PATCH)

---

## 3. Module Snippets (Bibliothèque de code)

### Objectif

Permettre aux développeurs de **sauvegarder et réutiliser** facilement des morceaux de code utiles.

### Fonctionnalités principales

#### 💾 Gestion des snippets
- **Création** : Titre + langage + code + description optionnelle
- **Liste** : Vue chronologique (plus récents en premier)
- **Édition** : Modification du titre, langage, code, description
- **Suppression** : du snippet
- **Ownership** : Chaque utilisateur voit uniquement ses propres snippets

#### 📋 Copier dans le presse-papier
- **Bouton "Copier"** : Copie le code dans le presse-papier
- **Feedback visuel** : "Copié !" affiché pendant 2 secondes


### Architecture technique

**Base de données** : MongoDB (collection `snippets`)

**Raisons du choix MongoDB** :
- Code stocké en texte brut (pas de structure fixe)
- Isolation de contenu potentiellement dangereux (code utilisateur arbitraire)
- Performance pour lecture/écriture de snippets (pas de relations complexes)

**Document MongoDB** :
```javascript
{
  _id: ObjectId,
  userId: "user_id_string",
  title: "Nom du snippet",
  language: "javascript",
  code: "console.log('Hello World');",
  description: "Description optionnelle",
  createdAt: ISODate,
}
```

**API REST** :
- `GET /api/snippets` : Liste des snippets de l'utilisateur
- `GET /api/snippets/{id}` : Détail d'un snippet (Voter)
- `POST /api/snippets` : Créer un snippet (CSRF)
- `PUT /api/snippets/{id}` : Modifier un snippet (CSRF + Voter)
- `DELETE /api/snippets/{id}` : Supprimer un snippet (CSRF + Voter)

**Sécurité** :
- `ResourceVoter` : Vérifie l'ownership MongoDB via `getUserId()`
- CSRF protection sur toutes les routes de modification
- **Pas d'exécution de code** : Snippets affichés uniquement (lecture seule)

### Interface utilisateur

**Layout desktop** :
  - Liste des snippets avec aperçu du langage (badge coloré)
  - Bouton "Nouveau snippet"
  - Recherche + filtre par langage
- **Zone principale** : Détail du snippet sélectionné
  - Titre + langage (badge)
  - Code avec coloration syntaxique
  - Description (si présente)
  - Boutons : Copier / Éditer / Supprimer

**Responsive mobile** :
- **Redirection vers "Desktop Only"** : Éditeur de code peu pratique sur petit écran
  - Alternative : Vue lecture seule (sans édition) en version future

**Interactions** :
- Clic sur snippet → Affiche le code dans la zone principale
- Clic sur "Éditer" → Ouvre modal d'édition avec textarea + sélection langage
- Clic sur "Supprimer" → Modal de confirmation

---

## 4. Module Compétences (Suivi de progression)

### Objectif

Permettre aux développeurs juniors de **suivre leur progression** et **s'auto-évaluer** de façon transparente.

### Fonctionnalités principales

#### ⭐ Système d'auto-évaluation
- **Notation sur 5 étoiles** : Interface interactive (clic sur étoile)
  - 0 étoile : Non acquis / Pas encore travaillé
  - 1 étoile : Débutant (notions de base)
  - 2 étoiles : Intermédiaire (pratique régulière)
  - 3 étoiles : Confirmé (autonome)
  - 4 étoiles : Avancé (bonne maîtrise)
  - 5 étoiles : Expert (maîtrise complète)

#### 📊 Métriques et progression
- **Niveau calculé automatiquement** : Moyenne des étoiles sur toutes les compétences
  - Affiché en pourcentage (5 étoiles = 100%)
- **Progression visuelle** : Barre de progression par compétence
- **Statistiques globales** :
  - Nombre de compétences validées (≥3 étoiles)
  - Compétences en cours (1-2 étoiles)
  - Compétences non commencées (0 étoile)

#### 📝 Annotations personnelles
- **Nom de la compétence** : Éditable (pour personnaliser le libellé)
- **Description** : Zone de texte libre pour notes personnelles
  - Exemples : "Utilisé sur projet MY-ANKODE", "À retravailler avec React", etc.

#### 🔗 Liens avec les autres modules
- **Projets liés** : Associer des projets (Kanban) à une compétence
  - Exemple : CP6 → Lié au projet "API REST MY-ANKODE"
- **Snippets liés** : Associer des snippets à une compétence
  - Exemple : CP3 → Lié au snippet "Fetch API avec async/await"

### Architecture technique

**Base de données** : PostgreSQL (table `competence`)

**Raisons du choix PostgreSQL** :
- Relations avec `Project` et `Snippet` (foreign keys)
- Calculs agrégés (moyenne, statistiques)
- Intégrité des données (validation des notes 0-5)

**Entité Doctrine** :
```php
class Competence {
    private int $id;
    private string $name;
    private ?string $description;
    private int $level; // 0-5 étoiles
    private User $owner;
    private Collection $projects; // ManyToMany
    private array $snippets; // IDs MongoDB stockés en JSON
    private DateTime $createdAt;
    private DateTime $updatedAt;
}
```

**API REST** :
- `GET /api/competences` : Liste des compétences de l'utilisateur
- `GET /api/competences/{id}` : Détail d'une compétence (Voter)
- `POST /api/competences` : Créer une compétence (CSRF)
- `PUT /api/competences/{id}` : Modifier une compétence (CSRF + Voter)
- `DELETE /api/competences/{id}` : Supprimer une compétence (CSRF + Voter)
- `GET /api/competences/stats` : Statistiques globales (nb compétences par niveau)
- `POST /api/competences/{id}/link-project` : Lier un projet (CSRF)
- `POST /api/competences/{id}/link-snippet` : Lier un snippet (CSRF)

**Calculs automatiques** :
- Méthode `calculateLevel()` appelée automatiquement à chaque modification
- Mise à jour du champ `updatedAt` via Doctrine Lifecycle Callbacks

### Interface utilisateur

**Layout desktop** :
- **Grille de cartes** : 1 carte par compétence
  - Badge CP1-CP8 (couleur cyan)
  - Titre + description
  - Notation étoiles (interactive)
  - Barre de progression visuelle
  - Boutons : Éditer / Lier projet / Lier snippet / Supprimer
- **Statistiques en header** :
  - Progression globale (pourcentage)
  - Répartition par niveau (graphique circulaire ou barres)

**Responsive mobile** :
- Layout vertical (1 colonne)
- Cartes empilées
- Interactions tactiles (tap sur étoiles)

**Interactions** :
- Clic sur étoile → Met à jour le niveau immédiatement (API PATCH)
- Clic sur "Éditer" → Modal avec formulaire (nom, description, niveau)
- Clic sur "Lier projet" → Modal avec liste des projets disponibles
- Clic sur "Lier snippet" → Modal avec liste des snippets disponibles
- Feedback visuel : Animation d'étoiles au changement de niveau

---

## 5. Fonctionnalités transversales

### Authentification et sécurité

**Système d'authentification** :
- Inscription avec email + username + password (validation Symfony)
- Connexion avec username + password
- Déconnexion (invalidation session)
- Sessions sécurisées (HttpOnly, Secure, SameSite)

**Protection CSRF** :
- Token CSRF global (`csrf_token('api')`) injecté dans meta tag
- `CsrfValidationSubscriber` vérifie automatiquement toutes les routes POST/PUT/PATCH/DELETE
- Headers personnalisés (`X-CSRF-Token`)

**Gestion des droits** :
- `ROLE_USER` : Accès aux 4 modules
- `ROLE_ADMIN` : Accès au Dashboard Admin (stats globales)
- `ResourceVoter` : Vérifie l'ownership sur toutes les ressources

### Dashboard

**Objectif** : Vue d'ensemble de l'activité utilisateur

**Widgets affichés** :
- **Derniers articles de veille** : 5 articles les plus récents
- **Projets en cours** : Nombre de tâches par statut (To Do, In Progress, Done)
- **Snippets récents** : 5 derniers snippets créés
- **Progression compétences** : Graphique circulaire (répartition par niveau)

**Navigation** : Liens directs vers chaque module depuis le Dashboard

### Thème Dark/Light

**Modes disponibles** :
- **Dark mode** (par défaut) : Fond cyan foncé `#003B4F`, texte clair
- **Light mode** : Fond clair `#E8F4F8`, texte sombre

**Persistance** : localStorage (`theme` = `dark` ou `light`)

**Switch** :
- Desktop : Bouton ampoule (bottom-right)
- Mobile : Bouton dans le header fixe

**Transitions** : Animation fluide entre les modes (300ms ease)

### Navigation responsive

**Desktop (≥768px)** :
- Navbar horizontale top-right (6 liens + Admin si ROLE_ADMIN)
- Logo top-left
- Sidebar utilisateur (greeting)

**Mobile (<768px)** :
- Header fixe (logo + greeting + legal + theme-switcher)
- Bottom navigation (4 icônes : Dashboard, Veille, Compétences, Desktop)
- Pas de menu hamburger (navigation toujours visible)

**Redirection "Desktop Only"** :
- Modules Kanban et Snippets redirigent vers page explicative sur mobile
- Bouton retour Dashboard + Déconnexion

---

## 6. Performance et optimisation

### Pagination
- **Veille** : 20 articles par page (MongoDB cursor + skip/limit)
- **Kanban** : Toutes les tâches chargées (petit volume prévu)
- **Snippets** : Lazy loading envisagé si volume > 100 snippets

### Caching
- **Articles RSS** : Stockés en MongoDB (pas de refetch à chaque visite)
- **Statiques** : CSS/JS minifiés en production
- **Images** : Lazy loading (`loading="lazy"`) sur images lourdes

### Optimisations JavaScript
- **Fetch API** : Requêtes asynchrones (async/await)
- **Debouncing** : Recherche Veille (300ms delay avant requête)
- **Event delegation** : Gestion d'événements optimisée (éviter listeners multiples)

---

## 7. Roadmap et améliorations futures

### Court terme (post-certification)
- **Migration Angular** : Réécrire le frontend en Angular 18+
  - Composants réutilisables
  - Routing Angular (SPA)
  - Services injectables pour API
  - RxJS pour gestion d'état
- **CI/CD** : GitHub Actions pour déploiement automatique

### Moyen terme
- **Module Veille** :
  - **Configuration personnalisée des flux RSS** (ajout/suppression par utilisateur)
  - **Import/Export OPML** (standard RSS pour migrer facilement ses abonnements)
  - Notifications push pour nouveaux articles
  - Export PDF des favoris
  - Catégorisation automatique (IA/NLP)
- **Module Kanban** :
  - Sous-tâches (checklist)
  - Dates d'échéance + rappels
  - Vue calendrier
- **Module Snippets** :
  - Tags/catégories pour organisation
  - Versioning (historique des modifications)
- **Module Compétences** :
  - Import/Export JSON (sauvegarde externe)
  - Génération de CV basé sur compétences
  - Timeline de progression (historique)

### Long terme
- **Collaboration** :
  - Commentaires sur tâches
  - Notifications temps réel (WebSockets)
- **Gamification** :
  - Badges déblocables (ex : "10 snippets créés")
  - Streaks (jours d'utilisation consécutifs)

---

## 8. Conclusion

MY-ANKODE offre un **écosystème complet** pour accompagner les développeurs juniors dans leur parcours d'apprentissage :

✅ **Veille** : Rester informé sans se disperser  
✅ **Kanban** : Organiser ses projets efficacement  
✅ **Snippets** : Capitaliser sur son code  
✅ **Compétences** : Mesurer sa progression  

Chaque module a été conçu avec une **approche MVP** :
- Fonctionnalités essentielles implémentées
- Code propre et maintenable
- Architecture extensible pour évolutions futures

L'application démontre la **maîtrise des compétences DWWM** (CP1 à CP8) à travers :
- Frontend responsive (CP2, CP3)
- Backend robuste (CP6, CP7)
- Base de données polyglotte (CP5)
- Sécurité (CSRF, Voters, OWASP Top 10)
- Tests automatisés (135 tests, 340 assertions)

MY-ANKODE n'est pas qu'un projet de certification, c'est un **outil réel** utilisable par des développeurs juniors pour structurer leur apprentissage et progresser méthodiquement.

---

**Dernière mise à jour** : Février 2026  
**Auteur** : Anthony Catan-Cavery  
**Projet** : MY-ANKODE - Certification DWWM


---

[← Retour au README principal](../README.md)
