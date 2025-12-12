document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.toggle-row-btn').forEach(button => {
        button.addEventListener('click', function () {
            const tr = button.closest('tr');
            const expanded = tr.classList.toggle('is-expanded');
            tr.classList.toggle('is-collapsed', !expanded);
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    });

    document.querySelectorAll('.giveaway-profil-promo-table tr.toggle-row').forEach(tr => {
        tr.classList.add('is-collapsed');
    });
});