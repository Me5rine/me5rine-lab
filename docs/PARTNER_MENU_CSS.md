# CSS pour le Menu Partenaires

Ce fichier contient les styles CSS à copier dans votre thème pour le menu partenaires.

Le menu utilise une structure HTML similaire à Ultimate Member mais avec des classes préfixées `me5rine-lab-menu-*`.

## Structure HTML

```html
<div class="me5rine-lab-menu-wrapper">
    <button class="me5rine-lab-menu-toggle" aria-expanded="false" aria-controls="me5rine-lab-menu">
        <p class="me5rine-lab-menu-toggle-text">Menu</p>
    </button>
    <nav id="me5rine-lab-menu" class="me5rine-lab-menu-vertical" style="display: flex;">
        <!-- Lien simple -->
        <a href="..." class="active">
            <span class="me5rine-lab-menu-icon"><i class="fa fa-icon"></i></span>
            <span>Label</span>
        </a>
        
        <!-- Item avec sous-menu -->
        <div class="has-sub open">
            <a href="..." class="active">
                <span class="me5rine-lab-menu-icon"><i class="fa fa-icon"></i></span>
                <span>Label</span>
            </a>
            <div class="submenu">
                <a href="..." class="active">Sous-item 1</a>
                <a href="...">Sous-item 2</a>
            </div>
        </div>
    </nav>
</div>
```

## CSS à copier dans votre thème

```css
/* Menu partenaires - Style Ultimate Member */

.me5rine-lab-menu-wrapper {
    margin-bottom: 20px;
}

.me5rine-lab-menu-toggle {
    display: none;
    width: 100%;
    padding: 12px 16px;
    background-color: var(--um-bg-secondary, #F9FAFB);
    border: 1px solid var(--um-border, #DEE5EC);
    border-radius: 8px;
    cursor: pointer;
    text-align: left;
    margin-bottom: 10px;
}

.me5rine-lab-menu-toggle:hover {
    background-color: var(--um-border, #DEE5EC);
}

.me5rine-lab-menu-toggle-text {
    margin: 0;
    font-weight: 600;
    color: var(--um-text, #11161E);
    font-size: 14px;
}

.me5rine-lab-menu-vertical {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.me5rine-lab-menu-vertical > a,
.me5rine-lab-menu-vertical > .has-sub > a {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    color: var(--um-text, #11161E);
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.2s ease;
    font-size: 14px;
    font-weight: 500;
    background-color: transparent;
    border: 1px solid transparent;
}

.me5rine-lab-menu-vertical > a:hover,
.me5rine-lab-menu-vertical > .has-sub > a:hover {
    background-color: var(--um-bg-secondary, #F9FAFB);
    color: var(--um-primary, #2E576F);
}

.me5rine-lab-menu-vertical > a.active,
.me5rine-lab-menu-vertical > .has-sub > a.active {
    background-color: var(--um-primary, #2E576F);
    color: var(--um-text-color, #FFFFFF);
    border-color: var(--um-primary, #2E576F);
}

.me5rine-lab-menu-icon {
    margin-right: 12px;
    width: 20px;
    text-align: center;
    font-size: 16px;
}

.me5rine-lab-menu-icon i {
    display: inline-block;
}

.me5rine-lab-menu-vertical > a.active .me5rine-lab-menu-icon,
.me5rine-lab-menu-vertical > .has-sub > a.active .me5rine-lab-menu-icon {
    color: var(--um-text-color, #FFFFFF);
}

/* Sous-menu */
.me5rine-lab-menu-vertical .has-sub {
    position: relative;
}

.me5rine-lab-menu-vertical .has-sub .submenu {
    display: none;
    padding-left: 0;
    margin-top: 4px;
    margin-bottom: 0;
    list-style: none;
}

.me5rine-lab-menu-vertical .has-sub.open .submenu {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.me5rine-lab-menu-vertical .has-sub .submenu a {
    display: block;
    padding: 10px 16px 10px 48px;
    color: var(--um-text-light, #5D697D);
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.2s ease;
    font-size: 13px;
    background-color: transparent;
}

.me5rine-lab-menu-vertical .has-sub .submenu a:hover {
    background-color: var(--um-bg-secondary, #F9FAFB);
    color: var(--um-text, #11161E);
}

.me5rine-lab-menu-vertical .has-sub .submenu a.active {
    background-color: var(--um-primary, #2E576F);
    color: var(--um-text-color, #FFFFFF);
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    .me5rine-lab-menu-wrapper {
        position: relative;
        width: auto;
        display: inline-block;
    }

    .me5rine-lab-menu-toggle {
        display: block;
    }

    .me5rine-lab-menu-vertical {
        display: none;
        position: absolute;
        left: 0;
        top: 100%;
        margin-top: 10px;
        min-width: 200px;
        background: var(--um-bg, #FFFFFF);
        border: 1px solid var(--um-border, #DEE5EC);
        border-radius: 8px;
        padding: 4px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        z-index: 1000;
    }

    .me5rine-lab-menu-wrapper.menu-open .me5rine-lab-menu-vertical {
        display: flex;
    }
}
```

## Variables CSS utilisées

Le CSS utilise les variables CSS Ultimate Member pour la cohérence :
- `--um-primary` : Couleur primaire
- `--um-secondary` : Couleur secondaire
- `--um-text` : Couleur du texte principal
- `--um-text-light` : Couleur du texte secondaire
- `--um-text-color` : Couleur du texte sur fond coloré (généralement blanc)
- `--um-bg` : Couleur de fond principale
- `--um-bg-secondary` : Couleur de fond secondaire
- `--um-border` : Couleur des bordures

Si Ultimate Member n'est pas installé, des valeurs par défaut sont fournies.

## Utilisation

1. Copiez le CSS ci-dessus dans le fichier CSS de votre thème (ex: `style.css` ou un fichier dédié)
2. Le JavaScript du menu doit être géré par le thème pour une intégration unifiée avec Ultimate Member
3. Le menu sera automatiquement stylé avec les variables Ultimate Member si disponibles
4. Le menu est responsive et s'adapte aux petits écrans avec un bouton toggle

### JavaScript

Le JavaScript pour gérer le toggle mobile et les sous-menus doit être intégré dans le thème, de préférence de manière unifiée avec Ultimate Member. Le plugin ne fournit pas de JavaScript pour le menu car il est géré par le thème.

