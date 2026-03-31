<?php

use ContentEgg\application\helpers\TemplateHelper;

defined('\ABSPATH') || exit;

?>
<div class="container px-0 mb-3 mt-1" <?php $this->colorMode(); ?>>
    <?php foreach ($items as $item) : ?>
        <div class="egg-description text-body"><?php TemplateHelper::description($item); ?></div>
    <?php endforeach; ?>
</div>