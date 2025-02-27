<?php
if (!defined('ABSPATH')) {
  exit;
}

class WP_Custom_Endpoint_Setting
{

  private $options;

  public function __construct()
  {
    // Add settings page and register settings
    add_action('admin_menu', [$this, 'add_settings_page']);
    add_action('admin_init', [$this, 'register_settings']);
    $this->options = get_option('wp_custom_endpoint_options');
  }

  // Add a settings page to the WordPress admin menu
  public function add_settings_page()
  {
    add_menu_page(
      'WP Custom Endpoint Settings', // Page title
      'WP Custom Endpoint',          // Menu title
      'manage_options',              // Capability
      'wp-custom-endpoint-settings', // Menu slug
      [$this, 'render_settings_page'], // Callback function
      'dashicons-rest-api',          // Icon
      100                            // Position
    );
  }

  // Render the settings page
  public function render_settings_page()
  {
?>
    <div class="wrap">
      <h1>WP Custom Endpoint Settings</h1>
      <form method="post" action="options.php">
        <?php
        settings_fields('wp_custom_endpoint_options_group'); // Output security fields
        do_settings_sections('wp-custom-endpoint-settings'); // Output settings sections
        submit_button(); // Output save settings button
        ?>
      </form>
    </div>
<?php
  }

  // Register settings and fields
  public function register_settings()
  {
    register_setting(
      'wp_custom_endpoint_options_group', // Option group
      'wp_custom_endpoint_options',      // Option name
      [$this, 'sanitize_settings']       // Sanitization callback
    );

    // Add a section for the settings
    add_settings_section(
      'wp_custom_endpoint_main_section', // Section ID
      'API Settings',                    // Section title
      [$this, 'section_text'],           // Callback for section description
      'wp-custom-endpoint-settings'     // Page slug
    );

    // Add a field for allowed domains
    add_settings_field(
      'allowed_domains',                 // Field ID
      'Allowed Domains',                 // Field title
      [$this, 'allowed_domains_field'],  // Callback for field input
      'wp-custom-endpoint-settings',     // Page slug
      'wp_custom_endpoint_main_section'  // Section ID
    );

    // Add a field for rate limit
    add_settings_field(
      'rate_limit',                      // Field ID
      'Rate Limit',                      // Field title
      [$this, 'rate_limit_field'],       // Callback for field input
      'wp-custom-endpoint-settings',     // Page slug
      'wp_custom_endpoint_main_section'  // Section ID
    );

    // Add a field for rate limit window
    add_settings_field(
      'rate_limit_window',               // Field ID
      'Rate Limit Window (seconds)',    // Field title
      [$this, 'rate_limit_window_field'], // Callback for field input
      'wp-custom-endpoint-settings',    // Page slug
      'wp_custom_endpoint_main_section'  // Section ID
    );
  }

  // Sanitize settings before saving
  public function sanitize_settings($input)
  {
    $sanitized_input = [];
    if (isset($input['allowed_domains'])) {
      $sanitized_input['allowed_domains'] = sanitize_text_field($input['allowed_domains']);
    }
    if (isset($input['rate_limit'])) {
      $sanitized_input['rate_limit'] = absint($input['rate_limit']);
    }
    if (isset($input['rate_limit_window'])) {
      $sanitized_input['rate_limit_window'] = absint($input['rate_limit_window']);
    }
    return $sanitized_input;
  }

  // Section description
  public function section_text()
  {
    echo '<p>Configure the settings for the WP Custom Endpoint plugin.</p>';
  }

  // Field for allowed domains
  public function allowed_domains_field()
  {
    $allowed_domains = isset($this->options['allowed_domains']) ? $this->options['allowed_domains'] : '';
    echo '<input id="allowed_domains" name="wp_custom_endpoint_options[allowed_domains]" type="text" value="' . esc_attr($allowed_domains) . '" class="regular-text" />';
    echo '<p class="description">Enter comma-separated domains (e.g., example.com, another.com).</p>';
  }

  // Field for rate limit
  public function rate_limit_field()
  {
    $rate_limit = isset($this->options['rate_limit']) ? $this->options['rate_limit'] : 100;
    echo '<input id="rate_limit" name="wp_custom_endpoint_options[rate_limit]" type="number" value="' . esc_attr($rate_limit) . '" class="small-text" />';
    echo '<p class="description">Maximum number of requests allowed per time window.</p>';
  }

  // Field for rate limit window
  public function rate_limit_window_field()
  {
    $rate_limit_window = isset($this->options['rate_limit_window']) ? $this->options['rate_limit_window'] : 60;
    echo '<input id="rate_limit_window" name="wp_custom_endpoint_options[rate_limit_window]" type="number" value="' . esc_attr($rate_limit_window) . '" class="small-text" />';
    echo '<p class="description">Time window (in seconds) for the rate limit.</p>';
  }
}
