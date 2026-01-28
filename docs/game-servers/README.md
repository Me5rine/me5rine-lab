# Game Servers – Documentation

Module **Game Servers** du plugin Me5rine LAB : gestion des serveurs de jeux, statistiques, pages automatiques, Minecraft (OAuth, whitelist).

## Contenu

| Fichier | Description |
|---------|-------------|
| [QUICK_START.md](QUICK_START.md) | Activer le module, ajouter un serveur, shortcodes, Minecraft (OAuth, whitelist) |
| [API_SPECIFICATION.md](API_SPECIFICATION.md) | Spécification des endpoints REST : minecraft-auth (whitelist), minecraft/update-stats |
| [MOD_INTEGRATION.md](MOD_INTEGRATION.md) | **Faire fonctionner l’API avec le mod** : checklist WordPress + config mod |
## Fonctionnalités

- **Serveurs** : liste, CRUD, stats mises à jour par le mod Minecraft (API key + IP/port)
- **Pages auto** : à l’activation du module, création des pages « Game Servers » et « Minecraft Servers »
- **Shortcodes** : `[game_servers_list]`, `[game_server id="X"]`, `[minecraft_link]`
- **Minecraft** : liaison de compte (Microsoft OAuth), whitelist basée sur le module `game_servers` dans les account types
- **API** : `GET /me5rine-lab/v1/minecraft-auth?uuid={uuid}` (whitelist), `POST /me5rine-lab/v1/minecraft/update-stats` (mod avec API key + IP/port)

## Liens rapides

- **Admin** : Me5rine LAB > Game Servers (onglets Servers, Minecraft Settings)
- **Endpoint whitelist** : `https://votresite.com/wp-json/me5rine-lab/v1/minecraft-auth?uuid={uuid}`
- **Endpoint stats (mod)** : `POST https://votresite.com/wp-json/me5rine-lab/v1/minecraft/update-stats`
