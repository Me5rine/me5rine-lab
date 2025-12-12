document.addEventListener('DOMContentLoaded', function () {
    const elements = document.querySelectorAll('.admin-lab-shortcode-name');

    elements.forEach(el => {
        el.style.cursor = 'pointer';

        el.addEventListener('click', function () {
            const shortcode = el.dataset.shortcode;

            const showMessage = () => {
                // Supprimer tout ancien message
                let oldMessage = el.querySelector('.copy-message');
                if (oldMessage) oldMessage.remove();

                // Créer et insérer le message
                const message = document.createElement('span');
                message.classList.add('copy-message');
                message.textContent = '✔ Copié !';
                el.appendChild(message);

                // Supprimer après 1.5 sec
                setTimeout(() => {
                    message.remove();
                }, 1500);
            };

            if (!navigator.clipboard) {
                // Fallback avec input invisible
                const tempInput = document.createElement('input');
                tempInput.value = shortcode;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                showMessage();
            } else {
                navigator.clipboard.writeText(shortcode)
                    .then(() => {
                        showMessage();
                    })
                    .catch(err => {
                        console.error('Clipboard error:', err);
                    });
            }
        });
    });
});
