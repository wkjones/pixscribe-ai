<?php
/**
 * Plugin Name: Pixscribe
 * Description: Sends a request to a backend API when a file is uploaded to the Media Library.
 * Version: 1.0
 * Author: West Jones
 */

// Include plugin files
require_once plugin_dir_path(__FILE__) . 'includes/pixscribe-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/pixscribe-status.php';
require_once plugin_dir_path(__FILE__) . 'includes/pixscribe-rest-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/pixscribe-hooks.php';
require_once plugin_dir_path(__FILE__) . 'includes/pixscribe-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/pixscribe-batch-generate.php';