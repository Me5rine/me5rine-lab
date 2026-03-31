<?php

defined('\ABSPATH') || exit;

/*
  Name: Specifications
 */

?>

<?php foreach ($items as $key => $item) : ?>
  <div class="container px-0 mb-5 mt-1" <?php $this->colorMode(); ?>>

    <?php if (empty($item['features'])) continue; ?>
    <table class='table table-sm cegg-features-table'>
      <tbody>
        <?php foreach ($item['features'] as $feature) : ?>
          <tr>
            <td class='text-body-secondary col-4'><?php echo esc_html(__($feature['name'], 'content-egg-tpl')) ?></td>
            <td><?php echo esc_html($feature['value']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endforeach; ?>