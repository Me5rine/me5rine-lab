"use strict";
contentEgg.factory("ModuleService", [
  "$http",
  "$timeout",
  function ($http, $timeout) {
    var service = function (module_id) {
      this.module_id = module_id;
      this.results = [];
      this.added = [];
      this.undo = [];
      this.added_changed = false;
      this.processing = false;
      this.loaded = false;
      this.error = "";
      this.aiError = "";
      this.aiProcessing = false;
    };

    service.prototype.find = function (query) {
      var self = this;

      self.processing = true;

      var params = {
        action: "content-egg-module-api",
        module: this.module_id,
        query: JSON.stringify(query),
        _contentegg_nonce: contentegg_params.nonce,
      };

      return $http({
        method: "post",
        url: ajaxurl,
        data: params,
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        transformRequest: function (obj) {
          var str = [];
          for (var p in obj)
            str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
          return str.join("&");
        },
      }).then(
        function (response) {
          var data = response.data;
          if (!data.error) {
            self.results = data.results;
            self.error = "";
            self.loaded = true;
          } else {
            self.results = [];
            self.error = data.error;
          }
          $timeout(function () {
            self.processing = false;
          }, 1000);

          return self.results;
        },
        function (error) {
          self.processing = false;
          self.error = JSON.stringify(error);
        }
      );
    };

    service.prototype.ai = function (ai_params) {
      var self = this;

      self.aiProcessing = true;

      var params = {
        action: "content-egg-ai-api",
        module: this.module_id,
        params: JSON.stringify(ai_params),
        _contentegg_nonce: contentegg_params.nonce,
      };

      return $http({
        method: "post",
        url: ajaxurl,
        data: params,
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        timeout: 180000,
        transformRequest: function (obj) {
          var str = [];
          for (var p in obj)
            str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
          return str.join("&");
        },
      }).then(
        function (response) {
          var data = response.data;
          self.aiProcessing = false;
          if (!data.error && data.results) {
            self.undo = self.added;
            self.added = data.results;
          } else {
            self.aiError = data.error;
          }
          $timeout(function () {
            self.aiProcessing = false;
          }, 1000);

          return self.added;
        },
        function (error) {
          self.error = "Internal Server Error";
          self.aiProcessing = false;
        }
      );
    };

    return service;
  },
]);
