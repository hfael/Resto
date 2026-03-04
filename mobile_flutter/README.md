# Resto Mobile (Flutter)

Application Flutter simple qui reproduit les fonctionnalites du front Twig.

## Fonctionnalites

- Authentification: inscription, connexion, deconnexion, profil courant
- Restaurants: liste, recherche, detail, creation, modification, suppression
- Mes restaurants: annuler, corriger/re-envoyer, voir reservations
- Reservations: creer depuis le detail d'un restaurant, lister mes reservations
- Admin: lister les restaurants en attente, accepter, refuser
- Export PDF d'un restaurant

## Pre-requis

- Flutter 3.38+
- API PHP disponible sur `http://localhost:8080/api`

Sur Android emulator, l'app utilise automatiquement `http://10.0.2.2:8080/api`.

## Lancer

```bash
cd mobile_flutter
flutter pub get
flutter run
```

Sur Windows, active le mode developpeur si `flutter pub get` indique un probleme de symlink:

```bash
start ms-settings:developers
```

## Changer l'URL API

```bash
flutter run --dart-define=API_BASE_URL=http://192.168.1.10:8080/api
```


Procédure push -> pull -> build:

Sur ce PC (avant push)
git add -A
git status
git commit -m "Parity Twig/Flutter + API + lockfiles"
git push origin <ta-branche>
Vérifie que ces fichiers sont bien versionnés
git ls-files composer.lock mobile_flutter/pubspec.lock
Tu dois voir les 2 chemins affichés.

Sur le PC de cours
git clone <repo>
cd Resto
git checkout <ta-branche>
docker compose up -d --build
docker compose exec -T -w /var/www php composer install --no-interaction --prefer-dist
Build Flutter
cd mobile_flutter
flutter pub get
flutter run
Si flutter pub get bloque sur symlink (Windows), active une fois:
start ms-settings:developers