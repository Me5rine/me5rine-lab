<?php
/*
 * Name: Top listings with Show More button
 * Module Types: PRODUCT
 */

use ContentEgg\application\helpers\TemplateHelper;;

defined('\ABSPATH') || exit;

$_container_id = rand(0, 9999999);

$this->renderPartial('block_top_listing', array('_container_id' => $_container_id));

$initial_count = !empty($params['cols']) ? $params['cols'] : 3;
?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const containerId = 'cegg-top-listing-<?php echo esc_attr($_container_id); ?>';
        const container = document.getElementById(containerId);

        if (!container) {
            console.warn(`Container with ID "${containerId}" not found.`);
            return;
        }

        const cards = container.querySelectorAll('.cegg-list-card');
        const initialVisibleCards = <?php echo esc_js($initial_count); ?>;

        if (cards.length <= initialVisibleCards) {
            return;
        }

        for (let i = initialVisibleCards; i < cards.length; i++) {
            cards[i].classList.add('d-none');
            cards[i].style.display = 'none';
        }

        const showMoreBtn = document.createElement('button');
        showMoreBtn.textContent = <?php echo wp_json_encode(sprintf(TemplateHelper::__("Show %d More") . ' ↓', count($items) - $initial_count)); ?>;
        showMoreBtn.classList.add('btn', 'btn-secondary');
        showMoreBtn.setAttribute('aria-controls', containerId);
        showMoreBtn.setAttribute('aria-expanded', 'false');

        const col12Div = document.createElement('div');
        col12Div.classList.add('col-12', 'text-center');

        // Append the button to the new <div>
        col12Div.appendChild(showMoreBtn);

        container.appendChild(col12Div);

        showMoreBtn.addEventListener('click', function() {
            for (let i = initialVisibleCards; i < cards.length; i++) {
                cards[i].classList.remove('d-none');
                cards[i].style.display = 'block';
            }
            showMoreBtn.classList.add('d-none');
            showMoreBtn.style.display = 'none';
            showMoreBtn.setAttribute('aria-expanded', 'true');
        });
    });
</script>