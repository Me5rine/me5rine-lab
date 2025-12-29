# Règles CSS Génériques pour les Formulaires

Ces règles CSS doivent être copiées dans votre fichier CSS de thème (ex: `assets/css/forms.css` ou `style.css`).

**Préfixe des classes** : `me5rine-lab-form-` (vous pouvez le modifier dans votre thème si besoin)

## Variables CSS (Ultimate Member)

Utilisez les variables Ultimate Member pour un design cohérent :

```css
:root {
    --ph-primary: var(--um-primary, #2E576F);
    --ph-secondary: var(--um-secondary, #0485C8);
    --ph-text: var(--um-text, #11161E);
    --ph-text-light: var(--um-text-light, #5D697D);
    --ph-bg: var(--um-bg, #FFFFFF);
    --ph-bg-secondary: var(--um-bg-secondary, #F9FAFB);
    --ph-border: var(--um-border, #DEE5EC);
    --ph-border-light: var(--um-border-light, #B5C2CF);
}
```

## Container Principal

```css
/* Container principal */
.me5rine-lab-form-container {
    padding: 0;
    max-width: 100%;
}

/* Section de formulaire */
.me5rine-lab-form-section {
    background: var(--ph-bg, #ffffff);
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
```

## Layout (Grid)

```css
/* Ligne avec 2 colonnes */
.me5rine-lab-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 24px;
}

.me5rine-lab-form-row:last-of-type {
    margin-bottom: 0;
}

/* Ligne pleine largeur */
.me5rine-lab-form-row-full {
    display: block;
    margin-bottom: 24px;
}

.me5rine-lab-form-row-full:last-child {
    margin-bottom: 0;
}

/* Colonne (dans une row) */
.me5rine-lab-form-col {
    /* Colonnes sont gérées par grid, pas besoin de width */
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

/* Colonne pleine largeur */
.me5rine-lab-form-col-full {
    grid-column: 1 / -1;
}

/* Responsive : mobile */
@media (max-width: 768px) {
    .me5rine-lab-form-row {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .me5rine-lab-form-col {
        margin-bottom: 0;
    }
}
```

## Champs de Formulaire

```css
/* Container d'un champ */
.me5rine-lab-form-field {
    margin-bottom: 24px;
}

.me5rine-lab-form-field:last-child {
    margin-bottom: 0;
}

/* Label */
.me5rine-lab-form-label {
    display: block;
    font-weight: 600;
    font-size: 14px;
    color: var(--ph-text, #11161E);
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Inputs (text, number, email, tel, url) */
.me5rine-lab-form-input,
.me5rine-lab-form-select,
.me5rine-lab-form-textarea {
    width: 100% !important;
    min-height: 44px !important;
    padding: 9px 16px !important;
    margin: 0 !important;
    border: 2px solid var(--ph-border, #DEE5EC) !important;
    border-radius: 8px !important;
    font-size: 15px !important;
    font-family: inherit !important;
    line-height: 1.5 !important;
    transition: all 0.2s ease !important;
    background-color: var(--ph-bg, #ffffff) !important;
    background-image: none !important;
    box-sizing: border-box !important;
    color: var(--ph-text-light, #5D697D) !important;
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    vertical-align: middle !important;
    height: auto !important;
    box-shadow: none !important;
    outline: 0 !important;
}

/* Select spécifique : ajouter la flèche */
.me5rine-lab-form-select {
    padding-right: 40px !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235D697D' d='M6 9L1 4h10z'/%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 12px center !important;
    background-size: 12px !important;
    cursor: pointer !important;
}

/* Textarea */
.me5rine-lab-form-textarea {
    min-height: 100px;
    resize: vertical;
}

/* États : Focus */
.me5rine-lab-form-input:focus,
.me5rine-lab-form-select:focus,
.me5rine-lab-form-textarea:focus {
    outline: none !important;
    border-color: var(--ph-secondary, #0485C8) !important;
    box-shadow: 0 0 0 3px rgba(4, 133, 200, 0.1) !important;
}

.me5rine-lab-form-select:focus {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%230485C8' d='M6 9L1 4h10z'/%3E%3C/svg%3E") !important;
}

/* États : Hover */
.me5rine-lab-form-input:hover:not(:focus),
.me5rine-lab-form-select:hover:not(:focus),
.me5rine-lab-form-textarea:hover:not(:focus) {
    border-color: var(--ph-border-light, #B5C2CF) !important;
}

/* Description/Help text */
.me5rine-lab-form-description {
    margin-top: 8px;
    font-size: 13px;
    color: var(--ph-text-light, #5D697D);
    line-height: 1.5;
}

/* Erreur */
.me5rine-lab-form-error {
    margin-top: 8px;
    font-size: 13px;
    color: #d63638;
    line-height: 1.5;
}

/* Remove number input spinners */
.me5rine-lab-form-input[type="number"]::-webkit-inner-spin-button,
.me5rine-lab-form-input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.me5rine-lab-form-input[type="number"] {
    -moz-appearance: textfield;
}
```

## Boutons

```css
/* Bouton principal */
.me5rine-lab-form-button {
    background: linear-gradient(135deg, var(--ph-secondary, #0485C8) 0%, var(--ph-primary, #2E576F) 100%);
    color: var(--ph-bg, #ffffff);
    border: none;
    padding: 14px 32px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(4, 133, 200, 0.2);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.me5rine-lab-form-button:hover {
    background: linear-gradient(135deg, var(--ph-primary, #2E576F) 0%, var(--ph-secondary, #0485C8) 100%);
    box-shadow: 0 4px 8px rgba(4, 133, 200, 0.3);
    transform: translateY(-1px);
}

.me5rine-lab-form-button:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(4, 133, 200, 0.2);
}

/* Bouton secondaire */
.me5rine-lab-form-button-secondary {
    background: var(--ph-bg-secondary, #F9FAFB);
    color: var(--ph-text, #11161E);
    border: 2px solid var(--ph-border, #DEE5EC);
}

.me5rine-lab-form-button-secondary:hover {
    background: var(--ph-border, #DEE5EC);
    border-color: var(--ph-border-light, #B5C2CF);
}
```

## Checkboxes

```css
/* Container de liste de checkboxes */
.me5rine-lab-form-checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 12px;
    margin-top: 8px;
}

/* Item checkbox individuel */
.me5rine-lab-form-checkbox-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border: 2px solid var(--ph-border, #DEE5EC);
    border-radius: 8px;
    background: var(--ph-bg-secondary, #F9FAFB);
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.me5rine-lab-form-checkbox-item:hover {
    background: var(--ph-border, #DEE5EC);
    border-color: var(--ph-border-light, #B5C2CF);
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

/* Input checkbox (visuellement caché) */
.me5rine-lab-form-checkbox-item .me5rine-lab-form-checkbox {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    margin: 0;
    cursor: pointer;
}

/* Icon container */
.me5rine-lab-form-checkbox-icon {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.me5rine-lab-form-checkbox-icon i {
    font-size: 22px;
    color: var(--ph-text-light, #5D697D);
    transition: all 0.2s ease;
}

/* Texte du label */
.me5rine-lab-form-checkbox-text {
    font-size: 14px;
    color: var(--ph-text-light, #5D697D);
    flex: 1;
    user-select: none;
}

/* État checked */
.me5rine-lab-form-checkbox-item.checked,
.me5rine-lab-form-checkbox-item:has(.me5rine-lab-form-checkbox:checked) {
    background: rgba(4, 133, 200, 0.1);
    border-color: var(--ph-secondary, #0485C8);
    box-shadow: 0 0 0 3px rgba(4, 133, 200, 0.1);
}

.me5rine-lab-form-checkbox-item.checked:hover,
.me5rine-lab-form-checkbox-item:has(.me5rine-lab-form-checkbox:checked):hover {
    background: rgba(4, 133, 200, 0.15);
    border-color: var(--ph-primary, #2E576F);
}

.me5rine-lab-form-checkbox-item.checked .me5rine-lab-form-checkbox-icon i,
.me5rine-lab-form-checkbox-item:has(.me5rine-lab-form-checkbox:checked) .me5rine-lab-form-checkbox-icon i {
    color: var(--ph-secondary, #0485C8);
}

.me5rine-lab-form-checkbox-item.checked .me5rine-lab-form-checkbox-text,
.me5rine-lab-form-checkbox-item:has(.me5rine-lab-form-checkbox:checked) .me5rine-lab-form-checkbox-text {
    color: var(--ph-text, #11161E);
    font-weight: 500;
}

/* Responsive checkboxes */
@media (max-width: 768px) {
    .me5rine-lab-form-checkbox-group {
        grid-template-columns: 1fr;
    }
}
```

## Vue (Read-only)

```css
/* Container pour la vue */
.me5rine-lab-form-view {
    background: var(--ph-bg, #ffffff);
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Ligne dans la vue */
.me5rine-lab-form-view-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 16px;
}

.me5rine-lab-form-view-row:last-of-type {
    margin-bottom: 0;
}

/* Item individuel (tuiles) */
.me5rine-lab-form-view-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px;
    background: var(--ph-bg-secondary, #F9FAFB);
    border-radius: 8px;
    border-left: 4px solid var(--ph-secondary, #0485C8);
    transition: all 0.2s ease;
}

.me5rine-lab-form-view-item:hover {
    background: var(--ph-border, #DEE5EC);
    transform: translateX(4px);
}

.me5rine-lab-form-view-item:last-child {
    margin-bottom: 0;
}

/* Half width item (dans une row) */
.me5rine-lab-form-view-item.me5rine-lab-form-col {
    margin-bottom: 0;
}

/* Label dans la vue */
.me5rine-lab-form-view-label {
    min-width: 140px;
    font-weight: 600;
    font-size: 14px;
    color: var(--ph-text, #11161E);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    flex-shrink: 0;
}

/* Valeur dans la vue */
.me5rine-lab-form-view-value {
    flex: 1;
    font-size: 15px;
    color: var(--ph-text-light, #5D697D);
    line-height: 1.6;
    word-break: break-word;
}

/* Row pleine largeur dans la vue (pour éléments qui prennent toute la largeur) */
.me5rine-lab-form-view-row-full {
    display: block;
    margin-bottom: 16px;
}

.me5rine-lab-form-view-row-full:last-child {
    margin-bottom: 0;
}

.me5rine-lab-form-view-row-full .me5rine-lab-form-view-item {
    grid-column: 1 / -1;
}

/* Responsive vue */
@media (max-width: 768px) {
    .me5rine-lab-form-view-row {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .me5rine-lab-form-view-item {
        flex-direction: column;
        gap: 8px;
    }

    .me5rine-lab-form-view-item.me5rine-lab-form-col {
        margin-bottom: 0;
    }

    .me5rine-lab-form-view-label {
        min-width: auto;
    }
}
```

## Messages

```css
/* Message générique */
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
```

## Responsive Global

```css
/* Responsive global pour les sections */
@media (max-width: 768px) {
    .me5rine-lab-form-section,
    .me5rine-lab-form-view {
        padding: 16px;
        border-radius: 8px;
    }
}
```

