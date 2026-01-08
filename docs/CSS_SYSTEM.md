# Système de Classes CSS Génériques

Ce document décrit le système de classes CSS génériques utilisé pour les formulaires et vues. Ces classes doivent être définies dans **le thème** et peuvent être réutilisées dans d'autres plugins.

## Préfixe des Classes

**Préfixe générique** : `me5rine-lab-form-` (peut être personnalisé dans le thème)

Les classes suivent le pattern : `me5rine-lab-form-{element}` où `{element}` décrit l'élément de formulaire.

## Structure des Classes

### Container Principal
- `.me5rine-lab-form-block` - Bloc conteneur dans un formulaire (sous-section avec titre)
- `.me5rine-lab-form-container` - Container principal du formulaire/vue
- `.me5rine-lab-form-section` - Section d'un formulaire (groupe de champs)
- `.me5rine-lab-profile-container` - **PROFILS UM UNIQUEMENT** : Container principal pour les éléments dans le profil Ultimate Member (giveaways)
- `.me5rine-lab-dashboard` - **DASHBOARDS ET FORMULAIRES FRONT** : Container principal pour les dashboards front et formulaires complets

### Layout (Grid/Flex)
- `.me5rine-lab-form-row` - Ligne avec 2 colonnes (CSS Grid)
- `.me5rine-lab-form-row-full` - Ligne pleine largeur
- `.me5rine-lab-form-col` - Colonne dans une ligne (50% par défaut)
- `.me5rine-lab-form-col-full` - Colonne pleine largeur

### Champs de Formulaire
- `.me5rine-lab-form-field` - Container d'un champ (label + input + description)
- `.me5rine-lab-form-label` - Label d'un champ
- `.me5rine-lab-form-input` - Input text, number, email, tel, url
- `.me5rine-lab-form-select` - Select/dropdown
- `.me5rine-lab-form-textarea` - Textarea
- `.me5rine-lab-form-description` - Texte de description/help sous un champ
- `.me5rine-lab-form-error` - Message d'erreur pour un champ

### Boutons
- `.me5rine-lab-form-button` - Bouton principal (submit)
- `.me5rine-lab-form-button-secondary` - Bouton secondaire
- `.me5rine-lab-form-button-remove` - Bouton de suppression (remove/delete)
- `.me5rine-lab-form-button-file` - Input de type file (upload)

### Checkboxes & Radio
- `.me5rine-lab-form-checkbox-group` - Container d'une liste de checkboxes
- `.me5rine-lab-form-checkbox-item` - Item checkbox individuel
- `.me5rine-lab-form-checkbox` - Input checkbox (visuellement caché)
- `.me5rine-lab-form-checkbox-label` - Label cliquable de la checkbox
- `.me5rine-lab-form-checkbox-icon` - Icône de la checkbox (checked/unchecked)
- `.me5rine-lab-form-checkbox-text` - Texte du label de la checkbox

### Vue (Read-only)
- `.me5rine-lab-form-view` - Container pour la vue en lecture seule
- `.me5rine-lab-form-view-row` - Ligne dans la vue (2 colonnes)
- `.me5rine-lab-form-view-item` - Item individuel dans la vue
- `.me5rine-lab-form-view-label` - Label dans la vue (strong)
- `.me5rine-lab-form-view-value` - Valeur dans la vue

### Messages et Textes
- `.me5rine-lab-form-text` - Paragraphe de texte simple (état, information)
- `.me5rine-lab-form-message` - Message générique
- `.me5rine-lab-form-message-success` - Message de succès
- `.me5rine-lab-form-message-error` - Message d'erreur
- `.me5rine-lab-form-message-warning` - Message d'avertissement

### Tableaux
- `.me5rine-lab-table` - Tableau générique (peut être utilisé dans n'importe quel contexte)
- `.me5rine-lab-table-title` - Titre dans une cellule de tableau
- `.me5rine-lab-table-summary-row` - Ligne de résumé dans une cellule de tableau
- `.me5rine-lab-table-row-toggleable` - Ligne de tableau qui peut être expandée/réduite
- `.me5rine-lab-table-toggle-btn` - Bouton pour expander/réduire une ligne
- `.me5rine-lab-sr-only` - Texte accessible uniquement aux lecteurs d'écran (visuellement caché)

## Règles CSS à Implémenter dans le Thème

- **Formulaires** : Voir le fichier [CSS_RULES.md](./CSS_RULES.md) pour les règles CSS complètes à copier dans votre thème.
- **Tableaux** : Voir le fichier [TABLE_CSS.md](./TABLE_CSS.md) pour les règles CSS complètes des tableaux à copier dans votre thème.
- **Front-End Unifié** : Voir le fichier [FRONT_CSS.md](./FRONT_CSS.md) pour TOUS les styles front-end unifiés (boutons, cartes, pagination, filtres, titres, etc.). **Ce fichier unifie tous les modules front** - une seule modification de variable change le style partout.

## Structure OBLIGATOIRE des Conteneurs

Il existe **trois structures distinctes** selon le contexte :

### 1. Profils Ultimate Member (UM)

**Utilisation** : Uniquement pour les éléments affichés dans les profils Ultimate Member (onglets de profil).

**Structure :**
```html
<div class="me5rine-lab-profile-container">
    <h2 class="me5rine-lab-title"><?php _e('Titre de la section', 'text-domain'); ?></h2>
    <p class="me5rine-lab-subtitle"><?php _e('Sous-titre optionnel', 'text-domain'); ?></p>
    
    <!-- Contenu (filtres, tableaux, etc.) -->
</div>
```

**Règles obligatoires :**
1. Container : `<div class="me5rine-lab-profile-container">`
2. Titre : `<h2 class="me5rine-lab-title">` en premier élément
3. Sous-titre : Optionnel avec `<p class="me5rine-lab-subtitle">`

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

### 2. Dashboards Front-End

**Utilisation** : Pour les pages de dashboard front (liste de giveaways, socials dashboard, etc.).

**Structure :**
```html
<div class="{nom-du-dashboard} me5rine-lab-dashboard">
    <h2 class="me5rine-lab-title-large"><?php _e('Titre du dashboard', 'text-domain'); ?></h2>
    
    <!-- Contenu du dashboard -->
</div>
```

**Règles obligatoires :**
1. Container : `<div class="{nom-du-dashboard} me5rine-lab-dashboard">` avec une classe spécifique (ex: `socials-dashboard`, `my-giveaways-dashboard`)
2. Titre : `<h2 class="me5rine-lab-title-large">` en premier élément

**Exemple :**
```html
<div class="socials-dashboard me5rine-lab-dashboard">
    <h2 class="me5rine-lab-title-large"><?php esc_html_e('My Socialls', 'me5rine-lab'); ?></h2>
    
    <!-- Contenu -->
</div>
```

### 3. Formulaires Front Complets

**Utilisation** : Pour les formulaires complets en front (création/édition de campagne, etc.).

**Structure :**
```html
<div class="{nom-du-formulaire}-dashboard me5rine-lab-dashboard">
    <h2 class="me5rine-lab-title-large"><?php _e('Titre du formulaire', 'text-domain'); ?></h2>
    
    <form>
        <div class="me5rine-lab-form-block">
            <h3 class="me5rine-lab-title-medium"><?php _e('Section du formulaire', 'text-domain'); ?></h3>
            
            <!-- Champs du formulaire -->
        </div>
        
        <div class="me5rine-lab-form-block">
            <h3 class="me5rine-lab-title-medium"><?php _e('Autre section', 'text-domain'); ?></h3>
            
            <!-- Autres champs -->
        </div>
    </form>
</div>
```

**Règles obligatoires :**
1. Container : `<div class="{nom-du-formulaire}-dashboard me5rine-lab-dashboard">` avec une classe spécifique (ex: `campaign-form-dashboard`)
2. Titre principal : `<h2 class="me5rine-lab-title-large">` à l'extérieur du formulaire
3. Sections : Chaque section utilise `<div class="me5rine-lab-form-block">` avec un titre `<h3 class="me5rine-lab-title-medium">` **à l'intérieur** du bloc

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

## Utilisation

Les classes sont appliquées dans le HTML généré par les fonctions du plugin. Le CSS doit être défini dans le thème, pas dans le plugin.

### Exemple d'utilisation (Formulaire standard)

```html
<div class="me5rine-lab-form-container">
    <form class="me5rine-lab-form-section">
        <div class="me5rine-lab-form-row">
            <div class="me5rine-lab-form-col">
                <div class="me5rine-lab-form-field">
                    <label class="me5rine-lab-form-label" for="username">Username</label>
                    <input type="text" id="username" class="me5rine-lab-form-input" />
                </div>
            </div>
            <div class="me5rine-lab-form-col">
                <div class="me5rine-lab-form-field">
                    <label class="me5rine-lab-form-label" for="country">Country</label>
                    <select id="country" class="me5rine-lab-form-select">
                        <option>FR</option>
                    </select>
                </div>
            </div>
        </div>
        <button type="submit" class="me5rine-lab-form-button">Save</button>
    </form>
</div>
```

