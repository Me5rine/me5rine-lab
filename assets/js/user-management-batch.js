// File: js/user-management-batch.js

document.addEventListener('DOMContentLoaded', () => {
    if (!adminLabBatch.start) return;

    let offset = 0;

    function runBatch() {
        fetch(adminLabBatch.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'admin_lab_batch_next',
                offset: offset
            })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;

            offset = data.data.offset;

            const bar = document.querySelector('.progress-bar');
            if (bar) {
                bar.style.width = data.data.percent + '%';
                bar.textContent = `${data.data.current} / ${data.data.total} (${data.data.percent}%)`;
            }

            if (data.data.has_more) {
                setTimeout(runBatch, 500);
            }
        });
    }

    runBatch();
});
