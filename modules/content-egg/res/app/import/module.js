(function () {
    'use strict';

  angular
    .module('contentEgg', ['ngSanitize'])
    .constant('ajaxurl', window.ajaxurl)
    .constant('contentEggNonce', window.contentegg_params.nonce)
    .constant('importNonce', window.contentegg_params.importNonce)
    .constant('moduleMeta', window.contentegg_params.moduleMeta)
    .constant('presetOptions', window.contentegg_params.presets)
    .constant('postCatOptions', window.contentegg_params.postCats)
    .constant('wooCatOptions', window.contentegg_params.wooCats)
    .constant('defaultPresetId', window.contentegg_params.defaultPresetId)
})();

angular
  .module('contentEgg')
  .directive('selectOnClick', function () {
    return {
      restrict: 'A',
      link: function (scope, element) {
        element.on('click', function () {
          this.select();
        });
      }
    };
  });
