# Règles CSS Génériques pour le Front-End

Ces règles CSS doivent être copiées dans votre fichier CSS de thème (ex: `assets/css/front.css` ou `style.css`).

**Préfixe des classes** : `me5rine-lab-` (vous pouvez le modifier dans votre thème si besoin)

Ce fichier unifie TOUS les styles front-end réutilisables de tous les modules. Une seule modification de variable change le style partout.

## ⚠️ Structure OBLIGATOIRE des Conteneurs

Il existe **trois structures distinctes** selon le contexte. Utilisez la bonne structure selon votre cas d'usage.

### 1. Profils Ultimate Member (UM)

**Utilisation** : Uniquement pour les éléments affichés dans les profils Ultimate Member (onglets de profil).

```html
<div class="me5rine-lab-profile-container">
    <h2 class="me5rine-lab-title"><?php _e('Titre de la section', 'text-domain'); ?></h2>
    <p class="me5rine-lab-subtitle"><?php _e('Sous-titre optionnel', 'text-domain'); ?></p>
    
    <!-- Contenu -->
</div>
```

### 2. Dashboards Front-End

**Utilisation** : Pour les pages de dashboard front (liste de giveaways, socials dashboard, etc.).

```html
<div class="{nom-du-dashboard} me5rine-lab-dashboard">
    <h2 class="me5rine-lab-title-large"><?php _e('Titre du dashboard', 'text-domain'); ?></h2>
    
    <!-- Contenu -->
</div>
```

**Exemple** : `socials-dashboard me5rine-lab-dashboard`, `my-giveaways-dashboard me5rine-lab-dashboard`

### 3. Formulaires Front Complets

**Utilisation** : Pour les formulaires complets en front (création/édition de campagne, etc.).

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

**Important** : Le titre principal est à l'extérieur du formulaire, les titres de sections sont dans les `me5rine-lab-form-block`.

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

/* Titre principal de page - OBLIGATOIRE pour toutes les pages front */
/* DOIT être utilisé avec un élément <h2> */
.me5rine-lab-title-large {
    font-size: 24px;
    font-weight: 600;
    color: var(--me5rine-lab-text, #11161E);
    margin: 0 0 var(--me5rine-lab-spacing-lg, 24px) 0;
    line-height: 1.3;
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
/* Aucune surcharge spécifique nécessaire - utiliser directement me5rine-lab-form-label */
.me5rine-lab-filter-label {
    /* Hérite de me5rine-lab-form-label (défini dans CSS_RULES.md) */
    /* Les styles de base sont déjà dans me5rine-lab-form-label */
    /* Aucune surcharge nécessaire - tous les labels de filtres utilisent les mêmes styles */
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

## Système de Statuts Réutilisable

Le système de statuts permet d'afficher des statuts avec des classes CSS prédéfinies (vert, orange, rouge, bleu) pour une cohérence visuelle dans tout le plugin.

### Variables CSS pour les Statuts

```css
:root {
    /* Couleurs de statut */
    --me5rine-lab-status-success-bg: var(--admin-lab-status-success-bg, #d4edda);
    --me5rine-lab-status-success-text: var(--admin-lab-status-success-text, #155724);
    --me5rine-lab-status-success-border: var(--admin-lab-status-success-border, #c3e6cb);
    
    --me5rine-lab-status-warning-bg: var(--admin-lab-status-warning-bg, #fff3cd);
    --me5rine-lab-status-warning-text: var(--admin-lab-status-warning-text, #856404);
    --me5rine-lab-status-warning-border: var(--admin-lab-status-warning-border, #ffeaa7);
    
    --me5rine-lab-status-error-bg: var(--admin-lab-status-error-bg, #f8d7da);
    --me5rine-lab-status-error-text: var(--admin-lab-status-error-text, #721c24);
    --me5rine-lab-status-error-border: var(--admin-lab-status-error-border, #f5c6cb);
    
    --me5rine-lab-status-info-bg: var(--admin-lab-status-info-bg, #d1ecf1);
    --me5rine-lab-status-info-text: var(--admin-lab-status-info-text, #0c5460);
    --me5rine-lab-status-info-border: var(--admin-lab-status-info-border, #bee5eb);
}
```

### Classes CSS pour les Statuts

```css
/* Classe de base pour tous les statuts */
.me5rine-lab-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: var(--me5rine-lab-radius-sm, 6px);
    font-size: 13px;
    font-weight: 500;
    line-height: 1.4;
    border: 1px solid;
    white-space: nowrap;
}

/* Statut Success (vert) - Pour les statuts positifs (gagnant, validé, etc.) */
.me5rine-lab-status-success {
    background-color: var(--me5rine-lab-status-success-bg, #d4edda);
    color: var(--me5rine-lab-status-success-text, #155724);
    border-color: var(--me5rine-lab-status-success-border, #c3e6cb);
}

/* Statut Warning (orange) - Pour les statuts en attente (en attente de tirage, en cours de traitement, etc.) */
.me5rine-lab-status-warning {
    background-color: var(--me5rine-lab-status-warning-bg, #fff3cd);
    color: var(--me5rine-lab-status-warning-text, #856404);
    border-color: var(--me5rine-lab-status-warning-border, #ffeaa7);
}

/* Statut Error (rouge) - Pour les statuts négatifs (perdu, erreur, annulé, etc.) */
.me5rine-lab-status-error {
    background-color: var(--me5rine-lab-status-error-bg, #f8d7da);
    color: var(--me5rine-lab-status-error-text, #721c24);
    border-color: var(--me5rine-lab-status-error-border, #f5c6cb);
}

/* Statut Info (bleu) - Pour les statuts informatifs (en cours, actif, etc.) */
.me5rine-lab-status-info {
    background-color: var(--me5rine-lab-status-info-bg, #d1ecf1);
    color: var(--me5rine-lab-status-info-text, #0c5460);
    border-color: var(--me5rine-lab-status-info-border, #bee5eb);
}
```

### Utilisation PHP

Utilisez la fonction helper `admin_lab_render_status()` pour générer les statuts :

```php
// Exemple 1 : Statut simple
echo admin_lab_render_status('Gagnant', 'success');
// Génère : <span class="me5rine-lab-status me5rine-lab-status-success">Gagnant</span>

// Exemple 2 : Statut avec tag personnalisé
echo admin_lab_render_status('En attente', 'warning', 'div');
// Génère : <div class="me5rine-lab-status me5rine-lab-status-warning">En attente</div>

// Exemple 3 : Statut avec attributs supplémentaires
echo admin_lab_render_status('Perdu', 'error', 'span', ['id' => 'status-1', 'class' => 'custom-class']);
// Génère : <span id="status-1" class="me5rine-lab-status me5rine-lab-status-error custom-class">Perdu</span>
```

### Types de Statuts Disponibles

- **`success`** (vert) : Pour les statuts positifs
  - Exemples : Gagnant, Validé, Confirmé, Actif
  
- **`warning`** (orange) : Pour les statuts en attente
  - Exemples : En attente de tirage, En cours de traitement, En attente de validation
  
- **`error`** (rouge) : Pour les statuts négatifs
  - Exemples : Perdu, Erreur, Annulé, Refusé
  
- **`info`** (bleu) : Pour les statuts informatifs
  - Exemples : En cours, Actif, En traitement

### Cas d'Usage

Ce système peut être utilisé pour :
- Statuts de concours (gagnant, perdu, en attente, en cours)
- Statuts de commandes (validée, en attente, annulée)
- Statuts d'erreur (erreur, succès, avertissement)
- Tout autre élément nécessitant un affichage de statut avec code couleur

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
/* Message générique - Système unifié */
.me5rine-lab-form-message {
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 20px;
    font-size: 14px;
}

.me5rine-lab-form-message p {
    margin: 0;
    font-weight: 500;
}

/* Message de succès */
.me5rine-lab-form-message-success {
    background: rgba(4, 133, 200, 0.1);
    border: 1px solid var(--ph-secondary, #0485C8);
    color: var(--ph-secondary, #0485C8);
}

/* Message d'erreur */
.me5rine-lab-form-message-error {
    background: rgba(214, 54, 56, 0.1);
    border: 1px solid #d63638;
    color: #d63638;
}

/* Message d'avertissement */
.me5rine-lab-form-message-warning {
    background: rgba(255, 152, 0, 0.1);
    border: 1px solid #ff9800;
    color: #ff9800;
}

/* Message d'information */
.me5rine-lab-form-message-info {
    background: rgba(4, 133, 200, 0.1);
    border: 1px solid var(--ph-secondary, #0485C8);
    color: var(--ph-secondary, #0485C8);
}

/* Message d'état vide */
.me5rine-lab-state-message {
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

/* Header de section dans une carte (titre + actions) */
.me5rine-lab-card-header,
.me5rine-lab-tile-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--me5rine-lab-spacing-md);
}

/* Actions dans un header de carte */
.me5rine-lab-card-actions,
.me5rine-lab-tile-actions {
    display: flex;
    gap: var(--me5rine-lab-spacing-sm);
}

.me5rine-lab-card-actions a,
.me5rine-lab-tile-actions a {
    margin-left: 0;
}

/* Titre de section dans une carte */
.me5rine-lab-card-section-title,
.me5rine-lab-tile-section-title {
    font-size: 1.2rem;
    margin: var(--me5rine-lab-spacing-lg) 0 var(--me5rine-lab-spacing-md);
    font-weight: 600;
    color: var(--me5rine-lab-text);
}

/* Container de wrapper */
.me5rine-lab-wrapper {
    background: transparent;
    border-radius: 0;
    padding: 0;
    box-shadow: none;
    margin-bottom: 0;
}

/* Notices dans les dashboards */
.me5rine-lab-dashboard .notice {
    margin: 1em 0;
    padding: 1em;
    border-left: 4px solid var(--me5rine-lab-border);
    background-color: var(--me5rine-lab-bg-secondary);
    border-radius: var(--me5rine-lab-radius-sm);
}

/* Liens dans les dashboards */
.me5rine-lab-dashboard a {
    color: var(--me5rine-lab-secondary);
    text-decoration: none;
    transition: color var(--me5rine-lab-transition);
}

.me5rine-lab-dashboard a:hover {
    text-decoration: underline;
    color: var(--me5rine-lab-button-primary-hover);
}

/* ============================================
   PROFILS (Ultimate Member)
   ============================================ */

/* Padding pour le contenu des onglets Ultimate Member */
.um-profile-body .um-tab-content,
.um-profile-body .um-tab-content > div {
    padding: var(--me5rine-lab-spacing-lg);
}

/* Container de profil (wrapper générique) */
.me5rine-lab-profile-container,
.me5rine-lab-profile-wrapper {
    background: transparent;
    border-radius: 0;
    padding: 0;
    box-shadow: none;
    margin-bottom: 0;
}

/* ============================================
   CARTES AVEC IMAGE (Cards avec thumbnail)
   ============================================ */

/* Carte avec image à gauche et contenu à droite */
.me5rine-lab-card-with-image {
    display: flex;
    gap: var(--me5rine-lab-spacing-md);
    margin-bottom: var(--me5rine-lab-spacing-md);
    transition: transform var(--me5rine-lab-transition);
}

.me5rine-lab-card-with-image:last-child {
    margin-bottom: 0;
}

.me5rine-lab-card-with-image:hover {
    transform: translateX(4px);
}

/* Image/thumbnail de la carte */
.me5rine-lab-card-image {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: var(--me5rine-lab-radius-md);
    flex-shrink: 0;
    border: 2px solid var(--me5rine-lab-border);
}

/* Contenu de la carte */
.me5rine-lab-card-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--me5rine-lab-spacing-sm);
}

/* Header de la carte */
.me5rine-lab-card-header {
    display: flex;
    flex-direction: column;
    gap: var(--me5rine-lab-spacing-xs);
}

/* Nom/titre dans la carte */
.me5rine-lab-card-name {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--me5rine-lab-text);
}

.me5rine-lab-card-name a {
    color: var(--me5rine-lab-text);
    text-decoration: none;
    transition: color var(--me5rine-lab-transition);
}

.me5rine-lab-card-name a:hover {
    color: var(--me5rine-lab-secondary);
}

/* Meta informations dans la carte */
.me5rine-lab-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--me5rine-lab-spacing-md);
    font-size: 13px;
    color: var(--me5rine-lab-text-light);
}

.me5rine-lab-card-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.me5rine-lab-card-meta .me5rine-lab-meta-label {
    font-weight: 500;
}

/* Description/contenu secondaire de la carte */
.me5rine-lab-card-description {
    margin: 0;
    font-size: 14px;
    color: var(--me5rine-lab-text-light);
    line-height: 1.6;
}

.me5rine-lab-card-description strong {
    color: var(--me5rine-lab-text);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 12px;
}

/* Bouton dans la carte */
.me5rine-lab-card-button {
    align-self: flex-start;
}

/* ============================================
   MESSAGES D'ÉTAT
   ============================================ */

/* Message d'état générique (vide, erreur, info) */
.me5rine-lab-state-message {
    color: var(--me5rine-lab-text-light);
    font-size: 14px;
    line-height: 1.6;
    margin: 0;
    padding: var(--me5rine-lab-spacing-md);
    background: var(--me5rine-lab-bg-secondary);
    border-radius: var(--me5rine-lab-radius-md);
    border-left: 4px solid var(--me5rine-lab-border);
}

/* ============================================
   LABELS DE FILTRES RESPONSIVE
   ============================================ */

/* Labels de filtres avec affichage conditionnel mobile/desktop */
.me5rine-lab-filter-label-mobile {
    display: none;
}

.me5rine-lab-filter-label-desktop {
    display: inline-flex;
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

## Dashboards et Profils Génériques

Tous les dashboards et pages de profil utilisent ces classes génériques unifiées :

```css
/* ============================================
   DASHBOARDS
   ============================================ */

/* Container principal de dashboard - OBLIGATOIRE pour toutes les pages front */
.me5rine-lab-dashboard {
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--me5rine-lab-text-light);
    font-size: 14px;
    padding: var(--me5rine-lab-spacing-md);
}

/* Titre principal de page - OBLIGATOIRE pour toutes les pages front */
.me5rine-lab-title-large {
    font-size: 24px;
    font-weight: 600;
    color: var(--me5rine-lab-text, #11161E);
    margin: 0 0 var(--me5rine-lab-spacing-lg, 24px) 0;
    line-height: 1.3;
}

/* Header de dashboard (titre + actions) */
.me5rine-lab-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--me5rine-lab-spacing-lg);
}

/* Notices dans les dashboards */
.me5rine-lab-dashboard .notice {
    margin: 1em 0;
    padding: 1em;
    border-left: 4px solid var(--me5rine-lab-border);
    background-color: var(--me5rine-lab-bg-secondary);
    border-radius: var(--me5rine-lab-radius-sm);
}

/* Liens dans les dashboards */
.me5rine-lab-dashboard a {
    color: var(--me5rine-lab-secondary);
    text-decoration: none;
    transition: color var(--me5rine-lab-transition);
}

.me5rine-lab-dashboard a:hover {
    text-decoration: underline;
    color: var(--me5rine-lab-button-primary-hover);
}

/* ============================================
   PROFILS (Ultimate Member)
   ============================================ */

/* Padding pour le contenu des onglets Ultimate Member */
.um-profile-body .um-tab-content,
.um-profile-body .um-tab-content > div {
    padding: var(--me5rine-lab-spacing-lg);
}

/* Container de profil (wrapper générique) */
.me5rine-lab-profile-container,
.me5rine-lab-profile-wrapper {
    background: transparent;
    border-radius: 0;
    padding: 0;
    box-shadow: none;
    margin-bottom: 0;
}

/* ============================================
   CARTES AVEC IMAGE (Cards avec thumbnail)
   ============================================ */

/* Carte avec image à gauche et contenu à droite */
.me5rine-lab-card-with-image {
    display: flex;
    gap: var(--me5rine-lab-spacing-md);
    margin-bottom: var(--me5rine-lab-spacing-md);
    transition: transform var(--me5rine-lab-transition);
}

.me5rine-lab-card-with-image:last-child {
    margin-bottom: 0;
}

.me5rine-lab-card-with-image:hover {
    transform: translateX(4px);
}

/* Image/thumbnail de la carte */
.me5rine-lab-card-image {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: var(--me5rine-lab-radius-md);
    flex-shrink: 0;
    border: 2px solid var(--me5rine-lab-border);
}

/* Contenu de la carte */
.me5rine-lab-card-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--me5rine-lab-spacing-sm);
}

/* Header de la carte */
.me5rine-lab-card-header {
    display: flex;
    flex-direction: column;
    gap: var(--me5rine-lab-spacing-xs);
}

/* Nom/titre dans la carte */
.me5rine-lab-card-name {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--me5rine-lab-text);
}

.me5rine-lab-card-name a {
    color: var(--me5rine-lab-text);
    text-decoration: none;
    transition: color var(--me5rine-lab-transition);
}

.me5rine-lab-card-name a:hover {
    color: var(--me5rine-lab-secondary);
}

/* Meta informations dans la carte */
.me5rine-lab-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--me5rine-lab-spacing-md);
    font-size: 13px;
    color: var(--me5rine-lab-text-light);
}

.me5rine-lab-card-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.me5rine-lab-card-meta .me5rine-lab-meta-label {
    font-weight: 500;
}

/* Description/contenu secondaire de la carte */
.me5rine-lab-card-description {
    margin: 0;
    font-size: 14px;
    color: var(--me5rine-lab-text-light);
    line-height: 1.6;
}

.me5rine-lab-card-description strong {
    color: var(--me5rine-lab-text);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 12px;
}

/* Bouton dans la carte */
.me5rine-lab-card-button {
    align-self: flex-start;
}

/* ============================================
   MESSAGES D'ÉTAT
   ============================================ */

/* Message d'état générique (vide, erreur, info) */
.me5rine-lab-state-message {
    color: var(--me5rine-lab-text-light);
    font-size: 14px;
    line-height: 1.6;
    margin: 0;
    padding: var(--me5rine-lab-spacing-md);
    background: var(--me5rine-lab-bg-secondary);
    border-radius: var(--me5rine-lab-radius-md);
    border-left: 4px solid var(--me5rine-lab-border);
}

/* ============================================
   LABELS DE FILTRES RESPONSIVE
   ============================================ */

/* Labels de filtres avec affichage conditionnel mobile/desktop */
.me5rine-lab-filter-label-mobile {
    display: none;
}

.me5rine-lab-filter-label-desktop {
    display: inline-flex;
}

@media screen and (max-width: 782px) {
    .me5rine-lab-filter-label-desktop {
        display: none;
    }
    .me5rine-lab-filter-label-mobile {
        display: inline-flex;
    }
}

/* ============================================
   RESPONSIVE GLOBAL
   ============================================ */

/* Responsive global */
@media (max-width: 782px) {
    .me5rine-lab-filters {
        flex-direction: column;
        gap: var(--me5rine-lab-spacing-md);
    }

    /* Dashboard header responsive */
    .me5rine-lab-dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--me5rine-lab-spacing-md);
    }

    /* Profile padding mobile */
    .um-profile-body .um-tab-content,
    .um-profile-body .um-tab-content > div {
        padding: var(--me5rine-lab-spacing-md);
    }

    /* Cartes avec image responsive */
    .me5rine-lab-card-with-image {
        flex-direction: column;
        gap: var(--me5rine-lab-spacing-md);
    }

    .me5rine-lab-card-image {
        width: 100%;
        height: auto;
        max-height: 200px;
    }

    .me5rine-lab-card-content {
        gap: var(--me5rine-lab-spacing-sm);
    }

    .me5rine-lab-card-meta {
        flex-direction: column;
        gap: var(--me5rine-lab-spacing-xs);
    }
}

@media (max-width: 480px) {
    /* Titres responsive */
    .me5rine-lab-title {
        font-size: 18px;
    }

    .me5rine-lab-card-name {
        font-size: 16px;
    }

    /* Boutons pleine largeur sur mobile */
    .me5rine-lab-card-button,
    .me5rine-lab-form-button {
        width: 100%;
        padding: 12px 20px;
    }
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

    /* Profile padding mobile */
    .um-profile-body .um-tab-content,
    .um-profile-body .um-tab-content > div {
        padding: var(--me5rine-lab-spacing-md);
    }

    /* Labels de filtres responsive */
    .me5rine-lab-filter-label-desktop {
        display: none;
    }
    .me5rine-lab-filter-label-mobile {
        display: inline-flex;
    }

    /* Cartes avec image responsive */
    .me5rine-lab-card-with-image {
        flex-direction: column;
        gap: var(--me5rine-lab-spacing-md);
    }

    .me5rine-lab-card-image {
        width: 100%;
        height: auto;
        max-height: 200px;
    }

    .me5rine-lab-card-content {
        gap: var(--me5rine-lab-spacing-sm);
    }

    .me5rine-lab-card-meta {
        flex-direction: column;
        gap: var(--me5rine-lab-spacing-xs);
    }
}

@media (max-width: 480px) {
    /* Titres responsive */
    .me5rine-lab-title {
        font-size: 18px;
    }

    .me5rine-lab-card-name {
        font-size: 16px;
    }

    /* Boutons pleine largeur sur mobile */
    .me5rine-lab-card-button,
    .me5rine-lab-form-button {
        width: 100%;
        padding: 12px 20px;
    }
}
```

## Utilisation

Tous les éléments front utilisent maintenant ces classes génériques. Pour modifier le style global, changez simplement les variables CSS en haut du fichier.

### Structure HTML Standardisée - Pages Front

**IMPORTANT** : Toutes les pages front (add giveaway, edit giveaway, my giveaways, socials, dashboard, etc.) doivent respecter cette structure :

1. **Div wrapper obligatoire** : Toutes les pages front doivent avoir une `<div class="me5rine-lab-dashboard">` qui englobe tout le contenu de la page
2. **Titre obligatoire** : Le titre principal de la page doit être un `<h2 class="me5rine-lab-title-large">` et être le premier élément après l'ouverture de la div wrapper

#### Structure Standardisée pour Toutes les Pages Front

```html
<div class="me5rine-lab-dashboard">
    <h2 class="me5rine-lab-title-large"><?php esc_html_e('Page Title', 'text-domain'); ?></h2>
    
    <!-- Notices (si nécessaire) -->
    <?php me5rine_display_profile_notice(); ?>
    
    <!-- Contenu de la page -->
    <!-- ... -->
</div>
```

#### Dashboard

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

#### 1. Profil Ultimate Member (UM)

**Uniquement pour les éléments dans les profils Ultimate Member.**

```html
<div class="me5rine-lab-profile-container">
    <h2 class="me5rine-lab-title"><?php _e('Titre de la section', 'text-domain'); ?></h2>
    <p class="me5rine-lab-subtitle"><?php _e('Sous-titre optionnel', 'text-domain'); ?></p>
    
    <!-- Contenu -->
</div>
```

**Exemple complet :**
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

#### 2. Dashboard Front-End

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

#### 3. Formulaire Front Complet

**Pour les formulaires complets (création/édition).**

```html
<div class="{nom-du-formulaire}-dashboard me5rine-lab-dashboard">
    <h2 class="me5rine-lab-title-large"><?php _e('Titre du formulaire', 'text-domain'); ?></h2>
    
    <form>
        <div class="me5rine-lab-form-block">
            <h3 class="me5rine-lab-title-medium"><?php _e('Section du formulaire', 'text-domain'); ?></h3>
            
            <!-- Champs -->
        </div>
        
        <div class="me5rine-lab-form-block">
            <h3 class="me5rine-lab-title-medium"><?php _e('Autre section', 'text-domain'); ?></h3>
            
            <!-- Autres champs -->
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
            <!-- Champs titre et dates -->
        </div>
        
        <div class="me5rine-lab-form-block">
            <h3 class="me5rine-lab-title-medium"><?php _e('Prizes', 'me5rine-lab'); ?></h3>
            <!-- Champs prix -->
        </div>
    </form>
</div>
```

**Important** : Le titre principal (`h2`) est à l'extérieur du formulaire, les titres de sections (`h3`) sont **à l'intérieur** des `me5rine-lab-form-block`.

#### Carte avec Image

```html
<div class="me5rine-lab-card-with-image me5rine-lab-card me5rine-lab-card-bordered-left">
    <img class="me5rine-lab-card-image" src="image.jpg" alt="Title">
    <div class="me5rine-lab-card-content">
        <div class="me5rine-lab-card-header">
            <h4 class="me5rine-lab-card-name">
                <a href="#">Card Title</a>
            </h4>
            <div class="me5rine-lab-card-meta">
                <span class="me5rine-lab-meta-label">Meta 1</span>
                <span class="me5rine-lab-meta-label">Meta 2</span>
            </div>
        </div>
        <p class="me5rine-lab-card-description">
            <strong>Label:</strong> Description text
        </p>
        <a href="#" class="me5rine-lab-form-button me5rine-lab-card-button">Action</a>
    </div>
</div>
```

#### Message d'État

```html
<p class="me5rine-lab-state-message">
    Message d'information, erreur ou état vide
</p>
```

#### Filtres avec Labels Responsive

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
            </select>
        </div>
    </form>
</div>
```

### Exemples d'utilisation

```html
<!-- Bouton -->
<button class="me5rine-lab-form-button">Action</button>

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

<!-- Podium (Top 3) -->
<div class="me5rine-lab-podium-wrapper">
    <div class="me5rine-lab-podium">
        <div class="me5rine-lab-podium-step me5rine-lab-podium-2 animate">
            <div class="me5rine-lab-podium-rank">2</div>
            <a href="#">Second Place</a>
            <div class="me5rine-lab-podium-info">100 participants</div>
        </div>
        <div class="me5rine-lab-podium-step me5rine-lab-podium-1 animate">
            <div class="me5rine-lab-podium-rank">1</div>
            <a href="#">First Place</a>
            <div class="me5rine-lab-podium-info">200 participants</div>
        </div>
        <div class="me5rine-lab-podium-step me5rine-lab-podium-3 animate">
            <div class="me5rine-lab-podium-rank">3</div>
            <a href="#">Third Place</a>
            <div class="me5rine-lab-podium-info">50 participants</div>
        </div>
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

## Composants Réutilisables

Ces composants sont spécifiques mais peuvent être réutilisés dans différents contextes (dashboards, profils, etc.).

### Podium (Top 3)

Composant visuel pour afficher un classement top 3 avec animation.

```css
/* ============================================
   PODIUM (Top 3)
   ============================================ */

/* Container du podium */
.me5rine-lab-podium-wrapper {
    display: flex;
    justify-content: center;
    margin: var(--me5rine-lab-spacing-lg) 0;
}

/* Alignement des podiums */
.me5rine-lab-podium {
    display: flex;
    align-items: flex-end;
    justify-content: center;
    gap: var(--me5rine-lab-spacing-md);
    width: 100%;
}

/* Étape du podium */
.me5rine-lab-podium-step {
    background: var(--me5rine-lab-bg-secondary, #f5f5f5);
    border-radius: var(--me5rine-lab-radius-md) var(--me5rine-lab-radius-md) 0 0;
    padding: var(--me5rine-lab-spacing-md);
    text-align: center;
    width: 140px;
    position: relative;
}

/* Rang du podium */
.me5rine-lab-podium-rank {
    font-size: 1.25rem;
    font-weight: bold;
    margin-bottom: var(--me5rine-lab-spacing-xs);
    color: var(--me5rine-lab-text);
}

/* Lien dans le podium */
.me5rine-lab-podium-step a {
    display: block;
    font-weight: 600;
    margin-bottom: var(--me5rine-lab-spacing-xs);
    text-decoration: none;
    color: var(--me5rine-lab-text);
    transition: color var(--me5rine-lab-transition);
}

.me5rine-lab-podium-step a:hover {
    color: var(--me5rine-lab-secondary);
}

/* Informations secondaires dans le podium */
.me5rine-lab-podium-info {
    font-size: 0.85rem;
    color: var(--me5rine-lab-text-light);
}

/* Hauteurs spécifiques pour les niveaux du podium */
.me5rine-lab-podium-1 {
    height: 160px;
    background-color: #ffd700; /* gold */
}

.me5rine-lab-podium-2 {
    height: 120px;
    background-color: #c0c0c0; /* silver */
}

.me5rine-lab-podium-3 {
    height: 100px;
    background-color: #cd7f32; /* bronze */
}

/* Animation de montée des podiums */
@keyframes me5rine-lab-podium-rise {
    0% {
        transform: translateY(50px) scale(0.9);
        opacity: 0;
    }
    100% {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

/* Classe pour activer l'animation */
.me5rine-lab-podium-step.animate {
    animation: me5rine-lab-podium-rise 0.6s ease-out forwards;
    opacity: 0;
}

/* Délais d'apparition pour l'effet "staggered" */
.me5rine-lab-podium-step.me5rine-lab-podium-2.animate {
    animation-delay: 0.2s;
}

.me5rine-lab-podium-step.me5rine-lab-podium-1.animate {
    animation-delay: 0.4s;
}

.me5rine-lab-podium-step.me5rine-lab-podium-3.animate {
    animation-delay: 0.6s;
}

/* Responsive pour les podiums */
@media (max-width: 768px) {
    .me5rine-lab-podium {
        flex-direction: column;
        align-items: center;
        gap: var(--me5rine-lab-spacing-sm);
    }

    .me5rine-lab-podium-step {
        width: 100%;
        max-width: 200px;
    }

    .me5rine-lab-podium-1,
    .me5rine-lab-podium-2,
    .me5rine-lab-podium-3 {
        height: auto;
        min-height: 100px;
    }
}
```

#### Structure HTML du Podium

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

## Options d'Écran - Système Générique Réutilisable

Le système d'options d'écran permet aux utilisateurs d'afficher/masquer des colonnes dans les tableaux front-end, similaire aux options d'écran WordPress admin. Ce système est **générique et réutilisable** pour tous les tableaux du plugin.

### Classes CSS Génériques

```css
/* Header avec actions (réutilisable pour tous les dashboards) */
.me5rine-lab-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.me5rine-lab-dashboard-header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

/* Panneau d'options d'écran (générique) */
.me5rine-lab-screen-options-panel {
    background: var(--me5rine-lab-bg, #ffffff);
    border: 1px solid var(--me5rine-lab-border, #DEE5EC);
    border-radius: var(--me5rine-lab-radius-sm, 6px);
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: var(--me5rine-lab-shadow-md, 0 2px 4px rgba(0, 0, 0, 0.1));
}

.me5rine-lab-screen-options-panel-content h4 {
    margin: 0 0 15px 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--me5rine-lab-text, #11161E);
}

.me5rine-lab-screen-options-columns {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
    margin-bottom: 15px;
}

.me5rine-lab-screen-options-column-item {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    padding: 5px;
    border-radius: 3px;
    transition: background-color 0.2s;
}

.me5rine-lab-screen-options-column-item:hover {
    background-color: var(--me5rine-lab-bg-secondary, #F9FAFB);
}

.me5rine-lab-screen-options-column-item input[type="checkbox"] {
    margin: 0;
    cursor: pointer;
}

.me5rine-lab-screen-options-column-item span {
    font-size: 13px;
    color: var(--me5rine-lab-text-light, #5D697D);
    user-select: none;
}

.me5rine-lab-screen-options-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 10px;
    border-top: 1px solid var(--me5rine-lab-border, #DEE5EC);
}

.me5rine-lab-screen-options-apply {
    min-width: 100px;
}

/* Masquer les colonnes (générique pour tous les tableaux) */
.me5rine-lab-table th.column-hidden,
.me5rine-lab-table td.column-hidden {
    display: none !important;
}

/* Responsive pour le header */
@media screen and (max-width: 768px) {
    .me5rine-lab-dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .me5rine-lab-dashboard-header-actions {
        width: 100%;
        flex-direction: column;
    }
    
    .me5rine-lab-dashboard-header-actions .me5rine-lab-form-button {
        width: 100%;
    }
    
    .me5rine-lab-screen-options-columns {
        grid-template-columns: 1fr;
    }
}
```

### Structure HTML Standardisée

```html
<div class="me5rine-lab-dashboard">
    <div class="me5rine-lab-dashboard-header">
        <h3 class="me5rine-lab-title">Titre du Dashboard</h3>
        <div class="me5rine-lab-dashboard-header-actions">
            <button type="button" class="me5rine-lab-form-button me5rine-lab-form-button-secondary me5rine-lab-screen-options-toggle" aria-expanded="false">
                Options d'écran
            </button>
            <a class="me5rine-lab-form-button" href="#">Action</a>
        </div>
    </div>
    
    <div class="me5rine-lab-screen-options-panel" style="display: none;">
        <div class="me5rine-lab-screen-options-panel-content">
            <h4>Afficher à l'écran</h4>
            <div class="me5rine-lab-screen-options-columns">
                <label class="me5rine-lab-screen-options-column-item">
                    <input type="checkbox" 
                           name="visible_columns[name]" 
                           value="1" 
                           data-column="name"
                           checked>
                    <span>Nom</span>
                </label>
                <label class="me5rine-lab-screen-options-column-item">
                    <input type="checkbox" 
                           name="visible_columns[date]" 
                           value="1" 
                           data-column="date"
                           checked>
                    <span>Date</span>
                </label>
                <!-- Autres colonnes... -->
            </div>
            <div class="me5rine-lab-screen-options-actions">
                <button type="button" class="me5rine-lab-form-button me5rine-lab-screen-options-apply">
                    Appliquer
                </button>
            </div>
        </div>
    </div>
    
    <!-- Tableau avec colonnes masquables -->
    <table class="me5rine-lab-table">
        <thead>
            <tr>
                <th class="column-name" data-column="name">Nom</th>
                <th class="column-date column-hidden" data-column="date">Date</th>
                <!-- Autres colonnes... -->
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="column-name" data-column="name">Exemple</td>
                <td class="column-date column-hidden" data-column="date">01/01/2024</td>
                <!-- Autres cellules... -->
            </tr>
        </tbody>
    </table>
</div>
```

### JavaScript Requis

Le JavaScript pour gérer l'affichage/masquage des colonnes doit être inclus. Exemple :

```javascript
document.addEventListener('DOMContentLoaded', function () {
    const screenOptionsToggle = document.querySelector('.me5rine-lab-screen-options-toggle');
    const screenOptionsPanel = document.querySelector('.me5rine-lab-screen-options-panel');
    const screenOptionsApply = document.querySelector('.me5rine-lab-screen-options-apply');
    const columnCheckboxes = document.querySelectorAll('.me5rine-lab-screen-options-column-item input[type="checkbox"]');
    
    if (screenOptionsToggle && screenOptionsPanel) {
        // Toggle du panneau
        screenOptionsToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const isExpanded = screenOptionsPanel.style.display !== 'none';
            screenOptionsPanel.style.display = isExpanded ? 'none' : 'block';
            screenOptionsToggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
        });
        
        // Application des changements
        if (screenOptionsApply) {
            screenOptionsApply.addEventListener('click', function(e) {
                e.preventDefault();
                
                const visibleColumns = {};
                columnCheckboxes.forEach(checkbox => {
                    const columnName = checkbox.getAttribute('data-column');
                    visibleColumns[columnName] = checkbox.checked;
                });
                
                // Sauvegarder via AJAX et masquer/afficher les colonnes
                // ... (code de sauvegarde AJAX)
                
                Object.keys(visibleColumns).forEach(columnName => {
                    const isVisible = visibleColumns[columnName];
                    const columnElements = document.querySelectorAll('[data-column="' + columnName + '"]');
                    columnElements.forEach(el => {
                        if (isVisible) {
                            el.classList.remove('column-hidden');
                        } else {
                            el.classList.add('column-hidden');
                        }
                    });
                });
                
                screenOptionsPanel.style.display = 'none';
                screenOptionsToggle.setAttribute('aria-expanded', 'false');
            });
        }
    }
});
```

### Règles d'Utilisation

1. **Classes génériques uniquement** : Toutes les classes utilisent le préfixe `me5rine-lab-` et sont génériques
2. **Attribut `data-column`** : Chaque colonne (th et td) doit avoir un attribut `data-column` avec l'identifiant unique de la colonne
3. **Classe `column-hidden`** : Les colonnes masquées doivent avoir la classe `column-hidden` sur les éléments `th` et `td`
4. **Sauvegarde des préférences** : Les préférences doivent être sauvegardées par utilisateur (user meta) via AJAX
5. **Valeurs par défaut** : Définir quelles colonnes sont masquées par défaut dans le code PHP

### Exemple d'Implémentation PHP

```php
// Définir les colonnes par défaut
$default_columns = [
    'name' => true,
    'date' => false, // Masqué par défaut
    'status' => true,
];

// Récupérer les préférences utilisateur
$saved_columns = get_user_meta($user_id, 'admin_lab_visible_columns', true);
if (!is_array($saved_columns)) {
    $saved_columns = $default_columns;
}

// Dans le HTML, appliquer les classes
$is_visible = isset($saved_columns['date']) ? $saved_columns['date'] : false;
$hidden_class = $is_visible ? '' : 'column-hidden';
?>
<th class="column-date <?php echo esc_attr($hidden_class); ?>" data-column="date">Date</th>
<td class="column-date <?php echo esc_attr($hidden_class); ?>" data-column="date"><?php echo $date; ?></td>
```

