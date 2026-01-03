// File: js/giveaway-form-campaign.js

document.addEventListener("DOMContentLoaded", function () {
    const i18n = window.mlab_i18n || {
        name: "Name",
        description: "Description",
        image: "Image (upload)",
        activate: "Activate",
        deactivate: "Deactivate",
        removePrize: "Remove",
        selectCountries: "Select countries...",
        searchCountries: "Search a country..."
    };

    // === Ajout de lots ===
    const addPrizeBtn = document.getElementById("add-prize");
    const wrapper = document.getElementById("prizes-wrapper");

    function attachRemoveEvent(button) {
        button.addEventListener("click", () => button.closest(".prize-item").remove());
    }

    // Remove button for existing prizes
    document.querySelectorAll(".remove-prize").forEach(attachRemoveEvent);

    // Add new prize
    if (addPrizeBtn && wrapper) {
        addPrizeBtn.addEventListener("click", function () {
            const block = document.createElement("div");
            block.classList.add("prize-item", "me5rine-lab-card");

            block.innerHTML = `
                <div class="me5rine-lab-form-field">
                    <label class="me5rine-lab-form-label">${i18n.name}</label>
                    <input type="text" name="prize_name[]" class="me5rine-lab-form-input" required>
                </div>
                <div class="me5rine-lab-form-field">
                    <label class="me5rine-lab-form-label">${i18n.description}</label>
                    <textarea name="prize_description[]" class="me5rine-lab-form-textarea"></textarea>
                </div>
                <div class="me5rine-lab-form-field">
                    <label class="me5rine-lab-form-label">${i18n.image}</label>
                    <input type="file" name="prize_image_file[]" class="me5rine-lab-form-button-file">
                </div>
                <button type="button" class="remove-prize me5rine-lab-form-button me5rine-lab-form-button-remove">${i18n.removePrize}</button>
            `;

            wrapper.appendChild(block);
            attachRemoveEvent(block.querySelector(".remove-prize"));
        });
    }

    // === Activation / Désactivation des actions ===
    function toggleLabel(button, enable) {
        const label = button.dataset.label || '';
        button.textContent = (enable ? i18n.desactivate : i18n.activate) + " " + label;
    }

    document.querySelectorAll(".btn-activate").forEach(function (button) {
        const key = button.dataset.key;
        const label = button.dataset.label || '';
        const scoreOptions = document.getElementById("score-options-" + key);
        const enabledInput = document.getElementById("enabled_" + key);
        const visitUrlField = document.getElementById("visit-url-" + key);

        const isEnabled = enabledInput.value === "1";

        scoreOptions.style.display = isEnabled ? "block" : "none";
        if (visitUrlField) visitUrlField.style.display = isEnabled ? "block" : "none";
        toggleLabel(button, isEnabled);

        button.addEventListener("click", function () {
            const enabled = enabledInput.value === "1";
            enabledInput.value = enabled ? "0" : "1";
            scoreOptions.style.display = enabled ? "none" : "block";
            if (visitUrlField) visitUrlField.style.display = enabled ? "none" : "block";
            toggleLabel(button, !enabled);
        });
    });

    // === Sélection du score ===
    document.querySelectorAll(".btn-group").forEach(function (group) {
        const key = group.dataset.key;
        const hiddenScore = document.getElementById("actions_" + key + "_points");
        const buttons = group.querySelectorAll(".btn-score");

        buttons.forEach(function (btn) {
            btn.addEventListener("click", function () {
                buttons.forEach(b => b.classList.remove("active"));
                btn.classList.add("active");
                hiddenScore.value = btn.dataset.score;
            });
        });
    });

    // === Redirection après succès ===
    const urlParams = new URLSearchParams(window.location.search);
    const successMessage = document.querySelector('.me5rine-lab-campaign-success');
    if (successMessage) {
        const redirectUrl = urlParams.get('redirect_url');
        setTimeout(() => {
            if (redirectUrl) window.location.href = redirectUrl;
        }, 5000);
    }

    // === Initialisation Choices.js pour le champ pays ===
    const countryField = document.getElementById('eligible_countries');
    if (countryField && typeof Choices !== 'undefined') {
        new Choices(countryField, {
            removeItemButton: true,
            placeholder: true,
            placeholderValue: i18n.selectCountries || 'Select countries...',
            searchPlaceholderValue: i18n.searchCountries || 'Search...',
            shouldSort: true,
        });
    }
});
