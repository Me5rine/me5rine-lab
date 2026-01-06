# Guide : Fichiers à Copier pour un Nouveau Plugin

Ce guide liste tous les fichiers à copier dans un autre plugin pour réutiliser la même structure CSS (front et admin) pour les modules à venir.

## 📦 Fichiers à Copier

### 1. Administration (Admin)

#### Fichiers CSS obligatoires

1. **`assets/css/admin-unified.css`**
   - **Emplacement dans le nouveau plugin** : `assets/css/admin-unified.css`
   - **Description** : Tous les styles admin unifiés (boutons, tableaux, formulaires, containers, progress bars, Select2, etc.)
   - **Action** : Copier le fichier tel quel

2. **`assets/css/global-colors.css`**
   - **Emplacement dans le nouveau plugin** : `assets/css/global-colors.css`
   - **Description** : Variables CSS centralisées (couleurs, espacements, etc.)
   - **Action** : Copier le fichier tel quel

#### Documentation (pour référence)

3. **`docs/ADMIN_CSS.md`**
   - **Emplacement dans le nouveau plugin** : `docs/ADMIN_CSS.md` (optionnel, pour documentation)
   - **Description** : Documentation complète des styles admin avec exemples HTML
   - **Action** : Copier pour référence (non obligatoire)

### 2. Front-End

#### Documentation (le CSS doit être dans le thème)

4. **`docs/FRONT_CSS.md`**
   - **Emplacement** : Dans le thème WordPress (voir `THEME_INTEGRATION.md`)
   - **Description** : Tous les styles front-end unifiés (boutons, tableaux, cartes, pagination, filtres, dashboards, profils, podiums, etc.)
   - **Action** : Copier le contenu CSS dans le thème (voir section "Intégration dans le thème" ci-dessous)

5. **`docs/TABLE_CSS.md`**
   - **Emplacement** : Dans le thème WordPress (optionnel si déjà inclus dans FRONT_CSS.md)
   - **Description** : Styles spécifiques pour les tableaux front-end avec comportement responsive WordPress admin
   - **Action** : Copier le contenu CSS dans le thème si vous utilisez des tableaux

#### JavaScript pour tableaux responsive (optionnel)

6. **`assets/js/giveaways-user-participation.js`** (extrait générique)
   - **Emplacement dans le nouveau plugin** : `assets/js/table-toggle.js` (renommer)
   - **Description** : JavaScript pour le toggle responsive des tableaux (expand/collapse)
   - **Action** : Copier et adapter le code générique (voir section "JavaScript générique" ci-dessous)

### 3. Dépendances JavaScript (si nécessaire)

7. **`assets/js/jquery.ui.touch-punch.min.js`**
   - **Emplacement dans le nouveau plugin** : `assets/js/jquery.ui.touch-punch.min.js`
   - **Description** : Support du drag & drop sur mobile (si vous utilisez des listes triables)
   - **Action** : Copier uniquement si nécessaire pour vos modules

## 🔧 Intégration dans le Plugin

### Étape 1 : Copier les fichiers CSS admin

```bash
# Dans votre nouveau plugin
mkdir -p assets/css
cp /chemin/vers/me5rine-lab/assets/css/admin-unified.css assets/css/
cp /chemin/vers/me5rine-lab/assets/css/global-colors.css assets/css/
```

### Étape 2 : Enqueue les fichiers CSS dans votre plugin

Ajoutez ce code dans le fichier principal de votre plugin :

```php
/**
 * Charger les styles CSS admin unifiés
 */
function mon_plugin_enqueue_admin_styles() {
    if (is_admin()) {
        // Variables CSS (doit être chargé en premier)
        wp_enqueue_style(
            'mon-plugin-colors',
            plugin_dir_url(__FILE__) . 'assets/css/global-colors.css',
            [],
            '1.0.0'
        );
        
        // Styles admin unifiés
        wp_enqueue_style(
            'mon-plugin-admin-unified',
            plugin_dir_url(__FILE__) . 'assets/css/admin-unified.css',
            ['mon-plugin-colors'], // Dépendance : global-colors.css
            '1.0.0'
        );
    }
}
add_action('admin_enqueue_scripts', 'mon_plugin_enqueue_admin_styles');
```

### Étape 3 : Intégration Front-End dans le Thème

**Important** : Les styles front-end doivent être dans le **thème**, pas dans le plugin.

1. Copiez le contenu de `docs/FRONT_CSS.md` (uniquement les blocs CSS entre ```css ... ```)
2. Créez un fichier dans votre thème : `assets/css/me5rine-lab-unified.css`
3. Collez le contenu CSS dans ce fichier
4. Enqueuez-le dans `functions.php` du thème :

```php
function mon_theme_enqueue_me5rine_lab_styles() {
    wp_enqueue_style(
        'me5rine-lab-unified',
        get_template_directory_uri() . '/assets/css/me5rine-lab-unified.css',
        [],
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'mon_theme_enqueue_me5rine_lab_styles');
```

Voir `THEME_INTEGRATION.md` pour plus de détails.

## 📝 JavaScript Générique pour Tableaux Responsive

Si vous utilisez des tableaux avec le comportement responsive WordPress admin (expand/collapse), créez un fichier `assets/js/table-toggle.js` :

```javascript
/**
 * Toggle responsive pour les tableaux front-end
 * Comportement WordPress admin : expand/collapse des lignes
 */
document.addEventListener('DOMContentLoaded', function () {
    // Gestion des boutons toggle
    document.querySelectorAll('.me5rine-lab-table-toggle-btn').forEach(button => {
        button.addEventListener('click', function () {
            const tr = button.closest('tr');
            const expanded = tr.classList.toggle('is-expanded');
            tr.classList.toggle('is-collapsed', !expanded);
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    });

    // Initialiser toutes les lignes toggleables comme collapsed
    document.querySelectorAll('.me5rine-lab-table tr.me5rine-lab-table-row-toggleable').forEach(tr => {
        tr.classList.add('is-collapsed');
    });
});
```

Enqueuez ce fichier dans votre plugin ou thème :

```php
// Dans le plugin (si les tableaux sont générés par le plugin)
function mon_plugin_enqueue_table_scripts() {
    wp_enqueue_script(
        'mon-plugin-table-toggle',
        plugin_dir_url(__FILE__) . 'assets/js/table-toggle.js',
        [],
        '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'mon_plugin_enqueue_table_scripts');
```

## 🎨 Préfixes des Classes

### Admin
- **Préfixe** : `admin-lab-` (vous pouvez le modifier dans `admin-unified.css` si besoin)
- **Exemples** : `.admin-lab-button-delete`, `.admin-lab-list-table`, `.admin-lab-field-label`

### Front-End
- **Préfixe** : `me5rine-lab-` (vous pouvez le modifier dans le thème si besoin)
- **Exemples** : `.me5rine-lab-table`, `.me5rine-lab-button-primary`, `.me5rine-lab-card`

## 📋 Checklist de Migration

- [ ] Copier `assets/css/admin-unified.css` dans le nouveau plugin
- [ ] Copier `assets/css/global-colors.css` dans le nouveau plugin
- [ ] Ajouter le code d'enqueue des CSS admin dans le plugin
- [ ] Copier le contenu de `docs/FRONT_CSS.md` dans le thème
- [ ] Ajouter le code d'enqueue du CSS front dans le thème
- [ ] (Optionnel) Créer `assets/js/table-toggle.js` si vous utilisez des tableaux responsive
- [ ] (Optionnel) Copier `docs/ADMIN_CSS.md` et `docs/TABLE_CSS.md` pour référence
- [ ] Tester que les styles s'appliquent correctement
- [ ] Adapter les préfixes de classes si nécessaire

## 🔗 Documentation Complémentaire

- **`THEME_INTEGRATION.md`** - Guide complet pour intégrer les styles dans le thème
- **`ADMIN_CSS.md`** - Documentation complète des styles admin avec exemples HTML
- **`FRONT_CSS.md`** - Documentation complète des styles front-end avec exemples HTML
- **`TABLE_CSS.md`** - Documentation des tableaux front-end avec structure HTML
- **`PLUGIN_INTEGRATION.md`** - Guide pour utiliser les classes dans d'autres plugins

## ⚠️ Notes Importantes

1. **Séparation Admin / Front** :
   - Les styles **admin** restent dans le **plugin**
   - Les styles **front** doivent être dans le **thème**

2. **Variables CSS** :
   - Les variables sont définies dans `global-colors.css`
   - Elles peuvent être surchargées par le thème via Elementor ou CSS custom

3. **Select2** :
   - Si vous utilisez Select2 dans l'admin, vous devez aussi enqueue Select2 :
   ```php
   wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
   wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', ['jquery'], null, true);
   ```

4. **Compatibilité** :
   - Les styles sont compatibles avec WordPress 5.0+
   - Les tableaux utilisent les classes WordPress natives (`wp-list-table`)
   - Les boutons utilisent les classes WordPress natives (`button`, `button-primary`, `button-secondary`)

