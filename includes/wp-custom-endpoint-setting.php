<?php
if (!defined('ABSPATH')) {
  exit;
}

class WP_Custom_Endpoint_Setting
{

  private $options;

  public function __construct()
  {
    // Add settings page
    add_action('admin_menu', [$this, 'add_settings_page']);
    // Register settings
    add_action('admin_init', [$this, 'register_settings']);
    // Retrieve plugin options
    $this->options = get_option('wp_custom_endpoint_options');
  }

  // Add settings page
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

  // Register settings and fields
  public function register_settings()
  {
    register_setting(
      'wp_custom_endpoint_options_group', // Option group
      'wp_custom_endpoint_options',      // Option name
      [$this, 'sanitize_settings']       // Sanitization callback
    );

    // // Add a section for allowed domains
    // add_settings_section(
    //   'wp_custom_endpoint_allowed_domains_section', // Section ID
    //   'Allowed Domains',                            // Section title
    //   [$this, 'allowed_domains_section_text'],      // Callback for section description
    //   'wp-custom-endpoint-settings'                // Page slug
    // );

    // // Add a section for rate limiting
    // add_settings_section(
    //   'wp_custom_endpoint_rate_limiting_section',   // Section ID
    //   'Rate Limiting',                              // Section title
    //   [$this, 'rate_limiting_section_text'],        // Callback for section description
    //   'wp-custom-endpoint-settings'                // Page slug
    // );
  }

  // Sanitize settings before saving
  public function sanitize_settings($input)
  {
    $sanitized_input = [];
    if (isset($input['allowed_domains'])) {
      $sanitized_input['allowed_domains'] = sanitize_text_field($input['allowed_domains']);
    }
    if (isset($input['rate_limit_enabled'])) {
      $sanitized_input['rate_limit_enabled'] = (bool) $input['rate_limit_enabled'];
    }
    if (isset($input['rate_limit'])) {
      $sanitized_input['rate_limit'] = absint($input['rate_limit']);
    }
    if (isset($input['rate_limit_window'])) {
      $sanitized_input['rate_limit_window'] = absint($input['rate_limit_window']);
    }
    return $sanitized_input;
  }

  // Section description for allowed domains
  public function allowed_domains_section_text()
  {
    echo '<p>Manage the list of domains allowed to access the API.</p>';
  }

  // Section description for rate limiting
  public function rate_limiting_section_text()
  {
    echo '<p>Configure rate limiting for the API.</p>';
  }

  // Render the settings page
public function render_settings_page() {
    // Retrieve plugin options
    $options = get_option('wp_custom_endpoint_options');
    $allowed_domains = isset($options['allowed_domains']) ? $options['allowed_domains'] : '';
    $rate_limit_enabled = isset($options['rate_limit_enabled']) ? $options['rate_limit_enabled'] : false;
    $rate_limit = isset($options['rate_limit']) ? $options['rate_limit'] : 100;
    $rate_limit_window = isset($options['rate_limit_window']) ? $options['rate_limit_window'] : 60;

    // Handle form submission for adding/deleting domains
    if (isset($_POST['wp_custom_endpoint_nonce']) && wp_verify_nonce($_POST['wp_custom_endpoint_nonce'], 'wp_custom_endpoint_allowed_domains')) {
        if (isset($_POST['add_domain'])) {
            // Add new domain
            $new_domain = sanitize_text_field($_POST['new_domain']);
            if (!empty($new_domain)) {
                $allowed_domains .= ($allowed_domains ? ',' : '') . $new_domain;
                $options['allowed_domains'] = $allowed_domains;
                update_option('wp_custom_endpoint_options', $options);
            }
        } elseif (isset($_POST['delete_domain'])) {
            // Delete selected domain
            $domain_to_delete = sanitize_text_field($_POST['domain_to_delete']);
            if (!empty($domain_to_delete)) {
                $domains = explode(',', $allowed_domains);
                $domains = array_diff($domains, [$domain_to_delete]);
                $allowed_domains = implode(',', $domains);
                $options['allowed_domains'] = $allowed_domains;
                update_option('wp_custom_endpoint_options', $options);
            }
        }
    }

    ?>
    <div class="wrap">
        <h1>WP Custom Endpoint Settings</h1>

        <!-- Allowed Domains Section -->
        <h2>Allowed Domains</h2>
        <form method="post" action="">
            <?php wp_nonce_field('wp_custom_endpoint_allowed_domains', 'wp_custom_endpoint_nonce'); ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($allowed_domains)) : ?>
                        <?php foreach (explode(',', $allowed_domains) as $domain) : ?>
                            <tr>
                                <td><?php echo esc_html($domain); ?></td>
                                <td>
                                    <input type="hidden" name="domain_to_delete" value="<?php echo esc_attr($domain); ?>" />
                                    <button type="submit" name="delete_domain" class="button">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="2">No domains added yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h3>Add New Domain</h3>
            <input type="text" name="new_domain" placeholder="Enter a new domain" />
            <input type="submit" name="add_domain" value="Add Domain" class="button" />
        </form>

        <!-- Rate Limiting Section -->
        <h2>Rate Limiting</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('wp_custom_endpoint_options_group');
            do_settings_sections('wp-custom-endpoint-settings');
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row">Enable Rate Limiting</th>
                    <td>
                        <label>
                            <input type="checkbox" name="wp_custom_endpoint_options[rate_limit_enabled]" value="1" <?php checked($rate_limit_enabled, 1); ?> />
                            Enable Rate Limiting
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Rate Limit</th>
                    <td>
                        <input type="number" name="wp_custom_endpoint_options[rate_limit]" value="<?php echo esc_attr($rate_limit); ?>" min="1" />
                        <p class="description">Maximum number of requests allowed per time window.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Rate Limit Window (seconds)</th>
                    <td>
                        <input type="number" name="wp_custom_endpoint_options[rate_limit_window]" value="<?php echo esc_attr($rate_limit_window); ?>" min="1" />
                        <p class="description">Time window (in seconds) for the rate limit.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
}
