# Guide d'Intégration pour Autres Plugins

Ce document explique comment utiliser les classes CSS génériques `me5rine-lab-form-*` dans un autre plugin pour avoir un design unifié.

## 📋 Prérequis

**Important** : Le CSS doit être défini dans le **thème**, pas dans le plugin. Le thème doit avoir copié le contenu de [CSS_RULES.md](./CSS_RULES.md) dans son fichier CSS.

## 🎨 Préfixe des Classes

**Préfixe** : `me5rine-lab-form-`

## 📐 Structure HTML à Utiliser

### Structure de Base

```html
<div class="me5rine-lab-form-container">
    <h2 class="me5rine-lab-form-title">Titre de la Section</h2>
    <p class="me5rine-lab-form-subtitle">Description optionnelle</p>
    <form class="me5rine-lab-form-section">
        <!-- Vos champs ici -->
    </form>
</div>
```

### Ligne avec 2 Colonnes

```html
<div class="me5rine-lab-form-row">
    <div class="me5rine-lab-form-col">
        <!-- Champ 1 -->
    </div>
    <div class="me5rine-lab-form-col">
        <!-- Champ 2 -->
    </div>
</div>
```

### Champ de Formulaire Complet

```html
<div class="me5rine-lab-form-field">
    <label class="me5rine-lab-form-label" for="mon_champ">Mon Label</label>
    <input type="text" id="mon_champ" class="me5rine-lab-form-input" />
    <div class="me5rine-lab-form-description">Texte d'aide optionnel</div>
</div>
```

## 🧩 Composants Disponibles

### Inputs Standards

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

### Boutons

```html
<!-- Bouton principal -->
<button type="submit" class="me5rine-lab-form-button">Enregistrer</button>

<!-- Bouton secondaire -->
<button type="button" class="me5rine-lab-form-button me5rine-lab-form-button-secondary">Annuler</button>
```

### Checkboxes

```html
<div class="me5rine-lab-form-checkbox-group">
    <label class="me5rine-lab-form-checkbox-item">
        <input type="checkbox" class="me5rine-lab-form-checkbox" name="options[]" value="option1" />
        <span class="me5rine-lab-form-checkbox-icon">
            <i class="um-icon-android-checkbox-outline-blank"></i>
        </span>
        <span class="me5rine-lab-form-checkbox-text">Option 1</span>
    </label>
    <label class="me5rine-lab-form-checkbox-item">
        <input type="checkbox" class="me5rine-lab-form-checkbox" name="options[]" value="option2" />
        <span class="me5rine-lab-form-checkbox-icon">
            <i class="um-icon-android-checkbox-outline-blank"></i>
        </span>
        <span class="me5rine-lab-form-checkbox-text">Option 2</span>
    </label>
</div>
```

**Note** : Pour les icônes de checkbox, utilisez Ultimate Member :
- Non coché : `um-icon-android-checkbox-outline-blank`
- Coché : `um-icon-android-checkbox`

### Messages et Textes

```html
<!-- Paragraphe de texte simple (état, information) -->
<p class="me5rine-lab-form-text">Aucun élément disponible.</p>

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
```

### Vue en Lecture Seule (Read-only)

```html
<!-- Vue avec 2 colonnes -->
<div class="me5rine-lab-form-view">
    <div class="me5rine-lab-form-view-row">
        <div class="me5rine-lab-form-view-item me5rine-lab-form-col">
            <span class="me5rine-lab-form-view-label">Nom</span>
            <span class="me5rine-lab-form-view-value">Jean Dupont</span>
        </div>
        <div class="me5rine-lab-form-view-item me5rine-lab-form-col">
            <span class="me5rine-lab-form-view-label">Email</span>
            <span class="me5rine-lab-form-view-value">jean@example.com</span>
        </div>
    </div>
</div>

<!-- Vue pleine largeur -->
<div class="me5rine-lab-form-view">
    <div class="me5rine-lab-form-view-row-full">
        <div class="me5rine-lab-form-view-item me5rine-lab-form-col-full">
            <span class="me5rine-lab-form-view-label">Description</span>
            <span class="me5rine-lab-form-view-value">Texte complet sur toute la largeur</span>
        </div>
    </div>
</div>
```

## 📝 Exemple Complet

```html
<div class="me5rine-lab-form-container">
    <h2 class="me5rine-lab-form-title">Mon Formulaire</h2>
    <p class="me5rine-lab-form-subtitle">Description du formulaire</p>
    
    <form method="post" class="me5rine-lab-form-section">
        
        <!-- Message de succès (optionnel) -->
        <div class="me5rine-lab-form-message me5rine-lab-form-message-success" style="display:none;">
            <p>Données sauvegardées !</p>
        </div>
        
        <!-- Ligne 1 : 2 colonnes -->
        <div class="me5rine-lab-form-row">
            <div class="me5rine-lab-form-col">
                <div class="me5rine-lab-form-field">
                    <label class="me5rine-lab-form-label" for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" class="me5rine-lab-form-input" />
                </div>
            </div>
            <div class="me5rine-lab-form-col">
                <div class="me5rine-lab-form-field">
                    <label class="me5rine-lab-form-label" for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" class="me5rine-lab-form-input" />
                </div>
            </div>
        </div>
        
        <!-- Ligne 2 : Pleine largeur -->
        <div class="me5rine-lab-form-row-full">
            <div class="me5rine-lab-form-field">
                <label class="me5rine-lab-form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="me5rine-lab-form-input" />
                <div class="me5rine-lab-form-description">Votre adresse email sera utilisée pour vous contacter.</div>
            </div>
        </div>
        
        <!-- Checkboxes -->
        <div class="me5rine-lab-form-row-full">
            <div class="me5rine-lab-form-field">
                <label class="me5rine-lab-form-label">Options</label>
                <div class="me5rine-lab-form-checkbox-group">
                    <label class="me5rine-lab-form-checkbox-item">
                        <input type="checkbox" class="me5rine-lab-form-checkbox" name="options[]" value="opt1" />
                        <span class="me5rine-lab-form-checkbox-icon">
                            <i class="um-icon-android-checkbox-outline-blank"></i>
                        </span>
                        <span class="me5rine-lab-form-checkbox-text">Option 1</span>
                    </label>
                    <label class="me5rine-lab-form-checkbox-item">
                        <input type="checkbox" class="me5rine-lab-form-checkbox" name="options[]" value="opt2" />
                        <span class="me5rine-lab-form-checkbox-icon">
                            <i class="um-icon-android-checkbox-outline-blank"></i>
                        </span>
                        <span class="me5rine-lab-form-checkbox-text">Option 2</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Bouton -->
        <div class="me5rine-lab-form-field">
            <button type="submit" class="me5rine-lab-form-button">Enregistrer</button>
        </div>
        
    </form>
</div>
```

## 🎯 Liste des Classes Principales

### Titres & Textes
- `.me5rine-lab-form-title` - Titre principal de section (h2, h3)
- `.me5rine-lab-form-subtitle` - Description/sous-titre de section (p)

### Container & Layout
- `.me5rine-lab-form-container` - Container principal
- `.me5rine-lab-form-section` - Section de formulaire
- `.me5rine-lab-form-row` - Ligne avec 2 colonnes (grid)
- `.me5rine-lab-form-row-full` - Ligne pleine largeur
- `.me5rine-lab-form-col` - Colonne (dans une row)
- `.me5rine-lab-form-col-full` - Colonne pleine largeur (dans une row)

### Champs
- `.me5rine-lab-form-field` - Container d'un champ (label + input + description)
- `.me5rine-lab-form-label` - Label d'un champ
- `.me5rine-lab-form-input` - Input text, number, email, tel, url
- `.me5rine-lab-form-select` - Select/dropdown
- `.me5rine-lab-form-textarea` - Textarea
- `.me5rine-lab-form-description` - Texte d'aide sous un champ
- `.me5rine-lab-form-error` - Message d'erreur pour un champ

### Boutons
- `.me5rine-lab-form-button` - Bouton principal
- `.me5rine-lab-form-button-secondary` - Bouton secondaire

### Checkboxes
- `.me5rine-lab-form-checkbox-group` - Container d'une liste de checkboxes
- `.me5rine-lab-form-checkbox-item` - Item checkbox individuel
- `.me5rine-lab-form-checkbox` - Input checkbox (visuellement caché)
- `.me5rine-lab-form-checkbox-icon` - Container de l'icône
- `.me5rine-lab-form-checkbox-text` - Texte du label

### Vue (Read-only)
- `.me5rine-lab-form-view` - Container pour la vue
- `.me5rine-lab-form-view-row` - Ligne dans la vue (2 colonnes)
- `.me5rine-lab-form-view-row-full` - Ligne pleine largeur dans la vue
- `.me5rine-lab-form-view-item` - Item individuel dans la vue
- `.me5rine-lab-form-view-label` - Label dans la vue
- `.me5rine-lab-form-view-value` - Valeur dans la vue

### Messages et Textes
- `.me5rine-lab-form-text` - Paragraphe de texte simple (état, information)
- `.me5rine-lab-form-message` - Message générique
- `.me5rine-lab-form-message-success` - Message de succès
- `.me5rine-lab-form-message-error` - Message d'erreur
- `.me5rine-lab-form-message-warning` - Message d'avertissement

## ⚠️ Notes Importantes

1. **CSS dans le thème** : Le CSS doit être dans le thème, pas dans le plugin
2. **Icônes checkbox** : Utilisez les classes Ultimate Member pour les icônes (`um-icon-android-checkbox`, `um-icon-android-checkbox-outline-blank`)
3. **JavaScript** : Pour les checkboxes, vous pouvez utiliser le JS du plugin `poke-hub-user-profiles-um.js` qui gère automatiquement la classe `checked`
4. **Responsive** : Le CSS est responsive par défaut (mobile < 768px)

## 🔗 Référence

- Voir [CSS_SYSTEM.md](./CSS_SYSTEM.md) pour la documentation complète des classes
- Voir [CSS_RULES.md](./CSS_RULES.md) pour les règles CSS complètes (à donner au thème)
- Voir [README.md](./README.md) pour la structure complète de la documentation

