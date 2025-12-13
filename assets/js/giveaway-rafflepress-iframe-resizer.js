// File: js/giveaway-rafflepress-iframe-resizer.js

jQuery(document).ready(function ($) {
    const iframe = $('iframe.rafflepress-iframe');

    if (!iframe.length) return;

    let resizerInitialized = false;
    let scrollTimer = null;
    let resizeTimeout = null;

    // Détecter le scroll et désactiver temporairement le resize
    let lastScrollTop = 0;
    let isScrolling = false;
    let scrollSpeed = 0;
    let lastScrollTime = Date.now();

    // Utiliser requestAnimationFrame pour un meilleur contrôle
    let ticking = false;
    
    function handleScroll() {
        const currentScrollTop = $(window).scrollTop();
        const currentTime = Date.now();
        const timeDelta = currentTime - lastScrollTime;
        const scrollDelta = Math.abs(currentScrollTop - lastScrollTop);
        
        // Calculer la vitesse de scroll (px/ms)
        if (timeDelta > 0) {
            scrollSpeed = scrollDelta / timeDelta;
        }
        
        // Détecter si on scroll rapidement
        const isFastScroll = scrollSpeed > 0.3; // Seuil réduit pour détecter plus tôt
        const isVeryFastScroll = scrollSpeed > 1.5; // Scroll très rapide (seuil réduit)
        
        // Pour les scrolls très rapides, désactiver immédiatement même pour de petits déplacements
        // Pour les scrolls rapides normaux, attendre un déplacement plus significatif
        const shouldDisable = isVeryFastScroll ? (scrollDelta > 3) : (scrollDelta > 10 && isFastScroll);
        
        if (shouldDisable) {
            if (!isScrolling) {
                isScrolling = true;
                // Désactiver autoResize pendant le scroll rapide
                if (iframe[0].iFrameResizer && iframe[0].iFrameResizer.autoResize) {
                    iframe[0].iFrameResizer.autoResize = false;
                }
            }
        }
        
        lastScrollTop = currentScrollTop;
        lastScrollTime = currentTime;
        ticking = false;

        // Réactiver le resize après la fin du scroll avec des délais adaptés
        // Délais plus longs pour les scrolls très rapides pour éviter les rebonds
        const reactivationDelay = isVeryFastScroll ? 800 : (isFastScroll ? 400 : 200);
        
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(function() {
            isScrolling = false;
            scrollSpeed = 0;
            
            // Réactiver autoResize après le scroll
            if (iframe[0].iFrameResizer && !iframe[0].iFrameResizer.autoResize) {
                iframe[0].iFrameResizer.autoResize = true;
            }
        }, reactivationDelay);
    }

    $(window).on('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(handleScroll);
            ticking = true;
        }
    });

    // APPROCHE SIMPLIFIÉE : Désactiver autoResize seulement pendant le scroll actif
    // Pas de blocage complexe du curseur qui interfère avec le comportement natif

    // Fonction pour forcer un resize de l'iframe avec plusieurs tentatives progressives
    function forceResize(immediate = false) {
        if (!iframe[0].iFrameResizer) return;
        
        // Ne pas forcer le resize si on scroll
        if (isScrolling || iframeScrollingLocal) {
            return;
        }
        
        // Réactiver temporairement le resize si désactivé
        const wasDisabled = !iframe[0].iFrameResizer.autoResize;
        if (wasDisabled) {
            iframe[0].iFrameResizer.autoResize = true;
        }
        
        if (immediate) {
            // Resize immédiat
            iframe[0].iFrameResizer.resize();
        } else {
            // Resize progressif : immédiat, puis après 200ms, puis après 500ms
            iframe[0].iFrameResizer.resize();
            setTimeout(function() {
                if (iframe[0].iFrameResizer && !isScrolling) {
                    iframe[0].iFrameResizer.resize();
                }
            }, 200);
            setTimeout(function() {
                if (iframe[0].iFrameResizer && !isScrolling) {
                    iframe[0].iFrameResizer.resize();
                }
            }, 500);
        }
        
        // Remettre l'état précédent après le resize (seulement si on scroll toujours)
        if (wasDisabled && isScrolling) {
            setTimeout(function() {
                if (iframe[0].iFrameResizer && isScrolling) {
                    iframe[0].iFrameResizer.autoResize = false;
                }
            }, 600);
        }
    }

    // Observer les changements dans l'iframe pour détecter l'ouverture/fermeture d'éléments
    function setupIframeObserver() {
        try {
            const iframeDoc = iframe[0].contentDocument || iframe[0].contentWindow.document;
            const iframeWindow = iframe[0].contentWindow;
            if (!iframeDoc || !iframeDoc.body) return;

            let lastBodyHeight = iframeDoc.body.offsetHeight;
            let heightCheckInterval = null;
            let iframeScrollTimer = null;
            let lastIframeScrollTop = 0;
            // iframeScrolling sera déclaré dans le scope de la fonction
            let iframeScrollingLocal = false;

            // Observer les changements de hauteur du body avec un intervalle
            // autoResize gérera le resize, on vérifie juste que tout est OK
            function checkHeightChange() {
                // Ne pas vérifier pendant le scroll
                if (!iframeDoc.body || isScrolling || iframeScrollingLocal) return;
                
                // S'assurer que autoResize est activé si on ne scroll pas
                if (iframe[0].iFrameResizer && !iframe[0].iFrameResizer.autoResize) {
                    iframe[0].iFrameResizer.autoResize = true;
                }
            }

            // Observer les changements de DOM
            const observer = new MutationObserver(function(mutations) {
                let shouldResize = false;
                
                mutations.forEach(function(mutation) {
                    // Détecter les changements de style (display) sur les divs contenant rafflepress-action
                    if (mutation.type === 'attributes') {
                        const target = mutation.target;
                        
                        // Détecter les changements de style (display) sur les divs d'accordéon
                        if (mutation.attributeName === 'style' && target.style) {
                            const displayValue = target.style.display;
                            // Si c'est un div qui contient ou est proche d'un rafflepress-action
                            if (target.querySelector && (
                                target.querySelector('.rafflepress-action') ||
                                target.classList.contains('rafflepress-action') ||
                                target.closest('.rafflepress-action')
                            )) {
                                // Si le display change (none -> block ou block -> none)
                                if (displayValue === 'none' || displayValue === 'block' || displayValue === '') {
                                    shouldResize = true;
                                }
                            }
                        }
                        
                        // Détecter les changements de classes ou d'attributs sur les éléments rafflepress-action
                        if (target.classList && (
                            target.classList.contains('rafflepress-action') ||
                            target.closest('.rafflepress-action') ||
                            target.classList.contains('rafflepress-entry-option') ||
                            target.closest('.rafflepress-entry-option') ||
                            target.classList.contains('btn-action') ||
                            target.closest('.btn-action')
                        )) {
                            shouldResize = true;
                        }
                    }
                    // Détecter les ajouts/suppressions de nœuds
                    if (mutation.type === 'childList') {
                        const addedNodes = Array.from(mutation.addedNodes);
                        const removedNodes = Array.from(mutation.removedNodes);
                        const allNodes = [...addedNodes, ...removedNodes];
                        if (allNodes.some(node => {
                            if (node.nodeType === 1) { // Element node
                                return node.classList && (
                                    node.classList.contains('rafflepress-action') ||
                                    node.closest('.rafflepress-action') ||
                                    node.classList.contains('rafflepress-entry-option') ||
                                    node.closest('.rafflepress-entry-option') ||
                                    node.classList.contains('btn-action') ||
                                    node.closest('.btn-action')
                                );
                            }
                            return false;
                        })) {
                            shouldResize = true;
                        }
                    }
                });

                if (shouldResize) {
                    // Ne pas resize pendant le scroll
                    if (isScrolling || iframeScrollingLocal) return;
                    
                    // Si autoResize est activé, il gérera le resize automatiquement
                    // On force juste un resize immédiat pour être sûr que ça se fait rapidement
                    if (iframe[0].iFrameResizer && !isScrolling && !iframeScrollingLocal) {
                        // S'assurer que autoResize est activé
                        if (!iframe[0].iFrameResizer.autoResize) {
                            iframe[0].iFrameResizer.autoResize = true;
                        }
                        // Forcer un resize immédiat pour les changements d'accordéons
                        iframe[0].iFrameResizer.resize();
                    }
                }
            });

            // Observer les changements dans le body
            // Observer spécifiquement les changements de style pour détecter les accordéons
            observer.observe(iframeDoc.body, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'style', 'aria-expanded', 'aria-hidden'],
                attributeOldValue: false
            });

            // Vérifier la hauteur toutes les 200ms pour détecter les changements rapidement
            heightCheckInterval = setInterval(checkHeightChange, 200);

            // Détecter le scroll à l'intérieur de l'iframe
            if (iframeWindow) {
                let iframeScrollTicking = false;
                let lastIframeScrollTime = Date.now();
                let iframeScrollSpeed = 0;
                
                function handleIframeScroll() {
                    const currentScrollTop = iframeWindow.pageYOffset || iframeDoc.documentElement.scrollTop;
                    const currentTime = Date.now();
                    const timeDelta = currentTime - lastIframeScrollTime;
                    const scrollDelta = Math.abs(currentScrollTop - lastIframeScrollTop);
                    
                    // Calculer la vitesse de scroll dans l'iframe
                    if (timeDelta > 0) {
                        iframeScrollSpeed = scrollDelta / timeDelta;
                    }
                    
                    const isFastIframeScroll = iframeScrollSpeed > 0.3; // Seuil réduit
                    const isVeryFastIframeScroll = iframeScrollSpeed > 1.5; // Seuil réduit
                    
                    // Pour les scrolls très rapides, désactiver immédiatement même pour de petits déplacements
                    // Pour les scrolls rapides normaux, attendre un déplacement plus significatif
                    const shouldDisableIframe = isVeryFastIframeScroll ? (scrollDelta > 3) : (scrollDelta > 10 && isFastIframeScroll);
                    
                    if (shouldDisableIframe) {
                        if (!iframeScrollingLocal) {
                            iframeScrollingLocal = true;
                            // Désactiver autoResize pendant le scroll rapide
                            if (iframe[0].iFrameResizer && iframe[0].iFrameResizer.autoResize) {
                                iframe[0].iFrameResizer.autoResize = false;
                            }
                        }
                    }
                    
                    lastIframeScrollTop = currentScrollTop;
                    lastIframeScrollTime = currentTime;
                    iframeScrollTicking = false;
                    
                    // Réactiver après la fin du scroll dans l'iframe avec des délais adaptés
                    // Délais plus longs pour les scrolls très rapides
                    const iframeReactivationDelay = isVeryFastIframeScroll ? 800 : (isFastIframeScroll ? 400 : 200);
                    
                    clearTimeout(iframeScrollTimer);
                    iframeScrollTimer = setTimeout(function() {
                        iframeScrollingLocal = false;
                        iframeScrollSpeed = 0;
                        // Réactiver autoResize après le scroll
                        if (iframe[0].iFrameResizer && !iframe[0].iFrameResizer.autoResize) {
                            iframe[0].iFrameResizer.autoResize = true;
                        }
                    }, iframeReactivationDelay);
                }
                
                // Écouter le scroll dans l'iframe
                iframeWindow.addEventListener('scroll', function() {
                    if (!iframeScrollTicking) {
                        iframeWindow.requestAnimationFrame(handleIframeScroll);
                        iframeScrollTicking = true;
                    }
                }, { passive: true });
            }

            // Écouter les clics sur les boutons d'action (accordéons)
            iframeDoc.addEventListener('click', function(e) {
                const target = e.target;
                // Détecter les clics sur les boutons d'action (btn-action)
                if (target.classList.contains('btn-action') || 
                    target.closest('.btn-action') ||
                    target.closest('.rafflepress-action') || 
                    target.closest('.rafflepress-entry-option') ||
                    target.classList.contains('btn-action-visit-a-page') ||
                    target.closest('.btn-action-visit-a-page') ||
                    target.closest('[data-entry-id]')) {
                    // Ne pas resize si le curseur est dans l'iframe ou pendant le scroll
                    // MAIS pour les clics, on veut quand même resize car c'est une action utilisateur
                    if (isScrolling || iframeScrollingLocal) return;
                    
                    // Pour les clics, forcer un resize après un court délai
                    if (iframe[0].iFrameResizer && !isScrolling && !iframeScrollingLocal) {
                        // S'assurer que autoResize est activé
                        if (!iframe[0].iFrameResizer.autoResize) {
                            iframe[0].iFrameResizer.autoResize = true;
                        }
                        // Petit délai pour laisser le DOM se mettre à jour, puis resize
                        setTimeout(function() {
                            if (iframe[0].iFrameResizer && !isScrolling && !iframeScrollingLocal) {
                                iframe[0].iFrameResizer.resize();
                            }
                        }, 100);
                    }
                }
            }, true);

            // Utiliser ResizeObserver si disponible pour une meilleure détection
            // autoResize gérera le resize, on s'assure juste qu'il est activé
            if (typeof ResizeObserver !== 'undefined' && iframeDoc.body) {
                const resizeObserver = new ResizeObserver(function(entries) {
                    // Ignorer pendant le scroll
                    if (isScrolling || iframeScrollingLocal) return;
                    
                    // S'assurer que autoResize est activé
                    if (iframe[0].iFrameResizer && !iframe[0].iFrameResizer.autoResize) {
                        iframe[0].iFrameResizer.autoResize = true;
                    }
                });
                resizeObserver.observe(iframeDoc.body);
            }

        } catch (e) {
            console.warn('[Me5rine LAB] Impossible d\'observer l\'iframe:', e);
        }
    }

    function initIframeResizer() {
        // Éviter la double initialisation
        if (resizerInitialized) return;
        if (iframe[0].iFrameResizer) return;

        resizerInitialized = true;

        // APPROCHE HYBRIDE : autoResize activé pour le resize automatique
        // Mais désactivé pendant le scroll pour éviter les rebonds
        iFrameResize({
            log: false,
            checkOrigin: false,
            heightCalculationMethod: 'max', // Utilise la plus grande valeur pour capturer tout le contenu
            minHeight: 900,
            tolerance: 5, // Tolérance modérée pour éviter les micro-ajustements tout en restant précis
            resizeFrom: 'parent', // Le parent contrôle le resize
            autoResize: true, // ACTIVÉ - resize automatique quand pas de scroll
            scrolling: false, // Désactiver le scroll dans l'iframe pour forcer le resize
            sizeWidth: false, // Ne pas redimensionner la largeur
            onMessage: function(message) {},
            onResized: function(messageData) {
                // Callback optionnel pour le debug
            }
        }, iframe[0]);

        // Configurer l'observer après l'initialisation du resizer
        setTimeout(setupIframeObserver, 500);
    }

    // Si l'iframe est déjà chargé, initialiser immédiatement
    if (iframe[0].contentDocument && iframe[0].contentDocument.readyState === 'complete') {
        setTimeout(initIframeResizer, 100);
    } else {
        // Attendre que l'iframe soit chargé avant d'initialiser le resizer
        iframe.on('load', function() {
            // Petit délai pour s'assurer que le contenu est prêt
            setTimeout(initIframeResizer, 100);
        });
    }

    // Écouter aussi les messages de l'iframe (si RafflePress en envoie)
    window.addEventListener('message', function(event) {
        // Vérifier que le message vient de l'iframe RafflePress
        if (iframe.length && event.source === iframe[0].contentWindow) {
            // Si c'est un message de changement de hauteur ou d'action
            if (event.data && (event.data.type === 'resize' || event.data.action === 'expand' || event.data.action === 'collapse')) {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(forceResize, 300);
            }
        }
    });
});