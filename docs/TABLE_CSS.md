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

Style inspiré des tableaux WordPress admin, modernisé avec les couleurs du thème :

```css
/* Tableau générique - Style WordPress admin modernisé */
.me5rine-lab-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--um-bg, #ffffff);
    border: 1px solid var(--um-border, #DEE5EC);
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
    margin-top: 20px;
    margin-bottom: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    font-size: 13px;
    line-height: 1.5;
    color: var(--um-text-light, #50575e);
}

/* En-tête du tableau - Style WordPress admin */
.me5rine-lab-table thead {
    background: var(--um-bg-secondary, #f6f7f7);
    border-bottom: 1px solid var(--um-border, #c3c4c7);
}

.me5rine-lab-table th {
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    color: var(--um-text, #1d2327);
    text-transform: none;
    letter-spacing: 0;
    border-bottom: 1px solid var(--um-border, #c3c4c7);
    vertical-align: middle;
    white-space: nowrap;
}

.me5rine-lab-table th:first-child {
    padding-left: 16px;
}

.me5rine-lab-table th:last-child {
    padding-right: 16px;
}

.me5rine-lab-table th .unsorted-column {
    display: inline-block;
}

/* Colonnes triables - Style WordPress */
.me5rine-lab-table th.sortable a,
.me5rine-lab-table th.sorted a {
    color: var(--um-text, #1d2327);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.me5rine-lab-table th.sortable a:hover,
.me5rine-lab-table th.sorted a:hover {
    color: var(--um-secondary, #2271b1);
}

.me5rine-lab-table th.sorted.asc a,
.me5rine-lab-table th.sorted.desc a {
    color: var(--um-secondary, #2271b1);
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

/* Corps du tableau - Style WordPress admin */
.me5rine-lab-table tbody tr {
    border-bottom: 1px solid var(--um-border, #c3c4c7);
    transition: background-color 0.15s ease;
}

.me5rine-lab-table tbody tr:last-child {
    border-bottom: none;
}

.me5rine-lab-table tbody tr:hover {
    background: var(--um-bg-secondary, #f6f7f7);
}

.me5rine-lab-table tbody tr.me5rine-lab-table-row-toggleable.is-expanded {
    background: var(--um-bg-secondary, #f6f7f7);
}

.me5rine-lab-table td {
    padding: 12px 16px;
    font-size: 13px;
    color: var(--um-text-light, #50575e);
    vertical-align: middle;
    border-top: 1px solid transparent;
    border-bottom: 1px solid transparent;
}

.me5rine-lab-table tbody tr:hover td {
    border-top-color: var(--um-border, #c3c4c7);
    border-bottom-color: var(--um-border, #c3c4c7);
}

/* Cellule de résumé (première colonne avec titre) - Style WordPress admin */
.me5rine-lab-table td.summary {
    position: relative;
    padding-right: 50px;
    font-weight: 600;
}

/* Ligne de résumé dans une cellule */
.me5rine-lab-table-summary-row {
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: space-between;
}

/* Actions de ligne (Edit, View, etc.) - Style WordPress admin */
.me5rine-lab-table .row-actions {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-left: 8px;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.me5rine-lab-table tbody tr:hover .row-actions {
    opacity: 1;
}

.me5rine-lab-table .row-actions a,
.me5rine-lab-table .row-actions button {
    font-size: 12px;
    padding: 2px 6px;
    text-decoration: none;
    border-radius: 2px;
    transition: all 0.15s ease;
}

.me5rine-lab-table .row-actions a:hover,
.me5rine-lab-table .row-actions button:hover {
    background: var(--um-bg-secondary, #f6f7f7);
}

/* Titre dans une cellule de tableau - Style WordPress admin */
.me5rine-lab-table-title {
    font-weight: 600;
    font-size: 14px;
    color: var(--um-text, #1d2327);
}

.me5rine-lab-table-title a {
    color: var(--um-text, #1d2327);
    text-decoration: none;
    transition: color 0.15s ease;
}

.me5rine-lab-table-title a:hover {
    color: var(--um-secondary, #2271b1);
}

/* Bouton pour expander/réduire une ligne - Style WordPress admin */
.me5rine-lab-table-toggle-btn {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 28px;
    height: 28px;
    border: 1px solid var(--um-border, #c3c4c7);
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
    border-color: var(--um-secondary, #2271b1);
    background: var(--um-bg-secondary, #f6f7f7);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.me5rine-lab-table-toggle-btn:focus {
    outline: none;
    border-color: var(--um-secondary, #2271b1);
    box-shadow: 0 0 0 1px var(--um-secondary, #2271b1);
}

.me5rine-lab-table-toggle-btn::before {
    content: '';
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-top: 5px solid var(--um-text-light, #50575e);
    transition: transform 0.15s ease, border-top-color 0.15s ease;
}

.me5rine-lab-table-toggle-btn[aria-expanded="true"]::before,
.me5rine-lab-table-row-toggleable.is-expanded .me5rine-lab-table-toggle-btn::before {
    transform: rotate(180deg);
}

.me5rine-lab-table-toggle-btn:hover::before {
    border-top-color: var(--um-secondary, #2271b1);
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
        padding-right: 16px;
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

/* Styles pour tableaux avec classe striped (lignes alternées) - Style WordPress admin */
.me5rine-lab-table.striped tbody tr:nth-child(odd) {
    background-color: var(--um-bg, #ffffff);
}

.me5rine-lab-table.striped tbody tr:nth-child(even) {
    background-color: var(--um-bg-secondary, #f6f7f7);
}

.me5rine-lab-table.striped tbody tr:nth-child(odd):hover,
.me5rine-lab-table.striped tbody tr:nth-child(even):hover {
    background-color: var(--um-bg-secondary, #f6f7f7);
}

/* Responsive : Mobile */
@media screen and (max-width: 782px) {
    .me5rine-lab-table {
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-top: 16px;
        margin-bottom: 16px;
        border-radius: 4px;
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
        border: 1px solid var(--um-border, #c3c4c7);
        border-radius: 4px;
        background: var(--um-bg, #ffffff);
        overflow: hidden;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
    }

    .me5rine-lab-table td {
        display: block;
        padding: 10px 12px;
        text-align: left;
        border-bottom: 1px solid var(--um-border, #c3c4c7);
        border-left: none;
        border-right: none;
    }

    .me5rine-lab-table td:last-child {
        border-bottom: none;
    }

    .me5rine-lab-table td.summary {
        padding-right: 50px;
        background: var(--um-bg-secondary, #f6f7f7);
        font-weight: 600;
        border-bottom: 1px solid var(--um-border, #c3c4c7);
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
        color: var(--um-text, #1d2327);
        text-transform: none;
        letter-spacing: 0;
        font-size: 12px;
        margin-right: 8px;
    }

    .me5rine-lab-table td.summary[data-colname]::before {
        display: none;
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

### Exemple de styles spécifiques

Si vous avez besoin de styles spécifiques pour un tableau, vous pouvez les ajouter dans votre thème en utilisant la classe spécifique du tableau pour cibler ses éléments internes :

```css
/* Style spécifique pour le tableau des participations */
.me5rine-lab-table-giveaways-participations {
    /* Styles spécifiques si nécessaire */
}

/* Cibler les lignes du tableau participations */
.me5rine-lab-table-giveaways-participations .me5rine-lab-table-row-toggleable {
    /* Styles spécifiques pour les lignes de ce tableau */
}

/* Cibler les cellules de résumé du tableau participations */
.me5rine-lab-table-giveaways-participations .summary {
    /* Styles spécifiques pour la cellule de résumé de ce tableau */
}

/* Cibler les titres du tableau participations */
.me5rine-lab-table-giveaways-participations .me5rine-lab-table-title {
    /* Styles spécifiques pour les titres de ce tableau */
}

/* Style spécifique pour le tableau socials */
.me5rine-lab-table-socials .social-type-title {
    text-align: center;
    background-color: var(--um-secondary);
    color: var(--um-bg);
}
```

**Important** : 
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
        <tr class="me5rine-lab-table-row-toggleable is-collapsed">
            <td class="summary" data-colname="Titre">
                <div class="me5rine-lab-table-summary-row">
                    <span class="me5rine-lab-table-title">
                        <a href="#">Mon titre</a>
                    </span>
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
   - `<div class="me5rine-lab-table-summary-row">`
   - `<span class="me5rine-lab-table-title">`
   - `<button class="me5rine-lab-table-toggle-btn" aria-expanded="false">`
6. **Bouton toggle** : Toujours inclure `aria-expanded="false"` et un texte accessible avec `me5rine-lab-sr-only`

**Principe** : Tous les éléments internes utilisent UNIQUEMENT des classes génériques. Pour cibler un élément spécifique d'un tableau, utilisez le sélecteur CSS : `.me5rine-lab-table-{type} .me5rine-lab-table-{element}`

## Notes importantes

1. **CSS dans le thème** : Ce CSS doit être dans le thème, pas dans le plugin
2. **Variables CSS** : Assurez-vous que les variables CSS sont définies dans votre thème
3. **Responsive** : Les tableaux s'adaptent automatiquement sur mobile avec affichage en cartes
4. **Accessibilité** : Utilisez toujours `.me5rine-lab-sr-only` pour le texte des lecteurs d'écran
5. **Classes génériques uniquement** : Toutes les classes à l'intérieur du tableau doivent être génériques (`me5rine-lab-table-*`). Seule la classe sur le `<table>` peut être spécifique.
6. **Structure unifiée** : Tous les tableaux front doivent suivre la même structure HTML pour garantir la cohérence visuelle

