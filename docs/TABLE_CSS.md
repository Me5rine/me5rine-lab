# Règles CSS Génériques pour les Tableaux

Ces règles CSS doivent être copiées dans votre fichier CSS de thème (ex: `assets/css/tables.css` ou `style.css`).

**Préfixe des classes** : `me5rine-lab-table-` (vous pouvez le modifier dans votre thème si besoin)

## Variables CSS

Utilisez les variables CSS pour un design cohérent. Les variables Ultimate Member sont prioritaires, avec fallback sur les variables admin-lab :

```css
:root {
    /* Variables Ultimate Member (prioritaires) */
    --um-bg: var(--admin-lab-color-white, #ffffff);
    --um-bg-secondary: var(--admin-lab-color-th-background, #F9FAFB);
    --um-text: var(--admin-lab-color-header-text, #11161E);
    --um-text-light: var(--admin-lab-color-text, #5D697D);
    --um-border: var(--admin-lab-color-borders, #DEE5EC);
    --um-border-light: #B5C2CF;
    --um-primary: var(--e-global-color-primary, #2E576F);
    --um-secondary: var(--admin-lab-color-secondary, #0485C8);
}
```

## Tableau Générique

Style inspiré du design table-05, adapté avec le comportement responsive WordPress admin :

```css
/* Tableau générique - Style table-05 adapté */
.me5rine-lab-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--um-bg, #ffffff);
    border: none;
    border-radius: 0;
    overflow: hidden;
    box-shadow: 0px 5px 12px -12px rgba(0, 0, 0, 0.29);
    margin-top: 20px;
    margin-bottom: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    font-size: 14px;
    line-height: 1.5;
    color: var(--um-text-light, #5D697D);
}

/* En-tête du tableau - Style table-05 */
.me5rine-lab-table thead {
    background: var(--um-bg, #ffffff);
    border-bottom: 4px solid #eceffa;
}

.me5rine-lab-table th {
    padding: 30px;
    text-align: left;
    font-weight: 500;
    font-size: 13px;
    color: var(--um-text-light, #5D697D);
    text-transform: none;
    letter-spacing: 0;
    border: none;
    vertical-align: middle;
    white-space: nowrap;
}

.me5rine-lab-table th:first-child {
    padding-left: 30px;
}

.me5rine-lab-table th:last-child {
    padding-right: 30px;
}

.me5rine-lab-table th .unsorted-column {
    display: inline-block;
}

/* Colonnes triables - Style WordPress */
.me5rine-lab-table th.sortable a,
.me5rine-lab-table th.sorted a {
    color: var(--um-text-light, #5D697D);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.me5rine-lab-table th.sortable a:hover,
.me5rine-lab-table th.sorted a:hover {
    color: var(--um-secondary, #0485C8);
}

.me5rine-lab-table th.sorted.asc a,
.me5rine-lab-table th.sorted.desc a {
    color: var(--um-secondary, #0485C8);
}

/* Indicateurs de tri */
.me5rine-lab-table .sorting-indicators {
    display: inline-flex;
    flex-direction: column;
    margin-left: 4px;
    vertical-align: middle;
}

.me5rine-lab-table .sorting-indicator {
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    margin: 1px 0;
    opacity: 0.3;
}

.me5rine-lab-table .sorting-indicator.asc {
    border-bottom: 4px solid currentColor;
    margin-bottom: 2px;
}

.me5rine-lab-table .sorting-indicator.desc {
    border-top: 4px solid currentColor;
    margin-top: 2px;
}

.me5rine-lab-table th.sorted.asc .sorting-indicator.asc,
.me5rine-lab-table th.sorted.desc .sorting-indicator.desc {
    opacity: 1;
}

/* Corps du tableau - Style table-05 */
.me5rine-lab-table tbody tr {
    margin-bottom: 0;
    border-bottom: 4px solid #f8f9fd;
    background: var(--um-bg, #ffffff);
    transition: background-color 0.15s ease;
}

.me5rine-lab-table tbody tr:last-child {
    border-bottom: none;
}

.me5rine-lab-table tbody tr:hover {
    background: var(--um-bg-secondary, #F9FAFB);
}

.me5rine-lab-table tbody tr.me5rine-lab-table-row-toggleable.is-expanded {
    background: var(--um-bg-secondary, #F9FAFB);
}

.me5rine-lab-table tbody th,
.me5rine-lab-table td {
    border: none;
    padding: 30px;
    font-size: 14px;
    background: transparent;
    vertical-align: middle;
    color: var(--um-text-light, #5D697D);
}

/* Cellule de résumé (première colonne avec titre) - Style WordPress admin */
.me5rine-lab-table td.summary {
    position: relative;
    padding-right: 50px;
    font-weight: 500;
}

/* Ligne de résumé dans une cellule */
.me5rine-lab-table-summary-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    justify-content: space-between;
}

.me5rine-lab-table-summary-row > div {
    flex: 1;
}

/* Titre dans une cellule de tableau - Style table-05 */
.me5rine-lab-table-title {
    font-weight: 500;
    font-size: 14px;
    color: var(--um-text, #11161E);
    display: block;
    margin-bottom: 4px;
}

.me5rine-lab-table-title a {
    color: var(--um-text, #11161E);
    text-decoration: none;
    transition: color 0.15s ease;
}

.me5rine-lab-table-title a:hover {
    color: var(--um-secondary, #0485C8);
}

/* Actions de ligne (Edit, View, etc.) - Style WordPress admin */
.me5rine-lab-table .row-actions {
    display: block;
    margin-top: 4px;
    font-size: 11px;
    line-height: 1.6;
}

.me5rine-lab-table .row-actions span {
    display: inline;
}

.me5rine-lab-table .row-actions span:not(:last-child)::after {
    content: ' | ';
    color: var(--um-text-light, #5D697D);
    margin: 0 4px;
}

.me5rine-lab-table .row-actions a {
    color: var(--um-text-light, #5D697D);
    text-decoration: none;
    transition: color 0.15s ease;
}

.me5rine-lab-table .row-actions a:hover {
    color: var(--um-secondary, #0485C8);
}

/* Bouton pour expander/réduire une ligne - Style WordPress admin */
.me5rine-lab-table-toggle-btn {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 28px;
    height: 28px;
    border: 1px solid var(--um-border, #DEE5EC);
    border-radius: 3px;
    background: var(--um-bg, #ffffff);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
    padding: 0;
    box-shadow: 0 1px 0 rgba(0, 0, 0, 0.05);
}

.me5rine-lab-table-toggle-btn:hover {
    border-color: var(--um-secondary, #0485C8);
    background: var(--um-bg-secondary, #F9FAFB);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.me5rine-lab-table-toggle-btn:focus {
    outline: none;
    border-color: var(--um-secondary, #0485C8);
    box-shadow: 0 0 0 1px var(--um-secondary, #0485C8);
}

.me5rine-lab-table-toggle-btn::before {
    content: '';
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-top: 5px solid var(--um-text-light, #5D697D);
    transition: transform 0.15s ease, border-top-color 0.15s ease;
}

.me5rine-lab-table-toggle-btn[aria-expanded="true"]::before,
.me5rine-lab-table-row-toggleable.is-expanded .me5rine-lab-table-toggle-btn::before {
    transform: rotate(180deg);
}

.me5rine-lab-table-toggle-btn:hover::before {
    border-top-color: var(--um-secondary, #0485C8);
}

/* Texte accessible uniquement aux lecteurs d'écran */
.me5rine-lab-sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

/* Colonnes de détails (masquées sur mobile) */
.me5rine-lab-table td.details {
    display: table-cell;
}

/* Masquer le bouton toggle sur desktop */
@media screen and (min-width: 783px) {
    .me5rine-lab-table .me5rine-lab-table-toggle-btn {
        display: none;
    }

    .me5rine-lab-table td.summary {
        padding-right: 30px;
    }
}

/* Labels de colonnes sur mobile (via data-colname) */
.me5rine-lab-table td[data-colname]::before {
    content: attr(data-colname) ": ";
    font-weight: 600;
    color: var(--um-text, #11161E);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 12px;
    margin-right: 8px;
}

/* Titre de section dans un tableau (ligne de séparation avec titre) */
.me5rine-lab-table-section-title {
    text-align: center;
    background-color: var(--um-secondary, #0485C8);
    color: var(--um-bg, #ffffff);
    text-transform: uppercase;
    font-weight: 500;
    padding: 12px 16px;
}

/* Inputs dans les tableaux */
.me5rine-lab-table input[type="text"],
.me5rine-lab-table input[type="email"],
.me5rine-lab-table input[type="number"],
.me5rine-lab-table input[type="url"],
.me5rine-lab-table input[type="tel"],
.me5rine-lab-table textarea,
.me5rine-lab-table select {
    width: 100%;
    min-width: 100%;
    box-sizing: border-box;
}

.me5rine-lab-table input[type="checkbox"] {
    min-width: auto;
    width: auto;
}

.me5rine-lab-table input[type="checkbox"]:focus,
.me5rine-lab-table input[type="text"]:focus,
.me5rine-lab-table input[type="email"]:focus,
.me5rine-lab-table input[type="number"]:focus,
.me5rine-lab-table input[type="url"]:focus,
.me5rine-lab-table input[type="tel"]:focus,
.me5rine-lab-table textarea:focus,
.me5rine-lab-table select:focus {
    outline: none;
    border-color: var(--um-secondary, #0485C8);
    box-shadow: 0 0 0 3px rgba(4, 133, 200, 0.1);
}

/* Styles pour tableaux avec classe striped (lignes alternées) - Style table-05 */
.me5rine-lab-table.striped tbody tr:nth-child(odd) {
    background-color: var(--um-bg, #ffffff);
}

.me5rine-lab-table.striped tbody tr:nth-child(even) {
    background-color: var(--um-bg-secondary, #F9FAFB);
}

.me5rine-lab-table.striped tbody tr:nth-child(odd):hover,
.me5rine-lab-table.striped tbody tr:nth-child(even):hover {
    background-color: var(--um-bg-secondary, #F9FAFB);
}

/* Responsive : Mobile avec comportement WordPress admin */
@media screen and (max-width: 782px) {
    .me5rine-lab-table {
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-top: 16px;
        margin-bottom: 16px;
        border-radius: 0;
        box-shadow: 0px 5px 12px -12px rgba(0, 0, 0, 0.29);
    }

    .me5rine-lab-table thead {
        display: none;
    }

    .me5rine-lab-table tbody {
        display: block;
    }

    .me5rine-lab-table tbody tr {
        display: block;
        margin-bottom: 12px;
        border: none;
        border-radius: 0;
        background: var(--um-bg, #ffffff);
        overflow: hidden;
        box-shadow: 0px 5px 12px -12px rgba(0, 0, 0, 0.29);
    }

    .me5rine-lab-table tbody tr:last-child {
        margin-bottom: 0;
    }

    .me5rine-lab-table td {
        display: block;
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid var(--um-border, #DEE5EC);
        border-left: none;
        border-right: none;
    }

    .me5rine-lab-table td:last-child {
        border-bottom: none;
    }

    .me5rine-lab-table td.summary {
        padding-right: 50px;
        background: var(--um-bg-secondary, #F9FAFB);
        font-weight: 600;
        border-bottom: 1px solid var(--um-border, #DEE5EC);
    }

    .me5rine-lab-table td.details {
        display: none;
    }

    .me5rine-lab-table-row-toggleable.is-expanded td.details {
        display: block;
    }

    .me5rine-lab-table .me5rine-lab-table-toggle-btn {
        display: flex;
    }

    /* Labels de colonnes sur mobile */
    .me5rine-lab-table td[data-colname]::before {
        content: attr(data-colname) ": ";
        font-weight: 600;
        color: var(--um-text, #11161E);
        text-transform: none;
        letter-spacing: 0;
        font-size: 12px;
        margin-right: 8px;
    }

    .me5rine-lab-table td.summary[data-colname]::before {
        display: none;
    }

    /* Titre de section sur mobile */
    .me5rine-lab-table-section-title {
        padding: 10px;
        font-size: 12px;
    }

    /* Summary sur mobile - ajustements spécifiques si nécessaire */
    .me5rine-lab-table td.summary {
        padding: 10px 12px;
    }
}
```

## Classes Spécifiques par Tableau

Chaque tableau doit avoir une classe spécifique en plus de la classe générique `.me5rine-lab-table` pour permettre des styles spécifiques si nécessaire. Cette classe spécifique est ajoutée au tableau lui-même, mais **toutes les classes à l'intérieur du tableau doivent être génériques**.

### Classes spécifiques disponibles

- `.me5rine-lab-table-giveaways-participations` - Tableau des participations aux concours (tab "mes concours")
- `.me5rine-lab-table-giveaways-dashboard` - Tableau de gestion des concours (dashboard partenaire)
- `.me5rine-lab-table-giveaways-promo` - Tableau des concours actifs (promo)
- `.me5rine-lab-table-socials` - Tableau de gestion des réseaux sociaux

### Règles d'Unification - Aucun Style Spécifique

**IMPORTANT** : Tous les éléments des tableaux utilisent UNIQUEMENT des classes génériques. Aucun style spécifique ne doit être ajouté dans les fichiers CSS du plugin.

**Classes génériques disponibles pour tous les éléments de tableau** :
- `.me5rine-lab-table-section-title` - Titre de section dans un tableau (ligne de séparation)
- `.me5rine-lab-table-title` - Titre dans une cellule
- `.me5rine-lab-table-summary-row` - Ligne de résumé
- `.summary` - Cellule de résumé (première colonne)
- `.details` - Cellules de détails
- `.row-actions` - Actions de ligne (Edit, View, etc.)
- `.me5rine-lab-table-toggle-btn` - Bouton toggle mobile

**Tous les styles sont dans TABLE_CSS.md** - Aucun CSS spécifique ne doit être ajouté dans les fichiers CSS du plugin.

Si vous avez vraiment besoin d'un style spécifique (cas très rare), vous pouvez l'ajouter dans votre thème en utilisant la classe spécifique du tableau :

```css
/* Exemple très rare de style spécifique dans le thème */
.me5rine-lab-table-giveaways-participations {
    /* Style vraiment spécifique si nécessaire */
}
```

**Principe** : 
- La classe spécifique est UNIQUEMENT sur le `<table>` (ex: `me5rine-lab-table-giveaways-participations`)
- Tous les éléments internes utilisent UNIQUEMENT des classes génériques (`me5rine-lab-table-*`)
- Pour cibler un élément spécifique d'un tableau, utilisez le sélecteur : `.me5rine-lab-table-{type} .me5rine-lab-table-{element}`

## Utilisation

Ce CSS générique s'applique automatiquement à tous les tableaux utilisant la classe `.me5rine-lab-table`. Les styles sont unifiés pour tous les contextes (profil Ultimate Member, dashboard front, etc.).

### Structure HTML standardisée

Tous les tableaux front doivent suivre cette structure. La classe spécifique est UNIQUEMENT sur le `<table>`, tous les éléments internes utilisent des classes génériques :

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

Où `{type}` est remplacé par le type de tableau (ex: `giveaways-participations`, `giveaways-dashboard`, `socials`, `giveaways-promo`) et est UNIQUEMENT sur le `<table>`.

### Règles de structure

1. **Classe du tableau** : `me5rine-lab-table` + classe spécifique (ex: `me5rine-lab-table-giveaways-participations`) + optionnel `striped` pour les lignes alternées
   - **La classe spécifique est UNIQUEMENT sur le `<table>`**, pas sur les éléments internes
2. **En-têtes** : 
   - `<tr>` : aucune classe spécifique
   - `<th>` : utiliser `<span class="unsorted-column">` pour les colonnes non triables
3. **Lignes** : `me5rine-lab-table-row-toggleable is-collapsed` pour les lignes expandables (classes génériques uniquement)
4. **Cellules** :
   - Première cellule : `class="summary"` avec `data-colname` pour le label mobile
   - Autres cellules : `class="details"` avec `data-colname` pour le label mobile
5. **Éléments internes** (tous avec classes génériques uniquement) :
   - `<div class="me5rine-lab-table-summary-row">` : conteneur flex pour le contenu et le bouton toggle
   - `<div>` : conteneur pour le titre et les actions (à l'intérieur de `summary-row`)
   - `<span class="me5rine-lab-table-title">` : titre cliquable avec lien vers l'élément
   - `<div class="row-actions">` : actions en dessous du titre (Edit, View, etc.) - style WordPress admin
   - `<button class="me5rine-lab-table-toggle-btn" aria-expanded="false">` : bouton toggle pour mobile
6. **Titre cliquable** : Le titre doit toujours être un lien (`<a>`) vers l'élément (comportement WordPress admin standard). Le lien pointe vers la page de l'élément (ex: `get_permalink($post)`).
7. **Row actions** : Petits liens en dessous du titre, séparés par ` | ` (pipe), toujours visibles. Format : `<div class="row-actions"><span class="view"><a href="#">View</a></span><span class="edit"><a href="#">Edit</a></span></div>`. Le séparateur ` | ` est ajouté automatiquement par le CSS via `::after` sur les spans.
8. **Bouton toggle** : Toujours inclure `aria-expanded="false"` et un texte accessible avec `me5rine-lab-sr-only`

**Principe** : Tous les éléments internes utilisent UNIQUEMENT des classes génériques. Pour cibler un élément spécifique d'un tableau, utilisez le sélecteur CSS : `.me5rine-lab-table-{type} .me5rine-lab-table-{element}`

## Comportement WordPress Admin Standard

Tous les tableaux suivent le comportement WordPress admin standard :

1. **Titre cliquable** : Le titre est toujours un lien vers l'élément (ex: `get_permalink($post)`)
2. **Row actions** : Petits liens en dessous du titre (Edit, View, etc.), séparés par ` | `, toujours visibles
3. **Pas de gros boutons** : Les actions sont des petits liens textuels, pas des boutons volumineux
4. **Structure standardisée** : Le titre et les actions sont dans un conteneur `<div>` à l'intérieur de `summary-row`

## Comportement Responsive

Le système de tableaux utilise le comportement responsive WordPress admin :
- **Desktop (≥783px)** : Affichage classique en tableau, toutes les colonnes visibles
- **Mobile (<783px)** : 
  - Les colonnes de détails sont masquées par défaut
  - Seule la colonne `summary` (avec le titre et les row-actions) est visible
  - Un bouton toggle permet d'expand/reduce la ligne pour afficher les détails
  - Les labels de colonnes sont affichés via `data-colname` avec `::before`

## Notes importantes

1. **CSS dans le thème** : Ce CSS doit être dans le thème, pas dans le plugin
2. **Variables CSS** : Assurez-vous que les variables CSS sont définies dans votre thème
3. **Responsive** : Les tableaux s'adaptent automatiquement sur mobile avec le comportement WordPress admin (toggle)
4. **Accessibilité** : Utilisez toujours `.me5rine-lab-sr-only` pour le texte des lecteurs d'écran
5. **Classes génériques uniquement** : Toutes les classes à l'intérieur du tableau doivent être génériques (`me5rine-lab-table-*`). Seule la classe sur le `<table>` peut être spécifique.
6. **Structure unifiée** : Tous les tableaux front doivent suivre la même structure HTML pour garantir la cohérence visuelle
7. **Style table-05** : Le style est adapté du design table-05 avec des bordures de 4px, un padding de 30px et une ombre douce, tout en conservant le comportement responsive WordPress admin

## Filtres et Pagination Unifiés

Les filtres et la pagination des tableaux utilisent des classes génériques unifiées. **Tous les styles sont dans FRONT_CSS.md** (section "Filtres Génériques" et "Pagination Générique"). Aucun CSS spécifique ne doit être ajouté dans les fichiers CSS du plugin.

### Structure HTML Standardisée pour les Filtres

Tous les tableaux front doivent utiliser cette structure pour les filtres :

```html
<div class="me5rine-lab-filters">
    <form method="get">
        <div class="me5rine-lab-filter-group">
            <label class="me5rine-lab-form-label me5rine-lab-filter-label" for="status_filter">
                <?php _e('Filter by status:', 'text-domain'); ?>
            </label>
            <select id="status_filter" name="status_filter" class="me5rine-lab-form-select me5rine-lab-filter-select">
                <option value=""><?php _e('All', 'text-domain'); ?></option>
                <option value="value1"><?php _e('Option 1', 'text-domain'); ?></option>
            </select>
        </div>

        <div class="me5rine-lab-filter-group">
            <label class="me5rine-lab-form-label me5rine-lab-filter-label" for="per_page">
                <?php _e('Show:', 'text-domain'); ?>
            </label>
            <select id="per_page" name="per_page" class="me5rine-lab-form-select me5rine-lab-filter-select">
                <option value="10">10</option>
                <option value="20">20</option>
            </select>
        </div>

        <button type="submit" name="filter_action" class="me5rine-lab-form-button me5rine-lab-form-button-secondary">
            <?php _e('Filter', 'text-domain'); ?>
        </button>
    </form>
</div>
```

**Classes génériques utilisées** :
- `.me5rine-lab-filters` : Container principal des filtres
- `.me5rine-lab-filter-group` : Groupe de filtre (label + input/select)
- `.me5rine-lab-filter-label` : Label du filtre (hérite de `.me5rine-lab-form-label`)
- `.me5rine-lab-filter-select` : Select de filtre (hérite de `.me5rine-lab-form-select`)
- `.me5rine-lab-filter-input` : Input de filtre (hérite de `.me5rine-lab-form-input`)

**Important** : Les styles sont définis dans FRONT_CSS.md. Aucun CSS spécifique ne doit être ajouté dans les fichiers CSS du plugin.

### Structure HTML Standardisée pour la Pagination

Tous les tableaux front doivent utiliser cette structure pour la pagination :

```html
<div class="tablenav-pages me5rine-lab-pagination">
    <span class="displaying-num me5rine-lab-pagination-info">
        <?php printf(_n('%s item', '%s items', $total_items, 'text-domain'), number_format_i18n($total_items)); ?>
    </span>
    <span class="pagination-links me5rine-lab-pagination-links">
        <?php if ($paged > 1): ?>
            <a class="first-page me5rine-lab-pagination-button" href="<?php echo esc_url(add_query_arg('pg', 1)); ?>">
                <span aria-hidden="true">«</span>
            </a>
            <a class="prev-page me5rine-lab-pagination-button" href="<?php echo esc_url(add_query_arg('pg', $paged - 1)); ?>">
                <span aria-hidden="true">‹</span>
            </a>
        <?php else: ?>
            <span class="tablenav-pages-navspan me5rine-lab-pagination-button disabled" aria-hidden="true">«</span>
            <span class="tablenav-pages-navspan me5rine-lab-pagination-button disabled" aria-hidden="true">‹</span>
        <?php endif; ?>

        <span class="me5rine-lab-sr-only"><?php _e('Current page', 'text-domain'); ?></span>
        <span class="paging-input">
            <span class="tablenav-paging-text me5rine-lab-pagination-text">
                <?php echo esc_html($paged); ?> <?php _e('of', 'text-domain'); ?> 
                <span class="total-pages"><?php echo esc_html($total_pages); ?></span>
            </span>
        </span>

        <?php if ($paged < $total_pages): ?>
            <a class="next-page me5rine-lab-pagination-button" href="<?php echo esc_url(add_query_arg('pg', $paged + 1)); ?>">
                <span aria-hidden="true">›</span>
            </a>
            <a class="last-page me5rine-lab-pagination-button" href="<?php echo esc_url(add_query_arg('pg', $total_pages)); ?>">
                <span aria-hidden="true">»</span>
            </a>
        <?php else: ?>
            <span class="tablenav-pages-navspan me5rine-lab-pagination-button disabled" aria-hidden="true">›</span>
            <span class="tablenav-pages-navspan me5rine-lab-pagination-button disabled" aria-hidden="true">»</span>
        <?php endif; ?>
    </span>
</div>
```

**Classes génériques utilisées** :
- `.me5rine-lab-pagination` : Container principal de la pagination
- `.me5rine-lab-pagination-info` : Information sur le nombre d'éléments
- `.me5rine-lab-pagination-links` : Container des liens de pagination
- `.me5rine-lab-pagination-button` : Bouton/lien de pagination
- `.me5rine-lab-pagination-text` : Texte de la page courante
- `.disabled` : État désactivé pour les boutons non disponibles

**Important** : Les styles sont définis dans FRONT_CSS.md. Aucun CSS spécifique ne doit être ajouté dans les fichiers CSS du plugin.

### Règles d'Unification

1. **Tous les filtres** doivent utiliser les classes génériques `me5rine-lab-filter-*`
2. **Tous les éléments de pagination** doivent utiliser les classes génériques `me5rine-lab-pagination-*`
3. **Aucun CSS spécifique** ne doit être ajouté dans les fichiers CSS du plugin pour les filtres et la pagination
4. **Tous les styles** sont centralisés dans FRONT_CSS.md (thème)
5. **Les classes spécifiques** (ex: `.my-giveaways-dashboard-filters`) ne doivent être utilisées que pour des cas très spécifiques et documentés

### Responsive

Les filtres et la pagination s'adaptent automatiquement sur mobile grâce aux styles définis dans FRONT_CSS.md. Aucun CSS spécifique n'est nécessaire.

## Options d'Écran - Affichage/Masquage de Colonnes

Le système d'options d'écran permet aux utilisateurs d'afficher/masquage des colonnes dans les tableaux. Ce système est **générique et réutilisable** pour tous les tableaux.

### Classes CSS Génériques

Les classes CSS pour les options d'écran sont définies dans `ADMIN_CSS.md` (section "Options d'Écran - Système Générique Réutilisable"). Toutes les classes utilisent le préfixe `me5rine-lab-` :

- `.me5rine-lab-dashboard-header` : Header du dashboard avec actions
- `.me5rine-lab-dashboard-header-actions` : Container des actions du header
- `.me5rine-lab-screen-options-toggle` : Bouton pour ouvrir/fermer le panneau
- `.me5rine-lab-screen-options-panel` : Panneau d'options d'écran
- `.me5rine-lab-screen-options-panel-content` : Contenu du panneau
- `.me5rine-lab-screen-options-columns` : Grid des colonnes
- `.me5rine-lab-screen-options-column-item` : Item de colonne (checkbox + label)
- `.me5rine-lab-screen-options-actions` : Actions du panneau (bouton Appliquer)
- `.me5rine-lab-screen-options-apply` : Bouton Appliquer
- `.column-hidden` : Classe pour masquer une colonne (sur `th` et `td`)

### Structure HTML Standardisée

**IMPORTANT** : Toutes les pages front doivent respecter cette structure :
1. Une div wrapper `<div class="me5rine-lab-dashboard">` qui englobe tout le contenu de la page
2. Un titre principal `<h2 class="me5rine-lab-title-large">` comme premier élément après l'ouverture de la div

```html
<div class="me5rine-lab-dashboard">
    <h2 class="me5rine-lab-title-large">Titre de la Page</h2>
    <div class="me5rine-lab-dashboard-header">
        <div class="me5rine-lab-dashboard-header-actions">
            <button type="button" class="me5rine-lab-form-button me5rine-lab-form-button-secondary me5rine-lab-screen-options-toggle" aria-expanded="false">
                Options d'écran
            </button>
        </div>
    </div>
    
    <div class="me5rine-lab-screen-options-panel" style="display: none;">
        <div class="me5rine-lab-screen-options-panel-content">
            <h4>Afficher à l'écran</h4>
            <div class="me5rine-lab-screen-options-columns">
                <label class="me5rine-lab-screen-options-column-item">
                    <input type="checkbox" name="visible_columns[name]" value="1" data-column="name" checked>
                    <span>Nom</span>
                </label>
            </div>
            <div class="me5rine-lab-screen-options-actions">
                <button type="button" class="me5rine-lab-form-button me5rine-lab-screen-options-apply">Appliquer</button>
            </div>
        </div>
    </div>
    
    <table class="me5rine-lab-table">
        <thead>
            <tr>
                <th class="column-name" data-column="name">Nom</th>
                <th class="column-date column-hidden" data-column="date">Date</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="column-name" data-column="name">Exemple</td>
                <td class="column-date column-hidden" data-column="date">01/01/2024</td>
            </tr>
        </tbody>
    </table>
</div>
```

### Règles d'Utilisation

1. **Attribut `data-column`** : Chaque colonne (th et td) doit avoir un attribut `data-column` avec l'identifiant unique
2. **Classe `column-hidden`** : Les colonnes masquées doivent avoir la classe `column-hidden` sur les éléments `th` et `td`
3. **Classes génériques** : Toutes les classes utilisent le préfixe `me5rine-lab-` et sont génériques
4. **Sauvegarde des préférences** : Les préférences doivent être sauvegardées par utilisateur (user meta) via AJAX

### Documentation Complète

Pour la documentation complète du système d'options d'écran (CSS, JavaScript, PHP), voir `FRONT_CSS.md` (section "Options d'Écran - Système Générique Réutilisable").