# Génération automatique de la documentation

Ce script permet de générer automatiquement la documentation (`README.md` et `readme.txt`) avec la version actuelle du plugin.

## Utilisation

Depuis le répertoire du plugin, exécutez :

```bash
php generate-docs.php
```

## Fonctionnement

Le script :

1. Lit la version du plugin depuis `me5rine-lab.php` (ligne 6 : `Version: X.X.X`)
2. Met à jour `readme.txt` avec la version dans :
   - Le champ `Stable tag`
   - Le changelog
3. Met à jour `README.md` avec la version dans :
   - Le commentaire HTML en haut du fichier

## Prérequis

- PHP 7.4 ou supérieur
- Accès au fichier `me5rine-lab.php`

## Note

Le script fonctionne même si WordPress n'est pas chargé. Il parse directement le fichier PHP pour extraire la version.





