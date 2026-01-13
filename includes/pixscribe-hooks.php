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

  pixscribe_send_api_request($attachment_id);
}
add_action('add_attachment', 'media_upload_api_trigger');

// Admin scripts
add_action('admin_enqueue_scripts', function ($hook) {
  if (!in_array($hook, ['upload.php', 'media.php'], true)) {
    return;
  }

  wp_enqueue_script(
    'pixscribe-poll-metadata',
    plugin_dir_url(dirname(__FILE__)) . 'pixscribe-poll-metadata.js',
    ['jquery', 'wp-api-request'],
    '1.0.0',
    true
  );

  wp_enqueue_script(
    'pixscribe-generate-meta',
    plugin_dir_url(dirname(__FILE__)) . 'pixscribe-generate-meta.js',
    ['jquery', 'media-views', 'wp-api-request', 'pixscribe-poll-metadata'],
    '1.0.0',
    true
  );

  wp_localize_script('pixscribe-generate-meta', 'PixscribeAPI', [
    'endpoint' => rest_url('media-meta/v1/generate'),
    'nonce'    => wp_create_nonce('wp_rest'),
  ]);
});

