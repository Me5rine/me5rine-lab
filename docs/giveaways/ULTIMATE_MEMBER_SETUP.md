# Configuration Ultimate Member - Onglet Pokémon GO

## Problème : L'onglet ne s'affiche pas

Si l'onglet "Pokémon GO" ne s'affiche pas dans les profils Ultimate Member, voici les étapes pour le configurer.

## Le shortcode ne s'affiche pas

Si `[poke_hub_user_profile]` ne retourne rien :

1. **Activer le mode debug** : Ajoutez `debug="true"` au shortcode pour voir les informations de débogage
2. **Vérifier que le module est activé** : Allez dans **Poké HUB > Settings** et vérifiez que le module `user-profiles` est bien activé
3. **Vérifier que les fonctions existent** : Le debug doit montrer que `poke_hub_get_user_profile`, `poke_hub_get_teams`, et `poke_hub_get_reasons` existent
4. **Éviter la classe `um-profile-note`** : Cette classe a `display: none` en CSS, assurez-vous que le shortcode n'est pas dans un élément avec cette classe

Si le shortcode retourne du contenu en debug mais rien en mode normal, vérifiez les classes CSS qui pourraient masquer le contenu.

## Méthode 1 : Via l'extension "Profile Tabs" (Recommandé)

Si vous avez l'extension **Ultimate Member - Profile Tabs** installée :

1. Allez dans **Ultimate Member > Profile Tabs**
2. Cliquez sur **"Add New"**
3. Configurez l'onglet :
   - **Title** : `Pokémon GO`
   - **Tab ID/Slug** : `pokehub-profile`
   - **Content Type** : Sélectionnez "Custom Content" ou laissez vide
   - **Content** : Laissez vide (le contenu est géré par notre plugin)
4. Cliquez sur **"Publish"**

L'onglet devrait maintenant apparaître et utiliser automatiquement notre code pour afficher le contenu.

## Méthode 2 : Configuration manuelle dans Ultimate Member

Si vous n'avez pas l'extension Profile Tabs, vous pouvez créer l'onglet manuellement via les hooks WordPress :

1. Vérifiez que le module `user-profiles` est activé dans **Poké HUB > Settings**
2. Vérifiez que Ultimate Member est installé et actif
3. Videz le cache WordPress si vous utilisez un plugin de cache
4. Testez sur une page de profil utilisateur : `/user/{username}/?profiletab=pokehub-profile`

## Méthode 3 : Vérification et Debug

Ajoutez ce code temporairement dans votre `functions.php` pour vérifier si les hooks fonctionnent :

```php
// Debug Ultimate Member tabs
add_action('wp_footer', function() {
    if (function_exists('um_is_core_page') && um_is_core_page('user')) {
        $tabs = apply_filters('um_profile_tabs', []);
        echo '<!-- Ultimate Member Tabs: ' . print_r($tabs, true) . ' -->';
    }
});
```

Cela affichera dans le code source HTML quels onglets sont enregistrés.

## Vérifications

- ✅ Le module `user-profiles` est activé dans Poké HUB
- ✅ Ultimate Member est installé et actif
- ✅ Vous êtes sur une page de profil Ultimate Member
- ✅ Vous avez vidé le cache

## Contact

Si l'onglet n'apparaît toujours pas après ces étapes, il peut y avoir un conflit avec votre thème ou un autre plugin.

