// File: js/giveaway-rafflepress-iframe-content.js

document.addEventListener('DOMContentLoaded', function () {
    const iframe = document.querySelector('iframe.rafflepress-iframe');
    if (!iframe) return;

    const userName = iframe.dataset.userName || '';
    const userEmail = iframe.dataset.userEmail || '';
    const partnerName = iframe.dataset.partnerName || '';
    const websiteName = iframe.dataset.websiteName || 'Me5rine LAB';
    const prizes = iframe.dataset.prizes ? iframe.dataset.prizes.split('|') : [];

    let iframeUrl = iframe.src;

    if (userName && userEmail) {
        iframeUrl = addQueryParams(iframeUrl, {
            'rp-name': userName,
            'rp-email': userEmail
        });
    }

    iframe.src = iframeUrl;

    // ⚠ Attendre une microseconde pour ne pas rater l'événement load
    setTimeout(() => {
        iframe.addEventListener('load', () => {
            console.log('[Me5rine LAB] iframe loaded, applying customizations...');
            runIframeCustomization(iframe, { userName, userEmail, partnerName, websiteName, prizes });
        });
    }, 0);

    function addQueryParams(url, params) {
        const urlObj = new URL(url);
        Object.keys(params).forEach(key => urlObj.searchParams.set(key, params[key]));
        return urlObj.toString();
    }
});

function getColor(slug, fallback = '') {
    return (typeof adminlabColors !== 'undefined' && adminlabColors[slug]) ? adminlabColors[slug] : fallback;
}

function runIframeCustomization(iframe, data) {
    const { userName, userEmail, partnerName, websiteName, prizes } = data;

    try {
        const iframeDoc = iframe.contentWindow.document;

        // 🔹 Bloc login personnalisé
        const loginBlock = iframeDoc.querySelector('#rafflepress-giveaway-login');
        if (loginBlock && typeof adminlabTranslations !== 'undefined') {
            let prizeMessage = '';
            if (prizes.length === 1) {
                prizeMessage = adminlabTranslations.prizeMessage.single.replace('%s', prizes[0]);
            } else if (prizes.length > 1) {
                prizeMessage = adminlabTranslations.prizeMessage.multiple.replace('%s', prizes.join(', '));
            } else {
                prizeMessage = adminlabTranslations.prizeMessage.none;
            }

            if (userEmail && userName) {
                loginBlock.innerHTML = `
                    <div class="admin-lab-welcome-block">
                    <p>${adminlabTranslations.greeting.replace('%s', `<strong>${userName}</strong>`)}</p>
                    <p>${prizeMessage}</p>
                    </div>
                `;
            } else {
                loginBlock.innerHTML = `
                    <div class="admin-lab-login-block" style="
                        background-color: ${getColor('3d5ef52', '#F9FAFB')};
                        border-radius: 12px;
                        padding: 5px;
                        text-align: center;
                        max-width: 400px;
                        margin: 5px auto;
                        font-family: 'Segoe UI', sans-serif;
                    ">
                        <p style="font-size: 16px; font-weight: 500; margin-bottom: 10px; color: #374151;">
                            ${adminlabTranslations.prizeMessage.login}
                        </p>
                        <a href="${iframe.dataset.loginUrl}" target="_parent" style="
                            display: inline-block;
                            margin: 8px 10px;
                            padding: 10px 20px;
                            font-size: 13px;
                            font-weight: 600;
                            text-decoration: none;
                            border-radius: 5px;
                            background-color: ${getColor('primary', '#02395A')};
                            color: ${getColor('338f618', '#FFFFFF')} !important;
                            border: none;
                            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
                            cursor: pointer;
                        ">
                            ${adminlabTranslations.prizeMessage.loginBtn}
                        </a>
                        <a href="${iframe.dataset.registerUrl}" target="_parent" style="
                            display: inline-block;
                            margin: 8px 10px;
                            padding: 10px 20px;
                            font-size: 13px;
                            font-weight: 600;
                            text-decoration: none;
                            border-radius: 5px;
                            background-color: ${getColor('primary', '#02395A')};
                            color: ${getColor('338f618', '#FFFFFF')} !important;
                            border: none;
                            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
                            cursor: pointer;
                        ">
                            ${adminlabTranslations.prizeMessage.registerBtn}
                        </a>
                    </div>
                `;

                const hoverStyle = iframeDoc.createElement('style');
                hoverStyle.textContent = `
                    .admin-lab-login-block a:hover {
                        background-color: ${getColor('secondary', '#0485C8')} !important;
                        color: ${getColor('338f618', '#FFFFFF')} !important;
                    }
                `;
                iframeDoc.head.appendChild(hoverStyle);
            }
        }

        // 🔹 Bloc remplacement de l’action automatique
        const autoEntryWrapper = iframeDoc.querySelector('.rafflepress-action-separator .btn-action-automatic-entry');
        if (autoEntryWrapper && typeof adminlabTranslations !== 'undefined') {
            const parentBlock = autoEntryWrapper.closest('.rafflepress-action-separator');
            if (parentBlock && !parentBlock.classList.contains('admin-lab-replaced')) {
                parentBlock.classList.add('admin-lab-replaced');
        
                const separator = iframeDoc.createElement('div');
                separator.innerHTML = `
                    <hr style="margin: 20px 0;">
                    <p style="text-align:center; font-weight:bold; font-size: 16px; color: #2d2d2d;">
                        ${adminlabTranslations.separator}
                    </p>
                `;
        
                parentBlock.replaceWith(separator);
            }
        }

        // 🔹 Ajout d’un style personnalisé pour l’action Discord
        const discordStyle = iframeDoc.createElement('style');
        discordStyle.textContent = `
            .icon-discord, .btn.btn-primary.btn-visit-a-page.btn-block.btn-discord {
                background-color: #36393e !important;
                border-color: #36393e !important;
                color: ${getColor('338f618', '#FFFFFF')} !important;
            }

            .btn-discord i.fa-discord {
                margin-right: 5px;
            }

            .icon-discord::before {
                font-family: "Font Awesome 6 Brands" !important;
                font-weight: 400 !important;
                content: "\\f392" !important;
                font-style: normal !important;
            }
        `;
        iframeDoc.head.appendChild(discordStyle);

        // 🔹 Style personnalisé pour l’action Bluesky
        const blueskyStyle = iframeDoc.createElement('style');
        blueskyStyle.textContent = `
            .icon-bluesky, .btn.btn-primary.btn-visit-a-page.btn-block.btn-bluesky {
                background-color: #1185fe !important;
                border-color: #1185fe !important;
                color: ${getColor('338f618', '#FFFFFF')} !important;
            }
            .btn-bluesky i.fa-bluesky {
                margin-right: 5px;
            }

            .icon-bluesky::before {
                font-family: "Font Awesome 6 Brands" !important;
                font-weight: 400 !important;
                content: "\\e671" !important;
                font-style: normal !important;
            }
        `;
        iframeDoc.head.appendChild(blueskyStyle);

        // 🔹 Style personnalisé pour l’action Threads
        const threadsStyle = iframeDoc.createElement('style');
        threadsStyle.textContent = `
            .icon-threads, .btn.btn-primary.btn-visit-a-page.btn-block.btn-threads {
                background-color: #000000 !important;
                border-color: #000000 !important;
                color: ${getColor('338f618', '#FFFFFF')} !important;
            }

            .btn-threads i.fa-threads {
                margin-right: 5px;
            }
                
            .icon-threads::before {
                font-family: "Font Awesome 6 Brands" !important;
                font-weight: 400 !important;
                content: "\\e618" !important;
                font-style: normal !important;
            }
        `;
        iframeDoc.head.appendChild(threadsStyle);

        const faLink = iframeDoc.createElement('link');
        faLink.rel = 'stylesheet';
        faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css';
        faLink.media = 'all';
        iframeDoc.head.appendChild(faLink);

        const fixIconAlignment = iframeDoc.createElement('style');
        fixIconAlignment.textContent = `
            .rafflepress-entry-option-icon {
                display: flex;
                align-items: center;
                justify-content: center;
                padding-top: 2px;
            }
        `;
        iframeDoc.head.appendChild(fixIconAlignment);

        const discordWrappers = iframeDoc.querySelectorAll('.rafflepress-action-discord, .rafflepress-action-discord_2');
        discordWrappers.forEach(wrapper => customizeVisitPageAction(wrapper, 'discord', iframe, data));

        const blueskyWrappers = iframeDoc.querySelectorAll('.rafflepress-action-bluesky');
        blueskyWrappers.forEach(wrapper => customizeVisitPageAction(wrapper, 'bluesky', iframe, data));

        const threadsWrappers = iframeDoc.querySelectorAll('.rafflepress-action-threads');
        threadsWrappers.forEach(wrapper => customizeVisitPageAction(wrapper, 'threads', iframe, data));

        // ➤ Nettoyage des classes de chargement
        iframe.classList.remove('rafflepress-iframe-loaded');
        if (iframe.parentElement) {
            iframe.parentElement.classList.remove('rafflepress-iframe-loaded');
            iframe.parentElement.classList.remove('loading');
        }

        // ➤ Affiche proprement l’iframe une fois la personnalisation terminée
        iframe.style.opacity = '1';
        iframe.style.pointerEvents = 'auto';

        iframe.classList.add('rafflepress-iframe-ready');

    } catch (e) {
        console.warn('[Me5rine LAB] Erreur iframe RafflePress :', e);
    }
}

function customizeVisitPageAction(wrapper, type, iframe, data) {
    const { partnerName, websiteName } = data;
    const button = wrapper.querySelector('.btn-action-visit-a-page');
    const label = wrapper.querySelector('.rafflepress-action-text');
    const icon = wrapper.querySelector('.rafflepress-entry-option-icon');

    if (button) button.classList.add(`btn-${type}`);
    if (icon) icon.classList.add(`icon-${type}`);

    let entryId = wrapper.getAttribute('data-entry-id') || '';
    if (!entryId) {
        const entryEl = wrapper.querySelector('[data-entry-id]');
        entryId = entryEl?.getAttribute('data-entry-id') || '';
    }
    if (!entryId) {
        if (wrapper.classList.contains(`rafflepress-action-${type}_2`)) {
            entryId = `${type}_2`;
        } else if (wrapper.classList.contains(`rafflepress-action-${type}`)) {
            entryId = `${type}`;
        }
    }

    console.log(`🔎 Detected entry ID (${type}):`, entryId, wrapper);

    let displayName = partnerName || '';
    if (entryId === `${type}_2`) {
        displayName = websiteName || 'Me5rine LAB';
    }

    if (label && typeof adminlabTranslations !== 'undefined') {
        const key = `${type}JoinLabel`;
        if (adminlabTranslations[key]) {
            label.textContent = adminlabTranslations[key].replace('%s', displayName);
        }
    }

    if (button) {
        button.addEventListener('click', () => {
            setTimeout(() => {
                const actionArea = wrapper.querySelector('.rafflepress-action');
                if (actionArea) {
                    const intro = actionArea.querySelector('p');
                    if (intro) {
                        intro.textContent = adminlabTranslations?.[`${type}JoinText`] || `To get credit for this entry, join us on ${type.charAt(0).toUpperCase() + type.slice(1)}.`;
                    }

                    const linkBtn = actionArea.querySelector('a.btn-visit-a-page');
                    if (linkBtn) {
                        const iconEl = linkBtn.querySelector('i.fas.fa-external-link-alt');
                        if (iconEl) {
                            iconEl.className = `fa-brands fa-${type}`;
                        }

                        const textNode = [...linkBtn.childNodes].find(n => n.nodeType === Node.TEXT_NODE);
                        if (textNode) {
                            textNode.nodeValue = ' ' + (adminlabTranslations?.[`${type}JoinBtn`] || `Join ${type.charAt(0).toUpperCase() + type.slice(1)}`);
                        }

                        linkBtn.classList.add(`btn-${type}`);
                    }
                }
            }, 0);
        });
    }
}
