# Spécification de l'API REST Game Servers

## Endpoint

**URL:** `POST /wp-json/admin-lab-game-servers/v1/update-stats`

## Authentification

Le token d'authentification doit être envoyé dans **le header HTTP** :

```
X-Server-Token: VOTRE_TOKEN_ICI
```

⚠️ **Important:** Le token est envoyé dans le header `X-Server-Token`, **PAS** dans un format "Bearer Token". C'est simplement la valeur du token sans préfixe.

### Format du header
```
X-Server-Token: abc123def456ghi789jkl012mno345pqr678stu901vwx234yz
```

Le token est une chaîne hexadécimale SHA-256 (64 caractères).

**Alternative:** Si le header n'est pas disponible, le token peut aussi être envoyé dans le body JSON sous la clé `server_token`, mais le header est **recommandé** pour des raisons de sécurité.

## Payload JSON attendu

### Structure minimale

Au moins un des champs suivants doit être présent :

```json
{
    "current_players": 25,
    "max_players": 100,
    "version": "1.21.1",
    "online": true
}
```

### Champs disponibles

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `current_players` | integer | Non | Nombre de joueurs actuellement connectés (0 ou plus) |
| `max_players` | integer | Non | Nombre maximum de joueurs autorisés |
| `version` | string | Non | Version du serveur (ex: "1.21.1", "Paper 1.21") |
| `online` | boolean | Non | Statut en ligne du serveur (true = en ligne, false = hors ligne) |
| `server_token` | string | Non* | Token d'authentification (si pas envoyé dans le header) |

*`server_token` n'est requis que si le header `X-Server-Token` n'est pas fourni.

## Exemples de requêtes

### Exemple 1 : Avec header (recommandé)

```bash
curl -X POST \
  https://votre-site.com/wp-json/admin-lab-game-servers/v1/update-stats \
  -H 'Content-Type: application/json' \
  -H 'X-Server-Token: abc123def456ghi789jkl012mno345pqr678stu901vwx234yz' \
  -d '{
    "current_players": 25,
    "max_players": 100,
    "version": "1.21.1",
    "online": true
}'
```

### Exemple 2 : Token dans le body (alternative)

```bash
curl -X POST \
  https://votre-site.com/wp-json/admin-lab-game-servers/v1/update-stats \
  -H 'Content-Type: application/json' \
  -d '{
    "server_token": "abc123def456ghi789jkl012mno345pqr678stu901vwx234yz",
    "current_players": 25,
    "max_players": 100,
    "version": "1.21.1",
    "online": true
}'
```

### Exemple 3 : Mise à jour partielle (seulement les joueurs)

```json
{
    "current_players": 30,
    "max_players": 100
}
```

### Exemple 4 : Signalement serveur hors ligne

```json
{
    "online": false
}
```

## Code de validation WordPress

Voici le code exact qui valide le payload côté WordPress :

```php
// Dans Game_Servers_Rest_API::verify_token()
$token = $request->get_header('X-Server-Token');

// Si pas dans le header, chercher dans les paramètres
if (empty($token)) {
    $token = $request->get_param('server_token');
}

if (empty($token)) {
    return new WP_Error('missing_token', 'Token manquant', ['status' => 401]);
}

// Validation: le token doit exister dans la base de données
$server = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT id, name FROM {$table_name} WHERE provider_server_id = %s AND status = 'active'",
        $token
    ),
    ARRAY_A
);

if (!$server) {
    return new WP_Error('invalid_token', 'Token invalide', ['status' => 403]);
}
```

```php
// Dans Game_Servers_Rest_API::update_server_stats()
$stats = [];

// Validation et nettoyage des données
if ($request->has_param('current_players')) {
    $stats['current_players'] = (int) $request->get_param('current_players');
}

if ($request->has_param('max_players')) {
    $stats['max_players'] = (int) $request->get_param('max_players');
}

if ($request->has_param('version')) {
    $stats['version'] = sanitize_text_field($request->get_param('version'));
}

// Le champ 'online' change le statut du serveur
if ($request->has_param('online') && !$request->get_param('online')) {
    $stats['status'] = 'inactive';
} elseif ($request->has_param('online') && $request->get_param('online')) {
    $stats['status'] = 'active';
}

// Au moins un champ doit être présent
if (empty($stats)) {
    return new WP_Error('no_data', 'Aucune donnée à mettre à jour', ['status' => 400]);
}
```

## Réponses

### Succès (200 OK)

```json
{
    "success": true,
    "message": "Statistiques mises à jour avec succès.",
    "server_id": 1
}
```

### Erreur 401 - Token manquant

```json
{
    "code": "missing_token",
    "message": "Token d'authentification manquant.",
    "data": {
        "status": 401
    }
}
```

### Erreur 403 - Token invalide

```json
{
    "code": "invalid_token",
    "message": "Token d'authentification invalide ou serveur inactif.",
    "data": {
        "status": 403
    }
}
```

### Erreur 400 - Aucune donnée

```json
{
    "code": "no_data",
    "message": "Aucune donnée à mettre à jour.",
    "data": {
        "status": 400
    }
}
```

## Endpoint Ping

**URL:** `GET /wp-json/admin-lab-game-servers/v1/ping`

Permet de tester la connexion et la validité du token.

**Header requis:**
```
X-Server-Token: VOTRE_TOKEN_ICI
```

**Réponse:**
```json
{
    "success": true,
    "message": "Connexion établie avec succès.",
    "server": "Mon Serveur",
    "timestamp": "2024-01-15 14:30:00"
}
```

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

Si aucune clé n’est configurée, l’endpoint est public (sans authentification).

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

### Logique d’autorisation

Un joueur est autorisé si :

1. Son UUID Minecraft est lié à un utilisateur WordPress (table des comptes Minecraft).
2. Cet utilisateur a au moins un **account type**.
3. Ce type de compte a le module **`game_servers`** dans ses modules actifs (champ `modules` du type de compte).

Cela ne concerne pas l’accès aux pages du site, uniquement la whitelist des serveurs Minecraft.

---

## Notes importantes

1. **Content-Type:** Toujours `application/json` pour les requêtes POST avec body.
2. **Méthode:** `POST` pour `/update-stats`, `GET` pour `/ping` et `/minecraft-auth`.
3. **Header token (stats):** `X-Server-Token` (pas `Authorization`, pas `Bearer`).
4. **Validation:** Le token est comparé exactement avec `provider_server_id` dans la table `game_servers`.
5. **Statut serveur:** Le serveur doit être `status = 'active'` pour accepter les requêtes de stats.
6. **Types de données:**
   - `current_players` et `max_players` sont convertis en `integer`
   - `version` est sanitized avec `sanitize_text_field()`
   - `online` est un boolean JSON (`true`/`false`)

