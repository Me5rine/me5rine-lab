// File: assets/js/events-admin.js
jQuery(function($){
  // On scope tout dans la meta box (id = 'admin_lab_event_box')
  const $mb          = $('#admin_lab_event_box');
  const $enabled     = $mb.find('input[name="admin_lab_events[enabled]"]');
  const $fieldsWrap  = $mb.find('.admin-lab-events-fields');
  const $recurToggle = $mb.find('input[name="admin_lab_events[recurring]"]');
  const $recurWrap   = $mb.find('.admin-lab-events-recur');

  function sync() {
    $fieldsWrap.toggle($enabled.is(':checked'));
    $recurWrap.toggle($recurToggle.is(':checked'));
  }

  $enabled.on('change', sync);
  $recurToggle.on('change', sync);
  sync();
});
