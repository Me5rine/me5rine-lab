# Règles CSS Unifiées pour l'Administration WordPress

Ces règles CSS doivent être copiées dans le fichier `assets/css/admin-unified.css` du plugin.

**Préfixe des classes** : `admin-lab-` (pour les classes spécifiques au plugin)

Ce fichier unifie TOUS les styles admin réutilisables de tous les modules. Une seule modification de variable change le style partout.

## Variables CSS Unifiées

Toutes les variables sont centralisées dans `global-colors.css`. Modifiez ces valeurs pour changer le style de tous les éléments admin :

```css
:root {
    /* Couleurs principales */
    --admin-lab-color-primary: var(--e-global-color-primary, #0073aa);
    --admin-lab-color-secondary: var(--e-global-color-secondary, #0485C8);
    --admin-lab-color-white: #ffffff;
    
    /* Couleurs des tableaux */
    --admin-lab-color-th-background: #f9f9f9;
    --admin-lab-color-odd: #f6f7f7;
    --admin-lab-color-borders: #ccd0d4;
    
    /* Couleurs des boutons */
    --admin-lab-color-button-primary-hover: #3582c4;
    --admin-lab-color-button-secondary: #f1f1f1;
    --admin-lab-color-button-secondary-hover: #f0f0f1;
    --admin-lab-color-button-remove: #e73838;
    --admin-lab-color-button-remove-hover: #a92424;
    
    /* Couleurs de fond */
    --admin-lab-color-block-background: #f7f9fa;
    --admin-lab-color-block-border: #e6e6e6;
    
    /* Couleurs de texte */
    --admin-lab-color-text: var(--e-global-color-text, #5D697D);
    --admin-lab-color-admin-text: #555;
    --admin-lab-color-header-text: #011322;
}
```

## Import des Variables

```css
@import url('global-colors.css');
```

## Boutons Génériques

```css
/* Bouton primaire WordPress */
.button.button-primary {
    background-color: var(--admin-lab-color-secondary);
    color: var(--admin-lab-color-white);
    border-color: var(--admin-lab-color-secondary);
}

.button.button-primary:hover {
    background-color: var(--admin-lab-color-button-primary-hover);
    border-color: var(--admin-lab-color-button-primary-hover);
}

/* Bouton secondaire WordPress */
.button.button-secondary {
    background-color: var(--admin-lab-color-button-secondary);
    color: var(--admin-lab-color-admin-text);
    border: 1px solid var(--admin-lab-color-borders);
}

.button.button-secondary:hover {
    background-color: var(--admin-lab-color-button-secondary-hover);
    border-color: var(--admin-lab-color-borders);
}

/* Bouton de suppression - Classe générique unique */
.admin-lab-button-delete,
.button.button-secondary.admin-lab-button-delete,
.button.button-danger.admin-lab-button-delete {
    background-color: var(--admin-lab-color-button-remove);
    color: var(--admin-lab-color-white);
    border: 1px solid var(--admin-lab-color-button-remove);
}

.admin-lab-button-delete:hover,
.button.button-secondary.admin-lab-button-delete:hover,
.button.button-danger.admin-lab-button-delete:hover {
    background-color: var(--admin-lab-color-button-remove-hover);
    border-color: var(--admin-lab-color-button-remove-hover);
    color: var(--admin-lab-color-white);
}

.admin-lab-button-delete:focus,
.button.button-secondary.admin-lab-button-delete:focus,
.button.button-danger.admin-lab-button-delete:focus {
    box-shadow: 0 0 0 0px var(--admin-lab-color-button-remove-hover);
    border-color: var(--admin-lab-color-button-remove-hover);
}
```

## Tableaux WordPress (WP_List_Table)

```css
/* Tableaux WordPress - Classe générique */
.admin-lab-list-table td,
table.wp-list-table td {
    vertical-align: middle;
}

/* Colonnes d'actions - Classe générique */
.admin-lab-column-actions,
.column-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.admin-lab-column-actions .button,
.column-actions .button {
    display: block;
    width: fit-content;
}

/* Container d'actions */
.column-actions .action-buttons {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 5px 8px;
}

/* Largeurs de colonnes spécifiques pour giveaways */
.column-giveaway_start_date,
.column-giveaway_end_date {
    width: 180px !important;
}

.column-giveaway_status {
    width: 120px !important;
}

.column-giveaway_participants,
.column-giveaway_entries {
    width: 110px !important;
}

.column-giveaway_rafflepress {
    width: 150px !important;
}

.column-giveaway_rafflepress a {
    padding: 6px 12px;
    text-align: center;
}
```

## Formulaires Génériques

```css
/* Labels de champs - Classe générique */
.admin-lab-field-label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

/* Inputs de champs - Classe générique */
.admin-lab-field-input {
    width: 100%;
    padding: 8px;
    border: 1px solid var(--admin-lab-color-borders);
    margin-bottom: 5px;
    border-radius: 4px;
    background-color: var(--admin-lab-color-white);
}

.admin-lab-field-input:focus {
    border-color: var(--admin-lab-color-secondary);
    outline: none;
    box-shadow: 0 0 0 1px var(--admin-lab-color-secondary);
}

.admin-lab-field-input option {
    padding: 8px;
}

/* Selects - Classe générique */
.admin-lab-field-select {
    width: 100%;
}
```

## Containers et Sections

```css
/* Container de modules */
.admin-lab-modules-container {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: flex-start;
}

/* Carte de module */
.admin-lab-module-card {
    padding: 1rem;
    border: 1px solid var(--admin-lab-color-borders);
    border-radius: 5px;
    background: var(--admin-lab-color-white);
    min-width: 220px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.admin-lab-module-card strong {
    font-size: 16px;
    margin-bottom: 10px;
}

.admin-lab-module-card .button {
    margin-top: 10px;
}

.admin-lab-module-card-additional-info {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: var(--admin-lab-color-admin-text);
}

.admin-lab-module-actions {
    margin-top: 1rem;
}

.admin-lab-module-actions a {
    margin-right: 1rem;
}

/* Sections de formulaire - Classe générique */
.admin-lab-form-section {
    margin: 20px 0;
}

/* Filtres - Classe générique */
.admin-lab-filters {
    margin: 20px 0;
    padding: 15px;
    background: var(--admin-lab-color-block-background);
    border: 1px solid var(--admin-lab-color-block-border);
}

.admin-lab-filters label {
    margin-right: 10px;
    display: inline-block;
}

.admin-lab-filters select,
.admin-lab-filters input[type="text"] {
    margin-left: 5px;
}

/* Tabs content - Classe générique */
.admin-lab-tab-content {
    margin-top: 20px;
}
```

## Progress Bars

```css
/* Container de barre de progression - Classe générique */
.admin-lab-progress-container {
    background: var(--admin-lab-color-block-background);
    border: 1px solid var(--admin-lab-color-borders);
    padding: 5px;
    width: 300px;
}

/* Barre de progression - Classe générique */
.admin-lab-progress-bar {
    background: var(--admin-lab-color-secondary);
    color: var(--admin-lab-color-white);
    padding: 5px;
    text-align: center;
}
```

## Select2

```css
/* Select2 containers - Style générique */
.select2-container {
    font-size: 14px;
    color: #2c3338;
    border: 1px solid #8c8f94;
    box-shadow: none;
    border-radius: 3px;
    padding: 0 24px 0 8px;
    min-height: 30px;
    max-width: 25rem;
    -webkit-appearance: none;
    appearance: none;
    background: #fff url(data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M5%206l5%205%205-5%202%201-7%207-7-7%202-1z%22%20fill%3D%22%23555%22%2F%3E%3C%2Fsvg%3E) no-repeat right 5px top 55%;
    background-size: 16px 16px;
    cursor: pointer;
    vertical-align: middle;
}

/* Masquer la flèche native de Select2 */
.select2-selection__arrow {
    display: none !important;
}

.select2-selection.select2-selection--single {
    border: none !important;
}

/* Select2 containers - Largeurs spécifiques */
#filter_reward + .select2-container,
#campaign_sidebar + .select2-container,
#campaign_banner + .select2-container,
#campaign_background + .select2-container {
    width: 200px !important;
    margin-right: 6px;
}
```

## Table Socials (Responsive)

```css
/* Table socials */
.admin-lab-socials-table {
    table-layout: fixed;
    width: 100%;
    border-collapse: collapse;
}

.admin-lab-socials-table th,
.admin-lab-socials-table td {
    padding: 8px;
    vertical-align: middle;
    text-align: left;
    border-bottom: 1px solid var(--admin-lab-color-borders);
}

/* Largeurs de colonnes spécifiques */
.admin-lab-socials-table th:nth-child(1),
.admin-lab-socials-table td:nth-child(1) {
    width: 20px;
    text-align: center;
}

.admin-lab-socials-table th:nth-child(2),
.admin-lab-socials-table td:nth-child(2) {
    width: 70px;
}

.admin-lab-socials-table th:nth-child(4),
.admin-lab-socials-table td:nth-child(4) {
    width: 35px;
}

.admin-lab-socials-table th:nth-child(5),
.admin-lab-socials-table td:nth-child(5) {
    width: 100px;
    text-align: center;
}

.admin-lab-socials-table th:nth-child(6),
.admin-lab-socials-table td:nth-child(6),
.admin-lab-socials-table th:nth-child(7),
.admin-lab-socials-table td:nth-child(7) {
    width: 60px;
    text-align: center;
}

.admin-lab-socials-table th:nth-child(8),
.admin-lab-socials-table td:nth-child(8),
.admin-lab-socials-table th:nth-child(9),
.admin-lab-socials-table td:nth-child(9) {
    width: 80px;
    text-align: center;
}

/* Inputs dans la table */
.admin-lab-socials-table input[name$="[key]"],
.admin-lab-socials-table input[name$="[label]"],
.admin-lab-socials-table input[type="text"] {
    width: 100%;
}

.admin-lab-socials-table input[name$="[fa]"] {
    width: 80px;
}

.admin-lab-socials-table input[name$="[color]"],
.admin-lab-socials-table input[type="color"] {
    width: 40px !important;
}

.admin-lab-socials-table + p > .button-primary {
    margin-top: 10px;
}

/* Handle column pour drag & drop */
.handle-column {
    width: 20px;
    text-align: center;
    cursor: move;
}

.handle {
    cursor: move;
    color: #888;
}

/* Sortable */
#socials-sortable tr {
    transition: background 0.2s;
}

#socials-sortable tr.ui-sortable-helper {
    background: var(--admin-lab-color-button-secondary);
}

#socials-new {
    margin-bottom: 25px;
    display: none;
}
```

## Status Indicators

```css
/* Indicateurs de statut - Classes génériques */
.admin-lab-status-active {
    color: green;
}

.admin-lab-status-inactive {
    color: red;
}

.admin-lab-status-pending {
    color: orange;
}

/* Spans avec couleur inline */
span[style*="color: green"] {
    color: green !important;
}

span[style*="color: red"] {
    color: red !important;
}
```

## Sections Spécifiques

### Events

```css
/* Events - Box */
#admin_lab_event_box > div.postbox-header > h2 {
    padding: 0 16px !important;
}

#admin_lab_event_box > div.inside {
    padding: 16px !important;
}

/* Events - Hidden states */
.admin-lab-events-fields.is-hidden,
.admin-lab-events-recur.is-hidden {
    display: none;
}

/* Events - Grid */
.admin-lab-events-grid {
    display: inline-block;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    max-width: 720px;
    width: 100%;
}

/* Events - Fields */
.admin-lab-events-fields hr {
    margin: 14px 0;
    border: 0;
    border-top: 1px solid #c3c4c7;
}

.admin-lab-events-fields label {
    display: inline-block;
    font-weight: 600;
    margin-bottom: 4px;
}

.admin-lab-event-type,
.admin-lab-event-interval,
.admin-lab-event-window-end,
.admin-lab-events-fields input[type="datetime-local"] {
    width: calc(100% - 2px);
    max-width: calc(100% - 2px);
}

.admin-lab-events-fields select {
    width: calc(100% - 34px);
    max-width: calc(100% - 34px);
    margin: 0;
}

.admin-lab-event-manage-types {
    margin-left: 8px;
}
```

### Subscription

```css
/* Subscription - View Toggle */
.subscription-view-toggle {
    margin: 20px 0;
}

/* Subscription - Statistics */
.subscription-stats {
    margin: 20px 0;
}

/* Subscription - Lists */
.subscription-identities-list {
    margin: 5px 0 0 20px;
    padding: 0;
    font-size: 11px;
}

.subscription-subscriptions-list {
    margin: 0;
    padding-left: 20px;
}

/* Subscription - Debug */
.subscription-debug-section {
    background: #f5f5f5;
    padding: 15px;
    overflow: auto;
    max-height: 400px;
}

/* Subscription - HR Separator */
.subscription-hr-separator {
    margin: 40px 0;
}

/* Subscription - Empty State */
.subscription-empty-state {
    color: #999;
    font-style: italic;
}

/* Subscription - Provider Select */
#filter_provider {
    min-width: 150px;
    width: auto;
}

/* Subscription - Filter Sections */
.subscription-filter-section {
    margin: 20px 0;
}

/* Subscription - Auto Sync Status */
.subscription-auto-sync-status {
    margin-top: 20px;
    padding: 15px;
    background: #f9f9f9;
    border-left: 4px solid #2271b1;
}

.subscription-auto-sync-status h3 {
    margin-top: 0;
}

.subscription-auto-sync-status-active {
    color: #2271b1;
}

.subscription-auto-sync-status-inactive {
    color: #d63638;
}

/* Subscription - List Container */
.subscription-subscriptions-list-container {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.subscription-subscription-item {
    border-left: 3px solid #2271b1;
    padding-left: 10px;
    margin-bottom: 5px;
}

/* Subscription - Sync Types */
.subscription-sync-types-section {
    margin-bottom: 20px;
}

.subscription-sync-types-form {
    display: inline-block;
    margin-left: 10px;
}

.subscription-sync-types-description {
    margin-left: 10px;
}

/* Subscription - Bot API */
.subscription-bot-api-label {
    color: #2271b1;
}

.subscription-bot-api-configured {
    color: green;
}

.subscription-bot-api-missing {
    color: orange;
}

/* Subscription - Config Sections */
.subscription-oauth-config-section,
.subscription-bot-config-section,
.subscription-oauth-info-section {
    padding: 10px;
    background: #f0f0f1;
    border-left: 4px solid #2271b1;
}

.subscription-oauth-config-section {
    margin-top: 15px;
}

.subscription-oauth-redirect-code {
    display: block;
    margin: 5px 0;
    padding: 5px;
    background: white;
}

.subscription-account-connected {
    color: green;
}

.subscription-account-not-connected {
    color: orange;
}

/* Subscription - Add Forms */
#add-tier-form,
#add-mapping-form {
    display: none;
    margin: 20px 0;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
}
```

### Shortcodes

```css
/* Shortcode Name Badge */
.admin-lab-shortcode-name {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    background: #f3f4f5;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s ease;
    font-weight: 500;
    font-size: 13px;
    color: #23282d;
    position: relative;
}

.admin-lab-shortcode-name:hover {
    background: #e2e4e7;
}

.admin-lab-shortcode-name .dashicons {
    margin-right: 6px;
    font-size: 16px;
    color: #007cba;
    line-height: 1;
    display: inline-flex;
    align-items: center;
}

.admin-lab-shortcode-name.copied {
    background: #d1e7dd;
    border-color: #badbcc;
    color: #155724;
}

.copy-message {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    left: calc(100% + 10px);
    background-color: #d1f7d6;
    color: #198754;
    padding: 2px 6px;
    font-size: 12px;
    font-weight: bold;
    border-radius: 3px;
    white-space: nowrap;
    z-index: 10;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Shortcodes - Edit Table */
.edit-shortcode-admin-table input,
.edit-shortcode-admin-table textarea {
    width: 100% !important;
}
```

### Marketing

```css
/* Marketing - Campaign Zone Form */
.campaign-zone-form {
    min-width: 200px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.campaign-zone-select {
    width: 100%;
}

.campaign-zones-table {
    margin-bottom: 20px;
}

.campaign-zone-selector {
    display: flex;
    gap: 6px;
    align-items: center;
    margin-bottom: 10px;
}

.campaign-zone-form > button {
    width: fit-content;
}

/* Marketing - Campaign Image Preview */
.campaign-image-preview {
    max-width: 200px;
    height: auto;
    margin-top: 10px;
}

.campaign-image-preview.hidden {
    display: none;
}

select.campaign-display-zones {
    min-width: 200px;
}
```

### Remote News

```css
/* Remote News - Form */
.remote-news-add-form input:not(.remote-news-sideload-checkbox),
.remote-news-add-form select {
    min-height: 30px;
    box-shadow: 0 0 0 transparent;
    border-radius: 4px;
    border: 1px solid #8c8f94;
    background-color: #fff;
    color: #2c3338;
    vertical-align: top;
}

table.me5rine-lab_page_admin-lab-remote-news .actions {
    width: 40px;
}
```

## Responsive

```css
/* Responsive - Mobile (< 782px) */
@media screen and (max-width: 782px) {
    /* Users */
    #wpbody-content div.alignleft.um-filter-by-status {
        width: 100%;
        flex-wrap: wrap;
        display: flex;
        gap: 0.25rem;
        align-items: center;
        padding: 0;
        margin-bottom: 20px;
    }

    #body.users-php #um_user_status {
        max-width: 100%;
        margin-bottom: 6px;
    }

    #filter_account_type,
    #um_user_status,
    #um_filter_users {
        margin: 0 0 6px 0 !important;
    }

    #um_user_status {
        max-width: 100%;
    }

    label.user-edit-account-type,
    label.user-edit-select-role,
    label.user-edit-partner-sites {
        display: inline-block;
        margin: .35em 0 .5em !important;
    }

    /* Settings */
    .available-modules-table label {
        display: inline-block;
        height: 2em;
    }

    /* Marketing campaigns */
    .campaign-zone-selector {
        width: 100%;
        margin: 0;
    }

    .campaign-zone-selector-inline {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
    }

    .campaign-zone-selector-inline .button {
        margin: 0;
    }

    .campaign-zone-selector-inline .select2-container {
        flex-grow: 1 !important;
        width: 100% !important;
        min-width: 0 !important;
    }

    .campaign-zone-selector-inline button {
        flex-shrink: 0;
        white-space: nowrap;
    }

    .campaign-zone-selector .select2-selection--single,
    .campaign-zone-selector .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }

    .campaign-zone-selector .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 40px;
    }

    form.marketing-campaign-search-block > p > input[type=search] {
        margin: 0 0 10px 0;
    }

    /* Account types */
    table.wp-list-table.account_types td.column-actions .button {
        display: inline-block;
    }

    /* Socials */
    table.admin-lab-socials-table thead {
        display: none;
    }

    table.admin-lab-socials-table tr {
        display: block;
        background: var(--admin-lab-color-white);
        position: relative;
    }

    table.admin-lab-socials-table tr.social-hidden {
        display: none !important;
    }

    table.admin-lab-socials-table td {
        display: flex;
        justify-content: space-between;
        padding: 3px 8px 3px 35%;
        height: 35px;
        border: none;
        vertical-align: middle;
        width: calc(65% - 8px) !important;
        position: relative;
    }

    table.admin-lab-socials-table td::before {
        content: attr(data-colname);
        position: absolute;
        left: 10px;
        display: block;
        overflow: hidden;
        width: 32%;
        white-space: nowrap;
        text-overflow: ellipsis;
        text-align: left;
        font-weight: 400;
        font-size: 13px;
        line-height: 1.5em;
    }

    table.admin-lab-socials-table td input {
        width: 100% !important;
    }

    table.admin-lab-socials-table td input,
    table.admin-lab-socials-table td select {
        min-height: 29px;
    }

    table.admin-lab-socials-table td input[type=checkbox] {
        width: 29px !important;
        min-width: 29px !important;
    }

    table.admin-lab-socials-table td input[type=checkbox]::before {
        width: 27px;
        height: 27px;
        margin: 0;
    }

    .admin-lab-socials-table input[name$="[color]"],
    .admin-lab-socials-table input[type="color"] {
        width: 40px !important;
    }

    .admin-lab-socials-table tbody tr:nth-of-type(odd) {
        background-color: var(--admin-lab-color-white);
    }

    .admin-lab-socials-table tbody tr:nth-of-type(even) {
        background-color: var(--admin-lab-color-th-background);
    }

    /* Toggle logic */
    .admin-lab-socials-table tr:not(.row-expanded) td:not(.column-primary):not(.handle-column) {
        display: none;
    }

    .admin-lab-socials-table td.handle-column {
        width: 100% !important;
        height: 40px;
        padding: 0;
    }

    .admin-lab-socials-table .social-header {
        width: 100%;
        cursor: default;
    }

    .admin-lab-socials-table tr.row-expanded td {
        display: flex;
    }

    .admin-lab-socials-table tr.row-expanded td[data-colname="Preview"] {
        display: none;
    }

    .admin-lab-socials-table tr.row-expanded td.column-primary {
        display: flex !important;
        flex-direction: column;
        gap: 4px;
        font-weight: 600;
        padding: 8px;
        background: #f7f7f7;
    }

    /* Custom toggle button */
    .social-toggle-button {
        all: unset;
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        padding: 6px 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        background: var(--admin-lab-color-th-background);
        border: 1px solid var(--admin-lab-color-borders);
        border-radius: 4px;
    }

    .social-toggle-button .dashicons {
        margin-left: auto;
        transition: transform 0.2s ease;
    }

    .social-toggle-button[aria-expanded="true"] .dashicons {
        transform: rotate(180deg);
    }

    /* Native WP-style toggle button */
    .social-header {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 40px;
        padding: 0 36px 0 0;
    }

    .social-header,
    .ui-sortable-handle {
        border-bottom: 1px solid var(--admin-lab-color-borders);
    }

    .admin-lab-socials-table .handle-column .dashicons-move {
        position: static;
        padding: 10px;
        cursor: move;
    }

    .social-header img,
    .social-header .social-key {
        margin-right: 6px;
        vertical-align: middle;
    }

    .social-header .toggle-row {
        position: absolute;
        top: 8px;
        right: 8px;
        background: none;
        border: none;
        cursor: pointer;
    }

    .social-header .toggle-row::after {
        content: "\f140";
        font-family: "Dashicons";
        font-size: 18px;
    }

    .admin-lab-socials-table .toggle-row {
        position: absolute;
        top: 10px;
        right: 10px;
        background: none;
        border: none;
        cursor: pointer;
    }

    .admin-lab-socials-table .toggle-row::after {
        content: "\f140";
        font-family: "Dashicons";
        font-size: 18px;
    }

    .admin-lab-socials-table tr.row-expanded .toggle-row::after {
        content: "\f142";
    }

    table.admin-lab-socials-table td.column-actions {
        height: 44px;
    }

    /* Subscription filters */
    .subscription-filters {
        padding: 10px;
    }
    
    .subscription-filters label {
        display: block;
        margin-bottom: 10px;
    }

    /* Events grid */
    .admin-lab-events-grid {
        grid-template-columns: 1fr;
    }
}

/* Responsive - Desktop (>= 782px) */
@media (min-width: 782px) {
    /* Socials table - Desktop */
    .admin-lab-socials-table .social-header {
        display: none;
    }

    .admin-lab-socials-table tr.row-expanded td {
        display: table-cell;
    }

    .admin-lab-socials-table tr td {
        display: table-cell;
    }

    .admin-lab-socials-table .handle-column .dashicons-move {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: move;
    }

    table.admin-lab-socials-table {
        width: 100%;
        border-collapse: collapse;
    }

    table.admin-lab-socials-table thead {
        display: table-header-group;
    }

    table.admin-lab-socials-table tr {
        display: table-row !important;
    }

    table.admin-lab-socials-table tr.social-hidden {
        display: none !important;
    }

    table.admin-lab-socials-table td,
    table.admin-lab-socials-table th {
        display: table-cell !important;
        padding: 8px;
        vertical-align: middle;
        background: var(--admin-lab-color-white);
    }

    .admin-lab-socials-table td::before {
        display: none !important;
    }

    .admin-lab-socials-table .social-header {
        display: none !important;
    }

    .admin-lab-socials-table td.handle-column {
        position: relative;
        height: auto;
        padding: 0;
    }

    .admin-lab-socials-table tr:nth-of-type(odd) td {
        background-color: var(--admin-lab-color-white);
    }

    .admin-lab-socials-table tr:nth-of-type(even) td {
        background-color: var(--admin-lab-color-th-background);
    }
}

/* Responsive - Medium screens */
@media screen and (max-width: 1400px) and (min-width: 1221px) {
    .column-giveaway_partner,
    #giveaway_partner {
        display: none;
    }

    .alignleft.actions {
        margin-bottom: 10px;
    }
}

@media screen and (max-width: 1220px) and (min-width: 783px) {
    .column-giveaway_partner,
    #giveaway_partner,
    #giveaway_start_date,
    .column-giveaway_start_date,
    #giveaway_end_date,
    .column-giveaway_end_date {
        display: none;
    }

    .alignleft.actions {
        margin-bottom: 10px;
    }
}
```

## Utilisation

Tous les éléments admin utilisent maintenant ces classes génériques. Pour modifier le style global, changez simplement les variables CSS dans `global-colors.css`.

### Structure HTML Standardisée

#### Boutons

```html
<!-- Bouton primaire -->
<button class="button button-primary">Action</button>

<!-- Bouton secondaire -->
<button class="button button-secondary">Action</button>

<!-- Bouton de suppression - Utiliser UNIQUEMENT la classe générique -->
<button class="button admin-lab-button-delete">Supprimer</button>
<!-- OU avec button-secondary ou button-danger (tous les deux donnent le style rouge) -->
<a href="#" class="button button-secondary admin-lab-button-delete">Supprimer</a>
<a href="#" class="button button-danger admin-lab-button-delete">Supprimer</a>
<!-- OU si ce n'est pas un bouton WordPress -->
<a href="#" class="admin-lab-button-delete">Supprimer</a>
```

#### Formulaires

```html
<div class="admin-lab-form-section">
    <label class="admin-lab-field-label">Label</label>
    <input type="text" class="admin-lab-field-input" />
</div>
```

#### Tableaux

```html
<!-- Utiliser la classe générique pour les colonnes d'actions -->
<table class="wp-list-table admin-lab-list-table">
    <thead>
        <tr>
            <th>Colonne</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="admin-lab-column-actions">
                <div class="action-buttons">
                    <button class="button">Action</button>
                    <a href="#" class="button button-danger admin-lab-button-delete">Supprimer</a>
                </div>
            </td>
        </tr>
    </tbody>
</table>
```

#### Status Indicators

```html
<!-- Utiliser UNIQUEMENT les classes génériques -->
<span class="admin-lab-status-active">✓ Active</span>
<span class="admin-lab-status-inactive">✗ Inactive</span>
<span class="admin-lab-status-pending">⏳ Pending</span>
```

## Notes

- Les styles admin restent dans le plugin (pas dans le thème)
- Tous les modules utilisent les mêmes classes génériques
- Les sections spécifiques (events, subscription, shortcodes) gardent leurs classes mais suivent les mêmes patterns
- Les variables CSS sont centralisées dans `global-colors.css`
- Le système d'options d'écran est **générique et réutilisable** pour tous les tableaux

