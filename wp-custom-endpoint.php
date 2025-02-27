<?php

/**
 * WP Custom Endpoint
 *
 * @author          M B Parvez
 * @copyright       M B Parvez & Gosoft.io
 * @license         GPL-2.0-or-later
 *
 * Plugin Name:     WP Custom Endpoint
 * Plugin URI:      https://github.com/mbparvezme/wp-custom-endpoint
 * Description:     Custom WordPress REST API endpoints for blog.
 * Version:         0.1.0
 * Author:          M B Parvez
 * Author URI:      https://www.mbparvez.me
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

// Load dependencies
require_once plugin_dir_path(__FILE__) . 'includes/wp-custom-endpoint-setting.php';
require_once plugin_dir_path(__FILE__) . 'includes/wp-custom-endpoint-utility.php';
require_once plugin_dir_path(__FILE__) . 'includes/wp-custom-endpoint.php';

// Initialize the plugin
new WP_Custom_Endpoint_Setting();
new WP_Custom_Endpoint_Utility();
new WP_Custom_Endpoint();