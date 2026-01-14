<?php
/**
 * Pixscribe WordPress Hooks
 */

// Media upload trigger
function media_upload_api_trigger($attachment_id) {
  // Only run for image uploads
  if (strpos(get_post_mime_type($attachment_id), 'image/') !== 0) {
    return;
  }

  //Run the Pixscribe API request to generate metadata
  pixscribe_send_api_request($attachment_id);
}
add_action('add_attachment', 'media_upload_api_trigger');

// Admin scripts
add_action('admin_enqueue_scripts', function ($hook) {
  // Only enqueue scripts on the upload and media pages
  if (!in_array($hook, ['upload.php', 'media.php'], true)) {
    return;
  }

  // Enqueue the generate metadata script
  wp_enqueue_script(
    'pixscribe-generate-meta',
    plugin_dir_url(dirname(__FILE__)) . 'pixscribe-generate-meta.js',
    ['jquery', 'media-views', 'wp-api-request'],
    '1.0.0',
    true
  );

  // Localize the generate metadata script
  wp_localize_script('pixscribe-generate-meta', 'PixscribeAPI', [
    'endpoint' => rest_url('media-meta/v1/generate'),
    'nonce'    => wp_create_nonce('wp_rest'),
  ]);
});

