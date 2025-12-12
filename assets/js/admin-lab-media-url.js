(function($){
    /*** 1) State controller générique pour l’onglet URL ***/
    const AdminLabUrlController = wp.media.controller.State.extend({
        defaults: {
            id: 'admin-lab-url',
            // Le titre sera override par la frame (via les labels localisés)
            title: (window.marketingMedia && marketingMedia.tabUrl) ||
                   (window.eventsTaxMedia && eventsTaxMedia.tabUrl) ||
                   'Insert from URL',
            priority: 200,
            menu: 'default',
            menuItem: {
                text: (window.marketingMedia && marketingMedia.tabUrl) ||
                      (window.eventsTaxMedia && eventsTaxMedia.tabUrl) ||
                      'Insert from URL',
                priority: 200
            },
            content: 'admin-lab-url'
        },

        initialize: function() {
            this.props = new Backbone.Model({
                url: ''
            });
        }
    });

    /*** 2) Vue du contenu (champ URL, preview) – compatible marketing + events ***/
    wp.media.view.AdminLabUrlContent = wp.media.View.extend({
        className: 'admin-lab-url-content',

        // On choisit le bon template automatiquement
        template: function() {
            if ($('#tmpl-marketing-url-template').length) {
                return wp.template('marketing-url-template');
            }
            if ($('#tmpl-events-tax-url-template').length) {
                return wp.template('events-tax-url-template');
            }
            // fallback au cas où
            return function(){ return ''; };
        },

        events: {
            'input  #marketing_url_input': 'updateUrl',
            'keydown #marketing_url_input': 'maybeInsert',
            'input  #events_tax_url_input': 'updateUrl',
            'keydown #events_tax_url_input': 'maybeInsert',
        },

        ready: function(){
            const state = this.controller.state();
            const url = state.props.get('url');

            const $input   = this.$('#marketing_url_input').length
                ? this.$('#marketing_url_input')
                : this.$('#events_tax_url_input');

            const $preview = this.$('#marketing_url_preview').length
                ? this.$('#marketing_url_preview')
                : this.$('#events_tax_url_preview');

            if (url) {
                $input.val(url);
                $preview.attr('src', url).show();
            }
        },

        updateUrl: function(e) {
            const val = $(e.currentTarget).val().trim();
            const state = this.controller.state();
            state.props.set('url', val);

            const isValid = /^(https?:)\/\//i.test(val);
            const btn = this.controller.$el.find('.media-button-select');
            if (!btn.length) return;

            const $preview = this.$('#marketing_url_preview').length
                ? this.$('#marketing_url_preview')
                : this.$('#events_tax_url_preview');

            if (isValid) {
                btn.removeAttr('disabled');
                btn.off('click.adminLabUrl').on('click.adminLabUrl', () => {
                    const url = state.props.get('url');
                    if (/^(https?:)\/\//i.test(url)) {
                        this.controller.trigger('insert', state);
                        this.controller.close();
                    }
                });
                $preview.attr('src', val).show();
            } else {
                btn.attr('disabled', 'disabled').off('click.adminLabUrl');
                $preview.hide().attr('src', '');
            }
        },

        maybeInsert: function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const state = this.controller.state();
                const url = state.props.get('url');
                if (/^(https?:)\/\//i.test(url)) {
                    this.controller.trigger('insert', state);
                    this.controller.close();
                }
            }
        }
    });

    /*** 3) Frame générique ***/
    wp.media.view.MediaFrame.AdminLab = wp.media.view.MediaFrame.Select.extend({
        initialize: function() {
            wp.media.view.MediaFrame.Select.prototype.initialize.apply(this, arguments);
            this.states.add(new AdminLabUrlController({ frame: this }));
            this.on('content:create:admin-lab-url', this.createAdminLabUrlContent, this);
        },

        createAdminLabUrlContent: function(region) {
            region.view = new wp.media.view.AdminLabUrlContent({
                controller: this,
                model: this.state().props
            });
        }
    });

    /*** 4) Initialisation des boutons selon le contexte ***/
    $(document).ready(function(){

        /**********************
         *  CONTEXTE MARKETING
         **********************/
        if (typeof marketingMedia !== 'undefined') {
            $('.upload_campaign_image').on('click', function(e){
                e.preventDefault();
                const type = $(this).data('type');

                const frame = new wp.media.view.MediaFrame.AdminLab({
                    title: marketingMedia.selectTitle,
                    multiple: false
                });

                // Pré-remplissage si déjà une image URL
                frame.on('open', function(){
                    const state = frame.state('admin-lab-url');
                    if (state) {
                        state.props.set({
                            url: $('#campaign_image_' + type).val(),
                        });
                    }
                });

                // Cas classique : image de la médiathèque
                frame.on('select', function(){
                    const attachment = frame.state().get('selection').first();
                    if (attachment) {
                        const data = attachment.toJSON();
                        $('#campaign_image_' + type).val(data.url);
                        $('#campaign_image_preview_' + type)
                            .attr('src', data.url)
                            .show();
                    }
                });

                // Cas URL (onglet custom)
                frame.on('insert', function(state){
                    if (!state || state.id !== 'admin-lab-url') return;
                    const url = state.props.get('url');
                    if (url) {
                        $('#campaign_image_' + type).val(url);
                        $('#campaign_image_preview_' + type)
                            .attr('src', url)
                            .show();
                    }
                });

                frame.open();
            });

            $('.remove_campaign_image').on('click', function(e) {
                e.preventDefault();
                const type = $(this).data('type');
                $('#campaign_image_' + type).val('');
                $('#campaign_image_preview_' + type)
                    .attr('src', '')
                    .attr('alt', '')
                    .attr('title', '')
                    .hide();
            });
        }

        /****************************
         *  CONTEXTE TAXONOMY EVENTS
         ****************************/
        if (typeof eventsTaxMedia !== 'undefined') {
            // Bouton "Choose Image"
            $(document).on('click', '.event-type-image-select', function(e){
                e.preventDefault();
                const $field = $(this).closest('.event-type-image-field');

                const frame = new wp.media.view.MediaFrame.AdminLab({
                    title: eventsTaxMedia.selectTitle,
                    multiple: false
                });

                // Pré-remplissage onglet URL avec la valeur déjà enregistrée
                frame.on('open', function(){
                    const state = frame.state('admin-lab-url');
                    if (state) {
                        state.props.set({
                            url: $field.find('.event-type-image-url').val() || ''
                        });
                    }
                });

                // Cas médiathèque → on stocke l’ID + URL pour la preview
                frame.on('select', function(){
                    const attachment = frame.state().get('selection').first();
                    if (!attachment) return;

                    const data = attachment.toJSON();
                    const $idInput  = $field.find('.event-type-image-id');
                    const $urlInput = $field.find('.event-type-image-url');
                    const $preview  = $field.find('.event-type-image-preview');
                    const $remove   = $field.find('.event-type-image-remove');

                    $idInput.val(data.id);
                    $urlInput.val('');
                    $preview.attr('src', data.url).show();
                    $remove.prop('disabled', false);
                });

                // Cas URL → on ne stocke que l’URL
                frame.on('insert', function(state){
                    if (!state || state.id !== 'admin-lab-url') return;
                    const url = state.props.get('url');
                    if (!url) return;

                    const $idInput  = $field.find('.event-type-image-id');
                    const $urlInput = $field.find('.event-type-image-url');
                    const $preview  = $field.find('.event-type-image-preview');
                    const $remove   = $field.find('.event-type-image-remove');

                    $idInput.val('');
                    $urlInput.val(url);
                    $preview.attr('src', url).show();
                    $remove.prop('disabled', false);
                });

                frame.open();
            });

            // Bouton "Remove"
            $(document).on('click', '.event-type-image-remove', function(e) {
                e.preventDefault();
                const $field   = $(this).closest('.event-type-image-field');
                const $idInput = $field.find('.event-type-image-id');
                const $urlInput = $field.find('.event-type-image-url');
                const $preview = $field.find('.event-type-image-preview');

                $idInput.val('');
                $urlInput.val('');
                $preview
                    .attr('src', '')
                    .attr('alt', '')
                    .attr('title', '')
                    .hide();

                $(this).prop('disabled', true);
            });
        }
    });

})(jQuery);
