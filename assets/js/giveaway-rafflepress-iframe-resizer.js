// File: js/giveaway-rafflepress-iframe-resizer.js

jQuery(document).ready(function ($) {
    const iframe = $('iframe.rafflepress-iframe');

    if (!iframe.length) return;

        iFrameResize({
        log: false,
        checkOrigin: false,
        heightCalculationMethod: 'bodyScroll',
        minHeight: 900,
        onMessage: function(message) {}
    }, iframe[0]);
});