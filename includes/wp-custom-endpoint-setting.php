<?php
if (!defined('ABSPATH')) {
  exit;
}

class WP_Custom_Endpoint_Setting
{

  private $options;

  public function __construct()
  {
    // Add settings page and subpages
    add_action('admin_menu', [$this, 'add_settings_page']);
    // Register settings
    add_action('admin_init', [$this, 'register_settings']);
    // Retrieve plugin options
    $this->options = get_option('wp_custom_endpoint_options');
  }

  // Add settings page and subpages
  public function add_settings_page()
  {
    // Main settings page
    add_menu_page(
      'WP Custom Endpoint Settings', // Page title
      'WP Custom Endpoint',          // Menu title
      'manage_options',              // Capability
      'wp-custom-endpoint-settings', // Menu slug
      [$this, 'render_main_settings_page'], // Callback function
      'dashicons-rest-api',          // Icon
      100                            // Position
    );

    // Allowed domains subpage
    add_submenu_page(
      'wp-custom-endpoint-settings', // Parent slug
      'Manage Allowed Domains',      // Page title
      'Allowed Domains',             // Menu title
      'manage_options',              // Capability
      'wp-custom-endpoint-allowed-domain', // Menu slug
      [$this, 'render_allowed_domains_page'] // Callback function
    );

    // Rate limiting subpage
    add_submenu_page(
      'wp-custom-endpoint-settings', // Parent slug
      'Rate Limiting Settings',      // Page title
      'Rate Limiting',               // Menu title
      'manage_options',              // Capability
      'wp-custom-endpoint-rate-limiting', // Menu slug
      [$this, 'render_rate_limiting_page'] // Callback function
    );
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
      'Rate Limit Window (seconds)',     // Field title
      [$this, 'rate_limit_window_field'], // Callback for field input
      'wp-custom-endpoint-settings',     // Page slug
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

  // Render the main settings page
  public function render_main_settings_page()
  {
    // Retrieve plugin options
    $options = get_option('wp_custom_endpoint_options');
    $allowed_domains = isset($options['allowed_domains']) ? $options['allowed_domains'] : '';
    $rate_limit_enabled = isset($options['rate_limit_enabled']) ? $options['rate_limit_enabled'] : false;
    $rate_limit = isset($options['rate_limit']) ? $options['rate_limit'] : 100;
    $rate_limit_window = isset($options['rate_limit_window']) ? $options['rate_limit_window'] : 60;

?>
    <div class="wrap">
      <h1>WP Custom Endpoint Settings</h1>
      <h2>Allowed Domains</h2>
      <p><?php echo esc_html($allowed_domains); ?></p>
      <a href="<?php echo admin_url('admin.php?page=wp-custom-endpoint-allowed-domain'); ?>" class="button">Manage Allowed Domains</a>

      <h2>Rate Limiting</h2>
      <p>Status: <?php echo $rate_limit_enabled ? 'Enabled' : 'Disabled'; ?></p>
      <?php if ($rate_limit_enabled) : ?>
        <p>Rate Limit: <?php echo esc_html($rate_limit); ?> requests per <?php echo esc_html($rate_limit_window); ?> seconds</p>
      <?php endif; ?>
      <a href="<?php echo admin_url('admin.php?page=wp-custom-endpoint-rate-limiting'); ?>" class="button">Manage Rate Limiting</a>
    </div>
  <?php
  }

  // Render the allowed domains page
  public function render_allowed_domains_page()
  {
    // Retrieve plugin options
    $options = get_option('wp_custom_endpoint_options');
    $allowed_domains = isset($options['allowed_domains']) ? $options['allowed_domains'] : '';

    // Handle form submission
    if (isset($_POST['submit'])) {
      if (isset($_POST['add_domain'])) {
        // Add new domain
        $new_domain = sanitize_text_field($_POST['new_domain']);
        if (!empty($new_domain)) {
          $allowed_domains .= ($allowed_domains ? ',' : '') . $new_domain;
        }
      } elseif (isset($_POST['delete_domain'])) {
        // Delete selected domain
        $domain_to_delete = sanitize_text_field($_POST['domain_to_delete']);
        if (!empty($domain_to_delete)) {
          $domains = explode(',', $allowed_domains);
          $domains = array_diff($domains, [$domain_to_delete]);
          $allowed_domains = implode(',', $domains);
        }
      }

      // Save updated allowed domains
      $options['allowed_domains'] = $allowed_domains;
      update_option('wp_custom_endpoint_options', $options);
    }

  ?>
    <div class="wrap">
      <h1>Manage Allowed Domains</h1>
      <form method="post" action="">
        <h2>Add New Domain</h2>
        <input type="text" name="new_domain" placeholder="Enter a new domain" />
        <input type="submit" name="add_domain" value="Add Domain" class="button" />

        <h2>Delete Existing Domain</h2>
        <select name="domain_to_delete">
          <?php foreach (explode(',', $allowed_domains) as $domain) : ?>
            <option value="<?php echo esc_attr($domain); ?>"><?php echo esc_html($domain); ?></option>
          <?php endforeach; ?>
        </select>
        <input type="submit" name="delete_domain" value="Delete Domain" class="button" />
      </form>
    </div>
  <?php
  }

  // Render the rate limiting page
  public function render_rate_limiting_page()
  {
    // Retrieve plugin options
    $options = get_option('wp_custom_endpoint_options');
    $rate_limit_enabled = isset($options['rate_limit_enabled']) ? $options['rate_limit_enabled'] : false;
    $rate_limit = isset($options['rate_limit']) ? $options['rate_limit'] : 100;
    $rate_limit_window = isset($options['rate_limit_window']) ? $options['rate_limit_window'] : 60;

    // Handle form submission
    if (isset($_POST['submit'])) {
      $rate_limit_enabled = isset($_POST['rate_limit_enabled']);
      $rate_limit = absint($_POST['rate_limit']);
      $rate_limit_window = absint($_POST['rate_limit_window']);

      // Save updated rate limiting settings
      $options['rate_limit_enabled'] = $rate_limit_enabled;
      $options['rate_limit'] = $rate_limit;
      $options['rate_limit_window'] = $rate_limit_window;
      update_option('wp_custom_endpoint_options', $options);
    }

  ?>
    <div class="wrap">
      <h1>Rate Limiting Settings</h1>
      <form method="post" action="">
        <label>
          <input type="checkbox" name="rate_limit_enabled" value="1" <?php checked($rate_limit_enabled, 1); ?> />
          Enable Rate Limiting
        </label>

        <h2>Rate Limit</h2>
        <input type="number" name="rate_limit" value="<?php echo esc_attr($rate_limit); ?>" min="1" />
        <p class="description">Maximum number of requests allowed per time window.</p>

        <h2>Rate Limit Window (seconds)</h2>
        <input type="number" name="rate_limit_window" value="<?php echo esc_attr($rate_limit_window); ?>" min="1" />
        <p class="description">Time window (in seconds) for the rate limit.</p>

        <input type="submit" name="submit" value="Save Settings" class="button" />
      </form>
    </div>
<?php
  }
}
