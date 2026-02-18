<?php
// src/DataFixtures/SnippetFixtures.php

namespace App\DataFixtures;

use App\Document\Snippet;
use App\Entity\User;
use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Bundle\MongoDBBundle\Fixture\FixtureGroupInterface;  
use Doctrine\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;

class SnippetFixtures extends Fixture implements FixtureGroupInterface  
{
    public function __construct(
        private DocumentManager $dm,
        private EntityManagerInterface $em
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Récupérer les users DIRECTEMENT depuis PostgreSQL
        $userRepository = $this->em->getRepository(User::class);
        $anthony = $userRepository->findOneBy(['username' => 'anthony_dev']);
        $alice = $userRepository->findOneBy(['username' => 'alice_codes']);
        $bob = $userRepository->findOneBy(['username' => 'bob_debug']);
        $clara = $userRepository->findOneBy(['username' => 'clara_learns']);

        // Vérifier que les users existent
        if (!$anthony || !$alice || !$bob || !$clara) {
            throw new \Exception('Users not found! Load UserFixtures first.');
        }

        // ========================================
        // 1- SNIPPETS ANTHONY - Code générique professionnel
        // ========================================
        
        $snippet1 = new Snippet();
        $snippet1->setUserId((string) $anthony->getId());
        $snippet1->setTitle('Entity Symfony - Pattern de base');
        $snippet1->setLanguage('php');
        $snippet1->setCode('<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column]
    private ?float $price = null;

    // Getters/Setters...
}');
        $snippet1->setDescription('Structure de base d\'une entité Symfony avec attributs PHP 8 : mapping ORM, validation, typage strict. Pattern recommandé pour toutes les entités.');
        $snippet1->setTags(['symfony', 'doctrine', 'entity', 'php8']);
        $this->dm->persist($snippet1);

        $snippet2 = new Snippet();
        $snippet2->setUserId((string) $anthony->getId());
        $snippet2->setTitle('Repository - Requête custom DQL');
        $snippet2->setLanguage('php');
        $snippet2->setCode('public function findActiveUserProjects(User $user): array
{
    return $this->createQueryBuilder(\'p\')
        ->innerJoin(\'p.tasks\', \'t\')
        ->where(\'p.owner = :user\')
        ->andWhere(\'t.status != :done\')
        ->setParameter(\'user\', $user)
        ->setParameter(\'done\', \'done\')
        ->orderBy(\'p.createdAt\', \'DESC\')
        ->getQuery()
        ->getResult();
}');
        $snippet2->setDescription('Requête DQL avancée avec jointure et filtres multiples. Récupère les projets d\'un user ayant des tâches non terminées. QueryBuilder Doctrine optimisé.');
        $snippet2->setTags(['doctrine', 'dql', 'repository', 'symfony']);
        $this->dm->persist($snippet2);

        $snippet3 = new Snippet();
        $snippet3->setUserId((string) $anthony->getId());
        $snippet3->setTitle('Service avec injection de dépendances');
        $snippet3->setLanguage('php');
        $snippet3->setCode('namespace App\Service;

use Psr\Log\LoggerInterface;

class NotificationService
{
    public function __construct(
        private LoggerInterface $logger,
        private MailerInterface $mailer
    ) {}

    public function notify(User $user, string $message): void
    {
        $this->logger->info("Sending notification", [\'user\' => $user->getId()]);
        $this->mailer->send($user->getEmail(), $message);
    }
}');
        $snippet3->setDescription('Pattern de service Symfony avec injection de dépendances (constructor injection). Typage strict PHP 8, séparation des responsabilités, testabilité maximale.');
        $snippet3->setTags(['symfony', 'service', 'di', 'architecture']);
        $this->dm->persist($snippet3);

        $snippet4 = new Snippet();
        $snippet4->setUserId((string) $anthony->getId());
        $snippet4->setTitle('CSRF token validation Symfony');
        $snippet4->setLanguage('php');
        $snippet4->setCode('use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

public function delete(Request $request, CsrfTokenManagerInterface $csrf): Response
{
    $token = $request->request->get(\'_token\');
    
    if (!$csrf->isTokenValid(new CsrfToken(\'delete_item\', $token))) {
        throw new AccessDeniedException(\'Invalid CSRF token\');
    }
    
    // Action sécurisée...
}');
        $snippet4->setDescription('Protection CSRF sur actions sensibles (DELETE, POST). Validation côté serveur obligatoire pour éviter les attaques Cross-Site Request Forgery.');
        $snippet4->setTags(['symfony', 'security', 'csrf', 'validation']);
        $this->dm->persist($snippet4);

        $snippet5 = new Snippet();
        $snippet5->setUserId((string) $anthony->getId());
        $snippet5->setTitle('Dark mode toggle avec localStorage');
        $snippet5->setLanguage('javascript');
        $snippet5->setCode('const themeToggle = document.getElementById(\'theme-toggle\');
const html = document.documentElement;

// Charger le thème sauvegardé
const savedTheme = localStorage.getItem(\'theme\') || \'light\';
html.setAttribute(\'data-theme\', savedTheme);

// Toggle au clic
themeToggle.addEventListener(\'click\', () => {
    const current = html.getAttribute(\'data-theme\');
    const newTheme = current === \'light\' ? \'dark\' : \'light\';
    
    html.setAttribute(\'data-theme\', newTheme);
    localStorage.setItem(\'theme\', newTheme);
});');
        $snippet5->setDescription('Système de dark mode persistant avec localStorage. Utilise data-attribute HTML pour éviter le flash au chargement. Compatible tous navigateurs modernes.');
        $snippet5->setTags(['javascript', 'dark-mode', 'localstorage', 'ui']);
        $this->dm->persist($snippet5);

        $snippet6 = new Snippet();
        $snippet6->setUserId((string) $anthony->getId());
        $snippet6->setTitle('Navbar responsive moderne');
        $snippet6->setLanguage('css');
        $snippet6->setCode('.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 2rem;
    background: var(--nav-bg);
}

.nav-links {
    display: flex;
    gap: 2rem;
    list-style: none;
}

@media (max-width: 768px) {
    .nav-links {
        position: fixed;
        flex-direction: column;
        top: 60px;
        right: -100%;
        transition: right 0.3s ease;
    }
    
    .nav-links.active {
        right: 0;
    }
}');
        $snippet6->setDescription('Navigation responsive avec menu mobile coulissant. Utilise Flexbox, CSS variables et media queries. Pattern mobile-first recommandé.');
        $snippet6->setTags(['css', 'responsive', 'navbar', 'mobile-first']);
        $this->dm->persist($snippet6);

        $snippet7 = new Snippet();
        $snippet7->setUserId((string) $anthony->getId());
        $snippet7->setTitle('Template HTML SEO-friendly');
        $snippet7->setLanguage('html');
        $snippet7->setCode('<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Description pertinente de la page (150-160 caractères)">
    <meta name="keywords" content="mots-clés, séparés, par, virgules">
    <meta name="author" content="Nom Auteur">
    
    <title>Titre optimisé SEO (50-60 caractères)</title>
    
    <!-- Open Graph pour réseaux sociaux -->
    <meta property="og:title" content="Titre partage social">
    <meta property="og:description" content="Description partage">
    <meta property="og:type" content="website">
</head>
<body>
    <header>
        <nav aria-label="Navigation principale">
            <!-- Menu accessible -->
        </nav>
    </header>
    
    <main>
        <h1>Titre principal (un seul h1 par page)</h1>
        <!-- Contenu structuré avec h2, h3... -->
    </main>
    
    <footer>
        <!-- Pied de page -->
    </footer>
</body>
</html>');
        $snippet7->setDescription('Template HTML5 optimisé SEO : balises meta complètes, Open Graph, structure sémantique (header/nav/main/footer), attributs ARIA pour accessibilité, hiérarchie titres respectée.');
        $snippet7->setTags(['html', 'seo', 'accessibility', 'semantic']);
        $this->dm->persist($snippet7);

        // ========================================
        // 2- SNIPPETS ALICE - Easter eggs frontend
        // ========================================

        $snippet8 = new Snippet();
        $snippet8->setUserId((string) $alice->getId());
        $snippet8->setTitle('Comment centrer une div (ENFIN !)');
        $snippet8->setLanguage('css');
        $snippet8->setCode('.container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

/* OU avec Grid (encore mieux) */
.container-grid {
    display: grid;
    place-items: center;
    min-height: 100vh;
}');
        $snippet8->setDescription('LA solution définitive au centrage vertical et horizontal. Flexbox classique ou Grid ultra-compact. Fini les margin: 0 auto et les position: absolute bizarres !');
        $snippet8->setTags(['css', 'flexbox', 'grid', 'layout', 'centrage']);
        $this->dm->persist($snippet8);

        $snippet9 = new Snippet();
        $snippet9->setUserId((string) $alice->getId());
        $snippet9->setTitle('Fix z-index qui marche pas');
        $snippet9->setLanguage('css');
        $snippet9->setCode('/* Ne fonctionne pas */
.element {
    z-index: 9999;
}

/* Solution : créer un stacking context */
.element {
    position: relative; /* ou absolute/fixed */
    z-index: 10;
}

/* Astuce : isoler avec */
.parent {
    isolation: isolate; /* Nouveau stacking context */
}');
        $snippet9->setDescription('Pourquoi z-index ne fonctionne pas ? Il faut un position (relative/absolute/fixed) ET comprendre les stacking contexts. La propriété isolation aide aussi !');
        $snippet9->setTags(['css', 'z-index', 'stacking-context', 'debug']);
        $this->dm->persist($snippet9);

        $snippet10 = new Snippet();
        $snippet10->setUserId((string) $alice->getId());
        $snippet10->setTitle('Regex email (merci StackOverflow)');
        $snippet10->setLanguage('javascript');
        $snippet10->setCode('function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

// Validation plus stricte (RFC 5322)
const strictRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;');
        $snippet10->setDescription('Regex email simple et fonctionnelle (merci internet). Version stricte pour validation serveur. Rappel : TOUJOURS valider côté backend aussi !');
        $snippet10->setTags(['javascript', 'regex', 'validation', 'email']);
        $this->dm->persist($snippet10);

        $snippet11 = new Snippet();
        $snippet11->setUserId((string) $alice->getId());
        $snippet11->setTitle('Animation CSS smooth sans lag');
        $snippet11->setLanguage('css');
        $snippet11->setCode('.card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    will-change: transform; /* Optimisation GPU */
}

.card:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

/* Attention : will-change consomme de la mémoire, 
   utiliser uniquement sur éléments animés */');
        $snippet11->setDescription('Animations fluides 60fps : utiliser transform (pas top/left), will-change pour GPU, transitions courtes (<400ms). Éviter d\'animer width/height/margin.');
        $snippet11->setTags(['css', 'animation', 'performance', 'gpu']);
        $this->dm->persist($snippet11);

        $snippet12 = new Snippet();
        $snippet12->setUserId((string) $alice->getId());
        $snippet12->setTitle('Fetch API avec gestion erreurs');
        $snippet12->setLanguage('javascript');
        $snippet12->setCode('async function fetchData(url) {
    try {
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return data;
        
    } catch (error) {
        console.error(\'Fetch failed:\', error);
        // Afficher un message à l\'utilisateur
        throw error; // Re-throw pour caller
    }
}

// Usage
fetchData(\'/api/users\')
    .then(users => console.log(users))
    .catch(err => alert(\'Erreur chargement\'));');
        $snippet12->setDescription('Pattern fetch avec gestion erreurs complète : vérification response.ok, try/catch, JSON parsing, propagation erreur. Toujours gérer les cas d\'échec réseau !');
        $snippet12->setTags(['javascript', 'fetch', 'async', 'error-handling']);
        $this->dm->persist($snippet12);

        $snippet13 = new Snippet();
        $snippet13->setUserId((string) $alice->getId());
        $snippet13->setTitle('Breakpoints responsive standards');
        $snippet13->setLanguage('css');
        $snippet13->setCode(':root {
    --breakpoint-xs: 375px;  /* Mobile small */
    --breakpoint-sm: 576px;  /* Mobile */
    --breakpoint-md: 768px;  /* Tablet */
    --breakpoint-lg: 992px;  /* Desktop */
    --breakpoint-xl: 1200px; /* Large desktop */
}

/* Mobile-first approach */
@media (min-width: 768px) { /* Tablet */ }
@media (min-width: 992px) { /* Desktop */ }
@media (min-width: 1200px) { /* XL */ }

/* Desktop-first (moins recommandé) */
@media (max-width: 991px) { /* < Desktop */ }');
        $snippet13->setDescription('Breakpoints standards inspirés de Bootstrap et Tailwind. Approche mobile-first recommandée (min-width). Utiliser CSS variables pour centraliser les valeurs.');
        $snippet13->setTags(['css', 'responsive', 'breakpoints', 'mobile-first']);
        $this->dm->persist($snippet13);

        // ========================================
        // 3- SNIPPETS BOB - Easter eggs backend
        // ========================================

        $snippet14 = new Snippet();
        $snippet14->setUserId((string) $bob->getId());
        $snippet14->setTitle('Debug requête N+1 avec Doctrine');
        $snippet14->setLanguage('php');
        $snippet14->setCode('// Problème N+1 (1 requête + N requêtes)
$users = $userRepository->findAll();
foreach ($users as $user) {
    echo count($user->getProjects()); // Requête SQL à chaque itération !
}

// Solution : JOIN avec fetch
$users = $entityManager->createQueryBuilder()
    ->select(\'u\', \'p\')
    ->from(User::class, \'u\')
    ->leftJoin(\'u.projects\', \'p\')
    ->getQuery()
    ->getResult();

// Ou utiliser findBy avec associations préchargées
// symfony console doctrine:query-log pour debugger');
        $snippet14->setDescription('Détecter et résoudre le problème N+1 queries : utiliser JOIN avec fetch, EXPLAIN ANALYZE en SQL, Symfony profiler. Peut diviser le temps de chargement par 100 !');
        $snippet14->setTags(['doctrine', 'performance', 'sql', 'n+1', 'optimization']);
        $this->dm->persist($snippet14);

        $snippet15 = new Snippet();
        $snippet15->setUserId((string) $bob->getId());
        $snippet15->setTitle('JWT decode sans bibliothèque');
        $snippet15->setLanguage('php');
        $snippet15->setCode('function decodeJWT(string $token): array
{
    $parts = explode(\'.\', $token);
    
    if (count($parts) !== 3) {
        throw new Exception(\'Invalid JWT format\');
    }
    
    [$header, $payload, $signature] = $parts;
    
    // Décoder payload (base64url)
    $decoded = json_decode(
        base64_decode(strtr($payload, \'-_\', \'+/\')), 
        true
    );
    
    // NE PAS UTILISER EN PROD sans vérifier signature !
    return $decoded;
}

// Pour vérifier signature : utiliser firebase/php-jwt');
        $snippet15->setDescription('Comprendre la structure JWT : header.payload.signature encodés en base64url. Attention : TOUJOURS vérifier la signature en production (utiliser une lib type firebase/php-jwt).');
        $snippet15->setTags(['php', 'jwt', 'security', 'decode', 'auth']);
        $this->dm->persist($snippet15);

        $snippet16 = new Snippet();
        $snippet16->setUserId((string) $bob->getId());
        $snippet16->setTitle('Prepared statements (bye SQL injection)');
        $snippet16->setLanguage('php');
        $snippet16->setCode('// DANGEREUX - Injection SQL possible
$email = $_GET[\'email\'];
$sql = "SELECT * FROM users WHERE email = \'$email\'";

// SÉCURISÉ - Prepared statement
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute([\'email\' => $email]);
$user = $stmt->fetch();

// Avec Doctrine (déjà sécurisé par défaut)
$user = $repository->findOneBy([\'email\' => $email]);

// Rule #1 : NEVER trust user input !');
        $snippet16->setDescription('Protection contre injection SQL : TOUJOURS utiliser prepared statements (PDO) ou ORM (Doctrine). Jamais de concaténation directe de variables utilisateur dans requêtes SQL.');
        $snippet16->setTags(['php', 'security', 'sql-injection', 'pdo', 'best-practice']);
        $this->dm->persist($snippet16);

        $snippet17 = new Snippet();
        $snippet17->setUserId((string) $bob->getId());
        $snippet17->setTitle('Docker one-liner magique');
        $snippet17->setLanguage('other');
        $snippet17->setCode('# Redémarrer container + clear cache + voir logs
docker-compose restart backend && docker exec myapp-backend php bin/console cache:clear && docker-compose logs -f backend

# Nettoyer TOUT Docker (dangereux !)
docker system prune -a --volumes

# Shell dans container
docker exec -it myapp-backend bash

# Stats temps réel containers
docker stats

# Rebuild sans cache
docker-compose build --no-cache && docker-compose up -d');
        $snippet17->setDescription('Commandes Docker utiles au quotidien : restart + cache clear, nettoyage système, accès shell container, monitoring ressources, rebuild complet sans cache.');
        $snippet17->setTags(['docker', 'devops', 'cli', 'productivity']);
        $this->dm->persist($snippet17);

        $snippet18 = new Snippet();
        $snippet18->setUserId((string) $bob->getId());
        $snippet18->setTitle('PHPUnit mock de service');
        $snippet18->setLanguage('php');
        $snippet18->setCode('use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    public function testUserCreation(): void
    {
        // Mock du repository
        $repository = $this->createMock(UserRepository::class);
        $repository->method(\'find\')
            ->willReturn(new User());
        
        // Mock du mailer
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method(\'send\');
        
        // Service avec dépendances mockées
        $service = new UserService($repository, $mailer);
        $result = $service->createUser(\'test@example.com\');
        
        $this->assertInstanceOf(User::class, $result);
    }
}');
        $snippet18->setDescription('Pattern de test avec mocks PHPUnit : createMock(), method(), willReturn(), expects(). Permet de tester une classe isolément sans dépendances réelles (DB, email, etc).');
        $snippet18->setTags(['phpunit', 'testing', 'mock', 'tdd', 'symfony']);
        $this->dm->persist($snippet18);

        $snippet19 = new Snippet();
        $snippet19->setUserId((string) $bob->getId());
        $snippet19->setTitle('Validation input backend (never trust)');
        $snippet19->setLanguage('php');
        $snippet19->setCode('use Symfony\Component\Validator\Constraints as Assert;

class CreateUserDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email;
    
    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 100)]
    #[Assert\Regex(
        pattern: \'/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/\',
        message: \'Password must contain lowercase, uppercase and digit\'
    )]
    public string $password;
}

// Dans le controller
$validator = $this->validator->validate($dto);
if (count($validator) > 0) {
    return $this->json($validator, 400);
}');
        $snippet19->setDescription('Validation stricte backend avec Symfony Validator : TOUJOURS valider côté serveur même si validation JS existe. Never trust user input = règle d\'or sécurité.');
        $snippet19->setTags(['symfony', 'validation', 'security', 'dto', 'backend']);
        $this->dm->persist($snippet19);

        // ========================================
        // 4- SNIPPETS CLARA - Progression débutante
        // ========================================

        $snippet20 = new Snippet();
        $snippet20->setUserId((string) $clara->getId());
        $snippet20->setTitle('Ma première fonction (addition)');
        $snippet20->setLanguage('javascript');
        $snippet20->setCode('function additionner(a, b) {
    const resultat = a + b;
    return resultat;
}

// Utilisation
const somme = additionner(5, 3);
console.log(somme); // Affiche : 8

// Version moderne (arrow function)
const add = (a, b) => a + b;
console.log(add(10, 20)); // Affiche : 30');
        $snippet20->setDescription('Ma toute première fonction ! Comprendre : paramètres (a, b), traitement (addition), valeur de retour. Les arrow functions c\'est plus court mais même principe.');
        $snippet20->setTags(['javascript', 'function', 'basics', 'débutant']);
        $this->dm->persist($snippet20);

        $snippet21 = new Snippet();
        $snippet21->setUserId((string) $clara->getId());
        $snippet21->setTitle('Boucle for pour afficher nombres');
        $snippet21->setLanguage('javascript');
        $snippet21->setCode('// Afficher nombres de 1 à 10
for (let i = 1; i <= 10; i++) {
    console.log(i);
}

// Parcourir un tableau
const fruits = [\'pomme\', \'banane\', \'orange\'];
for (let i = 0; i < fruits.length; i++) {
    console.log(fruits[i]);
}

// Méthode moderne (forEach)
fruits.forEach(fruit => {
    console.log(fruit);
});');
        $snippet21->setDescription('La boucle for pour répéter des actions : initialisation (i=1), condition (i<=10), incrémentation (i++). Très utile pour parcourir des tableaux !');
        $snippet21->setTags(['javascript', 'loop', 'for', 'array', 'basics']);
        $this->dm->persist($snippet21);

        $snippet22 = new Snippet();
        $snippet22->setUserId((string) $clara->getId());
        $snippet22->setTitle('addEventListener - réagir au clic');
        $snippet22->setLanguage('javascript');
        $snippet22->setCode('// Récupérer le bouton
const monBouton = document.getElementById(\'mon-bouton\');

// Écouter le clic
monBouton.addEventListener(\'click\', function() {
    alert(\'Bouton cliqué !\');
});

// Version avec arrow function
monBouton.addEventListener(\'click\', () => {
    console.log(\'Clic détecté\');
    // Faire quelque chose...
});

// Autres événements utiles : \'mouseover\', \'keypress\', \'submit\'');
        $snippet22->setDescription('addEventListener permet de réagir aux actions utilisateur (clic, survol, touche clavier...). C\'est la base de l\'interactivité en JavaScript !');
        $snippet22->setTags(['javascript', 'dom', 'events', 'click', 'interactive']);
        $this->dm->persist($snippet22);

        $snippet23 = new Snippet();
        $snippet23->setUserId((string) $clara->getId());
        $snippet23->setTitle('Bouton hover effect simple');
        $snippet23->setLanguage('css');
        $snippet23->setCode('.mon-bouton {
    background-color: #3498db;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.mon-bouton:hover {
    background-color: #2980b9; /* Plus foncé au survol */
}

.mon-bouton:active {
    transform: scale(0.95); /* Effet "enfoncé" au clic */
}');
        $snippet23->setDescription('Effet hover sur bouton : changer couleur au survol avec :hover, animation smooth avec transition. Le :active simule l\'effet "bouton enfoncé" au clic !');
        $snippet23->setTags(['css', 'hover', 'button', 'transition', 'ui']);
        $this->dm->persist($snippet23);

        $snippet24 = new Snippet();
        $snippet24->setUserId((string) $clara->getId());
        $snippet24->setTitle('Formulaire HTML avec validation');
        $snippet24->setLanguage('html');
        $snippet24->setCode('<form action="/submit" method="POST">
    <!-- Champ texte obligatoire -->
    <label for="nom">Nom :</label>
    <input type="text" id="nom" name="nom" required>
    
    <!-- Email avec validation -->
    <label for="email">Email :</label>
    <input type="email" id="email" name="email" required>
    
    <!-- Nombre avec min/max -->
    <label for="age">Âge :</label>
    <input type="number" id="age" name="age" min="18" max="99">
    
    <!-- Zone de texte -->
    <label for="message">Message :</label>
    <textarea id="message" name="message" rows="4" required></textarea>
    
    <button type="submit">Envoyer</button>
</form>');
        $snippet24->setDescription('Formulaire HTML avec validation native : required (obligatoire), type="email" (format email), min/max (nombres), textarea (texte long). Toujours associer label + input avec for/id !');
        $snippet24->setTags(['html', 'form', 'validation', 'input', 'accessibility']);
        $this->dm->persist($snippet24);

        // Flush MongoDB 
        $this->dm->flush();
        
        echo "\n 24 snippets chargés dans MongoDB\n";
        echo " Répartition : Anthony (7 pro), Alice (6 frontend), Bob (6 backend), Clara (5 débutant)\n";
    }

    public static function getGroups(): array
    {
        return ['snippet'];
    }
}