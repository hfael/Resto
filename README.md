# Projet Restaurant – PHP + Docker

Application scolaire en PHP sans framework backend, exécutée via Docker.

## Lancement
docker compose up -d --build

Application :
http://localhost:8080

phpMyAdmin :
http://localhost:8081  (root / root)

## Fonctionnement
- Le code PHP est dans /src
- Le point d’entrée est public/index.php
- Les contrôleurs sont appelés via : /controller/method
- La base MySQL est créée automatiquement depuis docker/mysql/init.sql
