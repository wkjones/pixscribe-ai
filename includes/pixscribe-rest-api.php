<?php
/**
 * Pixscribe REST API Endpoints
 */

add_action('rest_api_init', function () {
  register_rest_route('media-meta/v1', '/update', [
    'methods' => 'POST',
    'callback' => 'media_meta_update',
    'permission_callback' => function (WP_REST_Request $request) {
      $auth = $request->get_header('Authorization');
      $key  = get_option('pixscribe_api_key');
      return $auth && $key && strpos($auth, 'Bearer ') === 0 && trim(substr($auth, 7)) === $key;
    },
  ]);

  register_rest_route('media-meta/v1', '/generate', [
    'methods'             => 'POST',
    'callback'            => 'media_meta_generate',
    'permission_callback' => function (WP_REST_Request $request) {
      // Verify nonce from header (wp.apiRequest sends this automatically)
      $nonce = $request->get_header('X-WP-Nonce');
      if ($nonce && wp_verify_nonce($nonce, 'wp_rest')) {
        return current_user_can('upload_files');
      }
      // Fallback: check if user is logged in via cookies
      return is_user_logged_in() && current_user_can('upload_files');
    },
  ]);

  register_rest_route('media-meta/v1', '/get', [
    'methods'             => 'GET',
    'callback'            => 'media_meta_get',
    'permission_callback' => function (WP_REST_Request $request) {
      // Verify nonce from header (wp.apiRequest sends this automatically)
      $nonce = $request->get_header('X-WP-Nonce');
      if ($nonce && wp_verify_nonce($nonce, 'wp_rest')) {
        return current_user_can('upload_files');
      }
      // Fallback: check if user is logged in via cookies
      return is_user_logged_in() && current_user_can('upload_files');
    },
  ]);
});

function media_meta_update(WP_REST_Request $request) {
  $params = $request->get_json_params();
  $attachment_id = intval($params['attachment_id'] ?? 0);

  if (!$attachment_id) {
    return new WP_REST_Response(['error' => 'Missing attachment_id'], 400);
  }

  if (!empty($params['alt_text'])) {
    update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($params['alt_text']));
  }

  if (!empty($params['title'])) {
    wp_update_post(['ID' => $attachment_id, 'post_title' => sanitize_text_field($params['title'])]);
  }

  if (!empty($params['caption'])) {
    wp_update_post(['ID' => $attachment_id, 'post_excerpt' => sanitize_textarea_field($params['caption'])]);
  }

  if (!empty($params['description'])) {
    wp_update_post(['ID' => $attachment_id, 'post_content' => sanitize_textarea_field($params['description'])]);
  }

  return new WP_REST_Response(['success' => true, 'updated' => $attachment_id]);
}

function media_meta_generate(WP_REST_Request $request) {
  $attachment_id = intval($request->get_param('attachment_id'));

  if (!$attachment_id) {
    return new WP_REST_Response(['success' => false, 'error' => 'Missing attachment_id'], 400);
  }

  $result = pixscribe_send_api_request($attachment_id);

  if (!$result) {
    return new WP_REST_Response(['success' => false, 'error' => 'Failed to send API request'], 500);
  }

  return new WP_REST_Response(['success' => true, 'message' => 'Metadata generation started']);
}

function media_meta_get(WP_REST_Request $request) {
  $attachment_id = intval($request->get_param('attachment_id'));

  if (!$attachment_id) {
    return new WP_REST_Response(['error' => 'Missing attachment_id'], 400);
  }

  $attachment = get_post($attachment_id);
  if (!$attachment) {
    return new WP_REST_Response(['error' => 'Attachment not found'], 404);
  }

  return new WP_REST_Response([
    'success' => true,
    'data' => [
      'alt_text' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
      'title' => $attachment->post_title,
      'caption' => $attachment->post_excerpt,
      'description' => $attachment->post_content,
    ],
  ]);
}

