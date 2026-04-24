<?php
/**
 * Plugin Name: Pixscribe
 * Description: Connects WordPress sites with the Pixscribe.dev API to generate metadata for images.
 * Version: 1.0.2
 * Author: Pixscribe
 */

// Include plugin files
require_once plugin_dir_path(__FILE__) . 'includes/pixscribe-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/pixscribe-status.php';
require_once plugin_dir_path(__FILE__) . 'includes/pixscribe-rest-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/pixscribe-hooks.php';
require_once plugin_dir_path(__FILE__) . 'includes/pixscribe-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/pixscribe-batch-generate.php';