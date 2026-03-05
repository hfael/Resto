# Projet Bloc 3 - Resto

Projet d'ecole (niveau bac+2) pour le Bloc 3:
- Site web PHP (MVC sans framework back-end)
- API REST PHP
- Application mobile Flutter

## Fonctionnalites principales
- Authentification web (session) + roles (`user`, `admin`)
- CRUD restaurants (create/read/update/delete)
- Validation admin des restaurants (accept/refuse)
- Recherche AJAX sur la liste restaurants
- Export PDF d'un restaurant
- Reservation + envoi de mail de confirmation
- API JWT pour l'appli mobile
- Appli Flutter avec liste, details, creation, reservations
- Geolocalisation et camera dans l'appli mobile

## Conformite sujet/grille
- Champs imposes de l'entite presents:
  - `name` (varchar), `description` (text), `event_date` (date), `average_price` (int)
  - `latitude`, `longitude`, `contact_name`, `contact_email`, `photo`
- Mail de confirmation envoye a la creation de l'entite avec lien de details
- Pagination API exploitee par le scroll 80% dans Flutter
- CSRF ajoute sur les formulaires web sensibles

## Lancement rapide
- Docker:
  - `docker compose up --build`
- Web:
  - `http://localhost:8080`
- API:
  - `http://localhost:8080/api`
- Mail test:
  - `http://localhost:8025`

## Tests
- Tests API Python:
  - `pytest -q`

## Notes
- Projet volontairement simple et lisible, sans architecture complexe.
