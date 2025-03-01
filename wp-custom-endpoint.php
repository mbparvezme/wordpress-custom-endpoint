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
 * Version:         2.0.0-alpha2
 * Author:          M B Parvez
 * Author URI:      https://www.mbparvez.me
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */

use WP_Custom_Endpoint\WP_Custom_Endpoint_Setting as WPCE_Setting;
use WP_Custom_Endpoint\WP_Custom_Endpoint_Utility as WPCE_Utility;
use WP_Custom_Endpoint\WP_Custom_Endpoint as WPCE_Endpoint;

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

// Define plugin version
if (!defined('WP_CUSTOM_ENDPOINT_VERSION')) {
  define('WP_CUSTOM_ENDPOINT_VERSION', '2.0.0-alpha2');
}

// Load dependencies
require_once plugin_dir_path(__FILE__) . 'includes/loader.php';

// Initialize the plugin
function wp_custom_endpoint_init()
{
  new WPCE_Setting();
  new WPCE_Utility();
  new WPCE_Endpoint();
}
add_action('plugins_loaded', 'wp_custom_endpoint_init');

// Add "Settings" and "Documentation" links under the plugin title
function wp_custom_endpoint_action_links($links)
{
  $links[] = '<a href="' . esc_url(admin_url('admin.php?page=wp-custom-endpoint-settings')) . '">' . esc_html__('Settings', 'wp-custom-endpoint') . '</a>';
  $links[] = '<a href="https://github.com/mbparvezme/wp-custom-endpoint" target="_blank">' . esc_html__('Documentation', 'wp-custom-endpoint') . '</a>';
  return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wp_custom_endpoint_action_links');

// Clean up plugin data on uninstall
function wp_custom_endpoint_uninstall()
{
  delete_option('wp_custom_endpoint_options');
}
register_uninstall_hook(__FILE__, 'wp_custom_endpoint_uninstall');