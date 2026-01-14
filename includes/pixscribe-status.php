<?php
/**
 * Start polling the status endpoint for an attachment
 */
function pixscribe_start_status_polling($attachment_id) {
  $attachment_id = absint($attachment_id);
  if (!$attachment_id) {
    return;
  }

  // Use transient to track polling attempts (max 120 attempts = 2 minutes at 1 second intervals)
  $attempt_key = 'pixscribe_poll_attempts_' . $attachment_id;
  $attempts = get_transient($attempt_key);
  
  if ($attempts === false) {
    $attempts = 0;
  }
  
  // Stop if we've exceeded max attempts (120 attempts = 2 minutes at 1 second intervals)
  if ($attempts >= 120) {
    error_log("Pixscribe: Max polling attempts reached for attachment {$attachment_id}");
    delete_transient($attempt_key);
    return;
  }

  // Increment attempts
  set_transient($attempt_key, $attempts + 1, 300); // 5 minute expiry

  // Schedule next check in 1 second (faster polling)
  wp_schedule_single_event(time() + 1, 'pixscribe_check_status', [$attachment_id]);
}

/**
 * Check the status of an attachment and update metadata if completed
 */
function pixscribe_check_status($attachment_id) {
  $attachment_id = absint($attachment_id);
  if (!$attachment_id || !get_post($attachment_id)) {
    return;
  }

  // Track attempts
  $attempt_key = 'pixscribe_poll_attempts_' . $attachment_id;
  $attempts = get_transient($attempt_key);
  if ($attempts === false) {
    $attempts = 0;
  }
  
  // Stop if we've exceeded max attempts
  if ($attempts >= 120) {
    error_log("Pixscribe: Max polling attempts reached for attachment {$attachment_id}");
    delete_transient($attempt_key);
    return;
  }
  
  // Increment attempts
  set_transient($attempt_key, $attempts + 1, 300);

  $pixscribe_key = get_option('pixscribe_api_key');
  if (!$pixscribe_key) {
    return;
  }

  // Get status endpoint URL
  $api_url = pixscribe_get_api_url('api/wp/status');

  // Check status
  $response = wp_remote_get(
    add_query_arg(['attachment_id' => $attachment_id, 'website_url' => home_url()], $api_url),
    [
      'timeout' => 10,
      'headers' => [
        'Authorization' => 'Bearer ' . sanitize_text_field($pixscribe_key),
      ],
    ]
  );

  if (is_wp_error($response)) {
    error_log("Pixscribe: Status check failed - " . $response->get_error_message());
    // Continue polling on error (schedule next check in 1 second)
    wp_schedule_single_event(time() + 1, 'pixscribe_check_status', [$attachment_id]);
    return;
  }

  $body = wp_remote_retrieve_body($response);
  $data = json_decode($body, true);

  if (!$data || !isset($data['status'])) {
    // Continue polling if no status (schedule next check in 1 second)
    wp_schedule_single_event(time() + 1, 'pixscribe_check_status', [$attachment_id]);
    return;
  }

  // Check for errors
  if (!empty($data['error'])) {
    error_log("Pixscribe: Error for attachment {$attachment_id} - " . $data['error']);
    delete_transient('pixscribe_poll_attempts_' . $attachment_id);
    return;
  }

  // If status is completed, update metadata
  if ($data['status'] === 'completed') {
    // Update metadata (metadata is at top level, not nested)
    if (!empty($data['alt_text'])) {
      update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($data['alt_text']));
    }
    
    if (!empty($data['title'])) {
      wp_update_post([
        'ID' => $attachment_id,
        'post_title' => sanitize_text_field($data['title']),
      ]);
    }
    
    if (!empty($data['caption'])) {
      wp_update_post([
        'ID' => $attachment_id,
        'post_excerpt' => sanitize_textarea_field($data['caption']),
      ]);
    }
    
    if (!empty($data['description'])) {
      wp_update_post([
        'ID' => $attachment_id,
        'post_content' => sanitize_textarea_field($data['description']),
      ]);
    }

    // Clean up polling attempts
    delete_transient('pixscribe_poll_attempts_' . $attachment_id);
    
    error_log("Pixscribe: Metadata updated for attachment {$attachment_id}");
    return;
  }

  // If status is not completed, continue polling (schedule next check in 1 second)
  if ($data['status'] !== 'completed') {
    wp_schedule_single_event(time() + 1, 'pixscribe_check_status', [$attachment_id]);
  }
}

// Hook for scheduled status checks
add_action('pixscribe_check_status', 'pixscribe_check_status');
