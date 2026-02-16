# Choix techniques

Justification des décisions d'architecture et de technologies de MY-ANKODE.

[← Retour au README principal](../README.md)

---

## Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Architecture polyglotte](#architecture-polyglotte)
- [Stack backend](#stack-backend)
- [Stack frontend](#stack-frontend)
- [Environnement de développement](#environnement-de-développement)
- [Évolutions futures](#évolutions-futures)

---

## Vue d'ensemble

MY-ANKODE utilise une **architecture polyglotte** (PostgreSQL + MongoDB) avec un backend Symfony et un frontend Vanilla JavaScript, conçue pour répondre aux exigences de certification DWWM tout en restant pragmatique et MVP-oriented.

### Contraintes du projet

- ⏱️ **Deadline serrée** : Certification le 20 janvier 2026
- 📋 **Référentiel DWWM** : Couverture des 8 compétences professionnelles
- 🎯 **Objectif MVP** : Application fonctionnelle et testée
- 🚀 **Post-certification** : Migration vers Angular prévue

---

## Architecture polyglotte

### Pourquoi PostgreSQL **ET** MongoDB ?

MY-ANKODE utilise **deux bases de données** pour des raisons à la fois pédagogiques et techniques.

#### 1. Exigence de certification

Le référentiel DWWM encourage la **démonstration de polyvalence** :
- Maîtrise des bases relationnelles (PostgreSQL)
- Maîtrise des bases NoSQL (MongoDB)

#### 2. Séparation des préoccupations

**PostgreSQL** → Données sensibles et relationnelles
- **User** : Données personnelles, mots de passe hashés
- **Project** : Relations fortes avec tasks
- **Task** : Dépendance stricte au projet parent
- **Competence** : Relations avec projets et utilisateurs

**MongoDB** → Données publiques et flexibles
- **Articles RSS** : Partagés entre tous les utilisateurs (pas de propriété)
- **Snippets** : Code source volumineux, schéma flexible

#### 3. Isolation sécurité

**Choix stratégique** : Isoler les snippets de code de la base contenant les données utilisateur.

**Raison** : Les snippets contiennent du **code exécutable** potentiellement dangereux. En cas de faille de sécurité sur la collection snippets, la base PostgreSQL (users, passwords) reste protégée.

```
┌─────────────────┐     ┌─────────────────┐
│   PostgreSQL    │     │     MongoDB     │
│                 │     │                 │
│  ✓ Users        │     │  ✓ Snippets     │
│  ✓ Passwords    │     │  ✓ Articles     │
│  ✓ Projects     │     │                 │
│  ✓ Tasks        │     │ (pas de pwd!)   │
│  ✓ Competences  │     │                 │
└─────────────────┘     └─────────────────┘
    ACID garanties          Flexibilité
```

---

### PostgreSQL : Données relationnelles

**Choix** : PostgreSQL 16

**Raisons** :
- ✅ **ACID** : Garanties transactionnelles fortes
- ✅ **Relations complexes** : Cascades (suppression projet → suppression tasks)
- ✅ **Intégrité** : Contraintes foreign key strictes
- ✅ **Performance** : Index efficaces sur requêtes complexes

**Cas d'usage** :

```sql
-- Cascade : Suppression d'un projet = suppression des tasks
DELETE FROM project WHERE id = 5;
-- → Toutes les tasks du projet 5 sont automatiquement supprimées

-- Impossible de créer une task sans projet (foreign key)
INSERT INTO task (title, project_id) VALUES ('Tâche orpheline', NULL);
-- → ERREUR : NOT NULL violation
```

**Avantages pour MY-ANKODE** :
- Pas de tâche sans projet (cohérence)
- Pas de projet sans owner (sécurité)
- Suppression compte = suppression projets + tasks (RGPD)

---

### MongoDB : Documents flexibles

**Choix** : MongoDB 6

**Raisons** :
- ✅ **Schéma flexible** : Évolution facile des structures
- ✅ **Performance** : Lecture/écriture rapide pour gros documents
- ✅ **Pas de relations critiques** : Articles indépendants des users

**Cas d'usage 1 : Articles RSS**

```javascript
{
  title: "Article de veille tech",
  url: "https://...",
  description: "...",
  tags: ["php", "symfony"],  // Array dynamique
  userId: null,  // Article partagé entre TOUS les users
  readBy: ["user1", "user2"],  // Metadata user-specific
  favorites: ["user1"]
}
```

**Pourquoi MongoDB ici ?**
- Pas de relation forte (article appartient à personne)
- Schéma flexible (`tags` peut évoluer)
- Métadonnées utilisateur (`readBy`, `favorites`) facilement ajoutables

**Cas d'usage 2 : Snippets**

```javascript
{
  title: "React Hook personnalisé",
  language: "javascript",
  code: "... grosse source de code ...",  // String potentiellement longue
  description: "...",  // Peut évoluer (markdown, HTML, etc.)
  tags: ["react", "hooks"],
  userId: "user123"
}
```

**Pourquoi MongoDB ici ?**
- **Flexibilité** : Champ `description` peut devenir markdown, HTML, etc.
- **Performance** : Stockage efficace de gros blobs de texte (code source)
- **Isolation** : Code potentiellement dangereux séparé de la BDD sensible

---

## Stack backend

### Symfony 7.4

**Choix** : Framework Symfony 7.4

**Raisons** :
- ✅ **Standard PHP** : Framework le plus utilisé en entreprise
- ✅ **Robustesse** : Sécurité, validation, ORM intégrés
- ✅ **Doctrine ORM/ODM** : Support natif PostgreSQL + MongoDB
- ✅ **Écosystème** : Bundles pour tout (API Doc, Security, Tests)
- ✅ **Certification** : Bien couvert dans le référentiel DWWM

**Alternatives écartées** :
- ❌ **Laravel** : Moins présent dans les offres d'emploi DWWM
- ❌ **Node.js/Express** : Hors périmètre certification PHP

---

### PHP 8.3

**Choix** : PHP 8.3

**Raisons** :
- ✅ **Typage fort** : Attributes, readonly properties, union types
- ✅ **Performance** : JIT compiler
- ✅ **Moderne** : match expressions, named arguments

**Exemple dans MY-ANKODE** :

```php
#[Route('/api/projects/{id}', methods: ['GET'])]
#[IsGranted('VIEW', subject: 'project')]
public function getProject(Project $project): JsonResponse
{
    return $this->json($project);
}
```

---

### Doctrine ORM + ODM

**Choix** : Doctrine ORM (PostgreSQL) + Doctrine ODM (MongoDB)

**Raisons** :
- ✅ **Abstraction BDD** : Pas de SQL/Mongo brut
- ✅ **Migrations** : Schéma versionné
- ✅ **Relations** : Gestion automatique des cascades

**Exemple ORM (PostgreSQL)** :

```php
#[ORM\Entity]
#[ORM\Table(name: 'task')]
class Task
{
    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;
}
```

**Exemple ODM (MongoDB)** :

```php
#[ODM\Document(collection: 'snippets')]
class Snippet
{
    #[ODM\Field(type: 'string')]
    private ?string $code = null;  // Stockage flexible
}
```

---

## Stack frontend

### Vanilla JavaScript

**Choix** : JavaScript ES6+ sans framework

**Raisons principales** :

#### 1. Contrainte temporelle

- ⏱️ **3 mois** pour développer l'application complète
- 📚 **Courbe d'apprentissage** : React/Vue nécessitent du temps
- 🎯 **MVP first** : Livrer un produit fonctionnel avant tout

#### 2. SEO et accessibilité

```
React/Vue SPA → Rendu côté client → Mauvais SEO initial
Vanilla JS → HTML généré par Symfony (Twig) → SEO optimal
```

**Problème React** : Les moteurs de recherche peinent à indexer les apps entièrement côté client.

**Solution MY-ANKODE** : Pages servies par Twig + enrichissement JavaScript progressif.

#### 3. Contrôle total

```javascript
// Exemple : Drag & drop Kanban
document.addEventListener('dragstart', (e) => {
    e.dataTransfer.setData('taskId', e.target.dataset.id);
});

// Pas de dépendance externe, contrôle total du DOM
```

**Avantages** :
- Pas de build step complexe
- Debugging simple (pas de JSX, pas de Virtual DOM)
- Performance native du navigateur

---

### Bootstrap 5

**Choix** : Framework CSS Bootstrap 5

**Raisons** :
- ✅ **Rapidité** : Composants prêts à l'emploi
- ✅ **Responsive** : Grid system mobile-first
- ✅ **Accessibilité** : Composants ARIA-compliant

**Alternatives écartées** :
- ❌ **Tailwind CSS** : Trop de classes utilitaires (verbeux)
- ❌ **CSS from scratch** : Trop chronophage pour un MVP

**Personnalisation** :

```css
/* MY-ANKODE utilise des variables CSS personnalisées */
:root {
    --primary-color: #007bff;
    --sidebar-width: 250px;
}

/* Dark mode natif */
[data-theme="dark"] {
    --bg-color: #1a1a1a;
}
```

---

## Environnement de développement

### Docker + Docker Compose

**Choix** : Conteneurisation complète

**Raisons** :
- ✅ **Reproductibilité** : Même environnement dev/prod
- ✅ **Isolation** : Pas de conflit avec installations locales
- ✅ **Portabilité** : Fonctionne sur Windows/Mac/Linux
- ✅ **Démonstration** : Compétence CP1 du référentiel DWWM

**Services conteneurisés** :
```yaml
- backend (PHP 8.3 + Symfony)
- postgres (PostgreSQL 16)
- mongo (MongoDB 6)
- pgadmin (interface PostgreSQL)
- mongo-express (interface MongoDB)
```

---

### Git + GitHub

**Choix** : Versionning Git avec dépôt GitHub

**Raisons** :
- ✅ **Standard industrie** : Git obligatoire en entreprise
- ✅ **Git Flow** : Branches feature/develop/main
- ✅ **Historique** : Traçabilité des modifications

**Workflow** :

```bash
feature/XX-nom-feature → develop → main
```

---

## Évolutions futures

### Migration Angular (post-certification)

**Pourquoi Angular après certification ?**

#### 1. Employabilité

- 📊 **80% des offres** DWWM dans les Hauts-de-France demandent Angular
- 🏢 **Standard entreprise** : Angular privilégié dans les grands groupes

#### 2. Architecture préparée

MY-ANKODE est **déjà conçu pour Angular** :

```
Backend Symfony → API REST (JSON)
           ↓
  Frontend Angular (futur)
```

L'API est **découplée** du frontend, migration facile.

#### 3. Fonctionnalités avancées

Avec Angular :
- ✅ **Routing client** : Navigation instantanée
- ✅ **State management** : RxJS pour flux de données
- ✅ **Composants réutilisables** : Modularité
- ✅ **TypeScript** : Typage fort côté front

**Planning** :
1. ✅ **Janvier 2026** : MVP Symfony + Vanilla JS (certification)
2. 🔄 **Février-Mars 2026** : Migration frontend vers Angular
3. 🚀 **Avril 2026** : Déploiement version Angular en production

---

### Améliorations techniques prévues

**Backend** :
- JWT Authentication (remplacer sessions)
- WebSockets (notifications temps réel)
- Cache Redis (performance)
- Elasticsearch (recherche full-text)

**Frontend** :
- Progressive Web App (PWA)
- Service Workers (offline mode)
- Lazy loading (performance)

**DevOps** :
- CI/CD GitHub Actions
- Tests end-to-end (Playwright)
- Monitoring (Sentry)

---

## Récapitulatif des choix

| Technologie | Choix | Raison principale |
|-------------|-------|-------------------|
| **BDD relationnelle** | PostgreSQL 16 | ACID, relations fortes |
| **BDD document** | MongoDB 6 | Flexibilité, isolation sécurité |
| **Backend** | Symfony 7.4 | Standard PHP, référentiel DWWM |
| **Langage** | PHP 8.3 | Typage fort, performance |
| **ORM/ODM** | Doctrine | Abstraction, migrations |
| **Frontend** | Vanilla JS | Rapidité dev, SEO, contrôle |
| **CSS** | Bootstrap 5 | Composants prêts, responsive |
| **Conteneurisation** | Docker | Reproductibilité, CP1 |
| **Versionning** | Git + GitHub | Standard, Git Flow |

---

## Couverture référentiel DWWM

Ces choix techniques couvrent les **8 compétences professionnelles** :

- ✅ **CP1** : Docker, environnement de développement
- ✅ **CP2** : Interface utilisateur responsive (Bootstrap, Vanilla JS)
- ✅ **CP3** : API REST (Symfony, JSON)
- ✅ **CP4** : Base de données (PostgreSQL + Doctrine ORM)
- ✅ **CP5** : Composants d'accès aux données (Repositories, QueryBuilder)
- ✅ **CP6** : Composants métier (Services, Voters, Sécurité)
- ✅ **CP7** : Tests (PHPUnit, 135 tests)
- ✅ **CP8** : Documentation technique (vous êtes en train de la lire !)

---

[← Retour au README principal](../README.md)
