<?php
/**
 * Pixscribe WordPress Hooks
 */

// Run API request after upload (so medium size exists; add_attachment fires before thumbnails)
add_action('pixscribe_send_api_request_after_upload', 'pixscribe_send_api_request');

add_filter('wp_generate_attachment_metadata', function ($metadata, $attachment_id) {
  if (!get_post($attachment_id) || strpos(get_post_mime_type($attachment_id), 'image/') !== 0) {
    return $metadata;
  }
  wp_schedule_single_event(time(), 'pixscribe_send_api_request_after_upload', [$attachment_id]);
  return $metadata;
}, 10, 2);

// Admin scripts
add_action('admin_enqueue_scripts', function ($hook) {
  // Only enqueue scripts on the upload and media pages
  if (!in_array($hook, ['upload.php', 'media.php'], true)) {
    return;
  }

  // Enqueue the generate metadata script
  wp_enqueue_script(
    'pixscribe-generate-meta',
    plugin_dir_url(dirname(__FILE__)) . '/js/pixscribe-generate-meta.js',
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

