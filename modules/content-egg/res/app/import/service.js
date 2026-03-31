/* ----------------------------------------------------
   service.js
   ---------------------------------------------------- */
(function () {
  "use strict";

  /**
   * ProductImportService
   *  – search()         : wraps action=content-egg-module-api
   *  – enqueue()        : single OR array (forwards to enqueueMany when array)
   *  – enqueueMany()    : one request that enqueues multiple jobs at once
   *  – enqueueBulkWithLimit(): per-item requests with N-concurrency
   */
  function ProductImportService(
    $http,
    $q,
    $httpParamSerializerJQLike,
    ajaxurl,
    contentEggNonce, // for module search
    importNonce, // for queue API
    defaultPresetId // current preset
  ) {
    /* ------------------------------------------------
       Product search
    ------------------------------------------------ */
    this.search = function (module, query) {
      var params = {
        action: "content-egg-module-api",
        module: module,
        query: JSON.stringify(query),
        _contentegg_nonce: contentEggNonce,
      };

      return $http({
        method: "POST",
        url: ajaxurl,
        data: $httpParamSerializerJQLike(params),
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
      });
    };

    /* ------------------------------------------------
       Helpers
    ------------------------------------------------ */
    function deriveModuleId(product, importSettings) {
      importSettings = importSettings || {};
      return (
        (product && (product.module_id || product.module)) ||
        importSettings.module ||
        importSettings.module_id ||
        ""
      );
    }

    function buildCommonParams(importSettings, moduleId, payloadJson) {
      var params = {
        action: "cegg_import_enqueue",
        nonce: importNonce,
        preset_id:
          (importSettings && importSettings.preset_id) || defaultPresetId,
        module_id: moduleId,
      };
      if (payloadJson) {
        params.payload = payloadJson;
      }
      if (importSettings && importSettings.keyword) {
        params.keyword = importSettings.keyword;
      }
      if (importSettings && importSettings.post_cat) {
        params.post_cat = importSettings.post_cat;
      }
      if (importSettings && importSettings.woo_cat) {
        params.woo_cat = importSettings.woo_cat;
      }
      if (importSettings && importSettings.scheduled_at) {
        params.scheduled_at = importSettings.scheduled_at;
      }
      if (importSettings && importSettings.source_post_id) {
        params.source_post_id = importSettings.source_post_id;
      }

      return params;
    }

    function postForm(params) {
      return $http({
        method: "POST",
        url: ajaxurl,
        data: $httpParamSerializerJQLike(params),
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
      });
    }

    /* ------------------------------------------------
       Single enqueue (backward compatible)
       - If `product` is an array, forwards to enqueueMany()
    ------------------------------------------------ */
    this.enqueue = function (product, importSettings) {
      importSettings = importSettings || {};

      // allow passing an array here for convenience
      if (Array.isArray(product)) {
        return this.enqueueMany(product, importSettings);
      }

      var moduleId = deriveModuleId(product, importSettings);
      var params = buildCommonParams(
        importSettings,
        moduleId,
        JSON.stringify(product || {})
      );

      return postForm(params);
    };

    /* ------------------------------------------------
       NEW: Batch enqueue in one request
       - products: Array<Object>
       - importSettings: Object
    ------------------------------------------------ */
    this.enqueueMany = function (products, importSettings) {
      importSettings = importSettings || {};
      var list = Array.isArray(products) ? products : [];

      // Derive module once (all items belong to the same module)
      var moduleId = list.length
        ? deriveModuleId(list[0], importSettings)
        : importSettings.module || importSettings.module_id || "";

      var params = buildCommonParams(
        importSettings,
        moduleId,
        JSON.stringify(list)
      );
      return postForm(params);
    };

    /* ------------------------------------------------
       Bulk enqueue with limited concurrency
    ------------------------------------------------ */
    this.enqueueBulkWithLimit = function (products, importSettings, limit) {
      var self = this;
      var $defer = $q.defer();
      var total = products.length;
      var results = new Array(total);
      var inFlight = 0;
      var idx = 0;

      limit = limit || 5; // default parallelism

      function launchNext() {
        while (inFlight < limit && idx < total) {
          (function (i) {
            inFlight++;

            self
              .enqueue(products[i], importSettings)
              .then(function (res) {
                results[i] = { ok: true, res: res };
              })
              .catch(function (err) {
                results[i] = { ok: false, err: err };
              })
              .finally(function () {
                inFlight--;
                if (idx < total) {
                  launchNext(); // refill the pipeline
                } else if (inFlight === 0) {
                  $defer.resolve(results); // all done
                }
              });
          })(idx++);
        }
      }

      if (!total) {
        $defer.resolve([]); // nothing to do
      } else {
        launchNext();
      }

      return $defer.promise;
    };

    /* ------------------------------------------------
       Legacy helper – keeps previous behaviour
       (unlimited parallelism)
    ------------------------------------------------ */
    this.enqueueBulk = function (products, importSettings) {
      return this.enqueueBulkWithLimit(
        products,
        importSettings,
        products.length || 1
      );
    };
  }

  ProductImportService.$inject = [
    "$http",
    "$q",
    "$httpParamSerializerJQLike",
    "ajaxurl",
    "contentEggNonce",
    "importNonce",
    "defaultPresetId",
  ];

  angular
    .module("contentEgg")
    .service("ProductImportService", ProductImportService);
})();
