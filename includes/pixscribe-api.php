<?php
/**
 * Pixscribe API Functions
 */

/**
 * Get the base API URL
 */
function pixscribe_get_api_base_url() {
  return 'https://pixscribe.dev';
}

/**
 * Get the API URL for a specific endpoint
 */
function pixscribe_get_api_url($endpoint) {
  $base_url = pixscribe_get_api_base_url();
  return trailingslashit($base_url) . ltrim($endpoint, '/');
}

function pixscribe_send_api_request($attachment_id) {

  // Validate attachment ID
  $attachment_id = absint($attachment_id);
  if (!$attachment_id || !get_post($attachment_id)) {
    return false;
  }

  // Determine upload endpoint based on local setting
  if (get_option('pixscribe_is_local')) {
    $api_url = pixscribe_get_api_url('api/wp/local-upload');
  } else {
    $api_url = pixscribe_get_api_url('api/wp/remote-upload');
  }

  // Get the API key
  $pixscribe_key = get_option('pixscribe_api_key');

  // Get the focused keywords
  $focused_keywords = get_option('pixscribe_website_keywords');

  if (!$pixscribe_key) {
    error_log('Pixscribe: API key not configured.');
    return false;
  }

  $file_url = wp_get_attachment_url($attachment_id);
  if (!$file_url) {
    return false;
  }

  $parent_id = wp_get_post_parent_id($attachment_id);
  $page_title = $parent_id ? get_the_title($parent_id) : '';
  $page_description = $parent_id ? get_the_excerpt($parent_id) : '';

  // Get file info
  $file_path = get_attached_file($attachment_id);
  $file_content = null;
  if ($file_path && file_exists($file_path)) {
    $file_content = file_get_contents($file_path);
    if ($file_content === false) {
      error_log('Pixscribe: Failed to read file content for attachment ' . $attachment_id);
      return false;
    }
  }

  $body = [
    'website_url' => esc_url(home_url()),
    'attachment_id' => $attachment_id,
    'page_title' => sanitize_text_field($page_title),
    'page_description' => sanitize_textarea_field($page_description),
    'focused_keywords' => sanitize_text_field($focused_keywords),
    'uploaded_by' => get_current_user_id(),
    'callback_url' => esc_url_raw(rest_url('media-meta/v1/update')),
    'is_local' => (bool) get_option('pixscribe_is_local', 0),
    'file_url' => esc_url_raw($file_url),
    'file_name' => $file_path ? basename($file_path) : '',
    'file_mime_type' => get_post_mime_type($attachment_id) ?: '',
    'file_content' => base64_encode($file_content),
  ];

  // POST request to Pixscribe API
  $response = wp_remote_post($api_url, [
    'method'  => 'POST',
    'timeout' => 0.01,
    'headers'  => [
      'Content-Type'  => 'application/json',
      'Authorization' => 'Bearer ' . sanitize_text_field($pixscribe_key),
    ],
    'body' => wp_json_encode($body),
    'blocking' => false,
  ]);

  if (is_wp_error($response)) {
    error_log("Pixscribe: API request failed - " . $response->get_error_code());
    return false;
  }

  if (get_option('pixscribe_is_local')) {
    // Start polling for status
    pixscribe_start_status_polling($attachment_id);
  }

  return true;
}