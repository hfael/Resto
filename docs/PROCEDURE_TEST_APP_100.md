# Procedure de test complete (Web + API + Mobile)

Date: 2026-03-05

Objectif: tester 100% des fonctionnalites de l'application pour la soutenance Bloc 3.

Definition de "100%" dans ce document:
- 100% des exigences du sujet couvertes.
- 100% des routes API couvertes (auto + manuel).
- 100% des parcours Web et Mobile critiques verifies.

## 1) Prerequis

- Docker Desktop actif
- Python (pour `pytest`)
- Flutter 3.38+ (pour tests mobile manuels)
- Projet lance depuis la racine `Resto`

Commandes de base:

```powershell
docker compose up --build -d
```

URLs utiles:
- Web: `http://localhost:8080`
- API: `http://localhost:8080/api`
- Mailhog: `http://localhost:8025`
- PhpMyAdmin: `http://localhost:8081`

## 2) Preparation des donnees de test

### 2.1 Option recommandee: seed admin + reset via API

```powershell
$headers = @{ "X-Seed-Key" = "devseed"; "Content-Type" = "application/json" }
$body = @{
  type = "user"
  role = "admin"
  username = "admin_test"
  email = "admin_test@example.com"
  password = "password123"
  reset = $true
} | ConvertTo-Json
Invoke-RestMethod -Uri "http://localhost:8080/api/test/seed" -Method Post -Headers $headers -Body $body
```

Resultat attendu:
- Reponse `201`
- Un token admin est retourne
- Le compte admin web est `admin_test@example.com / password123`

### 2.2 Si la base est vide/non initialisee

```powershell
Get-Content .\docker\mysql\init.sql | docker compose exec -T mysql mysql -uapp -papp resto
```

## 3) Campagne A - Tests automatiques API (obligatoire)

Depuis la racine du projet:

```powershell
python -m pip install -r requirements.txt
$env:SEED_KEY="devseed"
pytest -q
```

Resultat attendu:
- `21 passed`
- Aucun test en echec

Preuve a garder:
- capture ecran du terminal avec le resultat final

## 4) Campagne B - Tests Web manuels

## B.1 Authentification

1. `WEB-AUTH-01` Inscription valide
- Etapes: aller sur `/register/index`, saisir username/email/password valides, soumettre
- Attendu: redirection vers `/login/index`

2. `WEB-AUTH-02` Inscription invalide (email)
- Etapes: email invalide
- Attendu: message d'erreur, pas de creation

3. `WEB-AUTH-03` Connexion valide
- Etapes: login avec compte cree
- Attendu: redirection `/home/index`, session active

4. `WEB-AUTH-04` Connexion invalide
- Etapes: mauvais mot de passe
- Attendu: message "Mot de passe incorrect."

5. `WEB-AUTH-05` Deconnexion
- Etapes: clic deconnexion
- Attendu: retour `/login/index`, session supprimee

## B.2 CRUD restaurants (front web)

1. `WEB-RESTO-01` Creation restaurant complete
- Etapes: `/restaurant/create`, remplir tous les champs, selectionner latitude/longitude via la carte, uploader image
- Attendu: redirection `/restaurant/my`, statut `pending`

2. `WEB-RESTO-02` Validation email contact
- Etapes: creation avec email invalide
- Attendu: erreur et pas de creation

3. `WEB-RESTO-03` Mail de confirmation creation
- Etapes: apres creation, ouvrir Mailhog
- Attendu: mail recu avec tous les champs + lien details

4. `WEB-RESTO-04` Annulation restaurant pending
- Etapes: bouton `Annuler` dans `/restaurant/my`
- Attendu: statut `cancelled`

5. `WEB-RESTO-05` Validation admin
- Etapes: compte admin, `/admin/restaurants`, accepter un restaurant
- Attendu: statut `accepted`, visible en public

6. `WEB-RESTO-06` Refus admin avec motif
- Etapes: refuser en saisissant un motif
- Attendu: statut `rejected`, motif visible cote proprietaire

7. `WEB-RESTO-07` Renvoyer un restaurant refuse
- Etapes: proprietaire clique `Corriger et renvoyer`, modifie, soumet
- Attendu: statut repasse en `pending`

8. `WEB-RESTO-08` Edition restaurant accepte
- Etapes: modifier un restaurant accepte
- Attendu: donnees mises a jour

9. `WEB-RESTO-09` Suppression restaurant
- Etapes: supprimer depuis `/restaurant/my`
- Attendu: restaurant absent de la liste

10. `WEB-RESTO-10` Recherche AJAX
- Etapes: `/restaurant/index`, taper dans la barre recherche
- Attendu: resultats filtres sans rechargement de page

11. `WEB-RESTO-11` Export PDF
- Etapes: `/restaurant/show?id=...`, clic `Exporter en PDF`
- Attendu: PDF telecharge/ouvre, contient les infos du restaurant

## B.3 Reservations web

1. `WEB-RESA-01` Reservation depuis detail restaurant
- Etapes: page detail, saisir date/heure, reserver
- Attendu: entree dans `/reservation/index`, code affiche

2. `WEB-RESA-02` Mail de confirmation reservation
- Etapes: verifier Mailhog
- Attendu: mail present avec date/heure/code

3. `WEB-RESA-03` Suppression de sa reservation
- Etapes: `/reservation/index`, supprimer
- Attendu: reservation retiree

4. `WEB-RESA-04` Vue reservations proprietaire
- Etapes: `/reservation/byRestaurant?id=...`
- Attendu: liste clients/date/heure/code

5. `WEB-RESA-05` Suppression reservation par proprietaire/admin
- Etapes: depuis la vue bookings, supprimer une reservation
- Attendu: reservation retiree

## B.4 Securite web

1. `WEB-SEC-01` CSRF bloque sans token
- Etapes: via DevTools, supprimer champ `_csrf` d'un formulaire POST, soumettre
- Attendu: HTTP 403 et message `Token CSRF invalide.`

2. `WEB-SEC-02` XSS (sortie echappee)
- Etapes: injecter `<script>alert(1)</script>` dans nom/description
- Attendu: script non execute, texte affiche/echappe

3. `WEB-SEC-03` SQL injection (champ recherche)
- Etapes: saisir `' OR 1=1 --` dans recherche
- Attendu: pas de crash SQL, pas de fuite stack trace

4. `WEB-SEC-04` Protection routes admin
- Etapes: acceder `/admin/restaurants` avec utilisateur non admin
- Attendu: redirection login/home, acces refuse

## B.5 Responsive + accessibilite (mini RGAA)

1. `WEB-UX-01` Responsive mobile
- Etapes: largeur 375px
- Attendu: contenu lisible sans blocage majeur

2. `WEB-UX-02` Responsive desktop
- Etapes: largeur >= 1280px
- Attendu: mise en page stable

3. `WEB-UX-03` Navigation clavier
- Etapes: `Tab` dans login/register/create/edit
- Attendu: tous les champs accessibles

4. `WEB-UX-04` Labels formulaires
- Etapes: verifier association labels/champs
- Attendu: labels visibles sur formulaires principaux

## 5) Campagne C - Tests API manuels (Postman/Insomnia)

Note: la suite `pytest` couvre deja fortement l'API. Cette campagne est une verification manuelle de demonstration.

## C.1 Auth

- `POST /auth/register` (ok + erreurs)
- `POST /auth/login` (ok + invalid_credentials)
- `GET /auth/me` (avec/sans token)
- `POST /auth/logout`

## C.2 Restaurants

- `GET /restaurants` (public)
- `GET /restaurants?page=1&per_page=10` (pagination)
- `GET /restaurants/search?q=...`
- `GET /restaurants/{id}`
- `POST /restaurants` (multipart + photo)
- `POST /restaurants/{id}` et `PUT /restaurants/{id}`
- `DELETE /restaurants/{id}`
- `POST|PUT /restaurants/{id}/cancel`
- `POST /restaurants/{id}/accept` (admin)
- `POST /restaurants/{id}/reject` (admin, reason obligatoire)
- `GET /restaurants/mine`
- `GET /restaurants/pending` (admin)
- `GET /restaurants/{id}/bookings` (owner/admin)

## C.3 Reservations

- `POST /reservations`
- `GET /reservations/user`
- `GET /reservations/restaurant/{id}`
- `DELETE /reservations/{id}`

## C.4 PDF

- `GET /restaurants/{id}/pdf`
- `GET /pdf/restaurant?id={id}`

## C.5 Securite API

- Sans JWT sur routes protegees => `401`
- Mauvais format `Authorization` => `401 invalid_format`
- Token invalide => `401 invalid_token`
- User non admin sur routes admin => `403`

## 6) Campagne D - Tests Mobile manuels (Flutter)

Lancer l'app:

```powershell
cd mobile_flutter
flutter pub get
flutter run
```

## D.1 Auth + session

1. `MOB-AUTH-01` Login valide
- Attendu: arrivee sur HomeScreen

2. `MOB-AUTH-02` Login invalide
- Attendu: snackbar erreur

3. `MOB-AUTH-03` Register valide
- Attendu: compte cree + session ouverte

4. `MOB-AUTH-04` Persistance session
- Etapes: fermer/reouvrir app
- Attendu: utilisateur reste connecte

5. `MOB-AUTH-05` Logout
- Attendu: retour ecran login

## D.2 Restaurants mobile

1. `MOB-RESTO-01` Liste restaurants + refresh
- Attendu: chargement correct

2. `MOB-RESTO-02` Pagination au scroll 80%
- Etapes: scroller proche fin liste
- Attendu: chargement page suivante

3. `MOB-RESTO-03` Recherche
- Attendu: liste filtree

4. `MOB-RESTO-04` Detail restaurant
- Attendu: tous les champs visibles + map

5. `MOB-RESTO-05` Export PDF
- Etapes: bouton PDF
- Attendu: ouverture externe PDF

6. `MOB-RESTO-06` Creation restaurant
- Etapes: photo + champs + coords
- Attendu: creation OK

7. `MOB-RESTO-07` Edition restaurant
- Attendu: sauvegarde OK

8. `MOB-RESTO-08` Suppression restaurant
- Attendu: element retire de la liste

## D.3 Device components

1. `MOB-DEV-01` Camera
- Etapes: prendre photo depuis formulaire
- Attendu: preview + envoi OK

2. `MOB-DEV-02` Geolocalisation
- Etapes: bouton position du telephone
- Attendu: latitude/longitude remplies

3. `MOB-DEV-03` Geoloc refusee
- Etapes: refuser permission
- Attendu: message erreur, app ne crash pas

## D.4 Reservations mobile

1. `MOB-RESA-01` Creer reservation depuis detail
- Attendu: message avec code

2. `MOB-RESA-02` Liste mes reservations
- Attendu: reservation visible

## D.5 Admin mobile

1. `MOB-ADM-01` Voir pending (admin)
- Attendu: liste pending chargee

2. `MOB-ADM-02` Accepter pending
- Attendu: element disparait de pending

3. `MOB-ADM-03` Refuser pending sans motif
- Attendu: blocage + message motif obligatoire

4. `MOB-ADM-04` Refuser pending avec motif
- Attendu: refus applique

## 7) Traceabilite Sujet -> Tests

- CRUD Web: `WEB-RESTO-*`, `WEB-RESA-*`
- Requete AJAX: `WEB-RESTO-10`
- API JWT: `C.5` + `pytest`
- CSRF: `WEB-SEC-01`
- SQL injection: `WEB-SEC-03`
- XSS: `WEB-SEC-02`
- Mail creation entite: `WEB-RESTO-03`
- PDF: `WEB-RESTO-11` + `C.4`
- Mobile 3 vues + navigation + widgets: `D.*`
- Scroll 80% + pagination: `MOB-RESTO-02`
- Geolocalisation + camera: `MOB-DEV-01`, `MOB-DEV-02`

## 8) Criteres de sortie (Go / No-Go)

Go si:
- `pytest -q` = 100% vert
- Tous les cas critiques Web/Mobile passes
- Aucun bug bloquant/majeur ouvert
- Toutes les exigences sujet cochees

No-Go si:
- Echec sur auth, creation entite, reservation, admin moderation, PDF, JWT
- Echec sur geoloc/camera mobile
- Echec sur securite CSRF/JWT

## 9) Mode preuve pour la soutenance

Pour chaque campagne, conserver:
- captures ecran (web/mobile/mailhog/postman)
- sortie terminal `pytest -q`
- mini tableau de suivi:

```text
ID_CASE | STATUT (OK/KO) | PREUVE | COMMENTAIRE
WEB-RESTO-01 | OK | capture_001.png | RAS
...
```

