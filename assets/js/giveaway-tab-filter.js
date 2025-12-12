// File: js/giveaway-tab-filter.js

jQuery(document).ready(function ($) {

  // Fonction de rechargement
  function reloadGiveawayTable(pg = 1) {
    const form = $('.giveaway-filters');
    const data = {
      action: 'admin_lab_filter_giveaways',
      status_filter: form.find('select[name="status_filter"]').val(),
      per_page: form.find('select[name="per_page"]').val(),
      user_id: form.find('input[name="user_id"]').val() || 0,
      pg: pg
    };

    $.post(admin_lab_ajax_obj.ajaxurl, data, function (response) {
      $('.giveaway-my-giveaways').replaceWith(response);
    });
  }

  // Changement de filtre : status ou per_page
  $(document).on('change', 'select.giveaway-filter', function () {
    reloadGiveawayTable(1); // reset à la page 1
  });

  // Clic pagination
  $(document).on('click', '.giveaway-pg', function (e) {
    e.preventDefault();
    const pg = $(this).data('pg');
    reloadGiveawayTable(pg);
  });
});
