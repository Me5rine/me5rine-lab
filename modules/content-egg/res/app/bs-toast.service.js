(function () {
  'use strict';

  angular.module('contentEgg')
    .service('BsToast', ['$document', '$timeout', function ($document, $timeout) {
      var doc = $document[0];

      function ensureContainer() {
        var c = doc.getElementById('cegg-toast-container');
        if (!c) {
          c = doc.createElement('div');
          c.id = 'cegg-toast-container';
          c.className = 'toast-container position-fixed top-0 end-0 p-3';
          c.style.zIndex = 1080;
          doc.body.appendChild(c);
        }
        return c;
      }

      var levelClass = {
        info:    'text-bg-primary',
        success: 'text-bg-success',
        warning: 'text-bg-warning',
        error:   'text-bg-danger'
      };

      function show(opts) {
        opts = opts || {};
        var level    = opts.level || 'info';
        var title    = opts.title || '';
        var body     = opts.body  || '';
        var delay    = typeof opts.delay === 'number' ? opts.delay : 8000;
        var autohide = (opts.autohide !== false); // default true

        var container = ensureContainer();

        // Build toast markup
        var toastEl = doc.createElement('div');
        toastEl.className = 'toast ' + (levelClass[level] || levelClass.info);
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');

        var header = doc.createElement('div');
        header.className = 'toast-header';

        var icon = doc.createElement('span');
        icon.className = 'rounded me-2 d-inline-block';
        icon.style.width = '0.75rem';
        icon.style.height = '0.75rem';
        icon.style.background = 'currentColor';
        header.appendChild(icon);

        var strong = doc.createElement('strong');
        strong.className = 'me-auto';
        strong.innerText = title || (level.charAt(0).toUpperCase() + level.slice(1));
        header.appendChild(strong);

        var small = doc.createElement('small');
        small.className = 'text-muted';
        small.innerText = '';
        header.appendChild(small);

        var btn = doc.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-close ms-2 mb-1';
        btn.setAttribute('data-bs-dismiss', 'toast');
        btn.setAttribute('aria-label', 'Close');
        header.appendChild(btn);

        var bodyEl = doc.createElement('div');
        bodyEl.className = 'toast-body';
        bodyEl.innerText = body;

        toastEl.appendChild(header);
        toastEl.appendChild(bodyEl);
        container.appendChild(toastEl);

        // Initialize via Bootstrap (no jQuery needed)
        var toast;
        if (window.bootstrap && window.bootstrap.Toast) {
          toast = new window.bootstrap.Toast(toastEl, { delay: delay, autohide: autohide });
          toast.show();
        } else {
          // Fallback if Bootstrap JS not loaded
          toastEl.classList.add('show');
          $timeout(function () {
            try { container.removeChild(toastEl); } catch (e) {}
          }, delay);
        }

        // Cleanup when hidden
        toastEl.addEventListener('hidden.bs.toast', function () {
          try { container.removeChild(toastEl); } catch (e) {}
        });

        return toastEl;
      }

      // Convenience helpers
      function make(level) {
        return function (body, title, opts) {
          opts = opts || {};
          opts.level = level;
          opts.body  = body;
          opts.title = title || '';
          return show(opts);
        };
      }

      this.show    = show;
      this.info    = make('info');
      this.success = make('success');
      this.warning = make('warning');
      this.error   = make('error');
    }]);
})();
