<?php
/**
 * Batch process media files missing alt text
 */

 function pixscribe_get_media_missing_alt_text() {
  $args = array(
    'post_type'      => 'attachment',
    'posts_per_page' => -1,
    'post_status'    => 'inherit',
    'meta_query'     => array(
      'relation' => 'OR',
      array(
        'key'     => '_wp_attachment_image_alt',
        'compare' => 'NOT EXISTS',
      ),
      array(
        'key'     => '_wp_attachment_image_alt',
        'value'   => '',
        'compare' => '=',
      ),
    ),
  );

  $attachments = get_posts( $args );

  return ! empty( $attachments ) ? $attachments : array();
}

function pixscribe_batch_get_attachments() {
  // Verify nonce and user capability
  check_ajax_referer( 'pixscribe_batch_nonce', 'nonce' );

  if ( ! current_user_can( 'manage_options' ) ) {
    wp_send_json_error( 'Insufficient permissions' );
  }

  // Get all attachments missing alt text
  $attachments = pixscribe_get_media_missing_alt_text();

  if ( empty( $attachments ) ) {
    wp_send_json_success( array(
      'attachments' => array(),
      'total'       => 0,
    ) );
  }

  // Return only IDs and basic info
  $attachment_data = array_map(
    function ( $attachment ) {
      return array(
        'id'    => $attachment->ID,
        'title' => $attachment->post_title,
      );
    },
    $attachments
  );

  wp_send_json_success( array(
    'attachments' => $attachment_data,
    'total'       => count( $attachment_data ),
  ) );
}
add_action(
  'wp_ajax_pixscribe_batch_get_attachments',
  'pixscribe_batch_get_attachments'
);

function pixscribe_batch_process_single() {
  // Verify nonce and user capability
  check_ajax_referer( 'pixscribe_batch_nonce', 'nonce' );

  if ( ! current_user_can( 'manage_options' ) ) {
    wp_send_json_error( 'Insufficient permissions' );
  }

  $attachment_id = isset( $_POST['attachment_id'] ) ? absint(
    $_POST['attachment_id']
  ) : 0;

  if ( ! $attachment_id ) {
    wp_send_json_error( 'Invalid attachment ID' );
  }

  // Verify attachment still exists and missing alt text
  $attachment = get_post( $attachment_id );
  if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
    wp_send_json_error( 'Attachment not found' );
  }

  $alt_text = get_post_meta(
    $attachment_id,
    '_wp_attachment_image_alt',
    true
  );
  if ( $alt_text ) {
    wp_send_json_success( array(
      'status'  => 'skipped',
      'message' => 'Alt text already exists',
    ) );
  }

  // Process the attachment
  $result = pixscribe_send_api_request( $attachment_id );

  if ( $result ) {
    wp_send_json_success( array(
      'status'  => 'success',
      'message' => 'Image processed successfully',
    ) );
  } else {
    wp_send_json_error( 'Failed to process image' );
  }
}
add_action( 'wp_ajax_pixscribe_batch_process_single', 'pixscribe_batch_process_single' );

function pixscribe_enqueue_batch_script() {
  wp_enqueue_script(
    'pixscribe-batch',
    plugin_dir_url( dirname( __FILE__ ) ) . 'js/pixscribe-batch-generate.js',
    array( 'jquery' ),
    '1.0.0',
    true
  );

  wp_localize_script( 'pixscribe-batch', 'pixscribeBatch', array(
    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'pixscribe_batch_nonce' ),
  ) );
}
add_action( 'admin_enqueue_scripts', 'pixscribe_enqueue_batch_script' );