<?php
/**
 * Pixscribe API Functions
 */

function pixscribe_get_api_base_url() {
  return 'http://localhost:3000';
}

function pixscribe_get_api_url($endpoint) {
  $base_url = pixscribe_get_api_base_url();
  return trailingslashit($base_url) . ltrim($endpoint, '/');
}

function pixscribe_send_api_request($attachment_id) {
  $attachment_id = absint($attachment_id);
  
  // Validate attachment exists
  if (!$attachment_id || !get_post($attachment_id)) {
    return false;
  }

  // Validate API key configured
  $pixscribe_key = get_option('pixscribe_api_key');
  if (!$pixscribe_key) {
    error_log('Pixscribe: API key not configured.');
    return false;
  }

  // Get attachment data
  $attachment_data = pixscribe_get_attachment_data($attachment_id);
  if (!$attachment_data) {
    return false;
  }

  // Build request body
  $body = pixscribe_build_request_body($attachment_id, $attachment_data);

  // Determine endpoint
  $is_local = (bool) get_option('pixscribe_is_local', 0);
  $endpoint = $is_local ? 'api/wp/local-upload' : 'api/wp/remote-upload';
  $api_url = pixscribe_get_api_url($endpoint);

  // Send request
  $response = wp_remote_post($api_url, [
    'method'    => 'POST',
    'timeout'   => 0.01,
    'headers'   => [
      'Content-Type'  => 'application/json',
      'Authorization' => 'Bearer ' . sanitize_text_field($pixscribe_key),
    ],
    'body'      => wp_json_encode($body),
    'blocking'  => false,
  ]);

  if (is_wp_error($response)) {
    error_log('Pixscribe: API request failed - ' . $response->get_error_code());
    return false;
  }

  // Start polling for local uploads
  if ($is_local) {
    pixscribe_start_status_polling($attachment_id);
  }

  return true;
}

function pixscribe_get_attachment_data($attachment_id) {
  // Get file URL (medium first, fallback to original)
  $medium_image = wp_get_attachment_image_src($attachment_id, 'medium');
  $file_url = $medium_image ? $medium_image[0] : wp_get_attachment_url($attachment_id);

  if (!$file_url) {
    error_log('Pixscribe: Unable to get file URL for attachment ' . $attachment_id);
    return false;
  }

  // Download file content
  $response = wp_remote_get($file_url);
  if (is_wp_error($response)) {
    error_log('Pixscribe: Failed to fetch file from URL - ' . $response->get_error_code());
    return false;
  }

  $file_content = wp_remote_retrieve_body($response);
  if (!$file_content) {
    error_log('Pixscribe: Failed to read file content from ' . $file_url);
    return false;
  }

  return [
    'file_url'      => $file_url,
    'file_content'  => $file_content,
    'file_name'     => basename($file_url),
    'file_mime_type' => get_post_mime_type($attachment_id) ?: '',
  ];
}

function pixscribe_build_request_body($attachment_id, $attachment_data) {
  $parent_id = wp_get_post_parent_id($attachment_id);

  return [
    'website_url'        => esc_url(home_url()),
    'attachment_id'      => $attachment_id,
    'file_url'           => esc_url_raw($attachment_data['file_url']),
    'file_name'          => $attachment_data['file_name'],
    'file_mime_type'     => $attachment_data['file_mime_type'],
    'file_content'       => base64_encode($attachment_data['file_content']),
    'page_title'         => sanitize_text_field(get_the_title($parent_id) ?: ''),
    'page_description'   => sanitize_textarea_field(get_the_excerpt($parent_id) ?: ''),
    'focused_keywords'   => sanitize_text_field(get_option('pixscribe_website_keywords') ?: ''),
    'callback_url'       => esc_url_raw(rest_url('media-meta/v1/update')),
  ];
}