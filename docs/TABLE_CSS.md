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

```css
/* Tableau générique - Style unifié pour tous les tableaux */
.me5rine-lab-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--um-bg, #ffffff);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    margin-top: 24px;
    margin-bottom: 24px;
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    font-size: 14px;
    color: var(--um-text-light, #5D697D);
}

/* En-tête du tableau */
.me5rine-lab-table thead {
    background: var(--um-bg-secondary, #F9FAFB);
}

.me5rine-lab-table th {
    padding: 16px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    color: var(--um-text, #11161E);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--um-border, #DEE5EC);
    vertical-align: middle;
}

.me5rine-lab-table th .unsorted-column {
    display: inline-block;
}

/* Corps du tableau */
.me5rine-lab-table tbody tr {
    border-bottom: 1px solid var(--um-border, #DEE5EC);
    transition: background-color 0.2s ease;
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

.me5rine-lab-table td {
    padding: 16px;
    font-size: 14px;
    color: var(--um-text-light, #5D697D);
    vertical-align: middle;
}

/* Cellule de résumé (première colonne avec titre) */
.me5rine-lab-table td.summary {
    position: relative;
    padding-right: 50px;
}

/* Ligne de résumé dans une cellule */
.me5rine-lab-table-summary-row {
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: space-between;
}

/* Titre dans une cellule de tableau */
.me5rine-lab-table-title {
    font-weight: 600;
    font-size: 15px;
    color: var(--um-text, #11161E);
}

.me5rine-lab-table-title a {
    color: var(--um-text, #11161E);
    text-decoration: none;
    transition: color 0.2s ease;
}

.me5rine-lab-table-title a:hover {
    color: var(--um-secondary, #0485C8);
}

/* Bouton pour expander/réduire une ligne */
.me5rine-lab-table-toggle-btn {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 32px;
    height: 32px;
    border: 2px solid var(--um-border, #DEE5EC);
    border-radius: 6px;
    background: var(--um-bg, #ffffff);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    padding: 0;
}

.me5rine-lab-table-toggle-btn:hover {
    border-color: var(--um-secondary, #0485C8);
    background: var(--um-bg-secondary, #F9FAFB);
}

.me5rine-lab-table-toggle-btn:focus {
    outline: none;
    border-color: var(--um-secondary, #0485C8);
    box-shadow: 0 0 0 3px rgba(4, 133, 200, 0.1);
}

.me5rine-lab-table-toggle-btn::before {
    content: '▼';
    font-size: 10px;
    color: var(--um-text-light, #5D697D);
    transition: transform 0.2s ease, color 0.2s ease;
}

.me5rine-lab-table-toggle-btn[aria-expanded="true"]::before,
.me5rine-lab-table-row-toggleable.is-expanded .me5rine-lab-table-toggle-btn::before {
    transform: rotate(180deg);
}

.me5rine-lab-table-toggle-btn:hover::before {
    color: var(--um-secondary, #0485C8);
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

/* Styles pour tableaux avec classe striped (lignes alternées) */
.me5rine-lab-table.striped tbody tr:nth-child(odd) {
    background-color: var(--um-bg-secondary, #F9FAFB);
}

.me5rine-lab-table.striped tbody tr:nth-child(even) {
    background-color: var(--um-bg, #ffffff);
}

/* Responsive : Mobile */
@media screen and (max-width: 782px) {
    .me5rine-lab-table {
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-top: 16px;
        margin-bottom: 16px;
    }

    .me5rine-lab-table thead {
        display: none;
    }

    .me5rine-lab-table tbody {
        display: block;
    }

    .me5rine-lab-table tbody tr {
        display: block;
        margin-bottom: 16px;
        border: 2px solid var(--um-border, #DEE5EC);
        border-radius: 8px;
        background: var(--um-bg, #ffffff);
        overflow: hidden;
    }

    .me5rine-lab-table td {
        display: block;
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid var(--um-border, #DEE5EC);
    }

    .me5rine-lab-table td:last-child {
        border-bottom: none;
    }

    .me5rine-lab-table td.summary {
        padding-right: 50px;
        background: var(--um-bg-secondary, #F9FAFB);
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

    /* Labels de colonnes sur mobile */
    .me5rine-lab-table td[data-colname]::before {
        content: attr(data-colname) ": ";
        font-weight: 600;
        color: var(--um-text, #11161E);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 12px;
        margin-right: 8px;
    }

    .me5rine-lab-table td.summary[data-colname]::before {
        display: none;
    }
}
```

## Utilisation

Ce CSS générique s'applique automatiquement à tous les tableaux utilisant la classe `.me5rine-lab-table`. Les styles sont unifiés pour tous les contextes (profil Ultimate Member, dashboard front, etc.).

### Exemple d'utilisation

```html
<table class="me5rine-lab-table">
    <thead>
        <tr>
            <th>Titre</th>
            <th>Date</th>
            <th>Statut</th>
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

## Notes importantes

1. **CSS dans le thème** : Ce CSS doit être dans le thème, pas dans le plugin
2. **Variables CSS** : Assurez-vous que les variables CSS sont définies dans votre thème
3. **Responsive** : Les tableaux s'adaptent automatiquement sur mobile avec affichage en cartes
4. **Accessibilité** : Utilisez toujours `.me5rine-lab-sr-only` pour le texte des lecteurs d'écran

