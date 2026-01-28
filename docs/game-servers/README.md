# Game Servers – Documentation

Module **Game Servers** du plugin Me5rine LAB : gestion des serveurs de jeux, statistiques, pages automatiques, Minecraft (OAuth, whitelist).

## Contenu

| Fichier | Description |
|---------|-------------|
| [QUICK_START.md](QUICK_START.md) | Activer le module, ajouter un serveur, token, shortcodes, Minecraft (OAuth, whitelist) |
| [API_SPECIFICATION.md](API_SPECIFICATION.md) | Spécification des endpoints REST : update-stats, ping, minecraft-auth (whitelist) |
| [MOD_INTEGRATION.md](MOD_INTEGRATION.md) | **Faire fonctionner l’API avec le mod** : checklist WordPress + config mod |
| [SERVER_PLUGIN_GUIDE.md](SERVER_PLUGIN_GUIDE.md) | Développer un plugin serveur (Minecraft, Python, etc.) pour envoyer les stats |

## Fonctionnalités

- **Serveurs** : liste, CRUD, token par serveur, stats envoyées par un plugin/bot
- **Pages auto** : à l’activation du module, création des pages « Game Servers » et « Minecraft Servers »
- **Shortcodes** : `[game_servers_list]`, `[game_server id="X"]`, `[minecraft_link]`
- **Minecraft** : liaison de compte (Microsoft OAuth), whitelist basée sur le module `game_servers` dans les account types
- **API** : `POST /admin-lab-game-servers/v1/update-stats`, `GET /admin-lab-game-servers/v1/ping`, `GET /me5rine-lab/v1/minecraft-auth?uuid={uuid}`

## Liens rapides

- **Admin** : Me5rine LAB > Game Servers (onglets Servers, Minecraft Settings)
- **Endpoint stats** : `https://votresite.com/wp-json/admin-lab-game-servers/v1/update-stats`
- **Endpoint whitelist** : `https://votresite.com/wp-json/me5rine-lab/v1/minecraft-auth?uuid={uuid}`
