# Guide de démarrage rapide - Game Servers (mod Minecraft)

Le module ne gère plus que le **mod Minecraft** : whitelist (vérification UUID) et mise à jour des stats via API key + IP/port.

---

## Comment tester (côté WordPress)

Une fois le module activé, un serveur créé et (optionnellement) une API key configurée :

### 1. Whitelist (GET) — navigateur ou cURL

**Sans API key configurée**, ouvrez dans le navigateur :
```
https://VOTRE-SITE.com/wp-json/me5rine-lab/v1/minecraft-auth?uuid=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
```
→ Vous devez voir `{"allowed":true}` ou `{"allowed":false}`.

**Avec API key** (cURL) :
```bash
curl -H "X-Api-Key: VOTRE_CLE" "https://VOTRE-SITE.com/wp-json/me5rine-lab/v1/minecraft-auth?uuid=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
```

### 2. Update stats (POST) — cURL

Remplacez `VOTRE-SITE.com` par votre domaine, `VOTRE_CLE` par votre API key (ou enlevez le header si pas de clé), et **l’IP/port** par ceux d’un serveur déjà créé dans **Game Servers** :

```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: VOTRE_CLE" \
  -d "{\"ip_address\":\"play.monserveur.com\",\"port\":25565,\"current_players\":2,\"max_players\":20,\"version\":\"1.21.1\",\"online\":true}" \
  "https://VOTRE-SITE.com/wp-json/me5rine-lab/v1/minecraft/update-stats"
```

**Réponse attendue (200) :**
```json
{"success":true,"message":"Statistics updated successfully.","server_id":1,"server_name":"Mon Serveur Minecraft"}
```

**Si 404** : le serveur n’existe pas avec cette IP/port dans **Me5rine LAB > Game Servers** → créez-le avec la même IP et le même port.

Détails et checklist : **Mod Minecraft** → `docs/game-servers/MOD_INTEGRATION.md`.

---

## 1. Activer le module

1. **Me5rine LAB > Settings** → cochez **Game Servers** → Enregistrer.
2. Les pages **Game Servers** et **Minecraft Servers** sont créées automatiquement.

## 2. Ajouter un serveur (pour le mod)

1. **Me5rine LAB > Game Servers** → **Ajouter un serveur**.
2. Renseignez :
   - **Nom** : ex. "Mon Serveur Minecraft"
   - **Adresse IP** : celle de votre serveur Minecraft (ex. `play.monserveur.com` ou `127.0.0.1`)
   - **Port** : ex. `25565`
   - **Statut** : Actif
3. Pour la whitelist abonné : ajoutez le tag **minecraft** et cochez **Enable subscriber whitelist** si besoin.
4. Enregistrez.

**Important** : L’IP et le port doivent correspondre à ceux que le mod envoie pour que les stats soient mises à jour.

## 3. Minecraft : OAuth et API key

- **Me5rine LAB > Game Servers** → onglet **Minecraft Settings**.
- **Client ID / Client Secret** Azure : pour que les joueurs lient leur compte Minecraft (shortcode `[minecraft_link]`).
- **API Key (Optional)** : si vous la définissez, le mod doit l’envoyer (`X-Api-Key` ou `Authorization: Bearer`). Sinon les endpoints sont publics.

## 4. Afficher les serveurs sur le site

Shortcodes (déjà utilisés sur les pages auto) :

- `[game_servers_list]` — liste des serveurs
- `[game_server id="1"]` — un serveur
- `[minecraft_link]` — lien pour lier son compte Minecraft (page membre)

## 5. Documentation

- **API** : `docs/game-servers/API_SPECIFICATION.md`
- **Mod (config + tests)** : `docs/game-servers/MOD_INTEGRATION.md`

## Dépannage

- **Module absent** : vérifier que `modules/game-servers/game-servers.php` existe et que le module est coché dans Settings.
- **Stats ne se mettent pas à jour** : vérifier IP/port du serveur dans WordPress = IP/port envoyés par le mod ; tester avec le cURL ci-dessus.
- **Whitelist toujours `false`** : UUID lié à un utilisateur dont un account type a le module **Game Servers** activé (voir MOD_INTEGRATION.md).
