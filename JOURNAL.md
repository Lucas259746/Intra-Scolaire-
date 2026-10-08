# Historique des commandes Symfony utilisées:

### 2026/05/06 - Cosmin (deprecated, on a refait la DB)

- php bin/console make:user
  - User (name)
  - yes (doctrine)
  - email (unique display property)
  - yes (hash passwords)

- php bin/console make:entity User
  - firstname (string 255 no)
  - lastname (string 255 no)
  - role (enum App\Enum\UserRole yes no)
  - phone_number (string 20 no)
  - parent_phone_number (string 20 no)
  - parent_email (string 255 no)

- php bin/console make:migration
- php bin/console doctrine:migrations:migrate (yes)

### 2026/05/07 - Cosmin (deprecated, on a refait la DB)

- php bin/console make:entity classes
  - name (string 255 no)
  - professor_id (relation user ManyToOne no yes)

- php bin/console make:entity class_users
  - user_id (relation user ManyToOne no no)
  - class_id (relation classes ManyToOne no no)

- php bin/console make:entity grades
  - class_id (relation classes ManyToOne no no)
  - student_id (relation classes ManyToOne no no)
  - grade (string 20 no)
  - comments (text yes)
  - updated_at (datetime_immutable no)

- php bin/console make:entity documents
  - user_id (relation user ManyToOne no no)
  - type (enum App\Enum\DocumentType no yes)
  - title (string 255 no)
  - path (string 255 no)

- php bin/console make:entity absences
  - user_id (relation user ManyToOne no no);
  - proof (relation documents ManyToOne yes no)
  - start_date (datetime no)
  - end_date (datetime yes)

- php bin/console make:entity user_actions
  - user_id (relation user ManyToOne no no);
  - ip (string 20 yes)
  - action (text no)
  - created_at (datetime_immutable no)

- php bin/console make:entity notifications
  - title (string 255 no)
  - message (text no)
  - type (enum App\Enum\NotificationType yes no)

- php bin/console make:entity notification_recipients
  - notification_id (relation notifications ManyToOne no no)
  - user_id (relation user ManyToOne no no)
  - is_read (boolean no)
  - read_at (datetime_immutable yes)

- php bin/console make:entity homework
  - class_id (relation classes no no)
  - title (string 255 no)
  - description (text no)
  - due_date (datetime no)

- php bin/console make:entity schedules
  - class_id (relation classes ManyToOne no no)
  - day_of_week (enum App\Enum\Weekdays no no)
  - start_time (time no)
  - end_time (time no)
  - room (string 255 no)

- php bin/console make:entity textbooks
  - class_id (relation classes ManyToOne no no)
  - title (string 255 no)
  - content (text no)

- php bin/console make:entity class_documents
  - class_id (relation classes ManyToOne no no)
  - document_id (relation documents ManyToOne no no)
  - title (string 255 no)
  - visibility (boolean no)

- php bin/console make:migration
- php bin/console doctrine:migrations:migrate

### 2026/05/11 - Cosmin (deprecated, on a refait la DB)

J'ai supprimé 'le colonne "roles" du User et adapté la colonne "role" pout qu'elle correspond aux critéres de sécurité Symfony.

### 2026/05/11 - Khaly

- php bin/console make:auth
  - Login form authenticator
  - SecurityController (name)
  - app_home (redirect after login)
  - yes (logout support)

- php bin/console make:registration-form
  - yes (UniqueEntity)
  - app_home (redirect after registration)
  - no (PHPUnit tests)

- php bin/console make:controller HomeController

### 2026/05/12 - Cosmin

Je nettoie le projet pour refaire toutes la DB plus adapté au enseignement supérieur.
On a refait une schéma DB.

### 2026/05/13 - Cosmin

Ajouté ROLE_STUDENT, ROLE_TEACHER et ROLE_ADMIN dans security.yaml.
Pour les utiliser:

- Controller:

#[IsGranted('ROLE_TEACHER')]
public function editExam(): Response
{
// Code
}

- Routes:
  access_control: - { path: ^/admin, roles: ROLE_ADMIN } - { path: ^/teacher, roles: ROLE_TEACHER } - { path: ^/student, roles: ROLE_STUDENT } // dans le security.yaml

- Twig:

{% if is_granted('ROLE_ADMIN') %}
<a href="/admin/settings">System Settings</a>
{% endif %}

Pour assigner un role dans le controller:

'''$user = new User();
$user->setEmail('prof@university.edu');
$user->setRoles(['ROLE_TEACHER']); // This is an array'''

J'ai aussi ajouté les enums (voir src/Enum).

### 2026/05/13 - Amad

- Ajout des champs manquants dans `RegistrationFormType.php` :
  - `phone_number` (TextType, optionnel, avec contrainte Regex `/^\+?[0-9]{7,15}$/` pour n'autoriser que les chiffres)
  - `github` (TextType, optionnel)
  - `google_drive` (TextType, optionnel)

- Ajout des champs correspondants dans `templates/registration/register.html.twig`

- Correction des `mappedBy` incorrects dans les entités (les noms référençaient les anciennes propriétés snake_case type `user_id`, `professor_id` au lieu des noms camelCase actuels) :
  - `User.php` : 7 corrections (`professor_id` → `professor`, `user_id` → `user`, `student_id` → `student`)
  - `Promotions.php` : 2 corrections (`promotion_id` → `promotion`, `prmotion_id` → `promotion`)
  - `Documents.php` : `document_id` → `document`
  - `Notifications.php` : `notification_id` → `notification`
  - `Projects.php` : `project_id` → `project`

- Correction de l'erreur `column t0.id does not exist` :
  `user` est un mot réservé PostgreSQL. Ajout de `#[ORM\Table(name: '"user"')]` dans `User.php` pour forcer le quoting du nom de table dans les requêtes SQL.
### 2026/05/13 - Josselin

 Journalisation des accès et actions sensibles (RGPD)

Créé manuellement (pas de commande make: disponible) :

- src/Service/ActionLogger.php
  - Service qui enregistre une action en base (user + action + horodatage)
  - Utilisé par AuthenticationListener et les contrôleurs

- src/EventListener/AuthenticationListener.php
  - Écoute LoginSuccessEvent et LogoutEvent de Symfony
  - Log automatiquement LOGIN et LOGOUT via ActionLogger

- src/Command/PurgeLogsCommand.php
  - php bin/console app:purge-logs
  - Supprime les logs UserActions de plus de 12 mois 

- src/Repository/UserActionsRepository.php
  - Ajout de la méthode deleteOlderThan(\DateTimeImmutable $before): int

### 2026/05/13 - Amad (suite)

- CRUD généré via `make:crud` pour les entités suivantes :
  - `Promotions` → PromotionsController + PromotionsType + templates/promotions/
  - `PromotionUsers` → PromotionUsersController + PromotionUsersType + templates/promotion_users/
  - `Absences` → AbsencesController + AbsencesType + templates/absences/
  - `Grades` → GradesController + GradesType + templates/grades/
  - `Projects` → ProjectsController + ProjectsType + templates/projects/

- Correction des `choice_label` générés avec `'id'` par défaut dans tous les FormType :
  - Relations User → `fn(User $u) => $u->getFirstname() . ' ' . $u->getLastname()`
  - Relations Promotions → `'name'`
  - Relations Projects → `'title'`
  - Relations Documents → `'title'`

- Ajout des types corrects pour les champs date (`DateTimeType`, `widget: single_text`), booléen (`CheckboxType`) et enum (`EnumType`) dans les FormType concernés

- Mise à jour des templates `index.html.twig` pour afficher les relations (nom du prof, de l'étudiant, de la promo) au lieu de l'id brut

### 2026/05/14 - Josselin

#### Contrôle d'accès par rôle

Ajout de `#[IsGranted]` sur les 6 controllers CRUD qui n'avaient aucune restriction de rôle.
Un utilisateur authentifié pouvait créer/modifier/supprimer des notes, promotions, projets, etc.

Règles appliquées :

- `GradesController`, `ProjectsController`, `PromotionsController`, `AbsencesController` :
  tout utilisateur connecté (`ROLE_USER`) peut consulter, mais seul un prof (`ROLE_TEACHER`) peut créer, modifier ou supprimer.

- `DocumentsController` :
  tout utilisateur connecté peut consulter, mais seul un prof (`ROLE_TEACHER`) peut uploader, modifier ou supprimer.

- `PromotionUsersController` (gestion des inscriptions en promotion) :
  seul un prof (`ROLE_TEACHER`) peut consulter, seul un admin (`ROLE_ADMIN`) peut modifier les inscriptions.

Fichiers modifiés : `GradesController.php`, `ProjectsController.php`, `PromotionsController.php`, `AbsencesController.php`, `DocumentsController.php`, `PromotionUsersController.php`

####  Validation MIME des fichiers uploadés

Ajout d'une vérification `getMimeType()` côté controller dans les actions `new` et `edit` de `DocumentsController`.
`getMimeType()` lit les magic bytes du fichier — impossible à contourner côté client, contrairement à l'extension ou au Content-Type déclaré.

Fichiers modifiés : `DocumentsController.php`


Optimisation des performances Docker sur Windows (`docker-compose.yml`)

**Problème** : Sur Windows, Docker monte les fichiers via un bind-mount NTFS → Linux, ce qui génère des I/O lentes. Les dossiers `vendor/` (milliers de fichiers Composer) et `var/` (cache Symfony) étaient particulièrement impactés, causant des temps de chargement de 30-40 secondes.

**Solution appliquée** : Remplacement des bind-mounts par des volumes Docker natifs pour `vendor/` et `var/`.

Modification dans `docker-compose.yml` :
- Ajout du volume nommé `app_vendor` monté sur `/var/www/html/vendor`
- Le volume `app_cache` existait déjà pour `/var/www/html/var`
- Déclaration du volume `app_vendor` dans la section `volumes:` racine

Ces volumes vivent dans le filesystem interne de Docker (Linux natif), sans synchronisation avec Windows, ce qui élimine la latence I/O.

**À faire après un `git pull`** :
```bash
docker compose down
docker compose up -d --build
docker compose exec app composer install
```
Le `composer install` est obligatoire au premier démarrage pour peupler le volume `app_vendor` qui démarre vide.

### 2026/05/16 - Josselin



**Correct-1 — `User.addDocument()` / `removeDocument()` appelaient des méthodes inexistantes**

`User.php` appelait `$document->setUserId()` et `getUserId()` alors que l'entité `Documents` avait été refactorée pour exposer `setUser()` / `getUser()`. Correction dans les deux méthodes.

Fichier modifié : `src/Entity/User.php`

**Correct-2 — `Grades.update_history` : colonne NOT NULL remplie manuellement via le form**

La colonne `update_history` est non-nullable en base (`#[ORM\Column]` sans `nullable: true`) mais le champ était exposé dans `GradesType` avec `required: false`. Soumettre le formulaire sans le remplir provoquait une erreur DB.

Correction :
- Suppression du champ `update_history` du formulaire `GradesType.php`
- Auto-remplissage via `$grade->setUpdateHistory(new \DateTime())` dans `GradesController` avant `persist()` (création) et avant `flush()` (modification)
- Suppression de l'import `DateTimeType` devenu inutile dans `GradesType.php`

Fichiers modifiés : `src/Form/GradesType.php`, `src/Controller/GradesController.php`

**Correct-3 — Typo `getPrmotionId()` / `setPrmotionId()` propagée sur 3 fichiers**

Un "o" manquant dans "Promotion" lors de la génération make:crud. Renommé en `getPromotionId()` / `setPromotionId()` et mis à jour partout.

Fichiers modifiés : `src/Entity/Projects.php`, `src/Entity/Promotions.php`, `src/Form/ProjectsType.php`

**Correct-4 — Mapping Doctrine sur `Absences.document`**

`Absences.php` déclarait `inversedBy: 'absences'` sur la relation vers `Documents`, mais `Documents` n'a pas de collection `$absences`. Mapping invalide pouvant causer une `MappingException` Doctrine. Remplacé par une relation unidirectionnelle (suppression de `inversedBy`).

Fichier modifié : `src/Entity/Absences.php`




**Correct-5 — Filtrage des données par rôle dans les listes**

Les index de Grades, Absences, Projects et Promotions appelaient `findAll()` sans restriction : un étudiant voyait les notes et absences de tous les autres utilisateurs.

Ajout de méthodes filtrées dans les repositories :
- `findByStudent(User)` : retourne uniquement les données liées à l'utilisateur connecté
- `findByTeacher(User)` : retourne uniquement les données des promotions dont l'utilisateur est professeur
- `findAll()` reste utilisé pour les admins uniquement

Logique ajoutée dans les controllers : ROLE_ADMIN → tout, ROLE_TEACHER → ses données, autre → ses données personnelles.

Fichiers modifiés : `src/Repository/GradesRepository.php`, `src/Repository/AbsencesRepository.php`, `src/Repository/ProjectsRepository.php`, `src/Repository/PromotionsRepository.php`, `src/Controller/GradesController.php`, `src/Controller/AbsencesController.php`, `src/Controller/ProjectsController.php`, `src/Controller/PromotionsController.php`

**Correct-06 — Vérification d'appartenance sur les promotions**

N'importe quel professeur pouvait modifier ou supprimer la promotion d'un autre prof en changeant l'ID dans l'URL. Ajout d'un contrôle d'appartenance dans `edit()` et `delete()` de `PromotionsController` : si l'utilisateur n'est pas admin et n'est pas le professeur de la promotion, une exception 403 est levée.

Fichier modifié : `src/Controller/PromotionsController.php`

**Correct-7 — Filtrage des sélecteurs EntityType par rôle**

Les formulaires de création de promotions, notes et absences listaient tous les utilisateurs sans distinction de rôle . Ajout d'un `query_builder` avec filtre `LIKE '%ROLE_TEACHER%'` ou `LIKE '%ROLE_STUDENT%'` selon le contexte.

Fichiers modifiés : `src/Form/PromotionsType.php`, `src/Form/GradesType.php`, `src/Form/AbsencesType.php`


### 2026/05/17 - Josselin

#### Filtrage des sélecteurs de projet et de document

**Sélecteur `project` dans `GradesType`**

Le formulaire de création de note listait tous les projets de toutes les promotions. Un professeur pouvait attribuer une note sur un projet d'un collègue. Ajout d'un `query_builder` avec `current_user` passé en option depuis le controller : un prof ne voit que les projets de ses propres promotions, un admin voit tout.

Fichiers modifiés : `src/Form/GradesType.php`, `src/Controller/GradesController.php`

**Sélecteur `document` dans `AbsencesType`**

Le formulaire de saisie d'absence listait tous les documents uploadés par tous les utilisateurs. Ajout d'un `query_builder` filtrant uniquement les documents appartenant à des étudiants (`ROLE_STUDENT`). Le `choice_label` affiche désormais `"Nom Prénom — Titre"` pour identifier l'étudiant propriétaire du justificatif.

Fichier modifié : `src/Form/AbsencesType.php`

#### Pagination sur Grades, Absences, Projects, Promotions

La pagination KnpPaginator était uniquement présente sur Documents. Les autres listes appelaient `findAll()` sans aucune limite.

Ajout de `PaginatorInterface` dans les 4 controllers et ajout de `{{ knp_pagination_render(...) }}` dans les 4 templates. Limite fixée à 10 éléments par page.

Fichiers modifiés : `src/Controller/GradesController.php`, `src/Controller/AbsencesController.php`, `src/Controller/ProjectsController.php`, `src/Controller/PromotionsController.php`, `templates/grades/index.html.twig`, `templates/absences/index.html.twig`, `templates/projects/index.html.twig`, `templates/promotions/index.html.twig`

### 2026/05/18 - Josselin

#### correc 1  — Comparaison d'objet dans `NotificationsController.markRead()`

`markRead()` vérifiait l'appartenance du destinataire avec `$recipient->getUserId() !== $this->getUser()` — comparaison de références d'objets PHP, pas d'IDs. Même bogue que SEC-02 (PromotionsController). Dans un seul cycle de requête Doctrine l'identity map garantit généralement la même référence, mais c'est fragile et non garanti.

Correction : comparaison des IDs entiers.

```php
// Avant
if ($recipient->getUserId() !== $this->getUser())

// Après
if ($recipient->getUserId()?->getId() !== $this->getUser()->getId())
```

Fichier modifié : `src/Controller/NotificationsController.php`

#### correct 2 — `show()` sans vérification d'appartenance dans `NotificationsController`

`show()` acceptait n'importe quel `ROLE_USER` sur `/notifications/{id}` même si l'utilisateur n'était pas destinataire de la notification. Ajout d'un contrôle via `findOneBy(['notification' => ..., 'user' => ...])` : si aucun `NotificationRecipients` n'existe pour cet utilisateur, une exception 403 est levée.

Fichier modifié : `src/Controller/NotificationsController.php`


#### correct 2 — Ciblage des notifications par audience et par rôle expéditeur

Le formulaire de création envoyait la notification à tous les utilisateurs (`findAll()`), sans distinction de rôle ni de promotion.

**Champs ajoutés dans `NotificationsType` (`mapped: false`) :**
- `audience` (ChoiceType) : choix du groupe destinataire
- `promotion` (EntityType, optionnel) : promotion cible si `audience = promotion`

**Logique d'audience selon le rôle de l'expéditeur :**

| Expéditeur | Choix disponibles | Promotions visibles |
|---|---|---|
| Admin | Tous / Étudiants / Professeurs / Une promotion | Toutes |
| Enseignant | Une promotion uniquement | Les siennes seulement |

Dans `NotificationsController::new()`, un `match` sur `audience` choisit les destinataires :
- `all` → `findAll()`
- `students` → `findByRole('ROLE_STUDENT')`
- `teachers` → `findByRole('ROLE_TEACHER')`
- `promotion` → membres via `getpromotionUsers()`

Validation serveur : un enseignant qui tenterait d'envoyer à une autre audience ou à une promotion ne lui appartenant pas reçoit un 403.

Ajout de `findByRole(string $role): array` dans `UserRepository`.

Fichiers modifiés : `src/Form/NotificationsType.php`, `src/Controller/NotificationsController.php`, `src/Repository/UserRepository.php`

#### correct 3 — Pagination de la liste des notifications (5 par page)

`index()` chargeait toutes les notifications d'un utilisateur sans limite via `findByUser()`. Ajout de KnpPaginator avec une limite de 5 par page et `{{ knp_pagination_render(recipients) }}` dans le template.

Fichiers modifiés : `src/Controller/NotificationsController.php`, `templates/notifications/index.html.twig`

