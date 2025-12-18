# MY-ANKODE - Backend Symfony 7

**Application web pour développeurs juniors** - Projet de certification DWWM

---

## 🚀 Démarrage rapide
```bash
# Lancer le serveur Symfony
symfony serve

# Accéder à l'application
http://127.0.0.1:8000
```

---

## 🎯 Routes disponibles

| Route | Méthode | Accès | Description |
|-------|---------|-------|-------------|
| `/` | GET | Public | Redirection vers `/auth` ou `/dashboard` selon état connexion |
| `/auth` | GET | Public | Page d'authentification unifiée (inscription + connexion) |
| `/register` | POST | Public | Traitement inscription (soumis depuis `/auth`) |
| `/login` | POST | Public | Traitement connexion (soumis depuis `/auth`) |
| `/logout` | GET | Authentifié | Déconnexion utilisateur |
| `/dashboard` | GET | Authentifié | Page d'accueil utilisateur connecté |

---

## 🔐 Architecture d'authentification

### Entités
- **User** : `email` (unique), `username` (unique), `password` (bcrypt), `roles`, `createdAt`

### Controllers
- **AuthController** : Affiche la page `/auth` avec les 2 formulaires (inscription + connexion)
- **RegistrationController** : Traite la soumission du formulaire d'inscription (POST `/register`)
- **SecurityController** : Traite la soumission du formulaire de connexion (POST `/login`)
- **DashboardController** : Affiche le dashboard après connexion

### Formulaires
- **RegistrationFormType** : `username`, `email`, `password`, `agreeTerms`
- Connexion : Formulaire manuel dans Twig (email + password)

### Sécurité
- **AppCustomAuthenticator** : Authentification par email + password
- Hash : `bcrypt` (auto dans `security.yaml`)
- Protection CSRF : Token `csrf_token('authenticate')` pour le login
- Remember Me : Option "Se souvenir de moi" configurée

---

## 🧪 Tests d'authentification
```bash
# Test page d'authentification
http://127.0.0.1:8000/auth

# Test inscription
1. Aller sur /auth
2. Remplir le formulaire gauche (S'inscrire)
3. Soumettre → Redirection vers /dashboard

# Test connexion
1. Aller sur /auth
2. Remplir le formulaire droit (Se connecter)
3. Soumettre → Redirection vers /dashboard

# Test accès Dashboard (authentifié)
http://127.0.0.1:8000/dashboard

# Test déconnexion
http://127.0.0.1:8000/logout
→ Redirection vers /auth
```

---

## ✅ Tests réalisés (Carte #10 + #11)

### Backend (Carte #10 - 16/12/2024)
- [x] Entité User (email, username, password, roles, createdAt)
- [x] Migration PostgreSQL (table `user_`)
- [x] RegistrationController + RegistrationFormType
- [x] SecurityController + AppCustomAuthenticator
- [x] Configuration `security.yaml` (bcrypt, firewall, remember_me)
- [x] Mot de passe hashé en bcrypt
- [x] Contraintes d'unicité (email, username)

### Frontend (Carte #11 - 18/12/2024)
- [x] AuthController : Route `/auth` affichant les 2 formulaires
- [x] Template Twig unifié (`auth/index.html.twig`)
- [x] Intégration Bootstrap 5
- [x] CSS personnalisé (`public/css/auth.css`)
- [x] Design système : Palette cyan/orange
- [x] Images intégrées (Ankode_Isometric, Ankode_OK, Ankode_Planet)
- [x] Responsive design (mobile-first)
- [x] Redirection `/` → `/auth` ou `/dashboard` selon état connexion
- [x] Tests fonctionnels : Inscription, connexion, déconnexion

---

## 📊 Base de données

### Table `user_`
```sql
CREATE TABLE user_ (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    roles VARCHAR(500) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

**Note :** Nom `user_` au lieu de `user` (mot réservé PostgreSQL)

---

## 🎨 Design System

### Palette de couleurs
```css
--cyan-primary: #00C2D1
--cyan-light: #7DD3DB
--cyan-dark: #007A85
--orange-accent: #FDAB5E
```

### Assets visuels
- `Ankode_Isometric.png` : Illustration développeur 3D
- `Ankode_OK.png` : Mascotte avec ampoule
- `Ankode_Planet.png` : Planète connectée (background)

---

## 📦 Stack Technique

- **Framework** : Symfony 7
- **PHP** : 8.2+
- **Base de données** : PostgreSQL 16
- **Frontend** : Twig + Bootstrap 5 + CSS personnalisé
- **Authentification** : Symfony Security + bcrypt

---

## 📁 Structure des fichiers (authentification)
```
backend/
├── src/
│   ├── Controller/
│   │   ├── AuthController.php          # Affiche /auth
│   │   ├── RegistrationController.php  # Traite inscription
│   │   ├── SecurityController.php      # Traite connexion
│   │   └── DashboardController.php     # Dashboard connecté
│   ├── Entity/
│   │   └── User.php                    # Entité User
│   ├── Form/
│   │   └── RegistrationFormType.php    # Formulaire inscription
│   └── Security/
│       └── AppCustomAuthenticator.php  # Authentification custom
├── templates/
│   ├── base.html.twig                  # Base HTML + Bootstrap
│   ├── auth/
│   │   └── index.html.twig             # Page auth unifiée
│   └── dashboard/
│       └── index.html.twig             # Placeholder dashboard
├── public/
│   ├── css/
│   │   └── auth.css                    # Styles personnalisés
│   └── images/
│       ├── Ankode_Isometric.png
│       ├── Ankode_OK.png
│       └── Ankode_Planet.png
└── migrations/
    └── Version20241216135401.php       # Migration table user_
```

---

## 🔜 Prochaines étapes

### Sprint 1 : Architecture Symfony (en cours)
- [ ] Carte #12 : Entités Project & Task (19/12/2024)
- [ ] Carte #13 : API REST CRUD Projects
- [ ] Carte #14 : API REST CRUD Tasks

### Sprint 2 : Fonctionnalités avancées
- [ ] MongoDB : Configuration + Collections (snippets, articles)
- [ ] Vue Kanban : Affichage 3 colonnes (todo, in_progress, done)

---

**Dernière mise à jour :** 18/12/2024 - Carte #11 complétée ✅