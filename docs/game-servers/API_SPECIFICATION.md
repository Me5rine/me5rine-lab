# Spécification de l'API REST Game Servers

API REST du module Game Servers : whitelist Minecraft (vérification UUID) et mise à jour des stats par le mod (API key + IP/port).

---

## Endpoint Minecraft Whitelist (auth UUID)

**URL:** `GET /wp-json/me5rine-lab/v1/minecraft-auth?uuid={uuid}`

Permet au mod Minecraft Me5rine LAB (ou tout client) de vérifier si un joueur (UUID) est autorisé à se connecter (whitelist : UUID lié + account type ayant le module `game_servers`).

### Paramètre

| Paramètre | Type   | Requis | Description                                      |
|-----------|--------|--------|--------------------------------------------------|
| `uuid`    | string | Oui    | UUID Minecraft du joueur (avec ou sans tirets)  |

### Authentification (optionnelle)

Si une **API Key** est configurée dans **Me5rine LAB > Game Servers > Minecraft Settings**, le client doit envoyer :

- **Header** : `X-Api-Key: VOTRE_CLE`
- **OU** : `Authorization: Bearer VOTRE_CLE`

Si aucune clé n'est configurée, l'endpoint est public (sans authentification).

### Réponse attendue (200 OK)

```json
{"allowed": true}
```

ou

```json
{"allowed": false}
```

- `allowed: true` → le joueur est autorisé (UUID lié à un utilisateur dont un account type a le module `game_servers` dans ses modules actifs).
- `allowed: false` → le joueur est refusé (UUID non lié ou utilisateur sans account type avec le module `game_servers`).

### Erreurs possibles

- **400** : `uuid` manquant ou format invalide.
- **401** : Clé API configurée mais requête sans clé ou clé invalide.

### Logique d'autorisation

Un joueur est autorisé si :

1. Son UUID Minecraft est lié à un utilisateur WordPress (table des comptes Minecraft).
2. Cet utilisateur a au moins un **account type**.
3. Ce type de compte a le module **`game_servers`** dans ses modules actifs (champ `modules` du type de compte).

Cela ne concerne pas l'accès aux pages du site, uniquement la whitelist des serveurs Minecraft.

---

## Endpoint Minecraft Update Stats (pour le mod)

**URL:** `POST /wp-json/me5rine-lab/v1/minecraft/update-stats`

Permet au mod Minecraft Me5rine LAB d'envoyer les statistiques du serveur (joueurs, version, statut). Le serveur est identifié par son IP et port.

### Authentification (optionnelle)

Si une **API Key** est configurée dans **Me5rine LAB > Game Servers > Minecraft Settings**, le mod doit envoyer :

- **Header** : `X-Api-Key: VOTRE_CLE`
- **OU** : `Authorization: Bearer VOTRE_CLE`

Si aucune clé n'est configurée, l'endpoint est public (sans authentification).

### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `ip_address` | string | Oui | Adresse IP du serveur (ex: `play.monserveur.com` ou `192.168.1.1`) |
| `port` | integer | Non | Port du serveur (0 ou 25565 pour port par défaut Minecraft) |
| `current_players` | integer | Non | Nombre de joueurs actuellement connectés |
| `max_players` | integer | Non | Nombre maximum de joueurs |
| `version` | string | Non | Version du serveur (ex: "1.21.1", "Paper 1.21") |
| `online` | boolean | Non | Statut en ligne du serveur (true = en ligne, false = hors ligne) |

### Exemple de requête

```bash
curl -X POST \
  https://votre-site.com/wp-json/me5rine-lab/v1/minecraft/update-stats \
  -H 'Content-Type: application/json' \
  -H 'X-Api-Key: VOTRE_CLE' \
  -d '{
    "ip_address": "play.monserveur.com",
    "port": 25565,
    "current_players": 25,
    "max_players": 100,
    "version": "1.21.1",
    "online": true
}'
```

### Réponse attendue (200 OK)

```json
{
    "success": true,
    "message": "Statistics updated successfully.",
    "server_id": 1,
    "server_name": "Mon Serveur Minecraft"
}
```

### Erreurs possibles

- **400** : `ip_address` manquant, ou aucune donnée à mettre à jour.
- **401** : Clé API configurée mais requête sans clé ou clé invalide.
- **404** : Serveur non trouvé avec cette IP/port (le serveur doit être créé dans WordPress avec cette IP/port).

### Notes importantes

- Le serveur doit **exister dans WordPress** (créé via **Me5rine LAB > Game Servers**) avec l'IP et le port correspondants.
- Si `port` n'est pas fourni ou vaut 0, le système cherche un serveur avec `port = 0` ou `port = 25565` (port par défaut Minecraft).
- Le serveur doit être **actif** (`status = 'active'`) pour être trouvé.

---

## Notes générales

1. **Content-Type:** Toujours `application/json` pour les requêtes POST avec body.
2. **Méthodes:** `GET` pour `/minecraft-auth`, `POST` pour `/minecraft/update-stats`.
3. **Authentification (optionnelle):** Si une API Key est configurée, utiliser `X-Api-Key` ou `Authorization: Bearer`.
4. **Statut serveur:** Pour update-stats, le serveur doit être `status = 'active'` et correspondre à l'IP/port envoyés.
5. **Types de données:** `current_players` et `max_players` en integer, `version` en string, `online` en boolean JSON.
