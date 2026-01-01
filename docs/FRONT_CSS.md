# Règles CSS Génériques pour le Front-End

Ces règles CSS doivent être copiées dans votre fichier CSS de thème (ex: `assets/css/front.css` ou `style.css`).

**Préfixe des classes** : `me5rine-lab-` (vous pouvez le modifier dans votre thème si besoin)

Ce fichier unifie TOUS les styles front-end réutilisables de tous les modules. Une seule modification de variable change le style partout.

## Variables CSS Unifiées

Toutes les variables sont centralisées ici. Modifiez ces valeurs pour changer le style de tous les éléments front :

```css
:root {
    /* ============================================
       COULEURS DE BASE
       ============================================ */
    
    /* Couleurs principales */
    --me5rine-lab-primary: var(--e-global-color-primary, #2E576F);
    --me5rine-lab-secondary: var(--admin-lab-color-secondary, #0485C8);
    --me5rine-lab-white: var(--admin-lab-color-white, #ffffff);
    
    /* Couleurs de fond */
    --me5rine-lab-bg: var(--admin-lab-color-white, #ffffff);
    --me5rine-lab-bg-secondary: var(--admin-lab-color-th-background, #F9FAFB);
    --me5rine-lab-bg-odd: var(--admin-lab-color-odd, #f6f7f7);
    
    /* Couleurs de texte */
    --me5rine-lab-text: var(--admin-lab-color-header-text, #11161E);
    --me5rine-lab-text-light: var(--admin-lab-color-text, #5D697D);
    --me5rine-lab-text-muted: #a7aaad;
    
    /* Couleurs de bordures */
    --me5rine-lab-border: var(--admin-lab-color-borders, #DEE5EC);
    --me5rine-lab-border-light: #B5C2CF;
    
    /* Couleurs de boutons */
    --me5rine-lab-button-primary-bg: var(--me5rine-lab-secondary, #0485C8);
    --me5rine-lab-button-primary-hover: var(--e-global-color-primary, #2E576F);
    --me5rine-lab-button-secondary-bg: var(--me5rine-lab-bg-secondary, #F9FAFB);
    --me5rine-lab-button-secondary-border: var(--me5rine-lab-border, #DEE5EC);
    
    /* Espacements */
    --me5rine-lab-spacing-xs: 4px;
    --me5rine-lab-spacing-sm: 8px;
    --me5rine-lab-spacing-md: 16px;
    --me5rine-lab-spacing-lg: 24px;
    --me5rine-lab-spacing-xl: 32px;
    
    /* Rayons de bordure */
    --me5rine-lab-radius-sm: 6px;
    --me5rine-lab-radius-md: 8px;
    --me5rine-lab-radius-lg: 12px;
    
    /* Ombres */
    --me5rine-lab-shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
    --me5rine-lab-shadow-md: 0 2px 4px rgba(0, 0, 0, 0.1);
    --me5rine-lab-shadow-lg: 0 4px 8px rgba(0, 0, 0, 0.15);
    
    /* Transitions */
    --me5rine-lab-transition: all 0.2s ease;
}
```

## Boutons Génériques

Tous les boutons front utilisent ces classes génériques (unifiées avec les formulaires) :

```css
/* Bouton générique */
.me5rine-lab-form-button {
    background: linear-gradient(135deg, var(--me5rine-lab-secondary, #0485C8) 0%, var(--me5rine-lab-primary, #2E576F) 100%);
    color: var(--me5rine-lab-white, #ffffff);
    border: none;
    padding: 4px 8px;
    border-radius: var(--me5rine-lab-radius-sm, 6px);
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease-in-out;
    box-shadow: none;
    text-transform: none;
    letter-spacing: normal;
    text-decoration: none;
    display: block;
    margin-top: 20px;
    text-align: center;
    white-space: nowrap;
    box-sizing: border-box;
    line-height: 1.5;
}

/* Variante avec text-transform uppercase */
.me5rine-lab-form-button.uppercase {
    text-transform: uppercase;
}

.me5rine-lab-form-button:hover {
    background: var(--me5rine-lab-secondary, #0485C8);
    box-shadow: none;
    transform: none;
    color: var(--me5rine-lab-white, #ffffff);
}

.me5rine-lab-form-button:active {
    transform: none;
    box-shadow: none;
}

/* Bouton secondaire (noir/gris) */
.me5rine-lab-form-button-secondary {
    background: var(--me5rine-lab-text, #11161E) !important;
    color: var(--me5rine-lab-white, #ffffff) !important;
    border: 2px solid var(--me5rine-lab-text, #11161E) !important;
    text-transform: none;
    letter-spacing: normal;
    box-shadow: none;
    font-weight: 500;
}

.me5rine-lab-form-button-secondary:hover {
    background: var(--me5rine-lab-text-light, #5D697D) !important;
    border-color: var(--me5rine-lab-text-light, #5D697D) !important;
    color: var(--me5rine-lab-white, #ffffff) !important;
    transform: translateY(-1px);
    box-shadow: var(--me5rine-lab-shadow-sm);
}

.me5rine-lab-form-button-secondary:active {
    transform: translateY(0);
    box-shadow: none;
}

/* Bouton de suppression (remove/delete) - unifié */
.me5rine-lab-form-button-remove {
    background: #dc3545 !important;
    color: var(--me5rine-lab-white, #ffffff) !important;
    border: 2px solid #dc3545 !important;
    text-transform: none;
    letter-spacing: normal;
    box-shadow: none;
    font-weight: 500;
    padding: 8px 16px;
    font-size: 13px;
    margin-top: 0.5rem;
}

.me5rine-lab-form-button-remove:hover {
    background: #c82333 !important;
    border-color: #c82333 !important;
    color: var(--me5rine-lab-white, #ffffff) !important;
    transform: translateY(-1px);
    box-shadow: var(--me5rine-lab-shadow-sm);
}

.me5rine-lab-form-button-remove:active {
    transform: translateY(0);
    box-shadow: none;
}

/* Bouton désactivé */
.me5rine-lab-form-button:disabled,
.me5rine-lab-form-button.disabled {
    opacity: 0.4;
    cursor: not-allowed;
    pointer-events: none;
}

/* Input de type file */
.me5rine-lab-form-button-file {
    /* Classe spécifique pour les inputs de type file */
    /* Permet de cibler et styliser les champs d'upload de fichiers */
    /* Les styles par défaut des inputs s'appliquent, cette classe permet des surcharges spécifiques */
}
```

## Cartes et Tiles Génériques

Pour les cartes, tuiles, et conteneurs similaires :

```css
/* Carte générique */
.me5rine-lab-card {
    background: var(--me5rine-lab-bg);
    border: 1px solid var(--me5rine-lab-border);
    border-radius: var(--me5rine-lab-radius-md);
    padding: var(--me5rine-lab-spacing-lg);
    box-shadow: var(--me5rine-lab-shadow-sm);
    transition: var(--me5rine-lab-transition);
}

.me5rine-lab-card:hover {
    box-shadow: var(--me5rine-lab-shadow-md);
    transform: translateY(-2px);
}

/* Carte avec bordure gauche colorée */
.me5rine-lab-card-bordered-left {
    border-left: 4px solid var(--me5rine-lab-secondary);
}

/* Tile (petite carte de statistique) */
.me5rine-lab-tile {
    background: var(--me5rine-lab-bg);
    border: 1px solid var(--me5rine-lab-border);
    border-radius: var(--me5rine-lab-radius-lg);
    padding: var(--me5rine-lab-spacing-lg);
    text-align: center;
    box-shadow: var(--me5rine-lab-shadow-sm);
}

.me5rine-lab-tile-number {
    display: block;
    font-size: 1.8rem;
    font-weight: bold;
    color: var(--me5rine-lab-text);
    margin-bottom: var(--me5rine-lab-spacing-sm);
}

.me5rine-lab-tile-label {
    font-size: 0.9rem;
    color: var(--me5rine-lab-text-light);
}

/* Grille de tiles */
.me5rine-lab-tiles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: var(--me5rine-lab-spacing-md);
}
```

## Titres Génériques

```css
/* Titre de section */
.me5rine-lab-title {
    font-weight: 600;
    font-size: 20px;
    color: var(--me5rine-lab-text);
    margin: 0 0 var(--me5rine-lab-spacing-lg) 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.me5rine-lab-title-large {
    font-size: 2rem;
    margin-bottom: 1.5rem;
}

.me5rine-lab-title-medium {
    font-size: 1.4rem;
    margin-bottom: var(--me5rine-lab-spacing-md);
}

/* Sous-titre */
.me5rine-lab-subtitle {
    color: var(--me5rine-lab-text-light);
    font-size: 14px;
    margin: 0 0 var(--me5rine-lab-spacing-md) 0;
    line-height: 1.6;
}
```

## Pagination Générique

```css
/* Container de pagination */
.me5rine-lab-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: var(--me5rine-lab-spacing-lg);
    padding-top: var(--me5rine-lab-spacing-md);
    border-top: 2px solid var(--me5rine-lab-border);
}

.me5rine-lab-pagination-info {
    font-size: 13px;
    color: var(--me5rine-lab-text-light);
    font-weight: 500;
}

.me5rine-lab-pagination-links {
    display: flex;
    align-items: center;
    gap: var(--me5rine-lab-spacing-xs);
}

.me5rine-lab-pagination-button {
    min-width: 36px;
    height: 36px;
    padding: 0 12px;
    border: 2px solid var(--me5rine-lab-border);
    border-radius: var(--me5rine-lab-radius-sm);
    background: var(--me5rine-lab-bg);
    color: var(--me5rine-lab-text-light);
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: var(--me5rine-lab-transition);
    box-sizing: border-box;
}

.me5rine-lab-pagination-button:hover:not(.disabled) {
    border-color: var(--me5rine-lab-secondary);
    background: var(--me5rine-lab-bg-secondary);
    color: var(--me5rine-lab-secondary);
}

.me5rine-lab-pagination-button.active {
    border-color: var(--me5rine-lab-secondary);
    background: var(--me5rine-lab-bg);
    color: var(--me5rine-lab-secondary);
}

.me5rine-lab-pagination-button.active:hover {
    background: var(--me5rine-lab-secondary);
    color: var(--me5rine-lab-white);
}

.me5rine-lab-pagination-button.disabled,
.me5rine-lab-pagination-button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    background: var(--me5rine-lab-bg-secondary);
}

.me5rine-lab-pagination-text {
    font-size: 13px;
    color: var(--me5rine-lab-text-light);
    font-weight: 500;
    margin: 0 var(--me5rine-lab-spacing-md);
}

.me5rine-lab-pagination-text .total-pages {
    font-weight: 600;
    color: var(--me5rine-lab-text);
}
```

## Filtres Génériques

Les filtres utilisent les mêmes styles de base que les formulaires (`me5rine-lab-form-*`) avec une classe supplémentaire (`me5rine-lab-filter-*`) pour les surcharges spécifiques si nécessaire.

```css
/* Container de filtres */
.me5rine-lab-filters {
    display: flex;
    flex-wrap: wrap;
    gap: var(--me5rine-lab-spacing-md);
    align-items: flex-end;
    margin-bottom: var(--me5rine-lab-spacing-lg);
    padding-bottom: var(--me5rine-lab-spacing-md);
    border-bottom: 2px solid var(--me5rine-lab-border);
}

.me5rine-lab-filter-group {
    display: flex;
    flex-direction: column;
    gap: var(--me5rine-lab-spacing-sm);
}

/* Les labels de filtres utilisent me5rine-lab-form-label comme base */
/* Surcharge spécifique pour les filtres si nécessaire */
.me5rine-lab-filter-label {
    /* Hérite de me5rine-lab-form-label (défini dans CSS_RULES.md) */
    /* Les styles de base sont déjà dans me5rine-lab-form-label */
    /* Surcharges spécifiques aux filtres si nécessaire */
}

/* Les inputs/selects de filtres utilisent me5rine-lab-form-input/me5rine-lab-form-select comme base */
/* Surcharges spécifiques pour les filtres */
.me5rine-lab-filter-select,
.me5rine-lab-filter-input {
    /* Hérite de me5rine-lab-form-select/me5rine-lab-form-input (défini dans CSS_RULES.md) */
    /* Les styles de base sont déjà dans me5rine-lab-form-select/me5rine-lab-form-input */
    /* Surcharges spécifiques aux filtres */
    min-width: 180px;
    width: auto !important; /* Surcharge du width: 100% des formulaires */
}
```

## Select2 et Choices (Unifié avec les autres select)

Pour unifier Select2 et Choices.js avec les autres select :

```css
/* Select2 - Unifié avec me5rine-lab-form-select */
.select2-container .select2-selection--single,
.choices__inner {
    min-height: 44px !important;
    padding: 9px 40px 9px 16px !important;
    border: 2px solid var(--me5rine-lab-border, #DEE5EC) !important;
    border-radius: var(--me5rine-lab-radius-md, 8px) !important;
    font-size: 14px !important;
    font-family: inherit !important;
    line-height: 1.5 !important;
    transition: var(--me5rine-lab-transition, all 0.2s ease) !important;
    background-color: var(--me5rine-lab-bg, #ffffff) !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235D697D' d='M6 9L1 4h10z'/%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 12px center !important;
    background-size: 12px !important;
    cursor: pointer !important;
    color: var(--me5rine-lab-text-light, #5D697D) !important;
    box-sizing: border-box !important;
}

.select2-container .select2-selection--single:focus,
.select2-container--open .select2-selection--single,
.choices__inner:focus,
.choices.is-focused .choices__inner {
    outline: none !important;
    border-color: var(--me5rine-lab-secondary, #0485C8) !important;
    box-shadow: 0 0 0 3px rgba(4, 133, 200, 0.1) !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%230485C8' d='M6 9L1 4h10z'/%3E%3C/svg%3E") !important;
}

.select2-container .select2-selection--single:hover:not(:focus),
.choices__inner:hover:not(:focus) {
    border-color: var(--me5rine-lab-border-light, #B5C2CF) !important;
}

/* Select2 dropdown */
.select2-dropdown,
.choices__list--dropdown {
    border: 2px solid var(--me5rine-lab-border, #DEE5EC) !important;
    border-radius: var(--me5rine-lab-radius-md, 8px) !important;
    box-shadow: var(--me5rine-lab-shadow-md, 0 2px 4px rgba(0, 0, 0, 0.1)) !important;
    background: var(--me5rine-lab-bg, #ffffff) !important;
}

.select2-results__option,
.choices__list--dropdown .choices__item {
    padding: 10px 16px !important;
    color: var(--me5rine-lab-text-light, #5D697D) !important;
    transition: var(--me5rine-lab-transition, all 0.2s ease) !important;
}

.select2-results__option--highlighted,
.choices__list--dropdown .choices__item--selectable.is-highlighted {
    background: var(--me5rine-lab-bg-secondary, #F9FAFB) !important;
    color: var(--me5rine-lab-text, #11161E) !important;
}

.select2-results__option[aria-selected="true"],
.choices__list--dropdown .choices__item--selectable.is-selected {
    background: var(--me5rine-lab-secondary, #0485C8) !important;
    color: var(--me5rine-lab-white, #ffffff) !important;
}

/* Choices multiple tags */
.choices__list--multiple .choices__item {
    background-color: var(--me5rine-lab-secondary, #0485C8) !important;
    border-color: var(--me5rine-lab-secondary, #0485C8) !important;
    color: var(--me5rine-lab-white, #ffffff) !important;
    border-radius: var(--me5rine-lab-radius-sm, 6px) !important;
    padding: 4px 8px !important;
    margin: 4px 4px 4px 0 !important;
}

.choices__button {
    background-color: var(--me5rine-lab-secondary, #0485C8) !important;
    border-left: 1px solid rgba(255, 255, 255, 0.3) !important;
    padding: 0 4px !important;
}
```

## Tuiles Social Action (Unifié)

Pour les tuiles d'actions sociales dans les formulaires de campagne :

```css
/* Container de tuiles sociales */
.me5rine-lab-social-actions-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: var(--me5rine-lab-spacing-md);
    justify-content: flex-start;
}

/* Tuile d'action sociale (design original) */
.me5rine-lab-social-action-tile,
.social-action-tile {
    flex: 1 1 calc(33.333% - 13.33px);
    min-width: 240px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    padding: 1rem;
    border: 1px solid var(--admin-lab-color-block-border);
    border-radius: 8px;
    background-color: var(--admin-lab-color-white);
    transition: transform 0.2s ease-in-out;
}

.me5rine-lab-social-action-tile:hover,
.social-action-tile:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
}

/* Bouton d'activation dans la tuile (design original) */
.me5rine-lab-social-action-tile .me5rine-lab-form-button-secondary,
.me5rine-lab-social-action-tile .btn-activate,
.social-action-tile .me5rine-lab-form-button-secondary,
.social-action-tile .btn-activate {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 0.75rem;
    background-color: var(--admin-lab-color-white, #ffffff) !important;
    border: 1px solid var(--admin-lab-color-borders, #DEE5EC) !important;
    color: var(--admin-lab-color-admin-text, #11161E) !important;
    font-weight: 500;
    padding: 0.6rem 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
    transition: all 0.2s ease-in-out;
    font-size: 0.9rem;
    white-space: normal;
    word-break: break-word;
    text-align: left;
    margin-top: 0;
}

.me5rine-lab-social-action-tile .me5rine-lab-form-button-secondary:hover,
.me5rine-lab-social-action-tile .btn-activate:hover,
.social-action-tile .me5rine-lab-form-button-secondary:hover,
.social-action-tile .btn-activate:hover {
    background-color: var(--admin-lab-color-button-secondary-hover, #F9FAFB) !important;
    border-color: var(--admin-lab-color-borders, #DEE5EC) !important;
    color: var(--admin-lab-color-admin-text, #11161E) !important;
    transform: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
}

.me5rine-lab-social-action-tile .me5rine-lab-form-button-secondary:focus {
    background-color: var(--me5rine-lab-bg, #ffffff);
    outline-width: 2px;
    outline-style: solid;
    outline-color: var(--me5rine-lab-secondary, #0485C8);
}

.me5rine-lab-social-action-tile .me5rine-lab-form-button-secondary::before {
    content: "";
    display: inline-block;
    min-width: 20px;
    height: 20px;
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    flex-shrink: 0;
}

/* Options de score */
.me5rine-lab-score-options {
    margin-top: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: nowrap;
    overflow-x: auto;
}

/* Bouton de score (design original) */
.me5rine-lab-btn-score,
.btn-score {
    padding: 0.4rem 0.5rem;
    border-radius: 6px;
    border: 1px solid var(--admin-lab-color-borders, #DEE5EC);
    background-color: var(--admin-lab-color-button-secondary-hover, #F9FAFB) !important;
    color: var(--admin-lab-color-text-accent, #5D697D) !important;
    cursor: pointer !important;
    transition: background-color 0.2s;
    font-size: 0.8rem !important;
    white-space: nowrap;
    line-height: 15px;
    font-weight: 400;
}

.me5rine-lab-btn-score:hover,
.btn-score:hover {
    background-color: var(--admin-lab-color-button-secondary-hover, #F9FAFB) !important;
}

.me5rine-lab-btn-score.active,
.btn-score.active {
    background-color: var(--admin-lab-color-button-black, #11161E) !important;
    color: var(--admin-lab-color-white, #ffffff) !important;
    border-color: var(--admin-lab-color-button-black, #11161E) !important;
}

/* Responsive pour les tuiles sociales */
@media (max-width: 768px) {
    .me5rine-lab-social-action-tile {
        width: 100%;
        min-width: auto;
    }
}
```

## Messages et Notices Génériques

```css
/* Notice générique */
.me5rine-lab-notice {
    margin: 1em 0;
    padding: 1em;
    border-left: 4px solid var(--me5rine-lab-border);
    background-color: var(--me5rine-lab-bg-secondary);
    border-radius: var(--me5rine-lab-radius-sm);
}

.me5rine-lab-notice p {
    margin: 0;
    color: var(--me5rine-lab-text-light);
    font-size: 14px;
    line-height: 1.6;
}

/* Message d'état */
.me5rine-lab-message {
    padding: var(--me5rine-lab-spacing-md);
    background: var(--me5rine-lab-bg-secondary);
    border-radius: var(--me5rine-lab-radius-md);
    border-left: 4px solid var(--me5rine-lab-border);
    color: var(--me5rine-lab-text-light);
    font-size: 14px;
    line-height: 1.6;
}
```

## Containers et Layout Génériques

```css
/* Container de dashboard */
.me5rine-lab-dashboard {
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--me5rine-lab-text-light);
    font-size: 14px;
}

.me5rine-lab-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--me5rine-lab-spacing-lg);
}

/* Container de section */
.me5rine-lab-section {
    background: transparent;
    border-radius: 0;
    padding: 0;
    box-shadow: none;
    margin-bottom: 0;
}

/* Container de wrapper */
.me5rine-lab-wrapper {
    background: transparent;
    border-radius: 0;
    padding: 0;
    box-shadow: none;
    margin-bottom: 0;
}

/* Bloc de formulaire (section avec fond et bordure) */
.me5rine-lab-form-block {
    background-color: var(--me5rine-lab-bg-secondary, #F9FAFB);
    border: 1px solid var(--me5rine-lab-border, #DEE5EC);
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
}

/* Bloc de formulaire en flex */
.me5rine-lab-form-block-flex {
    display: flex;
    flex-wrap: wrap;
}

/* Colonne de formulaire */
.me5rine-lab-form-col {
    flex: 1;
    min-width: 100%;
}

/* Colonne de formulaire avec gap */
.me5rine-lab-form-col-gap {
    gap: 2rem;
}

/* Ligne de règles (flex avec gap) */
.me5rine-lab-form-rules-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: flex-start;
    margin-bottom: 1rem;
}

/* Colonne de règles */
.me5rine-lab-form-rules-col {
    flex: 1 1 calc(50% - 10px);
    box-sizing: border-box;
}

/* Sélection de temps */
.me5rine-lab-form-time-selection {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: nowrap;
}

.me5rine-lab-form-time-selection .me5rine-lab-form-select {
    width: auto !important;
    min-width: auto;
    flex-shrink: 0;
}

.me5rine-lab-form-time-selection span {
    font-size: 0.8rem;
    color: var(--me5rine-lab-text-light, #5D697D);
    line-height: 1;
    margin-top: -10px;
    white-space: nowrap;
}
```

## Tableaux Génériques (Unifié)

Tous les tableaux front utilisent ce système unifié avec responsive cohérent :

```css
/* Table générique */
.me5rine-lab-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--me5rine-lab-bg);
    border: 1px solid var(--me5rine-lab-border);
    border-radius: var(--me5rine-lab-radius-md);
    overflow: hidden;
    box-shadow: var(--me5rine-lab-shadow-sm);
    margin-top: var(--me5rine-lab-spacing-lg);
    margin-bottom: var(--me5rine-lab-spacing-lg);
}

.me5rine-lab-table thead {
    background: var(--me5rine-lab-bg-secondary);
}

.me5rine-lab-table th {
    padding: var(--me5rine-lab-spacing-md);
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    color: var(--me5rine-lab-text);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--me5rine-lab-border);
}

.me5rine-lab-table tbody tr {
    border-bottom: 1px solid var(--me5rine-lab-border);
    transition: background-color var(--me5rine-lab-transition);
}

.me5rine-lab-table tbody tr:last-child {
    border-bottom: none;
}

.me5rine-lab-table tbody tr:hover {
    background: var(--me5rine-lab-bg-secondary);
}

.me5rine-lab-table tbody tr.me5rine-lab-table-row-toggleable.is-expanded {
    background: var(--me5rine-lab-bg-secondary);
}

.me5rine-lab-table td {
    padding: var(--me5rine-lab-spacing-md);
    font-size: 14px;
    color: var(--me5rine-lab-text-light);
    vertical-align: middle;
}

.me5rine-lab-table td.summary {
    position: relative;
    padding-right: 50px;
}

/* Ligne de résumé */
.me5rine-lab-table-summary-row {
    display: flex;
    align-items: center;
    gap: var(--me5rine-lab-spacing-sm);
}

/* Titre dans le tableau */
.me5rine-lab-table-title {
    font-weight: 600;
    font-size: 15px;
    color: var(--me5rine-lab-text);
}

.me5rine-lab-table-title a {
    color: var(--me5rine-lab-text);
    text-decoration: none;
    transition: color var(--me5rine-lab-transition);
}

.me5rine-lab-table-title a:hover {
    color: var(--me5rine-lab-secondary);
}

/* Bouton toggle pour mobile */
.me5rine-lab-table-toggle-btn {
    position: absolute;
    right: var(--me5rine-lab-spacing-md);
    top: 50%;
    transform: translateY(-50%);
    width: 32px;
    height: 32px;
    border: 2px solid var(--me5rine-lab-border);
    border-radius: var(--me5rine-lab-radius-sm);
    background: var(--me5rine-lab-bg);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--me5rine-lab-transition);
    padding: 0;
}

.me5rine-lab-table-toggle-btn:hover {
    border-color: var(--me5rine-lab-secondary);
    background: var(--me5rine-lab-bg-secondary);
}

.me5rine-lab-table-toggle-btn:focus {
    outline: none;
    border-color: var(--me5rine-lab-secondary);
    box-shadow: 0 0 0 3px rgba(4, 133, 200, 0.1);
}

.me5rine-lab-table-toggle-btn::before {
    content: '▼';
    font-size: 10px;
    color: var(--me5rine-lab-text-light);
    transition: transform var(--me5rine-lab-transition), color var(--me5rine-lab-transition);
}

.me5rine-lab-table-toggle-btn[aria-expanded="true"]::before,
.me5rine-lab-table-row-toggleable.is-expanded .me5rine-lab-table-toggle-btn::before {
    transform: rotate(180deg);
}

.me5rine-lab-table-toggle-btn:hover::before {
    color: var(--me5rine-lab-secondary);
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

/* Tableaux avec lignes alternées */
.me5rine-lab-table.striped tbody tr:nth-child(odd) {
    background-color: var(--me5rine-lab-bg-secondary);
}

.me5rine-lab-table.striped tbody tr:nth-child(even) {
    background-color: var(--me5rine-lab-bg);
}

/* Desktop : masquer le bouton toggle */
@media screen and (min-width: 783px) {
    .me5rine-lab-table .me5rine-lab-table-toggle-btn {
        display: none;
    }

    .me5rine-lab-table td.summary {
        padding-right: var(--me5rine-lab-spacing-md);
    }
}

/* Mobile : affichage en cartes */
@media screen and (max-width: 782px) {
    .me5rine-lab-table {
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-top: var(--me5rine-lab-spacing-md);
        margin-bottom: var(--me5rine-lab-spacing-md);
    }

    .me5rine-lab-table thead {
        display: none;
    }

    .me5rine-lab-table tbody {
        display: block;
    }

    .me5rine-lab-table tbody tr {
        display: block;
        margin-bottom: var(--me5rine-lab-spacing-md);
        border: 2px solid var(--me5rine-lab-border);
        border-radius: var(--me5rine-lab-radius-md);
        background: var(--me5rine-lab-bg);
        overflow: hidden;
    }

    .me5rine-lab-table td {
        display: block;
        padding: 12px var(--me5rine-lab-spacing-md);
        text-align: left;
        border-bottom: 1px solid var(--me5rine-lab-border);
    }

    .me5rine-lab-table td:last-child {
        border-bottom: none;
    }

    .me5rine-lab-table td.summary {
        padding-right: 50px;
        background: var(--me5rine-lab-bg-secondary);
        font-weight: 600;
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

    /* Labels de colonnes sur mobile (via data-colname) */
    .me5rine-lab-table td[data-colname]::before {
        content: attr(data-colname) ": ";
        font-weight: 600;
        color: var(--me5rine-lab-text);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 12px;
        margin-right: var(--me5rine-lab-spacing-sm);
    }

    .me5rine-lab-table td.summary[data-colname]::before {
        display: none;
    }
}
```

## Tuiles Génériques (Unifié)

Toutes les tuiles (stat-tile, card, etc.) utilisent ce système unifié :

```css
/* Grille de tuiles */
.me5rine-lab-tiles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: var(--me5rine-lab-spacing-md);
    margin-bottom: var(--me5rine-lab-spacing-lg);
}

/* Tuile générique */
.me5rine-lab-tile {
    background: var(--me5rine-lab-bg);
    border: 1px solid var(--me5rine-lab-border);
    border-radius: var(--me5rine-lab-radius-md);
    padding: var(--me5rine-lab-spacing-lg);
    text-align: center;
    box-shadow: var(--me5rine-lab-shadow-sm);
    transition: var(--me5rine-lab-transition);
}

.me5rine-lab-tile:hover {
    box-shadow: var(--me5rine-lab-shadow-md);
    transform: translateY(-2px);
}

/* Numéro dans une tuile */
.me5rine-lab-tile-number {
    display: block;
    font-size: 1.8rem;
    font-weight: bold;
    color: var(--me5rine-lab-text);
    margin-bottom: var(--me5rine-lab-spacing-xs);
}

/* Label dans une tuile */
.me5rine-lab-tile-label {
    font-size: 0.9rem;
    color: var(--me5rine-lab-text-light);
}
```

## Responsive Global

```css
/* Responsive global */
@media (max-width: 782px) {
    .me5rine-lab-filters {
        flex-direction: column;
        gap: var(--me5rine-lab-spacing-md);
    }

    .me5rine-lab-filter-select,
    .me5rine-lab-filter-input {
        width: 100% !important;
        min-width: auto;
    }

    .me5rine-lab-pagination {
        flex-direction: column;
        gap: var(--me5rine-lab-spacing-md);
        align-items: stretch;
    }

    .me5rine-lab-pagination-info {
        text-align: center;
    }

    .me5rine-lab-pagination-links {
        justify-content: center;
        flex-wrap: wrap;
    }

    .me5rine-lab-tiles-grid {
        grid-template-columns: 1fr;
    }

    .me5rine-lab-dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--me5rine-lab-spacing-md);
    }
}
```

## Utilisation

Tous les éléments front utilisent maintenant ces classes génériques. Pour modifier le style global, changez simplement les variables CSS en haut du fichier.

### Exemple d'utilisation

```html
<!-- Bouton -->
<button class="me5rine-lab-button me5rine-lab-button-primary">Action</button>

<!-- Carte -->
<div class="me5rine-lab-card me5rine-lab-card-bordered">
    <h3 class="me5rine-lab-title">Titre</h3>
    <p class="me5rine-lab-subtitle">Description</p>
</div>

<!-- Pagination -->
<div class="me5rine-lab-pagination">
    <span class="me5rine-lab-pagination-info">10 résultats</span>
    <div class="me5rine-lab-pagination-links">
        <a href="#" class="me5rine-lab-pagination-button">1</a>
        <a href="#" class="me5rine-lab-pagination-button active">2</a>
    </div>
</div>
```

## Formulaire de Campagne (Giveaway)

Le formulaire de campagne (`giveaway-form-campaign.css`) utilise maintenant les variables CSS unifiées et les classes génériques. Il reste dans le plugin mais s'aligne sur le système unifié :

- Utilise les variables `--me5rine-lab-*` pour les couleurs, espacements, rayons
- Utilise les mêmes patterns que les autres formulaires
- Styles spécifiques pour les tiles sociales et les prix (mais avec variables unifiées)

## Fichiers CSS Spécifiques (Non Unifiés)

Ces fichiers contiennent des styles spécifiques à des fonctionnalités uniques et ne doivent PAS être unifiés :

- `comparator-widgets.css` - Widgets comparator (styles spécifiques uniques)
- `giveaway-rafflepress-custom.css` - Personnalisation spécifique RafflePress
- `autor-socials-style.css` - Styles spécifiques aux auteurs/réseaux sociaux

Ces fichiers restent dans le plugin et ne sont pas migrés dans le thème.

