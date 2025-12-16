# 📊 PROGRESSION MY-ANKODE - CERTIFICATION DWWM

**Projet :** My-Ankode - Application fullstack pour développeurs juniors  
**Période :** Décembre 2024 - Janvier 2025  
**Candidat :** Anthony  

---

## 🏆 SPRINTS & CARTES COMPLÉTÉES

### Sprint 1 : Architecture Symfony (16-22 déc)

#### ✅ Carte #10 : User & Auth (3h estimées, 5h réelles) - 16/12/2024

**Objectif :** Implémenter l'authentification utilisateur avec Symfony 7 + PostgreSQL

**Réalisations :**
- ✅ Entité User (email, username, password, roles, created_at)
- ✅ Migration PostgreSQL (table user_ - mot réservé contourné)
- ✅ Configuration security.yaml (bcrypt, firewall, remember_me)
- ✅ Formulaires : RegistrationFormType, LoginFormType
- ✅ Controllers : RegistrationController, SecurityController, DashboardController
- ✅ AppCustomAuthenticator (système de connexion personnalisé)
- ✅ Templates Twig : register, login, dashboard
- ✅ Tests complets : inscription, connexion, déconnexion, accès protégé

**Fichiers créés :**
- `src/Entity/User.php`
- `src/Repository/UserRepository.php`
- `src/Form/RegistrationFormType.php`
- `src/Controller/RegistrationController.php`
- `src/Controller/SecurityController.php`
- `src/Controller/DashboardController.php`
- `src/Security/AppCustomAuthenticator.php`
- `templates/registration/register.html.twig`
- `templates/security/login.html.twig`
- `templates/dashboard/index.html.twig`
- `migrations/Version20251216135401.php`

**Commits Git :**
- Branch : `feature/user-auth`
- Merged dans `develop` le 16/12/2024

**Compétences DWWM validées :**
- CP5 : Mettre en place une base de données relationnelle ✅
- CP7 : Développer des composants métier côté serveur ✅
- CP6 : Développer des composants d'accès aux données SQL (partiel) ✅
- Sécurité : Hashage bcrypt, protection CSRF, sessions ✅

**Difficultés rencontrées & solutions :**
- ⚠️ Champ username manquant dans formulaire → Ajout manuel dans RegistrationFormType
- ⚠️ Redirection TODO après login → Création DashboardController + correction AppCustomAuthenticator
- ⚠️ Mot réservé PostgreSQL "user" → Utilisation de "user_" avec #[ORM\Table(name: 'user_')]




## 🔗 RESSOURCES

**Repository GitHub :** https://github.com/ton-username/my-ankode  
**Documentation Symfony :** [ARCHITECTURE.md](./ARCHITECTURE.md)  
**Décisions techniques :** [DECISIONS.md](./docs/DECISIONS.md)  
**Référentiel DWWM :** [Référentiel_Activités_Compétences_Evaluation_TP_DWWM.pdf](./Référentiel_Activités_Compétences_Evaluation_TP_DWWM.pdf)

---

**Dernière mise à jour :** 16/12/2024 - 17:00
```
