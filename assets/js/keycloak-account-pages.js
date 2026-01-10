(function () {
    async function api(path, options = {}) {
      const url = (AdminLabKAP.rest || '').replace(/\/$/, '') + path;
      const res = await fetch(url, {
        ...options,
        credentials: 'same-origin', // Inclure les cookies de session
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': AdminLabKAP.nonce,
          ...(options.headers || {})
        }
      });
      const data = await res.json().catch(() => null);
      if (!data || data.ok !== true) {
        // ✅ Préserver la structure d'erreur si elle contient un code
        const error = data && data.error ? data.error : ('Erreur HTTP ' + res.status);
        const errorObj = new Error(typeof error === 'string' ? error : (error.message || 'Erreur inconnue'));
        if (typeof error === 'object' && error.code) {
          errorObj.code = error.code;
          errorObj.message = error.message || errorObj.message;
        }
        throw errorObj;
      }
      return data.data;
    }
  
    function setMsg(key, text, ok) {
      const el = document.querySelector(`.me5rine-lab-form-message[data-msg="${key}"]`);
      if (!el) return;
      if (!text || text.trim() === '') {
        el.style.display = 'none';
        el.textContent = '';
        el.className = 'me5rine-lab-form-message';
        return;
      }
      el.textContent = text;
      el.className = 'me5rine-lab-form-message ' + (ok ? 'me5rine-lab-form-message-success' : 'me5rine-lab-form-message-error');
      el.style.display = '';
    }
  
    async function renderConnections() {
      const wrap = document.getElementById('admin-lab-kap-connections');
      if (!wrap) return;
  
      try {
        const list = await api('/connections', { method: 'GET' });
        
        const providerCards = list.map(p => {
          let statusHtml = '';
          if (p.connected) {
            const statusText = p.external_username 
              ? `${p.label}\nConnected (${p.external_username})`
              : `${p.label}\nConnected`;
            statusHtml = `<span class="me5rine-lab-status me5rine-lab-status-success" title="${statusText.replace(/"/g, '&quot;')}">Connected</span>`;
          } else {
            statusHtml = `<span class="me5rine-lab-status me5rine-lab-status-warning" title="${p.label}\nNot Connected">Not Connected</span>`;
          }

          const btn = p.connected
            ? `<button class="me5rine-lab-form-button me5rine-lab-form-button-danger" data-action="disconnect" data-provider="${p.provider_slug}">${kapStrings.disconnect || 'Disconnect'}</button>`
            : `<button class="me5rine-lab-form-button" data-action="connect" data-provider="${p.provider_slug}">${kapStrings.connect || 'Connect'}</button>`;

          // Structure : nom, statut et bouton sur la même ligne, info compte en dessous
          const accountInfo = p.connected && p.external_username 
            ? `<div style="margin-top: 8px; font-size: 13px; color: var(--ph-text-light, #5D697D);">${p.external_username}</div>`
            : '';

          return `<div class="me5rine-lab-profile-container" style="padding: 16px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: ${p.connected && p.external_username ? '8px' : '0'};">
              <strong>${p.label}</strong>
              ${statusHtml}
              <div style="margin-left: auto;">
                ${btn}
              </div>
            </div>
            ${accountInfo}
          </div>`;
        }).join('');

        wrap.innerHTML = providerCards || '<p style="grid-column: 1 / -1;">' + (kapStrings.noProvidersConfigured || 'No providers configured.') + '</p>';
  
        wrap.querySelectorAll('button[data-action]').forEach(btn => {
          btn.addEventListener('click', async () => {
            const provider = btn.getAttribute('data-provider');
            const action = btn.getAttribute('data-action');
  
            btn.disabled = true;
            try {
              if (action === 'connect') {
                const out = await api('/connect', { method: 'POST', body: JSON.stringify({ provider_slug: provider }) });
                if (out.redirect) window.location.href = out.redirect;
              } else {
                const result = await api('/disconnect', { method: 'POST', body: JSON.stringify({ provider_slug: provider }) });
                
                // ✅ Rediriger vers la page de profil avec la notice de succès
                if (result && result.redirect) {
                  // Construire l'URL de redirection avec les paramètres de notice
                  const currentUrl = new URL(window.location.href);
                  currentUrl.searchParams.set('kap', 'disconnected');
                  currentUrl.searchParams.set('provider', provider);
                  // Supprimer les anciens paramètres d'erreur/succès s'ils existent
                  currentUrl.searchParams.delete('error');
                  currentUrl.searchParams.delete('error_description');
                  window.location.href = currentUrl.toString();
                } else {
                  await renderConnections();
                }
              }
            } catch (e) {
              // ✅ Gérer les erreurs avec codes spécifiques
              let errorMsg = e.message || 'Une erreur est survenue';
              let errorCode = null;
              
              // Si l'erreur a un code, l'extraire
              if (e.code) {
                errorCode = e.code;
              } else if (typeof errorMsg === 'object' && errorMsg.code) {
                errorCode = errorMsg.code;
                errorMsg = errorMsg.message || errorMsg;
              }
              
              // Si c'est une erreur de type last_provider_no_password, afficher un message spécial
              if (errorCode === 'last_provider_no_password') {
                // Afficher une notice warning dans la page (au-dessus de la liste des connexions)
                const wrap = document.getElementById('admin-lab-kap-connections');
                const container = wrap ? wrap.closest('.me5rine-lab-profile-container') : null;
                const target = container || wrap;
                
                if (target) {
                  // Vérifier si une notice existe déjà
                  const existingNotice = target.querySelector('.me5rine-lab-form-message-warning[data-kap-warning]');
                  if (existingNotice) {
                    existingNotice.remove();
                  }
                  
                  const warningDiv = document.createElement('div');
                  warningDiv.className = 'me5rine-lab-form-message me5rine-lab-form-message-warning';
                  warningDiv.setAttribute('data-kap-warning', 'last-provider');
                  const messageText = typeof errorMsg === 'object' ? (errorMsg.message || errorMsg) : errorMsg;
                  const setPasswordText = 'You can set a password in the "My Account" tab to be able to disconnect this provider.';
                  warningDiv.innerHTML = '<p><strong>⚠️ ' + (kapStrings.attention || 'Attention') + ':</strong> ' + messageText + '</p><p><small>' + (kapStrings.setPasswordHint || setPasswordText) + '</small></p>';
                  
                  // Insérer au début du conteneur (après le titre)
                  if (container) {
                    const title = container.querySelector('h2');
                    if (title && title.nextSibling) {
                      container.insertBefore(warningDiv, title.nextSibling);
                    } else {
                      container.insertBefore(warningDiv, container.firstChild);
                    }
                  } else {
                    wrap.insertBefore(warningDiv, wrap.firstChild);
                  }
                  
                  // Scroll vers la notice
                  warningDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                  
                  // Supprimer la notice après 15 secondes
                  setTimeout(() => {
                    if (warningDiv.parentNode) {
                      warningDiv.parentNode.removeChild(warningDiv);
                    }
                  }, 15000);
                } else {
                  alert(typeof errorMsg === 'object' ? (errorMsg.message || errorMsg) : errorMsg);
                }
              } else {
                alert(typeof errorMsg === 'object' ? (errorMsg.message || errorMsg) : errorMsg);
              }
            } finally {
              btn.disabled = false;
            }
          });
        });
  
      } catch (e) {
        wrap.innerHTML = `<p class="me5rine-lab-form-message me5rine-lab-form-message-error">${e.message || (kapStrings.error || 'An error occurred')}</p>`;
      }
    }
  
    function updateEmailStatus(email, emailVerified) {
      const statusBadge = document.getElementById('admin-lab-kap-email-status-badge');
      const resendBtn = document.getElementById('admin-lab-kap-resend-verification');
      
      if (!statusBadge) return;
      
      if (emailVerified) {
        // ✅ Email vérifié : badge vert avec le texte "Verified"
        const verifiedText = kapStrings.emailVerified || 'Verified';
        statusBadge.innerHTML = '<span class="me5rine-lab-status me5rine-lab-status-success" title="' + verifiedText.replace(/"/g, '&quot;') + '">' + verifiedText + '</span>';
        statusBadge.style.display = '';
        if (resendBtn) resendBtn.style.display = 'none';
      } else {
        // ⚠️ Email non vérifié : badge warning avec le texte "Not Verified"
        const notVerifiedText = kapStrings.emailNotVerified || 'Not Verified';
        statusBadge.innerHTML = '<span class="me5rine-lab-status me5rine-lab-status-warning" title="' + notVerifiedText.replace(/"/g, '&quot;') + '">' + notVerifiedText + '</span>';
        statusBadge.style.display = '';
        if (resendBtn) resendBtn.style.display = 'inline-block';
      }
    }

    async function loadProfileForm() {
      const profileForm = document.getElementById('admin-lab-kap-profile-form');
      const emailForm = document.getElementById('admin-lab-kap-email-form');
      const passwordForm = document.getElementById('admin-lab-kap-password-form');
      
      try {
        const p = await api('/profile', { method: 'GET' });
        
        // Charger les données du profil
        if (profileForm) {
          const firstNameInput = document.getElementById('admin-lab-kap-first-name');
          const lastNameInput = document.getElementById('admin-lab-kap-last-name');
          const nicknameInput = document.getElementById('admin-lab-kap-nickname');
          if (firstNameInput) firstNameInput.value = p.first_name || '';
          if (lastNameInput) lastNameInput.value = p.last_name || '';
          if (nicknameInput) nicknameInput.value = p.nickname || '';
        }
        
        // Charger l'email et son statut
        if (emailForm) {
          const emailInput = document.getElementById('admin-lab-kap-email-input');
          if (emailInput) {
            emailInput.value = p.email || '';
          }
          updateEmailStatus(p.email, p.email_verified);
        }
        
        // Le formulaire de mot de passe ne nécessite plus de logique conditionnelle
        // On demande toujours une ré-authentification avant de changer le mot de passe
      } catch (e) {
        setMsg('profile', e.message, false);
      }

      // Formulaire profil
      if (profileForm) {
        profileForm.addEventListener('submit', async (ev) => {
          ev.preventDefault();
          setMsg('profile', kapStrings.saving || 'Saving…', true);
          try {
            const firstNameInput = document.getElementById('admin-lab-kap-first-name');
            const lastNameInput = document.getElementById('admin-lab-kap-last-name');
            const nicknameInput = document.getElementById('admin-lab-kap-nickname');
            await api('/profile', {
              method: 'POST',
              body: JSON.stringify({
                first_name: firstNameInput ? firstNameInput.value : '',
                last_name: lastNameInput ? lastNameInput.value : '',
                nickname: nicknameInput ? nicknameInput.value : '',
              })
            });
            setMsg('profile', kapStrings.profileUpdated || 'Profile updated.', true);
          } catch (e) {
            const errorMsg = typeof e.message === 'object' ? (e.message.message || e.message) : e.message;
            setMsg('profile', errorMsg, false);
          }
        });
      }

      // Formulaire email
      if (emailForm) {
        emailForm.addEventListener('submit', async (ev) => {
          ev.preventDefault();
          setMsg('email', kapStrings.updating || 'Updating…', true);
          try {
            const emailInput = document.getElementById('admin-lab-kap-email-input');
            const returnUrl = window.location.href;

            // ✅ Appeler init_email_change qui demande toujours une ré-authentification
            const result = await api('/email/init-change', {
              method: 'POST',
              body: JSON.stringify({
                email: emailInput.value,
                return_url: returnUrl,
              })
            });

            // ✅ Si ré-authentification requise, rediriger vers Keycloak
            if (result.requires_reauth && result.redirect) {
              window.location.href = result.redirect;
              return;
            }

            // Ne devrait normalement pas arriver ici (toujours requires_reauth = true)
            setMsg('email', result.message || (kapStrings.emailUpdated || 'Email updated. A verification email has been sent.'), true);
            // Recharger le profil pour mettre à jour le statut
            setTimeout(async () => {
              try {
                const p = await api('/profile', { method: 'GET' });
                updateEmailStatus(p.email, p.email_verified);
              } catch (e) {
                // Ignorer les erreurs de rechargement
              }
            }, 1000);
          } catch (e) {
            const errorMsg = typeof e.message === 'object' ? (e.message.message || e.message) : e.message;
            setMsg('email', errorMsg, false);
          }
        });
      }

      // Bouton renvoyer email de vérification
      const resendBtn = document.getElementById('admin-lab-kap-resend-verification');
      if (resendBtn) {
        resendBtn.addEventListener('click', async () => {
          resendBtn.disabled = true;
          setMsg('email', kapStrings.sending || 'Sending…', true);
          try {
            const result = await api('/email/resend-verification', {
              method: 'POST',
            });
            setMsg('email', result.message || (kapStrings.verificationEmailSent || 'Verification email sent successfully.'), true);
          } catch (e) {
            const errorMsg = typeof e.message === 'object' ? (e.message.message || e.message) : e.message;
            setMsg('email', errorMsg, false);
          } finally {
            resendBtn.disabled = false;
          }
        });
      }
    }

    async function bindPasswordForm() {
      const form = document.getElementById('admin-lab-kap-password-form');
      if (!form) return;

      form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        setMsg('password', kapStrings.changing || 'Changing…', true);
        try {
          const passwordInput = document.getElementById('admin-lab-kap-password');
          const passwordConfirmInput = document.getElementById('admin-lab-kap-password-confirm');
          
          // Récupérer l'URL de retour (page actuelle)
          const returnUrl = window.location.href;

          // ✅ Appeler init_password_change qui demandera toujours une ré-authentification
          const result = await api('/password/init-change', {
            method: 'POST',
            body: JSON.stringify({
              password: passwordInput?.value || '',
              password_confirm: passwordConfirmInput?.value || '',
              return_url: returnUrl,
            })
          });

          // ✅ Ré-authentification toujours requise, rediriger vers Keycloak
          if (result.requires_reauth && result.redirect) {
            window.location.href = result.redirect;
            return;
          }

          // Ne devrait normalement pas arriver ici (toujours requires_reauth = true)
          // Nettoyer les champs
          if (passwordInput) passwordInput.value = '';
          if (passwordConfirmInput) passwordConfirmInput.value = '';
          
          setMsg('password', kapStrings.passwordChanged || 'Password changed.', true);
        } catch (e) {
          const errorMsg = typeof e.message === 'object' ? (e.message.message || e.message) : e.message;
          setMsg('password', errorMsg, false);
        }
      });
    }
  
    document.addEventListener('DOMContentLoaded', function () {
      renderConnections();
      loadProfileForm();
      bindPasswordForm();
    });
  })();
