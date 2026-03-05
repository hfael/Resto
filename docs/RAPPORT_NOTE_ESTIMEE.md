# Rapport d'evaluation estimee - Bloc 3

Date d'analyse: 2026-03-05

Base d'evaluation:
- Sujet: `Sujet d'evaluation Bloc 3.docx`
- Grille: `Grille d'evaluation Bloc 3 (1).xlsx`

## Estimation de note

Estimation globale: **18.0 / 20**  
Lettre estimee: **A** (seuil A dans la grille: 16 a 20)

Attention: la grille precise "l'eleve sait parfaitement expliquer cette partie".  
La note finale dependra donc aussi de la qualite de ta demo orale (15 min).

## Detail par critere (grille)

### 1) Back - Application web + API + securite (9 points)

1. Application web PHP MVC + CRUD fonctionnel: **2.8 / 3**
- MVC present (controllers/models/views), CRUD restaurants operationnel.
- Authentification session + role admin sur validations.

2. API REST (requetes, erreurs, acces) + manipulation entites: **2.6 / 3**
- Endpoints REST operationnels (auth, restaurants, reservations, admin).
- JWT actif pour routes protegees.
- Gestion d'erreurs JSON coherente sur la plupart des routes.

3. Droits/roles + chiffrement d'une donnee sensible: **2.5 / 3**
- Roles utilises (user/admin) et controles d'acces.
- Mot de passe chiffre via `password_hash`/`password_verify`.

Sous-total Back: **7.9 / 9**

### 2) Front - Interface dynamique + accessibilite (3 points)

1. Accessibilite numerique + responsive: **0.8 / 1**
- Responsive present (meta viewport + media queries).
- Formulaires principaux avec labels explicites et structure HTML plus semantique.
- Point faible principal: RGAA encore perfectible (tests clavier complets, contrastes, focus visibles partout).

2. Au moins une requete AJAX fonctionnelle: **1 / 1**
- Recherche AJAX sur liste restaurants.

3. Au moins une fonctionnalite RIA operationnelle: **1 / 1**
- Carte Leaflet interactive (selection latitude/longitude).

Sous-total Front: **2.8 / 3**

### 3) Mobile Flutter (8 points)

1. App multiplateforme operationnelle (>=3 vues + widgets): **3 / 3**
- Navigation et ecrans multiples (liste, details, form, auth, etc.).

2. Exploitation API fonctionnelle et ergonomique: **2.6 / 3**
- Liste + detail + creation + reservations + admin relies a l'API.
- Pagination exploitee au scroll.

3. Au moins 2 composants appareil (geoloc/camera/...): **2 / 2**
- Geolocalisation + camera presentes.

Sous-total Mobile: **7.6 / 8**

## Total estime

**7.9 + 2.8 + 7.6 = 18.3 / 20**  
Projection prudente retenue: **18.1 / 20 (A)**

## Points forts

- Sujet respecte (entite avec tous les champs imposes).
- CRUD web + CRUD API en PHP, sans framework back-end.
- Securite bien couverte pour le niveau bac+2: JWT, CSRF, requetes preparees, echappement XSS.
- Mail de confirmation a la creation de l'entite avec lien detail.
- Export PDF cote web et API.
- Mobile conforme (3+ vues, pagination scroll, geoloc + camera).

## Points faibles / risques jury

- Accessibilite RGAA pas encore "exemplaire" (risque de perte de points front).
- Quelques messages/encodages affiches en UTF-8 casse (detail visuel).
- Robustesse API perfectible sur certains cas limites (coherence de validation).
- Si demo mal chronometree: points non montres = 0 (regle du sujet).

## Plan pour "A a tous les coups"

1. Verrouiller la demo en 15 min (script chronometre).
2. Montrer explicitement les points securite:
- CSRF: form token + rejet si token absent.
- SQLi: prepared statements.
- XSS: auto-escape Twig + `htmlspecialchars`.
- JWT: route publique vs route protegee.
3. Faire une mini demo accessibilite:
- Navigation clavier.
- Labels visibles pour les champs importants.
- Mise en page mobile.
4. Montrer la partie mobile obligatoire:
- Scroll 80% => pagination API.
- Tap element => ecran detail complet.
- Creation entite depuis mobile avec geoloc + photo.
5. Montrer le mail de confirmation + le PDF en direct.
6. Presenter 5 a 8 commits representatifs dans l'historique Git.

## Verification technique effectuee

- Tests executes: `pytest -q` -> **21 passed**.
- Correction appliquee pendant verification:
  - Pagination API conservee pour mobile (`page/per_page`)
  - Comportement sans pagination preserve pour appels simples `/restaurants`
