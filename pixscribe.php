<?php
/**
 * Plugin Name: Pixscribe
 * Description: Sends a request to a backend API when a file is uploaded to the Media Library.
 * Version: 1.0
 * Author: West Jones
 */

// Include plugin files
require_once plugin_dir_path(__FILE__) . 'includes/pixscribe-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/pixscribe-rest-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/pixscribe-hooks.php';
require_once plugin_dir_path(__FILE__) . 'pixscribe-settings.php';


add_action('acf/input/admin_footer', function() {
  ?>
  <script>
    (function($) {
      acf.add_action('ready', function() {
        $('.acf-field-text input, .acf-field-textarea textarea').each(function() {
          const $field = $(this);
          const $toolbar = $('<div class="acf-custom-toolbar" style="margin-bottom:5px;"></div>');
          const buttons = [
            { label: 'Uppercase', action: v => v.toUpperCase() },
            { label: 'Wrap **', action: v => `**${v}**` },
          ];

          buttons.forEach(btn => {
            const $btn = $('<button type="button" class="button button-small"></button>').text(btn.label);
            $btn.on('click', () => {
              const val = $field.val();
              $field.val(btn.action(val)).trigger('change');
            });
            $toolbar.append($btn);
          });

          $field.before($toolbar);
        });
      });
    })(jQuery);
  </script>
  <?php
});