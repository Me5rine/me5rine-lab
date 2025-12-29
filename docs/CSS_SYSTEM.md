# Système de Classes CSS Génériques

Ce document décrit le système de classes CSS génériques utilisé pour les formulaires et vues. Ces classes doivent être définies dans **le thème** et peuvent être réutilisées dans d'autres plugins.

## Préfixe des Classes

**Préfixe générique** : `me5rine-lab-form-` (peut être personnalisé dans le thème)

Les classes suivent le pattern : `me5rine-lab-form-{element}` où `{element}` décrit l'élément de formulaire.

## Structure des Classes

### Container Principal
- `.me5rine-lab-form-container` - Container principal du formulaire/vue
- `.me5rine-lab-form-section` - Section d'un formulaire (groupe de champs)

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

## Règles CSS à Implémenter dans le Thème

Voir le fichier [CSS_RULES.md](./CSS_RULES.md) pour les règles CSS complètes à copier dans votre thème.

## Utilisation

Les classes sont appliquées dans le HTML généré par les fonctions du plugin. Le CSS doit être défini dans le thème, pas dans le plugin.

### Exemple d'utilisation

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

