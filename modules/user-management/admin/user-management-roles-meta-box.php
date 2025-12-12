<?php
// File: modules/user-management/admin/user-management-roles-meta-box.php

if (!defined('ABSPATH')) exit;

// Supprime l'action native qui écrase les rôles multiples
add_action('admin_init', function () {
    remove_action('edit_user_profile_update', 'edit_user');
});

// Affiche les rôles de l'utilisateur avec case à cocher
function admin_lab_render_um_roles_field($user) {
    if (!current_user_can('edit_users')) return;

    $user_roles  = (array) $user->roles;
    $all_roles   = wp_roles()->roles;
    $grouped     = [
        'Ultimate Member' => [],
        'WooCommerce'     => [],
        'WordPress'       => [],
        'Autres'          => [],
    ];

    foreach ($all_roles as $key => $role) {
        if (str_starts_with($key, 'um_')) {
            $grouped['Ultimate Member'][$key] = $role['name'];
        } elseif (str_contains($key, 'customer') || str_starts_with($key, 'shop_')) {
            $grouped['WooCommerce'][$key] = $role['name'];
        } else {
            $grouped['WordPress'][$key] = $role['name'];
        }
    }

    echo '<h2>' . esc_html__('User Roles and Account Type Management', 'me5rine-lab') . '</h2>';
    echo '<table class="form-table"><tr><th><label>' . esc_html__('Account Type', 'me5rine-lab') . '</label></th><td>';

    $account_types = admin_lab_get_registered_account_types();
    uasort($account_types, function($a, $b) {
        return strcasecmp($a['label'], $b['label']);
    });
    
    $user_account_types = get_user_meta($user->ID, 'admin_lab_account_types', true);
    if (!is_array($user_account_types)) $user_account_types = [];

    foreach ($account_types as $slug => $data) {
        $label = $data['label'];
        $checked = in_array($slug, $user_account_types) ? 'checked' : '';
        echo '<label class="user-edit-account-type"><input type="checkbox" name="um_account_types[]" value="' . esc_attr($slug) . '" ' . $checked . '> ' . $label . '</label><br>';
    }

    echo '</td></tr></table>';

    echo '<h3>' . esc_html__('User Roles', 'me5rine-lab') . '</h3>';
    echo '<table class="form-table"><tr><th><label>' . esc_html__('Roles', 'me5rine-lab') . '</label></th><td>';

    $account_types = admin_lab_get_registered_account_types();
    $user_account_types = get_user_meta($user->ID, 'admin_lab_account_types', true);
    if (!is_array($user_account_types)) $user_account_types = [];

    $user_roles = (array) $user->roles;

    // Tous les rôles liés à un type de compte, peu importe qu'ils soient attribués ou non
    $roles_from_registered_types = array_unique(array_filter(array_column($account_types, 'role')));

    foreach ($grouped as $group_name => $roles) {
        if (empty($roles)) continue;

        echo '<fieldset><legend><strong>' . esc_html($group_name) . '</strong></legend>';

        foreach ($roles as $key => $label) {
            $is_locked = in_array($key, $roles_from_registered_types, true);
            $is_checked = in_array($key, $user_roles);
            $checked = $is_checked ? ' checked' : '';
            $disabled = $is_locked ? ' disabled' : '';
            $style = $is_locked ? 'opacity:0.6;' : '';
            $icon = $is_locked ? ' <span title="' . esc_attr__('Ce rôle est lié à un type de compte et ne peut être modifié manuellement.', 'me5rine-lab') . '">🔒</span>' : '';

            if ($is_locked) {
                // Rôle lié à un type de compte : affichage uniquement (non modifiable, non envoyé)
                printf(
                    '<label style="%s"><input type="checkbox" name="um_roles[]" value="%s"%s%s> %s%s</label><br>',
                    esc_attr($style),
                    esc_attr($key),
                    $checked,
                    $disabled,
                    esc_html($label),
                    $icon
                );
            } else {
                // Rôle manuel : modifiable et envoyé dans $_POST
                printf(
                    '<label style="%s"><input type="checkbox" name="um_roles[]" value="%s"%s> %s</label><br>',
                    esc_attr($style),
                    esc_attr($key),
                    $checked,
                    esc_html($label)
                );
            }
        }

        echo '</fieldset><br>';
    }

    echo '</td></tr></table>';
    echo '<div id="partner-sites-extra"></div>';
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const partnerBoxes = document.querySelectorAll("input[value='partenaire'], input[value='partenaire_plus']");
        const container = document.getElementById("partner-sites-extra");
        const userIdInput = document.querySelector("input[name=user_id]");
        if (!userIdInput || !container) return;
        const userId = userIdInput.value;

        function loadPartnerSites() {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", ajaxurl, true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function () {
                if (xhr.status === 200) container.innerHTML = xhr.responseText;
            };
            xhr.send("action=load_partner_sites_field&user_id=" + userId);
        }

        partnerBoxes.forEach(box => {
            box.addEventListener("change", () => {
                const anyChecked = Array.from(partnerBoxes).some(b => b.checked);
                if (anyChecked) {
                    loadPartnerSites();
                } else {
                    container.innerHTML = '';
                }
            });

            // Chargement initial si déjà coché
            if (box.checked && container.innerHTML.trim() === '') {
                loadPartnerSites();
            }
        });
    });
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const typeInputs = document.querySelectorAll("input[name='um_account_types[]'][type='checkbox']");
        const exclusives = ['partenaire', 'partenaire_plus'];

        typeInputs.forEach(input => {
            if (!exclusives.includes(input.value)) return;

            input.addEventListener("change", function () {
                if (this.checked) {
                    // Désélectionner les autres exclusifs
                    typeInputs.forEach(other => {
                        if (other !== this && exclusives.includes(other.value)) {
                            other.checked = false;
                        }
                    });
                }
            });
        });
    });
    </script>
    <?php
}
add_action('show_user_profile', 'admin_lab_render_um_roles_field');
add_action('edit_user_profile', 'admin_lab_render_um_roles_field');

function admin_lab_sync_user_account($user_id, array $post) {
    if (!current_user_can('edit_user', $user_id)) return;

    // 1. Gérer la portée partenaire si nécessaire
    $partner_scope = null;
    if (!empty($post['partner_all_sites'])) {
        $partner_scope = 'all';
    } elseif (!empty($post['partner_sites'])) {
        $partner_scope = (array) $post['partner_sites'];
    }
    $posted_account_types = isset($post['um_account_types']) ? (array) $post['um_account_types'] : [];
    foreach (['partenaire', 'partenaire_plus'] as $partner_type) {
        admin_lab_set_account_scope($user_id, $partner_type, $partner_scope, $posted_account_types);
    }

    // 2. Récupérer les anciens types pour comparer
    $previous_account_types = get_user_meta($user_id, 'admin_lab_account_types', true);
    if (!is_array($previous_account_types)) $previous_account_types = [];

    // 3. Mettre à jour les types de comptes (remplacement complet)
    admin_lab_set_account_types_batch($user_id, $posted_account_types);

    // 4. Déduire les rôles liés aux types (ajouts et suppressions)
    $type_roles = array_filter(array_map('admin_lab_account_type_to_role', $posted_account_types));

    $previous_roles = array_filter(array_map('admin_lab_account_type_to_role', $previous_account_types));
    $removed_roles = array_diff($previous_roles, $type_roles);
    // On déplace la propagation vers le shutdown (via profile_update)
    update_user_meta($user_id, '_admin_lab_roles_to_add', $type_roles);
    update_user_meta($user_id, '_admin_lab_roles_to_remove', $removed_roles);

    // 5. Extraire les rôles manuels cochés
    $posted_roles = isset($post['um_roles']) ? (array) $post['um_roles'] : [];
    $manual_roles = array_values(array_filter($posted_roles, fn($r) => !in_array($r, $type_roles, true)));

    // 6. Stocker pour traitement différé (shutdown)
    update_user_meta($user_id, '_admin_lab_manual_roles', $manual_roles);
}

add_action('edit_user_profile_update', function ($user_id) {
    admin_lab_sync_user_account($user_id, $_POST);
});
add_action('personal_options_update', function ($user_id) {
    admin_lab_sync_user_account($user_id, $_POST);
});

add_action('profile_update', function ($user_id) {
    if (!current_user_can('edit_users') || empty($_POST['user_id'])) return;

    $user_id = (int) $_POST['user_id'];
    $user = new WP_User($user_id);

    // 🚨 Important : si le formulaire n’envoie pas les rôles (ex: via UM), on ne touche à rien
    if (!isset($_POST['um_roles'])) {
        return;
    }

    $manual_roles = get_user_meta($user_id, '_admin_lab_manual_roles', true);
    if (!is_array($manual_roles)) $manual_roles = [];

    $expected_roles = $manual_roles;
    $current_roles = $user->roles;

    // Supprimer les rôles non attendus
    foreach ($current_roles as $role) {
        if (!in_array($role, $expected_roles, true)) {
            $user->remove_role($role);
        }
    }

    // Si plus aucun rôle, forcer un rôle manuel
    $user = new WP_User($user_id); // recharger après suppressions
    if (empty($user->roles) && !empty($manual_roles)) {
        $user->set_role(array_shift($manual_roles));
    }

    // Ajouter les autres rôles manuels
    foreach ($manual_roles as $role) {
        if (!in_array($role, $user->roles, true)) {
            $user->add_role($role);
        }
    }

    // Propagation des rôles liés aux types
    $roles_to_add = get_user_meta($user_id, '_admin_lab_roles_to_add', true);
    $roles_to_remove = get_user_meta($user_id, '_admin_lab_roles_to_remove', true);

    if (is_array($roles_to_add)) {
        foreach ($roles_to_add as $role) {
            admin_lab_update_user_role_across_sites($user_id, $role, true);
        }
    }
    if (is_array($roles_to_remove)) {
        foreach ($roles_to_remove as $role) {
            admin_lab_update_user_role_across_sites($user_id, $role, false);
        }
    }

    delete_user_meta($user_id, '_admin_lab_manual_roles');
    delete_user_meta($user_id, '_admin_lab_roles_to_add');
    delete_user_meta($user_id, '_admin_lab_roles_to_remove');
});

add_action('wp_ajax_load_partner_sites_field', function () {
    if (!current_user_can('edit_users') || empty($_POST['user_id'])) exit;
    $user = get_userdata((int) $_POST['user_id']);
    if (!$user) exit;
    ob_start();
    do_action('user_management_render_partner_sites_field', $user);
    echo ob_get_clean();
    wp_die();
});

function admin_lab_hide_default_role_selectors() {
    echo '<style>
        #um_role_selector_wrapper,
        h3#um_user_screen_block,
        tr.user-role-wrap,
        h3:has(+ table .partner-sites-toggle) {
            display: none !important;
        }
    </style>';
}
add_action('admin_head-user-edit.php', 'admin_lab_hide_default_role_selectors');
add_action('admin_head-profile.php', 'admin_lab_hide_default_role_selectors');

add_action('user_management_render_partner_sites_field', 'user_management_add_partner_sites_field');