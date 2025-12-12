// File: js/giveaways-rafflepress-save-listener.js

document.addEventListener('DOMContentLoaded', function () {
    const saveButton = document.getElementById('rafflepress-builder-save');

    function extractGiveawayIdFromUrl() {
        const match = window.location.href.match(/[?&]id=(\d+)/);
        return match ? match[1] : null;
    }

    function updateGiveawayInWordPress(giveawayId) {
        if (typeof admin_lab_ajax_obj === 'undefined') return;

        jQuery.ajax({
            url: admin_lab_ajax_obj.ajaxurl,
            method: 'POST',
            data: {
                action: 'rafflepress_campaign_sync',
                giveaway_id: giveawayId,
                nonce: admin_lab_ajax_obj.nonce,
            }
        });
    }

    if (saveButton) {
        saveButton.addEventListener('click', function () {
            const giveawayId = extractGiveawayIdFromUrl();
            if (!giveawayId) return;

            const observer = new MutationObserver(function (mutationsList, observer) {
                for (const mutation of mutationsList) {
                    if (
                        mutation.addedNodes.length &&
                        mutation.addedNodes[0].classList &&
                        mutation.addedNodes[0].classList.contains('swal2-container')
                    ) {
                        const successText = mutation.addedNodes[0].querySelector('#swal2-content');
                        if (successText && successText.textContent.includes('Saved')) {
                            updateGiveawayInWordPress(giveawayId);
                            observer.disconnect();
                        }
                    }
                }
            });

            observer.observe(document.body, { childList: true, subtree: true });
        });
    }
});
