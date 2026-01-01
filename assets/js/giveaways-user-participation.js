document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.me5rine-lab-table-toggle-btn').forEach(button => {
        button.addEventListener('click', function () {
            const tr = button.closest('tr');
            const expanded = tr.classList.toggle('is-expanded');
            tr.classList.toggle('is-collapsed', !expanded);
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    });

    document.querySelectorAll('.me5rine-lab-table tr.me5rine-lab-table-row-toggleable').forEach(tr => {
        tr.classList.add('is-collapsed');
    });
});