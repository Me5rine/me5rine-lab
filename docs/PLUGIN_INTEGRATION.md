# Guide d'Intégration pour Autres Plugins

Ce document explique comment utiliser **TOUTES** les classes CSS génériques (admin ET front) dans un autre plugin pour avoir un design unifié.

## 📋 Prérequis

### 1. Fichiers CSS à Copier

**Pour l'Administration (dans le plugin)** :
- Copier `assets/css/admin-unified.css` dans votre plugin
- Copier `assets/css/global-colors.css` dans votre plugin
- Voir [PLUGIN_COPY_GUIDE.md](./PLUGIN_COPY_GUIDE.md) pour les détails complets

**Pour le Front-End (dans le thème)** :
- Copier le contenu de `docs/FRONT_CSS.md` dans le thème
- Copier le contenu de `docs/TABLE_CSS.md` dans le thème (si vous utilisez des tableaux)
- Voir [THEME_INTEGRATION.md](./THEME_INTEGRATION.md) pour les détails complets

### 2. Enqueue des Fichiers CSS

**Dans votre plugin (pour l'admin)** :
```php
function mon_plugin_enqueue_admin_styles() {
    if (is_admin()) {
        wp_enqueue_style(
            'mon-plugin-colors',
            plugin_dir_url(__FILE__) . 'assets/css/global-colors.css',
            [],
            '1.0.0'
        );
        wp_enqueue_style(
            'mon-plugin-admin-unified',
            plugin_dir_url(__FILE__) . 'assets/css/admin-unified.css',
            ['mon-plugin-colors'],
            '1.0.0'
        );
    }
}
add_action('admin_enqueue_scripts', 'mon_plugin_enqueue_admin_styles');
```

**Dans le thème (pour le front)** :
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

## 🎨 Préfixes des Classes

### Administration
- **Préfixe** : `admin-lab-`
- **Exemples** : `.admin-lab-button-delete`, `.admin-lab-list-table`, `.admin-lab-field-label`, `.admin-lab-status-active`

### Front-End
- **Préfixe** : `me5rine-lab-` (ou `me5rine-lab-form-` pour les formulaires)
- **Exemples** : `.me5rine-lab-table`, `.me5rine-lab-button-primary`, `.me5rine-lab-card`, `.me5rine-lab-form-input`

---

## 🔧 ADMINISTRATION - Classes à Utiliser

### Boutons

```html
<!-- Bouton primaire WordPress -->
<button class="button button-primary">Action</button>

<!-- Bouton secondaire WordPress -->
<button class="button button-secondary">Action</button>

<!-- Bouton de suppression - Utiliser UNIQUEMENT la classe générique -->
<button class="button admin-lab-button-delete">Supprimer</button>
<!-- OU avec button-secondary ou button-danger (tous les deux donnent le style rouge) -->
<a href="#" class="button button-secondary admin-lab-button-delete">Supprimer</a>
<a href="#" class="button button-danger admin-lab-button-delete">Supprimer</a>
```

### Formulaires Admin

```html
<div class="admin-lab-form-section">
    <label class="admin-lab-field-label">Label</label>
    <input type="text" class="admin-lab-field-input" />
    <select class="admin-lab-field-select">
        <option>Option</option>
    </select>
</div>
```

### Tableaux WordPress (WP_List_Table)

```html
<table class="wp-list-table admin-lab-list-table">
    <thead>
        <tr>
            <th>Colonne</th>
            <th class="admin-lab-column-actions">Actions</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Donnée</td>
            <td class="admin-lab-column-actions">
                <div class="action-buttons">
                    <a href="#" class="button button-primary">Edit</a>
                    <a href="#" class="button button-secondary admin-lab-button-delete">Delete</a>
                </div>
            </td>
        </tr>
    </tbody>
</table>
```

### Status Indicators

```html
<!-- Utiliser UNIQUEMENT les classes génériques -->
<span class="admin-lab-status-active">✓ Active</span>
<span class="admin-lab-status-inactive">✗ Inactive</span>
<span class="admin-lab-status-pending">⏳ Pending</span>
```

### Progress Bars

```html
<div class="admin-lab-progress-container">
    <div class="admin-lab-progress-bar" style="width: 50%;">50%</div>
</div>
```

### Containers et Sections

```html
<div class="admin-lab-module-container">
    <div class="admin-lab-module-card">
        <h2>Titre</h2>
        <!-- Contenu -->
    </div>
</div>
```

---

## 🎨 FRONT-END - Classes à Utiliser

### Formulaires Front-End

#### Structure de Base

```html
<div class="me5rine-lab-form-container">
    <h2 class="me5rine-lab-form-title">Titre de la Section</h2>
    <p class="me5rine-lab-form-subtitle">Description optionnelle</p>
    <form class="me5rine-lab-form-section">
        <!-- Vos champs ici -->
    </form>
</div>
```

#### Ligne avec 2 Colonnes

```html
<div class="me5rine-lab-form-row">
    <div class="me5rine-lab-form-col">
        <div class="me5rine-lab-form-field">
            <label class="me5rine-lab-form-label" for="nom">Nom</label>
            <input type="text" id="nom" class="me5rine-lab-form-input" />
        </div>
    </div>
    <div class="me5rine-lab-form-col">
        <div class="me5rine-lab-form-field">
            <label class="me5rine-lab-form-label" for="prenom">Prénom</label>
            <input type="text" id="prenom" class="me5rine-lab-form-input" />
        </div>
    </div>
</div>
```

#### Champs de Formulaire

```html
<!-- Input text, email, tel, url, number -->
<input type="text" class="me5rine-lab-form-input" id="username" name="username" />

<!-- Select/Dropdown -->
<select class="me5rine-lab-form-select" id="country" name="country">
    <option value="">-- Sélectionner --</option>
    <option value="fr">France</option>
</select>

<!-- Textarea -->
<textarea class="me5rine-lab-form-textarea" id="message" name="message"></textarea>
```

#### Boutons Front-End

```html
<!-- Bouton principal -->
<button type="submit" class="me5rine-lab-form-button">Enregistrer</button>

<!-- Bouton secondaire -->
<button type="button" class="me5rine-lab-form-button me5rine-lab-form-button-secondary">Annuler</button>

<!-- Bouton de suppression -->
<button type="button" class="me5rine-lab-form-button me5rine-lab-form-button-remove">Supprimer</button>
```

### Tableaux Front-End

```html
<table class="me5rine-lab-table me5rine-lab-table-{type} striped">
    <thead>
        <tr>
            <th><span class="unsorted-column">Titre</span></th>
            <th><span class="unsorted-column">Date</span></th>
            <th><span class="unsorted-column">Statut</span></th>
        </tr>
    </thead>
    <tbody>
        <!-- Exemple de titre de section (optionnel) -->
        <tr>
            <td class="me5rine-lab-table-section-title" colspan="3">
                <strong>Section Title</strong>
            </td>
        </tr>
        
        <!-- Ligne de données standard -->
        <tr class="me5rine-lab-table-row-toggleable is-collapsed">
            <td class="summary" data-colname="Titre">
                <div class="me5rine-lab-table-summary-row">
                    <div>
                        <span class="me5rine-lab-table-title">
                            <a href="#">Mon titre</a>
                        </span>
                        <div class="row-actions">
                            <span class="view"><a href="#">View</a></span>
                            <span class="edit"><a href="#">Edit</a></span>
                        </div>
                    </div>
                </div>
                <button type="button" class="me5rine-lab-table-toggle-btn" aria-expanded="false">
                    <span class="me5rine-lab-sr-only">Afficher plus de détails</span>
                </button>
            </td>
            <td class="details" data-colname="Date">01/01/2024</td>
            <td class="details" data-colname="Statut">Actif</td>
        </tr>
    </tbody>
</table>
```

**JavaScript pour tableaux responsive** (à ajouter dans votre plugin) :
```javascript
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.me5rine-lab-table-toggle-btn').forEach(button => {
        button.addEventListener('click', function () {
            const tr = button.closest('tr');
            const expanded = tr.classList.toggle('is-expanded');
            tr.classList.toggle('is-collapsed', !expanded);
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    });

    document.querySelectorAll('.me5rine-lab-table tr.me5rine-lab-table-row-toggleable').forEach(tr => {
        tr.classList.add('is-collapsed');
    });
});
```

### Cartes (Cards)

```html
<!-- Carte simple -->
<div class="me5rine-lab-card me5rine-lab-card-bordered">
    <h3 class="me5rine-lab-title">Titre</h3>
    <p class="me5rine-lab-subtitle">Description</p>
</div>

<!-- Carte avec image -->
<div class="me5rine-lab-card-with-image me5rine-lab-card me5rine-lab-card-bordered-left">
    <img class="me5rine-lab-card-image" src="image.jpg" alt="Title">
    <div class="me5rine-lab-card-content">
        <div class="me5rine-lab-card-header">
            <h4 class="me5rine-lab-card-name">
                <a href="#">Card Title</a>
            </h4>
        </div>
        <p class="me5rine-lab-card-description">Description</p>
        <a href="#" class="me5rine-lab-form-button me5rine-lab-card-button">Action</a>
    </div>
</div>
```

### Pagination

```html
<div class="me5rine-lab-pagination">
    <span class="me5rine-lab-pagination-info">10 résultats</span>
    <div class="me5rine-lab-pagination-links">
        <a href="#" class="me5rine-lab-pagination-button me5rine-lab-pagination-button-active">1</a>
        <a href="#" class="me5rine-lab-pagination-button">2</a>
        <a href="#" class="me5rine-lab-pagination-button">3</a>
    </div>
</div>
```

### Filtres

```html
<div class="me5rine-lab-filters">
    <form method="get">
        <div class="me5rine-lab-filter-group">
            <label class="me5rine-lab-form-label me5rine-lab-filter-label">
                <span class="me5rine-lab-filter-label-mobile">Show:</span>
                <span class="me5rine-lab-filter-label-desktop">Filter by status:</span>
            </label>
            <select name="filter" class="me5rine-lab-form-select me5rine-lab-filter-select">
                <option value="">All</option>
                <option value="active">Active</option>
            </select>
        </div>
    </form>
</div>
```

### Dashboards

**IMPORTANT** : Toutes les pages front doivent respecter cette structure :
1. Une div wrapper `<div class="me5rine-lab-dashboard">` qui englobe tout le contenu de la page
2. Un titre principal `<h2 class="me5rine-lab-title-large">` comme premier élément après l'ouverture de la div

```html
<div class="me5rine-lab-dashboard">
    <h2 class="me5rine-lab-title-large">Dashboard Title</h2>
    <div class="me5rine-lab-dashboard-header">
        <a href="#" class="me5rine-lab-form-button">Action</a>
    </div>
    
    <div class="notice">Message d'information</div>
    
    <!-- Contenu du dashboard -->
</div>
```

### Structures des Conteneurs Front-End

Il existe **trois structures distinctes** selon le contexte :

#### 1. Profils Ultimate Member (UM)

**Uniquement pour les éléments dans les profils Ultimate Member.**

```html
<div class="me5rine-lab-profile-container">
    <h2 class="me5rine-lab-title"><?php _e('Titre de la section', 'text-domain'); ?></h2>
    <p class="me5rine-lab-subtitle"><?php _e('Sous-titre optionnel', 'text-domain'); ?></p>
    
    <!-- Contenu -->
</div>
```

**Exemple :**
```html
<div class="me5rine-lab-profile-container">
    <h2 class="me5rine-lab-title"><?php _e('My Giveaway Entries', 'giveaways'); ?></h2>
    
    <div class="me5rine-lab-form-container">
        <form method="get" class="me5rine-lab-filters">
            <!-- Filtres -->
        </form>
    </div>
    
    <table class="me5rine-lab-table">
        <!-- Tableau -->
    </table>
</div>
```

#### 2. Dashboards Front-End

**Pour les pages de dashboard front (liste, gestion, etc.).**

```html
<div class="{nom-du-dashboard} me5rine-lab-dashboard">
    <h2 class="me5rine-lab-title-large"><?php _e('Titre du dashboard', 'text-domain'); ?></h2>
    
    <!-- Contenu -->
</div>
```

**Exemples :**
```html
<!-- Dashboard socials -->
<div class="socials-dashboard me5rine-lab-dashboard">
    <h2 class="me5rine-lab-title-large"><?php esc_html_e('My Socialls', 'me5rine-lab'); ?></h2>
    
    <!-- Contenu -->
</div>

<!-- Dashboard giveaways -->
<div class="my-giveaways-dashboard me5rine-lab-dashboard">
    <h2 class="me5rine-lab-title-large"><?php esc_html_e('My Giveaways', 'me5rine-lab'); ?></h2>
    
    <!-- Contenu -->
</div>
```

#### 3. Formulaires Front Complets

**Pour les formulaires complets (création/édition).**

```html
<div class="{nom-du-formulaire}-dashboard me5rine-lab-dashboard">
    <h2 class="me5rine-lab-title-large"><?php _e('Titre du formulaire', 'text-domain'); ?></h2>
    
    <form>
        <div class="me5rine-lab-form-block">
            <h3 class="me5rine-lab-title-medium"><?php _e('Section', 'text-domain'); ?></h3>
            <!-- Champs -->
        </div>
    </form>
</div>
```

**Exemple :**
```html
<div class="campaign-form-dashboard me5rine-lab-dashboard">
    <h2 class="me5rine-lab-title-large"><?php _e('Create a Giveaway Campaign', 'me5rine-lab'); ?></h2>
    
    <form id="rafflepress-campaign-form" method="post">
        <div class="me5rine-lab-form-block">
            <h3 class="me5rine-lab-title-medium"><?php _e('Title and dates', 'me5rine-lab'); ?></h3>
            <!-- Champs -->
        </div>
        
        <div class="me5rine-lab-form-block">
            <h3 class="me5rine-lab-title-medium"><?php _e('Prizes', 'me5rine-lab'); ?></h3>
            <!-- Champs -->
        </div>
    </form>
</div>
```

**Important** : Le titre principal (`h2`) est à l'extérieur du formulaire, les titres de sections (`h3`) sont **à l'intérieur** des `me5rine-lab-form-block`.

### Messages et Notices

**Système unifié** : Toutes les notices front-end utilisent maintenant `me5rine-lab-form-message` avec les variantes de type.

#### HTML Direct

```html
<!-- Message de succès -->
<div class="me5rine-lab-form-message me5rine-lab-form-message-success">
    <p>Opération réussie !</p>
</div>

<!-- Message d'erreur -->
<div class="me5rine-lab-form-message me5rine-lab-form-message-error">
    <p>Une erreur est survenue.</p>
</div>

<!-- Message d'avertissement -->
<div class="me5rine-lab-form-message me5rine-lab-form-message-warning">
    <p>Attention !</p>
</div>

<!-- Message d'information -->
<div class="me5rine-lab-form-message me5rine-lab-form-message-info">
    <p>Information importante.</p>
</div>

<!-- Message d'état vide -->
<p class="me5rine-lab-state-message">
    Aucun élément disponible.
</p>
```

#### Fonctions PHP

**1. Notice via paramètres GET (redirection)**

```php
// Rediriger avec notice
$redirect_url = add_query_arg([
    'notice' => 'success',
    'notice_msg' => rawurlencode('Votre concours a été créé avec succès !')
], $url);
wp_safe_redirect($redirect_url);
exit;

// Afficher la notice dans le template
me5rine_display_profile_notice();
```

**2. Notice via transient (formulaires)**

```php
// Définir le transient après traitement
set_transient('rafflepress_campaign_success', 'Votre concours a été créé avec succès !', 60);

// Afficher la notice dans le template
display_transient_message_front('rafflepress_campaign_success', 'success');
display_transient_message_front('rafflepress_duplicate_error', 'error');
display_transient_message_front('rafflepress_file_error', 'error');
```

**Types disponibles** : `success`, `error`, `warning`, `info`

### Podium (Top 3) - Composant Réutilisable

```html
<div class="me5rine-lab-podium-wrapper">
    <div class="me5rine-lab-podium">
        <!-- 2ème place (gauche) -->
        <div class="me5rine-lab-podium-step me5rine-lab-podium-2 animate">
            <div class="me5rine-lab-podium-rank">2</div>
            <a href="#">Item Name</a>
            <div class="me5rine-lab-podium-info">Info text</div>
        </div>
        
        <!-- 1ère place (centre) -->
        <div class="me5rine-lab-podium-step me5rine-lab-podium-1 animate">
            <div class="me5rine-lab-podium-rank">1</div>
            <a href="#">Item Name</a>
            <div class="me5rine-lab-podium-info">Info text</div>
        </div>
        
        <!-- 3ème place (droite) -->
        <div class="me5rine-lab-podium-step me5rine-lab-podium-3 animate">
            <div class="me5rine-lab-podium-rank">3</div>
            <a href="#">Item Name</a>
            <div class="me5rine-lab-podium-info">Info text</div>
        </div>
    </div>
</div>
```

**Note** : L'ordre d'affichage dans le HTML doit être 2, 1, 3 pour que l'affichage visuel soit correct (1 au centre, 2 à gauche, 3 à droite).

---

## 📋 Checklist Complète

### Pour l'Administration
- [ ] Copier `assets/css/admin-unified.css` dans le plugin
- [ ] Copier `assets/css/global-colors.css` dans le plugin
- [ ] Enqueue les deux fichiers CSS dans le plugin
- [ ] Utiliser les classes `admin-lab-*` dans le HTML
- [ ] Utiliser `button button-primary` / `button button-secondary` pour les boutons WordPress
- [ ] Utiliser `admin-lab-button-delete` pour les boutons de suppression
- [ ] Utiliser `admin-lab-status-active` / `admin-lab-status-inactive` pour les statuts
- [ ] Utiliser `admin-lab-list-table` pour les tableaux WordPress

### Pour le Front-End
- [ ] Copier le contenu de `docs/FRONT_CSS.md` dans le thème
- [ ] Copier le contenu de `docs/TABLE_CSS.md` dans le thème (si tableaux)
- [ ] Enqueue le fichier CSS dans le thème
- [ ] Utiliser les classes `me5rine-lab-*` dans le HTML
- [ ] Utiliser `me5rine-lab-form-*` pour les formulaires
- [ ] Utiliser `me5rine-lab-table` pour les tableaux
- [ ] Ajouter le JavaScript pour les tableaux responsive (si nécessaire)

---

## 🔗 Documentation Complémentaire

- **[PLUGIN_COPY_GUIDE.md](./PLUGIN_COPY_GUIDE.md)** - Guide complet : Fichiers à copier pour réutiliser la structure
- **[THEME_INTEGRATION.md](./THEME_INTEGRATION.md)** - Guide complet pour intégrer les styles dans le thème
- **[ADMIN_CSS.md](./ADMIN_CSS.md)** - Documentation complète des styles admin avec exemples HTML
- **[FRONT_CSS.md](./FRONT_CSS.md)** - Documentation complète des styles front-end avec exemples HTML
- **[TABLE_CSS.md](./TABLE_CSS.md)** - Documentation des tableaux front-end avec structure HTML
- **[CSS_SYSTEM.md](./CSS_SYSTEM.md)** - Liste complète de toutes les classes disponibles

---

## ⚠️ Notes Importantes

1. **Séparation Admin / Front** :
   - Les styles **admin** restent dans le **plugin**
   - Les styles **front** doivent être dans le **thème**

2. **Préfixes** :
   - Admin : `admin-lab-`
   - Front : `me5rine-lab-` ou `me5rine-lab-form-`

3. **Variables CSS** :
   - Les variables sont définies dans `global-colors.css`
   - Elles peuvent être surchargées par le thème via Elementor ou CSS custom

4. **Select2 (Admin)** :
   - **📖 Consultez [SELECT2_INITIALIZATION.md](./SELECT2_INITIALIZATION.md) pour le guide complet** sur l'initialisation de Select2 dans différents contextes (admin, front-end, meta boxes, filtres, etc.)
   - Si vous utilisez Select2 dans l'admin, vous devez aussi enqueue Select2 :
   ```php
   wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
   wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', ['jquery'], null, true);
   ```

5. **Compatibilité** :
   - Les styles sont compatibles avec WordPress 5.0+
   - Les tableaux utilisent les classes WordPress natives (`wp-list-table`)
   - Les boutons utilisent les classes WordPress natives (`button`, `button-primary`, `button-secondary`)
