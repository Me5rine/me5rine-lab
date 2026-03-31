/* ----------------------------------------------------
   controllers.js
   ---------------------------------------------------- */
(function () {
  'use strict';

  angular
    .module('contentEgg')
    .controller('SearchController', SearchController);

  SearchController.$inject = [
    '$q',
    'ProductImportService',
    'moduleMeta',
    'presetOptions',
    'postCatOptions',
    'wooCatOptions',
    'defaultPresetId'
  ];

  function SearchController(
    $q,
    ProductImportService,
    moduleMeta,
    presetOptions,
    postCatOptions,
    wooCatOptions,
    defaultPresetId
  ) {
    var vm            = this;

    /* ------------------------------------------------
       Static data injected from PHP
    ------------------------------------------------ */
    vm.moduleMeta = moduleMeta;                       // hash by module_id
    vm.modules    = Object.values(moduleMeta);        // array for ng-options

    vm.presets   = presetOptions;
    vm.postCats  = postCatOptions;
    vm.wooCats   = wooCatOptions;

    // ints for easier comparison
    vm.presets.forEach(function (p) { p.id = +p.id; });
    defaultPresetId = +defaultPresetId;

    /* ------------------------------------------------
       Import-settings panel state
    ------------------------------------------------ */
    vm.importSettings = {
      preset_id : defaultPresetId || (vm.presets[0] ? vm.presets[0].id : null),
      post_cat  : '',
      woo_cat   : ''
    };

    vm.selectedPreset = function () {
      return vm.presets.find(function (p) {
        return p.id === vm.importSettings.preset_id;
      });
    };

    vm.hasPending = function(){
      return vm.results.some(function(it){
        return !it._enqueued && !it._error;
      });
    };

    /* ------------------------------------------------
       Search-form model
    ------------------------------------------------ */
    var firstMod = vm.modules[0] || {};
    vm.form = {
      module   : firstMod.module_id || '',
      keyword  : '',
      minPrice : '',
      maxPrice : '',
      locale   : firstMod.default_locale || ''
    };

    /* ------------------------------------------------
       View state
    ------------------------------------------------ */
    vm.results        = [];
    vm.error          = '';
    vm.loading        = false;
    vm.searched       = false;
    vm.enqueueing     = {};   // unique_id -> boolean
    vm.enqueueErrors  = {};   // unique_id -> string
    vm.importingAll   = false;

    /* ------------------------------------------------
       Handlers
    ------------------------------------------------ */
    vm.onModuleChange = function () {
      var m       = vm.moduleMeta[vm.form.module] || {};
      vm.form.locale = m.default_locale || '';
      // leave price fields as-is (they may still apply)
    };

    /* ---------------- search() ---------------- */
    vm.search = function () {
      if (!vm.form.keyword || !vm.form.module) {
        vm.error = 'Please enter both a keyword and select a module.';
        return;
      }

      vm.loading  = true;
      vm.error    = '';
      vm.results  = [];
      vm.searched = true;

      var query = {
        locale        : vm.form.locale,
        minimum_price : vm.form.minPrice,
        maximum_price : vm.form.maxPrice,
        keyword       : vm.form.keyword
      };

      ProductImportService.search(vm.form.module, query)
        .then(function (res) {
          if (res.data && res.data.error) {
            vm.error = res.data.error;
            return;
          }
          vm.results = (res.data && res.data.results) || [];
          if (!vm.results.length) {
            vm.error = 'No products found for "' + vm.form.keyword + '".';
          }
        })
        .catch(function (err) {
          vm.error =
            (err.data && (err.data.error || err.data.message)) ||
            'Error fetching products.';
        })
        .finally(function () { vm.loading = false; });
    };

    /* ---------------- enqueue() single ---------------- */
    vm.enqueue = function (product) {
      var uid = product.unique_id || product.ASIN || product.SKU || product.url || Math.random();
      vm.enqueueing[uid]   = true;
      vm.enqueueErrors[uid] = null;

      ProductImportService.enqueue(product, vm.importSettings)
        .then(function () {
          product._enqueued = true;
        })
        .catch(function (err) {
          var msg =
            (err.data && err.data.data && err.data.data.message) ||
            (err.data && err.data.message) ||
            'Failed';
          vm.enqueueErrors[uid] = msg;
          product._error        = true;
        })
        .finally(function () {
          vm.enqueueing[uid] = false;
        });
    };

    /* ---------------- Import All ---------------- */

    vm.importAll = function () {
      // Filter out already-queued or errored items
      var batch = vm.results.filter(function (p) {
        return !p._enqueued && !p._error;
      });
      if (!batch.length || vm.importingAll) { return; }

      vm.importingAll = true;

      // Mark all as enqueueing so each shows a spinner immediately
      batch.forEach(function (p) {
        var uid = p.unique_id || p.ASIN || p.SKU || p.url;
        vm.enqueueing[uid] = true;
      });

      // Build an array of per-item promises with concurrency limit (5 by default)
      ProductImportService
        .enqueueBulkWithLimit(batch, vm.importSettings, 5)
        .then(function (outcomes) {
          console.log(outcomes);
          outcomes.forEach(function (outcome, idx) {
            var product = batch[idx];
            var uid     = product.unique_id;

            vm.enqueueing[uid] = false;

            if (outcome.ok) {
              product._enqueued = true;
            } else {
              product._error = true;
              // capture error message
              vm.enqueueErrors[uid] =
                (outcome.err.data && (outcome.err.data.data.message)) ||
                'Failed';
            }
          });
        })
        .finally(function () {
          vm.importingAll = false;
        });
    };

  }
})();
