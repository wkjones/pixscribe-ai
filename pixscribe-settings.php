<?php
/**
 * Pixscribe Settings Page
 */

add_action('admin_menu', function () {
  add_options_page(
    'Pixscribe Settings',
    'Pixscribe',
    'manage_options',
    'pixscribe',
    'pixscribe_settings_page'
  );
});

add_action('admin_init', function () {
  register_setting('pixscribe', 'pixscribe_api_key', [
    'sanitize_callback' => 'sanitize_text_field',
  ]);

  register_setting('pixscribe', 'pixscribe_website_keywords', [
    'sanitize_callback' => 'sanitize_text_field',
  ]);
});

function pixscribe_settings_page() {
  ?>
  <div class="wrap">
    <h1>Pixscribe Settings</h1>
    <form method="post" action="options.php">
      <?php
      settings_fields('pixscribe');
      ?>
      <table class="form-table">
        <tr valign="top">
          <th scope="row">Pixscribe API Key</th>
          <td>
            <input type="password" name="pixscribe_api_key" value="<?php echo esc_attr(get_option('pixscribe_api_key')); ?>" size="60" />
            <p class="description">
              Enter the API key from your Pixscribe dashboard.
            </p>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">Focused Keywords</th>
          <td>
            <input type="text" name="pixscribe_website_keywords" value="<?php echo esc_attr(get_option('pixscribe_website_keywords')); ?>" size="60" />
            <p class="description">
              Enter the specific keywords you would like the AI to use to generate metadata.
            </p>
          </td>
        </tr>
      </table>
      <?php submit_button(); ?>
    </form>
  </div>
  <?php
}

