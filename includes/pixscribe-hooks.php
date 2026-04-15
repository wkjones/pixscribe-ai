<?php
/**
 * Pixscribe WordPress Hooks
 */

// Admin scripts
add_action('admin_enqueue_scripts', function ($hook) {
  // Enqueue on media library plus post/page editors (Add Media modal).
  if (!in_array($hook, ['upload.php', 'media.php', 'post.php', 'post-new.php'], true)) {
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

