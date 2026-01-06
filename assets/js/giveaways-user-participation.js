document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.me5rine-lab-table-toggle-btn').forEach(button => {
        button.addEventListener('click', function () {
            const tr = button.closest('tr');
            const expanded = tr.classList.toggle('is-expanded');
            tr.classList.toggle('is-collapsed', !expanded);
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    });

    document.querySelectorAll('.me5rine-lab-table tr.me5rine-lab-table-row-toggleable').forEach(tr => {
        tr.classList.add('is-collapsed');
    });

    // Empêcher Select2 de s'initialiser sur les selects des filtres
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        // Fonction pour détruire Select2 sur les selects avec la classe no-select2
        function destroySelect2OnFilters() {
            jQuery('.no-select2').each(function() {
                const $select = jQuery(this);
                // Vérifier si Select2 est initialisé
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }
                // Retirer les classes et attributs ajoutés par Select2
                $select.removeClass('select2-hidden-accessible')
                      .removeAttr('data-select2-id')
                      .removeAttr('tabindex')
                      .removeAttr('aria-hidden');
            });
        }

        // Détruire immédiatement si Select2 est déjà chargé
        destroySelect2OnFilters();

        // Détruire après un court délai au cas où Select2 s'initialise après le chargement
        setTimeout(destroySelect2OnFilters, 100);
        setTimeout(destroySelect2OnFilters, 500);
        setTimeout(destroySelect2OnFilters, 1000);

        // Observer les mutations DOM pour détecter si Select2 est ajouté dynamiquement
        const observer = new MutationObserver(function(mutations) {
            destroySelect2OnFilters();
        });

        // Observer les changements dans le conteneur des filtres
        const filtersContainer = document.querySelector('.me5rine-lab-filters');
        if (filtersContainer) {
            observer.observe(filtersContainer, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'data-select2-id']
            });
        }
    }
});