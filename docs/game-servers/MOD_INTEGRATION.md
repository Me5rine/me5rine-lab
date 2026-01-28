# Faire fonctionner l’API avec le mod Minecraft Me5rine LAB

Ce guide liste ce qu’il faut configurer **côté WordPress** et **côté mod** pour que la whitelist fonctionne.

---

## 1. Côté WordPress

### 1.1 Module et pages

- **Me5rine LAB > Settings** : le module **Game Servers** est coché et enregistré.
- Les pages **Game Servers** et **Minecraft Servers** sont créées (automatiquement à l’activation du module).

### 1.2 Lier les comptes Minecraft (OAuth)

Pour qu’un joueur soit reconnu par son UUID, son compte Minecraft doit être lié à un compte WordPress :

- **Me5rine LAB > Game Servers** → onglet **Minecraft Settings**.
- Renseigner **Client ID** et **Client Secret** Azure (voir les instructions sur la page).
- Les utilisateurs lient leur compte via une page contenant le shortcode `[minecraft_link]` (ex. page « Mon compte » ou espace membre).

Sans cette étape, aucun UUID n’est enregistré → l’API répondra toujours `{"allowed": false}`.

### 1.3 Account types et module `game_servers`

Un joueur n’est autorisé que si son compte WordPress a un **account type** qui a le module **Game Servers** dans ses modules actifs :

- **Me5rine LAB > User management** (ou équivalent) : gérer les **Account types**.
- Pour chaque type qui doit donner accès aux serveurs Minecraft (ex. « Abonné », « VIP ») : cocher le module **Game Servers** dans les « Enabled Modules » (ou équivalent).
- S’assurer que les utilisateurs concernés ont bien ce type de compte assigné.

### 1.4 (Optionnel) Clé API pour l’endpoint whitelist

- **Me5rine LAB > Game Servers** → onglet **Minecraft Settings**.
- Champ **API Key (Optional)** : saisir une clé secrète (ex. longue chaîne aléatoire).
- Si une clé est définie, le mod **doit** l’envoyer (voir ci‑dessous). Si le champ est vide, l’endpoint whitelist est public (pas d’auth).

### 1.5 Récupérer l’URL de l’endpoint

L’URL de base pour le mod est :

```text
https://VOTRE-SITE.com/wp-json/me5rine-lab/v1/minecraft-auth
```

Exemple complet pour un joueur :  
`https://VOTRE-SITE.com/wp-json/me5rine-lab/v1/minecraft-auth?uuid=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`

Cette URL est aussi affichée dans **Minecraft Settings** (bloc « Whitelist Endpoint »).

---

## 2. Côté mod (configuration)

Le mod doit appeler l’API avec les paramètres suivants.

### 2.1 URL de l’API

Configurer l’URL de base du site WordPress, par exemple :

- Dans `me5rinelab.json` (ou équivalent) :  
  **wordpressApiUrl** = `https://VOTRE-SITE.com`  
  (sans slash final, sans `/wp-json/...` si le mod reconstruit le chemin)

Ou, si le mod attend l’URL complète de l’endpoint :

- **wordpressApiUrl** = `https://VOTRE-SITE.com/wp-json/me5rine-lab/v1/minecraft-auth`

À adapter selon ce que lit le mod (consulter sa doc ou son code).

### 2.2 Clé API (si vous en avez configuré une dans WordPress)

Si vous avez renseigné une **API Key** dans **Minecraft Settings** :

- Dans `me5rinelab.json` (ou équivalent) :  
  **wordpressApiKey** = la même valeur que la clé saisie dans WordPress.

Le mod doit envoyer cette clé à chaque requête vers l’endpoint whitelist :

- **Header** : `X-Api-Key: VOTRE_CLE`  
  **ou**  
  **Header** : `Authorization: Bearer VOTRE_CLE`

Si vous n’avez pas configuré de clé dans WordPress, ne pas mettre de clé dans le mod.

---

## 3. Vérification rapide

### 3.1 Test de l’endpoint depuis un navigateur ou cURL

Remplacer `VOTRE-SITE.com` et l’UUID par de vraies valeurs :

```bash
curl "https://VOTRE-SITE.com/wp-json/me5rine-lab/v1/minecraft-auth?uuid=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
```

- Sans clé API configurée : vous devez obtenir `{"allowed":true}` ou `{"allowed":false}`.
- Avec clé API : ajouter par exemple :  
  `-H "X-Api-Key: VOTRE_CLE"`  
  sinon vous obtiendrez une erreur 401.

### 3.2 Checklist joueur autorisé

Un joueur reçoit `{"allowed": true}` seulement si :

1. Son **UUID Minecraft** est lié à un **compte WordPress** (via OAuth / `[minecraft_link]`).
2. Ce compte a au moins un **account type**.
3. Cet account type a le module **`game_servers`** dans ses modules actifs.

Si une de ces conditions manque, l’API renvoie `{"allowed": false}`.

---

## 4. Résumé des réglages mod

| Réglage mod          | Valeur / action |
|----------------------|-----------------|
| **URL API**          | `https://VOTRE-SITE.com` (ou URL complète de l’endpoint, selon le mod). |
| **wordpressApiKey**  | Identique à la clé définie dans **Minecraft Settings** (ou vide si pas de clé). |
| **Requête**          | `GET .../minecraft-auth?uuid={uuid}` avec optionnellement `X-Api-Key` ou `Authorization: Bearer`. |

Une fois WordPress (OAuth, account types, optionnellement clé API) et le mod (URL + clé si besoin) configurés ainsi, l’API fonctionne avec le mod.
