# Guide de démarrage rapide - Game Servers

## 1. Activer le module

1. Allez dans **Me5rine LAB > Settings**
2. Dans l'onglet **General**, cochez **Game Servers** dans la liste des modules
3. Cliquez sur **Enregistrer les modifications**

## 2. Ajouter un serveur

1. Allez dans **Me5rine LAB > Game Servers**
2. Cliquez sur **Ajouter un serveur**
3. Remplissez les informations :
   - **Nom du serveur** : Le nom qui apparaîtra sur le site
   - **Description** : Description optionnelle
   - **Jeu associé** : Sélectionnez le jeu depuis ClicksNGames (optionnel)
   - **Adresse IP** : L'adresse IP de votre serveur (ex: `play.monserveur.com`)
   - **Port** : Le port du serveur (ex: `25565` pour Minecraft)
   - **Fournisseur** : Sélectionnez "OMGserv"
   - **Statut** : "Actif" pour que le serveur apparaisse
4. Cliquez sur **Enregistrer**

## 3. Obtenir le token d'authentification

Après avoir sauvegardé le serveur :

1. Cliquez sur **Modifier** sur le serveur que vous venez de créer
2. Dans le champ **Token d'authentification**, vous verrez un token généré automatiquement
3. Cliquez sur le bouton **Copier** pour copier le token
4. Notez également l'**URL de l'endpoint** affichée juste en dessous

⚠️ **Important** : Conservez ce token précieusement, il vous servira à authentifier votre bot/plugin serveur.

## 4. Configurer votre bot/plugin serveur

### Pour un bot Discord, script Python, etc.

Vous devez envoyer périodiquement (toutes les 60 secondes recommandé) une requête HTTP POST à l'endpoint WordPress avec :

**URL de l'endpoint :**
```
https://votre-site.com/wp-json/admin-lab-game-servers/v1/update-stats
```

**Header requis :**
```
Content-Type: application/json
X-Server-Token: VOTRE_TOKEN_ICI
```

**Body JSON :**
```json
{
    "current_players": 25,
    "max_players": 100,
    "version": "1.21.1",
    "online": true
}
```

### Exemple Python simple

```python
import requests
import time

WORDPRESS_URL = "https://votre-site.com/wp-json/admin-lab-game-servers/v1/update-stats"
SERVER_TOKEN = "VOTRE_TOKEN_COPIÉ_ICI"

def send_stats():
    data = {
        "current_players": 25,  # Récupérer depuis votre serveur
        "max_players": 100,      # Récupérer depuis votre serveur
        "version": "1.21.1",     # Récupérer depuis votre serveur
        "online": True
    }
    
    headers = {
        "Content-Type": "application/json",
        "X-Server-Token": SERVER_TOKEN
    }
    
    try:
        response = requests.post(WORDPRESS_URL, json=data, headers=headers)
        if response.status_code == 200:
            print("✅ Statistiques envoyées avec succès!")
        else:
            print(f"❌ Erreur {response.status_code}: {response.text}")
    except Exception as e:
        print(f"❌ Erreur: {e}")

# Envoyer toutes les 60 secondes
while True:
    send_stats()
    time.sleep(60)
```

## 5. Tester la connexion

Vous pouvez tester votre configuration avec cURL :

```bash
curl -X POST \
  https://votre-site.com/wp-json/admin-lab-game-servers/v1/update-stats \
  -H 'Content-Type: application/json' \
  -H 'X-Server-Token: VOTRE_TOKEN_ICI' \
  -d '{
    "current_players": 25,
    "max_players": 100,
    "version": "1.21.1",
    "online": true
}'
```

Si tout fonctionne, vous devriez recevoir :
```json
{
    "success": true,
    "message": "Statistiques mises à jour avec succès.",
    "server_id": 1
}
```

## 6. Afficher les serveurs sur votre site

Utilisez le shortcode suivant dans vos pages/articles :

```
[game_servers_list]
```

Ou pour un serveur spécifique :
```
[game_server id="1"]
```

### Options du shortcode `[game_servers_list]`

- `status="active"` : Filtrer par statut (par défaut: active)
- `game_id="123"` : Filtrer par jeu (ID ClicksNGames)
- `limit="10"` : Limiter le nombre de serveurs affichés
- `orderby="name"` : Trier par champ (name, current_players, etc.)
- `order="ASC"` : Ordre de tri (ASC ou DESC)

Exemple :
```
[game_servers_list status="active" limit="5" orderby="current_players" order="DESC"]
```

## Documentation complète

Pour plus de détails :
- **API REST** : Voir `docs/game-servers/API_SPECIFICATION.md`
- **Développement plugin serveur** : Voir `docs/game-servers/SERVER_PLUGIN_GUIDE.md`

## Dépannage

### Le module n'apparaît pas dans les paramètres
- Vérifiez que le fichier `modules/game-servers/game-servers.php` existe
- Videz le cache de WordPress

### Erreur 401 (Token manquant)
- Vérifiez que le header `X-Server-Token` est bien envoyé
- Vérifiez que le token est correct (copie exacte)

### Erreur 403 (Token invalide)
- Vérifiez que le serveur est actif dans WordPress
- Vérifiez que le token correspond bien au serveur

### Les statistiques ne se mettent pas à jour
- Vérifiez que votre bot/plugin envoie bien les requêtes
- Vérifiez les logs d'erreur de votre bot
- Testez avec cURL pour vérifier que l'endpoint fonctionne

