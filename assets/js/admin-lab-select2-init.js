/**
 * Initialisation automatique de Select2 pour tous les selects avec la classe .admin-lab-select2
 * 
 * Ce script initialise Select2 sur tous les éléments ayant la classe .admin-lab-select2
 * dans l'administration WordPress.
 * 
 * Configuration par défaut :
 * - width: 'resolve' (s'ajuste au conteneur parent)
 * - allowClear: true (affiche un bouton pour effacer la sélection)
 * - placeholder: récupéré depuis data-placeholder ou texte par défaut
 * 
 * Pour personnaliser, utilisez les attributs data-* sur le select :
 * - data-placeholder : Placeholder personnalisé
 * - data-ajax-action : Action AJAX WordPress (active le mode AJAX)
 * - data-minimum-input-length : Nombre minimum de caractères pour la recherche AJAX
 * - data-ajax-delay : Délai avant l'envoi de la requête AJAX (ms)
 */

(function($) {
    'use strict';

    /**
     * Initialise Select2 sur un élément select
     * @param {jQuery} $select - L'élément jQuery du select
     */
    function initSelect2($select) {
        // Vérifier que Select2 est disponible
        if (typeof $.fn.select2 === 'undefined') {
            console.warn('Select2 is not loaded');
            return;
        }

        // Vérifier que Select2 n'est pas déjà initialisé
        if ($select.data('select2')) {
            return;
        }

        // Vérifier que le select n'a pas la classe no-select2
        if ($select.hasClass('no-select2')) {
            return;
        }

        // Configuration par défaut
        var config = {
            width: 'resolve',
            allowClear: true,
            placeholder: function() {
                return $(this).data('placeholder') || 'Choose...';
            }
        };

        // Vérifier si le select a un attribut data-ajax-action (mode AJAX)
        var ajaxAction = $select.data('ajax-action');
        if (ajaxAction && typeof ajaxurl !== 'undefined') {
            config.ajax = {
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                delay: parseInt($select.data('ajax-delay')) || 250,
                data: function(params) {
                    return {
                        action: ajaxAction,
                        search: params.term || '',
                        page: params.page || 1
                    };
                },
                processResults: function(data) {
                    // Format attendu : { results: [{id: 1, text: 'Item 1'}, ...] }
                    if (data && data.results) {
                        return data;
                    }
                    // Si les données sont directement un tableau
                    if (Array.isArray(data)) {
                        return { results: data };
                    }
                    return { results: [] };
                },
                cache: true
            };

            // Nombre minimum de caractères pour lancer la recherche AJAX
            var minimumInputLength = $select.data('minimum-input-length');
            if (minimumInputLength !== undefined) {
                config.minimumInputLength = parseInt(minimumInputLength) || 1;
            } else {
                config.minimumInputLength = 1; // Par défaut
            }
        }

        // Si le select est multiple, ne pas fermer après sélection
        if ($select.prop('multiple')) {
            config.closeOnSelect = false;
        }

        // Initialiser Select2
        $select.select2(config);
    }

    /**
     * Initialise tous les Select2 avec la classe .admin-lab-select2
     */
    function initAllSelect2() {
        $('.admin-lab-select2').each(function() {
            initSelect2($(this));
        });
    }

    // Initialiser au chargement du DOM
    $(document).ready(function() {
        initAllSelect2();
    });

    // Initialiser aussi après un court délai pour les éléments chargés dynamiquement
    setTimeout(initAllSelect2, 100);

    // Observer les mutations DOM pour détecter les nouveaux éléments ajoutés dynamiquement
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function(mutations) {
            var shouldInit = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    for (var i = 0; i < mutation.addedNodes.length; i++) {
                        var node = mutation.addedNodes[i];
                        if (node.nodeType === 1) { // Element node
                            // Vérifier si le nœud lui-même a la classe ou contient des éléments avec la classe
                            if ($(node).hasClass('admin-lab-select2') || 
                                $(node).find('.admin-lab-select2').length > 0) {
                                shouldInit = true;
                                break;
                            }
                        }
                    }
                }
            });

            if (shouldInit) {
                setTimeout(initAllSelect2, 50);
            }
        });

        // Observer les changements dans le body
        $(document).ready(function() {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    }

})(jQuery);

