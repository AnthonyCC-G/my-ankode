# MY-ANKODE

---

## 🎯 Routes disponibles

| Route | Méthode | Accès | Description |
|-------|---------|-------|-------------|
| `/register` | GET, POST | Public | Inscription nouvel utilisateur |
| `/login` | GET, POST | Public | Connexion utilisateur |
| `/logout` | GET | Authentifié | Déconnexion utilisateur |
| `/dashboard` | GET | Authentifié | Page d'accueil utilisateur connecté |

## 🔐 Tests d'authentification
```bash
# Test inscription
http://localhost:8000/register

# Test connexion
http://localhost:8000/login

# Test accès Dashboard (authentifié)
http://localhost:8000/dashboard

# Test déconnexion
http://localhost:8000/logout
```

---

## 🎯 Routes disponibles

| Route | Méthode | Accès | Description |
|-------|---------|-------|-------------|
| `/register` | GET, POST | Public | Inscription nouvel utilisateur |
| `/login` | GET, POST | Public | Connexion utilisateur |
| `/logout` | GET | Authentifié | Déconnexion utilisateur |
| `/dashboard` | GET | Authentifié | Page d'accueil utilisateur connecté |

## ✅ Tests réalisés

- [x] Inscription utilisateur
- [x] Connexion utilisateur
- [x] Dashboard (accès authentifié)
- [x] Redirection automatique (accès non authentifié)
- [x] Déconnexion
- [x] Mot de passe hashé en bcrypt
- [x] Contraintes d'unicité (email, username)

---