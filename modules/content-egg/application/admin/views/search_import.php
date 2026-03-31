<div
  class="cegg5-container"
  ng-app="contentEgg"
  ng-controller="SearchController as vm"
  <?php if (\ContentEgg\application\Plugin::isDevEnvironment()): ?>
  ng-init="vm.results = [
    {
      unique_id: 'prod-001',
      title: 'Wireless Headphones',
      price: 59.99,
      _priceFormatted: '$59.99',
      _priceOldFormatted: '$79.99',
      img: 'https://m.media-amazon.com/images/I/51HrtoZ9YVL._AC_SL1080_.jpg',
      url: 'https://example.com/1',
      _enqueued: false,
      _error: true,
      _errorMessage : 'Duplicate product',
    },
    {
      unique_id: 'prod-002',
      title: 'Smart Watch',
      price: 129.99,
      _priceFormatted: '$129.99',
      img: 'https://m.media-amazon.com/images/I/51HrtoZ9YVL._AC_SL1080_.jpg',
      url: 'https://example.com/2',
      _enqueued: true
    },
    {
      unique_id: 'prod-003',
      title: 'Ultra-Slim Portable Charger Power Bank with Dual USB-C Ports and Fast Charging Capability for Smartphones and Tablets',
      price: 39.99,
      _priceFormatted: '$39.99',
      _priceOldFormatted: '$50.99',
      img: 'https://m.media-amazon.com/images/I/713oP52aAcL._AC_SL1500_.jpg',
      url: 'https://example.com/3',
      _enqueued: false,
      'module_id': 'Amazon',
      'percentageSaved': 20,
    },
    {
      unique_id: 'prod-004',
      title: 'Stainless Steel Insulated Travel Mug with Leak-Proof Lid and Temperature Retention Technology for Hot and Cold Beverages',
      price: 24.49,
      _priceFormatted: '$24.49',
      _priceOldFormatted: '$29.99',
      img: 'https://m.media-amazon.com/images/I/811Pl2DEBtL._AC_SX679_.jpg',
      url: 'https://example.com/4',
      _enqueued: false
    },
    {
      unique_id: 'prod-005',
      title: 'Ergonomic Memory Foam Lumbar Support Pillow for Office Chairs and Car Seats with Adjustable Straps',
      price: 19.95,
      _priceFormatted: '$19.95',
      _priceOldFormatted: '$24.99',
      img: 'https://m.media-amazon.com/images/I/71NuS4cBUWL._AC_SL1500_.jpg',
      url: 'https://example.com/5',
      _enqueued: true
    },
    {
      unique_id: 'prod-006',
      title: 'Multi-Functional Smart LED Desk Lamp with Wireless Qi Charging Pad and Adjustable Color Temperatures',
      price: 49.99,
      _priceFormatted: '$49.99',
      img: 'https://m.media-amazon.com/images/I/71tA1l-biML._AC_SL1500_.jpg',
      url: 'https://example.com/6',
      _enqueued: false
    }
  ]"
  <?php endif; ?>>

  <!-- Search Form -->
  <form name="searchForm" ng-submit="vm.search()" novalidate class="pt-4 mb-4">

    <div class="row ">

      <!-- Keyword -->
      <div class="col-md-5 col-lg-5">
        <label for="keyword" class="form-label">
          <?php esc_html_e('Keyword', 'content-egg'); ?> *</label>
        <div class="input-group">
          <input
            type="text"
            id="keyword"
            class="form-control"
            placeholder="<?php esc_attr_e('Search products...', 'content-egg'); ?>"
            ng-model="vm.form.keyword"
            select-on-click
            required />
          <!-- Locale -->
          <select
            ng-if="vm.moduleMeta[vm.form.module].is_locale_filter"
            id="locale"
            style="max-width: 4.5rem;"
            class="form-select form-select-sm w-auto"
            ng-model="vm.form.locale"
            ng-options="code as name for (code, name) in vm.moduleMeta[vm.form.module].locales">
          </select>
          <button
            type="submit"
            class="btn btn-primary"
            ng-disabled="searchForm.$invalid || vm.loading">
            <span
              ng-if="vm.loading"
              class="spinner-border spinner-border-sm me-1"
              role="status"
              aria-hidden="true"></span>
            <?php esc_html_e('Search', 'content-egg'); ?>
          </button>

        </div>
      </div>

      <!-- Module -->
      <div class="col-md-3 col-lg-3">
        <label for="module" class="form-label">
          <?php esc_html_e('Module', 'content-egg'); ?>
        </label>
        <select
          id="module"
          class="form-select"
          ng-model="vm.form.module"
          ng-options="m.module_id as m.module_name for m in vm.modules"
          ng-change="vm.onModuleChange()"
          required>
        </select>
      </div>

      <!-- Price range -->
      <div class="col-6 col-md-2 col-lg-2">
        <label for="min" class="form-label" ng-hide="!vm.moduleMeta[vm.form.module].is_price_filter">
          <?php esc_html_e('Min Price', 'content-egg'); ?>
        </label>

        <input
          ng-hide="!vm.moduleMeta[vm.form.module].is_price_filter"
          type="number"
          id="min"
          class="form-control"
          placeholder="<?php esc_attr_e('0.00', 'content-egg'); ?>"
          ng-model="vm.form.minPrice"
          min="0"
          step="1" />
      </div>
      <div class="col-6 col-md-2 col-lg-2">
        <label for="max" class="form-label" ng-hide="!vm.moduleMeta[vm.form.module].is_price_filter">
          <?php esc_html_e('Max Price', 'content-egg'); ?>
        </label>

        <input
          ng-hide="!vm.moduleMeta[vm.form.module].is_price_filter"
          type="number"
          id="max"
          class="form-control"
          placeholder="<?php esc_attr_e('0.00', 'content-egg'); ?>"
          ng-model="vm.form.maxPrice"
          min="0"
          step="1" />
      </div>

    </div>

  </form>

  <!-- Import Settings -->
  <div class="row mb-4">

    <!-- Preset -->
    <div class="col-md-5">
      <label for="import_preset" class="form-label">
        <?php esc_html_e('Import Preset', 'content-egg'); ?>
      </label>
      <div class="input-group">

        <select
          id="import_preset"
          class="form-select"
          ng-model="vm.importSettings.preset_id"
          ng-options="p.id as (p.title + ' [' + p.type + ']') for p in vm.presets">
        </select>

        <button
          type="button"
          class="btn btn-success"
          ng-click="vm.importAll()"
          ng-disabled="!vm.hasPending() || vm.importingAll">
          <span
            ng-if="vm.importingAll"
            class="spinner-border spinner-border-sm me-1"
            role="status"
            aria-hidden="true"></span>
          <?php esc_html_e('Import All', 'content-egg'); ?>
        </button>
      </div>
    </div>

    <!-- Post category -->
    <div class="col-md-3" ng-if="vm.selectedPreset().type === 'post'">

      <label for="import_post_cat" class="form-label">
        <?php esc_html_e('Import to category', 'content-egg'); ?>
      </label>
      <select
        id="import_post_cat"
        class="form-select"
        ng-model="vm.importSettings.post_cat"
        ng-options="id as name for (id, name) in vm.postCats">
        <option value=""><?php esc_html_e('Choose…', 'content-egg'); ?></option>
      </select>
    </div>

    <!-- Woo category -->
    <div class="col-md-3" ng-if="vm.selectedPreset().type === 'product'">
      <label for="import_woo_cat" class="form-label">
        <?php esc_html_e('Import to category', 'content-egg'); ?>
      </label>

      <select
        id="import_woo_cat"
        class="form-select"
        ng-model="vm.importSettings.woo_cat"
        ng-options="id as name for (id, name) in vm.wooCats">
        <option value=""><?php esc_html_e('Choose…', 'content-egg'); ?></option>
      </select>
    </div>

  </div>

  <!-- Status / Errors -->
  <div
    ng-if="vm.error"
    class="alert alert-danger"
    role="alert"
    aria-live="assertive">
    {{ vm.error }}
  </div>

  <!-- Results -->

  <div class="results-wrapper overflow-auto"
    style="max-height:100vh;overflow-y:scroll;padding-right:15px;padding-bottom:15px;">

    <h2 class="visually-hidden"><?php esc_html_e('Search Results', 'content-egg'); ?></h2>
    <div ng-if="vm.loading" class="d-flex justify-content-center py-5">
      <div class="spinner-border" role="status" aria-hidden="true"></div>
      <span class="visually-hidden"><?php esc_html_e('Loading…', 'content-egg'); ?></span>
    </div>

    <div class="row g-3 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 row-cols-xxl-6"
      ng-if="!vm.loading && vm.results.length"
      aria-live="polite">

      <div class="col" ng-repeat="item in vm.results track by item.unique_id">
        <div class="cegg-grid-card cegg-card h-100 px-3 py-2 border bg-white position-relative"
          ng-class="{
             'border-success opacity-50 pointer-events-none': item._enqueued,
             'border-danger opacity-50 pointer-events-none': item._error
           }">

          <div ng-show="item.percentageSaved" class="badge bg-danger rounded-1 position-absolute top-0 end-0 z-3 mt-1 me-2 mt-lg-2 me-lg-2">-{{ item.percentageSaved }}%</div>

          <!-- Overlay when disabled -->
          <div ng-if="vm.enqueueing[item.unique_id]"
            class="position-absolute top-0 start-0 w-100 h-100 rounded"
            style="background:rgba(128, 128, 128, 0.1);z-index: 1; "></div>
          <div ng-if="item._enqueued"
            class="position-absolute top-0 start-0 w-100 h-100 rounded"
            style="background:rgba(144, 238, 144, 0.1);"></div>
          <div ng-if="item._error"
            class="position-absolute top-0 start-0 w-100 h-100 rounded"
            style="background:rgba(255, 99, 71, 0.1);"></div>

          <!-- Image -->
          <div ng-show="item.img" class="ratio ratio-1x1">
            <img
              class="card-img-top object-fit-scale rounded"
              ng-src="{{ item.img }}"
              style="max-height: 250px; width: 100%; object-fit: contain;" />
          </div>

          <!-- Body -->
          <div class="card-body p-0 mt-2 mb-2">

            <div ng-show="item.domain"
              class="cegg-merchant small fs-6 text-body-secondary text-truncate">
              <small>{{ item.domain }}</small>
            </div>

            <div class="cegg-card-price lh-1 pt-3 pb-2">
              <div class="hstack gap-3">
                <div ng-show="item._priceFormatted">
                  <span
                    class="cegg-price fs-6 lh-1 mb-0"
                    ng-bind-html="item._priceFormatted">
                  </span>

                  <del ng-show="item._priceOldFormatted"
                    class="cegg-old-price fs-6 _priceOldFormatted-body-tertiary fw-normal me-1"
                    ng-bind-html="item._priceFormatted">
                  </del>
                </div>

                <div ng-if="item.promo"
                  class="text-success pt-2 pt-md-0 small">{{item.promo}}</div>

                <div title="<?php esc_attr_e('Free Shipping', 'content-egg'); ?>"
                  ng-if="item.extra && item.extra.IsEligibleForSuperSaverShipping"
                  class="text-success small d-flex align-items-center gap-1 pt-2 pt-md-0">
                  <i class="bi bi-truck" aria-hidden="true"></i>
                </div>
              </div>
            </div>

            <div class="card-title fw-normal lh-base cegg-title cegg-text-truncate mb-2">
              {{ item.title }}
            </div>

            <!-- Buttons -->
            <div class="cegg-card-button d-flex gap-2">
              <a href="javascript:void(0)"
                class="stretched-link import-stretch btn btn-sm btn-outline-success flex-fill d-flex align-items-center justify-content-center"
                ng-if="!item._enqueued && !item._error"
                ng-disabled="vm.enqueueing[item.unique_id]"
                ng-click="vm.enqueue(item)">
                <span ng-if="vm.enqueueing[item.unique_id]"
                  class="spinner-border spinner-border-sm me-2"></span>
                <?php esc_html_e('Import', 'content-egg'); ?>
              </a>

              <!-- Success Badge -->
              <span ng-if="item._enqueued"
                class="badge bg-success w-100 py-2">
                <?php esc_html_e('Queued', 'content-egg'); ?>
              </span>

              <!-- Error Badge -->
              <span ng-if="item._error"
                class="badge bg-danger w-100 py-2">
                {{ vm.enqueueErrors[item.unique_id] || item._errorMessage || item._error || 'Unknown error' }}
              </span>

              <a ng-href="{{ item.url }}"
                target="_blank"
                rel="noopener"
                class="view-deal-link btn btn-sm btn-outline-secondary flex-fill d-flex align-items-center justify-content-center"
                ng-if="!item._enqueued && !item._error">
                <?php esc_html_e('View', 'content-egg'); ?>
              </a>
            </div>
          </div> <!-- /.card-body -->
        </div> <!-- /.cegg-grid-card -->
      </div> <!-- /.col -->

      <!-- Empty-state message -->
      <div ng-if="!vm.loading && !vm.results.length && vm.searched"
        class="alert alert-warning text-center" role="alert">
        <?php esc_html_e('No products found.', 'content-egg'); ?>
      </div>
    </div> <!-- /.row -->
  </div> <!-- /.results-wrapper -->

  <style>
    .results-wrapper .cegg-grid-card {
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .results-wrapper .cegg-grid-card .card-body {
      display: flex;
      flex-direction: column;
      flex: 1 1 auto;
    }

    .results-wrapper .cegg-grid-card .cegg-card-button {
      margin-top: auto;
    }

    .cegg-grid-card {
      position: relative;
    }

    .cegg-grid-card .view-deal-link {
      position: relative;
      z-index: 2;
    }

    .cegg-grid-card .import-stretch::after {
      z-index: 1;
    }

    .cegg-grid-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .cegg-grid-card:hover,
    .cegg-list-card:hover {
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }

    .cegg-grid-card:hover .card-title,
    .cegg-list-card:hover .card-title {
      text-decoration: underline;
      text-underline-offset: 0.25em;
      text-decoration-color: rgba(grey, 0.5)
    }

    .cegg-grid-card img,
    .cegg-list-card img {
      transition: transform 0.3s ease;
    }

    .cegg-grid-card:hover img,
    .cegg-list-card:hover img {
      transform: scale(1.05);
    }

    .cegg-text-truncate {
      display: -webkit-box;
      -webkit-box-orient: vertical;
      overflow: hidden;
      -webkit-line-clamp: 3 !important;
    }

    .cegg-title {
      text-decoration: none !important;
    }

    #wpbody-content {
      padding-bottom: 5px
    }
  </style>