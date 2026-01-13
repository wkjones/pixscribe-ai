<?php
/**
 * Pixscribe API Functions
 */

function pixscribe_send_api_request($attachment_id) {
  // Validate attachment ID
  $attachment_id = absint($attachment_id);
  if (!$attachment_id || !get_post($attachment_id)) {
    return false;
  }

  $api_url = 'http://localhost:3000/api/wp-upload';
  $pixscribe_key = get_option('pixscribe_api_key');
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

  $body = [
    'website_url'   => esc_url(home_url()),
    'attachment_id' => $attachment_id,
    'file_url'      => esc_url_raw($file_url),
    'page_title' => sanitize_text_field($page_title),
    'page_description' => sanitize_textarea_field($page_description),
    'focused_keywords' => sanitize_text_field($focused_keywords),
    'uploaded_by'   => get_current_user_id(),
    'callback_url'  => esc_url_raw(rest_url('media-meta/v1/update')),
  ];

  // POST request to your backend
  $response = wp_remote_post($api_url, [
    'method'  => 'POST',
    'timeout' => 30,
    'sslverify' => !preg_match('/^http:\/\/localhost/', $api_url),
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

  return true;
}

