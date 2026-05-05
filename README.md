# Tournoi Sport - API REST

Systeme pour gerer les tournois sportifs avec Symfony 8, PostgreSQL et Docker.

## Ce qu'il faut avant de commencer

- Docker Desktop installé
- Docker Compose

## Comment lancer le projet

```bash
docker-compose up -d
docker-compose exec app bash
symfony console doctrine:database:create
symfony console doctrine:migrations:migrate
exit
```

L'API tourne sur `http://localhost`

## Tester l'API

Importe la collection Bruno depuis le dossier `bruno-api-tournoi` et teste les endpoints.

## Comment c'est organisé
├── src/              # Le code
├── migrations/       # Les migrations BD
├── config/          # Config
├── tests/           # Tests
└── public/          # Point d'entrée

## Config Docker

- User: `app`
- Password: `app_password`
- Database: `app`

## Pour arreter tout

```bash
docker-compose down
```

## Faut checker quoi si ça marche pas

```bash
docker-compose logs db      # Voir les logs de la BD
docker-compose logs app     # Voir les logs de l'app
```
