# Guide de développement du plugin serveur

Ce guide explique comment créer un plugin pour votre serveur de jeu (Minecraft, etc.) qui enverra les statistiques vers WordPress.

## Architecture

Le système fonctionne avec un endpoint REST API WordPress qui reçoit les données depuis votre plugin serveur. Le plugin serveur doit envoyer périodiquement les statistiques du serveur.

## Configuration WordPress

1. Créez un serveur dans l'interface WordPress (Me5rine LAB > Game Servers)
2. Sélectionnez "OMGserv" comme fournisseur
3. Après la création, un **token d'authentification** sera généré automatiquement
4. Notez l'**URL de l'endpoint** affichée dans le formulaire

## Endpoint REST API

**URL:** `https://votre-site.com/wp-json/admin-lab-game-servers/v1/update-stats`

**Méthode:** `POST`

**Headers:**
```
Content-Type: application/json
X-Server-Token: VOTRE_TOKEN_ICI
```

**Body (JSON):**
```json
{
    "current_players": 25,
    "max_players": 100,
    "version": "1.21.1",
    "online": true
}
```

**Réponse (succès):**
```json
{
    "success": true,
    "message": "Statistiques mises à jour avec succès.",
    "server_id": 1
}
```

**Réponse (erreur):**
```json
{
    "code": "invalid_token",
    "message": "Token d'authentification invalide ou serveur inactif.",
    "data": {
        "status": 403
    }
}
```

## Exemple de plugin Minecraft (Bukkit/Spigot)

### Structure du plugin

```
GameServerStats/
├── plugin.yml
└── src/
    └── com/
        └── yourdomain/
            └── gameserverstats/
                └── GameServerStats.java
```

### plugin.yml

```yaml
name: GameServerStats
version: 1.0.0
main: com.yourdomain.gameserverstats.GameServerStats
api-version: 1.21
author: VotreNom
description: Envoie les statistiques du serveur vers WordPress
```

### GameServerStats.java

```java
package com.yourdomain.gameserverstats;

import org.bukkit.Bukkit;
import org.bukkit.plugin.java.JavaPlugin;
import org.bukkit.scheduler.BukkitTask;

import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.concurrent.CompletableFuture;

public class GameServerStats extends JavaPlugin {
    
    private static final String WORDPRESS_URL = "https://votre-site.com/wp-json/admin-lab-game-servers/v1/update-stats";
    private static final String SERVER_TOKEN = "VOTRE_TOKEN_ICI";
    private static final int UPDATE_INTERVAL = 60; // Secondes
    
    private BukkitTask updateTask;
    
    @Override
    public void onEnable() {
        getLogger().info("GameServerStats activé!");
        
        // Démarrer la tâche de mise à jour
        updateTask = Bukkit.getScheduler().runTaskTimerAsynchronously(
            this,
            this::sendStats,
            0L, // Délai initial (0 = immédiat)
            UPDATE_INTERVAL * 20L // Intervalle en ticks (20 ticks = 1 seconde)
        );
    }
    
    @Override
    public void onDisable() {
        if (updateTask != null) {
            updateTask.cancel();
        }
        getLogger().info("GameServerStats désactivé!");
    }
    
    private void sendStats() {
        CompletableFuture.runAsync(() -> {
            try {
                // Récupérer les statistiques
                int currentPlayers = Bukkit.getOnlinePlayers().size();
                int maxPlayers = Bukkit.getMaxPlayers();
                String version = Bukkit.getVersion();
                boolean online = Bukkit.getServer().getOnlineMode();
                
                // Préparer le JSON
                String json = String.format(
                    "{\"current_players\":%d,\"max_players\":%d,\"version\":\"%s\",\"online\":%s}",
                    currentPlayers,
                    maxPlayers,
                    version.replace("\"", "\\\""),
                    online
                );
                
                // Envoyer la requête
                URL url = new URL(WORDPRESS_URL);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setRequestProperty("X-Server-Token", SERVER_TOKEN);
                conn.setDoOutput(true);
                
                // Écrire le body
                try (OutputStream os = conn.getOutputStream()) {
                    byte[] input = json.getBytes(StandardCharsets.UTF_8);
                    os.write(input, 0, input.length);
                }
                
                // Lire la réponse
                int responseCode = conn.getResponseCode();
                if (responseCode == 200) {
                    getLogger().info("Statistiques envoyées avec succès!");
                } else {
                    getLogger().warning("Erreur lors de l'envoi: " + responseCode);
                }
                
                conn.disconnect();
                
            } catch (Exception e) {
                getLogger().severe("Erreur lors de l'envoi des statistiques: " + e.getMessage());
                e.printStackTrace();
            }
        });
    }
}
```

## Exemple avec cURL (pour tests)

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

## Exemple Python (pour serveurs Python)

```python
import requests
import time
from mcstatus import JavaServer

WORDPRESS_URL = "https://votre-site.com/wp-json/admin-lab-game-servers/v1/update-stats"
SERVER_TOKEN = "VOTRE_TOKEN_ICI"
UPDATE_INTERVAL = 60  # Secondes

def send_stats():
    try:
        # Récupérer les stats du serveur Minecraft
        server = JavaServer.lookup("localhost:25565")
        status = server.status()
        
        data = {
            "current_players": status.players.online,
            "max_players": status.players.max,
            "version": status.version.name,
            "online": True
        }
        
        headers = {
            "Content-Type": "application/json",
            "X-Server-Token": SERVER_TOKEN
        }
        
        response = requests.post(WORDPRESS_URL, json=data, headers=headers)
        
        if response.status_code == 200:
            print("Statistiques envoyées avec succès!")
        else:
            print(f"Erreur: {response.status_code} - {response.text}")
            
    except Exception as e:
        print(f"Erreur: {e}")

# Boucle principale
while True:
    send_stats()
    time.sleep(UPDATE_INTERVAL)
```

## Sécurité

- Le token est unique pour chaque serveur
- Le token est stocké de manière sécurisée dans WordPress
- Les requêtes doivent inclure le token dans le header `X-Server-Token`
- Le serveur doit être actif dans WordPress pour accepter les requêtes

## Fréquence de mise à jour

Il est recommandé d'envoyer les statistiques toutes les **60 secondes** pour un bon équilibre entre précision et charge serveur.

## Dépannage

### Erreur 401 (Missing Token)
- Vérifiez que le header `X-Server-Token` est bien envoyé
- Vérifiez que le token est correct

### Erreur 403 (Invalid Token)
- Vérifiez que le token correspond bien au serveur
- Vérifiez que le serveur est actif dans WordPress

### Erreur 404 (Server Not Found)
- Vérifiez que l'URL de l'endpoint est correcte
- Vérifiez que le module Game Servers est activé

## Support

Pour toute question, consultez la documentation WordPress ou contactez le support.

