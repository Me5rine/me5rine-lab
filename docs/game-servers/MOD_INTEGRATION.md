# Faire fonctionner l'API avec le mod Minecraft Me5rine LAB

Ce guide liste ce qu'il faut configurer **côté WordPress** et **côté mod** pour que la whitelist et l'envoi des stats fonctionnent.

---

## 1. Côté WordPress

### 1.1 Module et pages

- **Me5rine LAB > Settings** : le module **Game Servers** est coché et enregistré.
- Les pages **Game Servers** et **Minecraft Servers** sont créées (automatiquement à l'activation du module).

### 1.2 Créer le serveur Minecraft

- **Me5rine LAB > Game Servers** → onglet **Servers**.
- Cliquez sur **Ajouter un serveur**.
- Remplissez :
  - **Nom du serveur** : ex. "Mon Serveur Minecraft"
  - **Adresse IP** : l'IP ou domaine de votre serveur (ex: `play.monserveur.com`)
  - **Port** : le port du serveur (ex: `25565` pour Minecraft, ou `0` pour port par défaut)
  - **Tags** : ajoutez `minecraft` si vous voulez activer la whitelist abonné
  - **Statut** : "Actif"
- Enregistrez.

**Important** : L'IP et le port doivent correspondre exactement à ceux de votre serveur Minecraft pour que le mod puisse envoyer les stats.

### 1.3 Lier les comptes Minecraft (OAuth)

Pour qu'un joueur soit reconnu par son UUID, son compte Minecraft doit être lié à un compte WordPress :

- **Me5rine LAB > Game Servers** → onglet **Minecraft Settings**.
- Renseigner **Client ID** et **Client Secret** Azure (voir les instructions sur la page).
- Les utilisateurs lient leur compte via une page contenant le shortcode `[minecraft_link]` (ex. page « Mon compte » ou espace membre).

Sans cette étape, aucun UUID n'est enregistré → l'API répondra toujours `{"allowed": false}`.

### 1.4 Account types et module `game_servers`

Un joueur n'est autorisé que si son compte WordPress a un **account type** qui a le module **Game Servers** dans ses modules actifs :

- **Me5rine LAB > User management** (ou équivalent) : gérer les **Account types**.
- Pour chaque type qui doit donner accès aux serveurs Minecraft (ex. « Abonné », « VIP ») : cocher le module **Game Servers** dans les « Enabled Modules » (ou équivalent).
- S'assurer que les utilisateurs concernés ont bien ce type de compte assigné.

### 1.5 (Optionnel) Clé API pour les endpoints

- **Me5rine LAB > Game Servers** → onglet **Minecraft Settings**.
- Champ **API Key (Optional)** : saisir une clé secrète (ex. longue chaîne aléatoire).
- **Important** : Après avoir sauvegardé, la clé s'affiche en clair avec un bouton **Copier** — copiez-la immédiatement car elle sera masquée lors de la prochaine visite de la page.
- Si une clé est définie, le mod **doit** l'envoyer (voir ci‑dessous). Si le champ est vide, les endpoints sont publics (pas d'auth).

### 1.6 Récupérer les URLs des endpoints

**Whitelist (vérifier UUID) :**
```
https://VOTRE-SITE.com/wp-json/me5rine-lab/v1/minecraft-auth?uuid={uuid}
```

**Stats (envoyer statistiques serveur) :**
```
https://VOTRE-SITE.com/wp-json/me5rine-lab/v1/minecraft/update-stats
```

Ces URLs sont aussi affichées dans **Minecraft Settings** (bloc « Whitelist Endpoint »).

---

## 2. Côté mod (configuration)

Le mod doit appeler l'API avec les paramètres suivants.

### 2.1 URL de l'API

Configurer l'URL de base du site WordPress, par exemple :

- Dans `me5rinelab.json` (ou équivalent) :  
  **wordpressApiUrl** = `https://VOTRE-SITE.com`  
  (sans slash final, sans `/wp-json/...` si le mod reconstruit le chemin)

Ou, si le mod attend l'URL complète de l'endpoint :

- **wordpressApiUrl** = `https://VOTRE-SITE.com/wp-json/me5rine-lab/v1/minecraft-auth`

À adapter selon ce que lit le mod (consulter sa doc ou son code).

### 2.2 Clé API (si vous en avez configuré une dans WordPress)

Si vous avez renseigné une **API Key** dans **Minecraft Settings** :

- **Où la trouver** : **Me5rine LAB > Game Servers** → onglet **Minecraft Settings** → champ **API Key (Optional)**.  
  Si une clé existe déjà, elle s'affiche en clair avec un bouton **Copier** (copiez-la avant de quitter la page).
- Dans `me5rinelab.json` (ou équivalent) :  
  **wordpressApiKey** = la **même valeur exacte** que celle affichée/copiée dans WordPress.

Le mod doit envoyer cette clé à chaque requête vers les endpoints :

- **Header** : `X-Api-Key: VOTRE_CLE`  
  **ou**  
  **Header** : `Authorization: Bearer VOTRE_CLE`

Si vous n'avez pas configuré de clé dans WordPress, ne pas mettre de clé dans le mod.

---

## 3. Vérification rapide

### 3.1 Test de l'endpoint whitelist depuis un navigateur

**Oui, c'est possible !** L'endpoint est en `GET`, donc vous pouvez le tester directement dans votre navigateur.

**URL à coller dans la barre d'adresse :**
```
https://VOTRE-SITE.com/wp-json/me5rine-lab/v1/minecraft-auth?uuid=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
```

Remplacez :
- `VOTRE-SITE.com` par votre domaine WordPress
- `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx` par un UUID Minecraft valide (avec tirets)

**Résultats possibles :**

- **Sans clé API configurée** : vous verrez directement `{"allowed":true}` ou `{"allowed":false}` dans le navigateur.
- **Avec clé API configurée** : vous verrez `{"error":"unauthorized","message":"Invalid or missing API key."}` car le navigateur n'envoie pas les headers d'authentification.

### 3.2 Test avec cURL (pour tester avec clé API)

Si vous avez configuré une clé API, utilisez cURL pour tester avec l'authentification :

```bash
# Whitelist (sans clé API ou si aucune clé n'est configurée)
curl "https://VOTRE-SITE.com/wp-json/me5rine-lab/v1/minecraft-auth?uuid=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"

# Whitelist (avec clé API - header X-Api-Key)
curl -H "X-Api-Key: VOTRE_CLE" "https://VOTRE-SITE.com/wp-json/me5rine-lab/v1/minecraft-auth?uuid=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"

# Stats (avec clé API)
curl -X POST \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: VOTRE_CLE" \
  -d '{"ip_address":"play.monserveur.com","port":25565,"current_players":25,"max_players":100,"version":"1.21.1","online":true}' \
  "https://VOTRE-SITE.com/wp-json/me5rine-lab/v1/minecraft/update-stats"
```

**Résultats attendus :**

- `{"allowed":true}` → UUID lié à un utilisateur avec account type ayant le module `game_servers`
- `{"allowed":false}` → UUID non lié ou utilisateur sans account type avec module `game_servers`
- `{"error":"unauthorized",...}` → Clé API manquante ou invalide (si une clé est configurée)
- `{"error":"server_not_found",...}` → Serveur non trouvé avec cette IP/port (pour l'endpoint stats)

### 3.3 Checklist joueur autorisé

Un joueur reçoit `{"allowed": true}` seulement si :

1. Son **UUID Minecraft** est lié à un **compte WordPress** (via OAuth / `[minecraft_link]`).
2. Ce compte a au moins un **account type**.
3. Cet account type a le module **`game_servers`** dans ses modules actifs.

Si une de ces conditions manque, l'API renvoie `{"allowed": false}`.

---

## 4. Envoyer les stats du serveur depuis le mod

Le mod peut envoyer les statistiques du serveur (joueurs, version, statut) via un endpoint dédié :

**URL:** `POST /wp-json/me5rine-lab/v1/minecraft/update-stats`

**Headers:**
```
Content-Type: application/json
X-Api-Key: VOTRE_CLE (si API key configurée)
```

**Body JSON:**
```json
{
    "ip_address": "play.monserveur.com",
    "port": 25565,
    "current_players": 25,
    "max_players": 100,
    "version": "1.21.1",
    "online": true
}
```

**Important :**
- Le serveur doit **exister dans WordPress** (créé via **Me5rine LAB > Game Servers**) avec l'IP et le port correspondants.
- Le serveur est identifié par son **IP** et **port** (pas besoin de token serveur).
- Si `port` n'est pas fourni ou vaut 0, le système cherche un serveur avec port 0 ou 25565.

**Réponse (succès):**
```json
{
    "success": true,
    "message": "Statistics updated successfully.",
    "server_id": 1,
    "server_name": "Mon Serveur Minecraft"
}
```

**Réponse (erreur 404 - serveur non trouvé):**
```json
{
    "error": "server_not_found",
    "message": "Server not found with this IP address and port."
}
```

---

## 5. Résumé des réglages mod

| Réglage mod          | Valeur / action |
|----------------------|-----------------|
| **URL API whitelist** | `https://VOTRE-SITE.com/wp-json/me5rine-lab/v1/minecraft-auth?uuid={uuid}` |
| **URL API stats**    | `https://VOTRE-SITE.com/wp-json/me5rine-lab/v1/minecraft/update-stats` |
| **wordpressApiKey**  | Identique à la clé définie dans **Minecraft Settings** (ou vide si pas de clé). |
| **Requête whitelist** | `GET .../minecraft-auth?uuid={uuid}` avec optionnellement `X-Api-Key` ou `Authorization: Bearer`. |
| **Requête stats**    | `POST .../minecraft/update-stats` avec body JSON (ip_address, port, current_players, etc.) et optionnellement `X-Api-Key`. |

Une fois WordPress (OAuth, account types, serveur créé avec IP/port, optionnellement clé API) et le mod (URLs + clé si besoin) configurés ainsi, l'API fonctionne avec le mod.
