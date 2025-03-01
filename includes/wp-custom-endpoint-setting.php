<?php
namespace WP_Custom_Endpoint;

if (!defined('ABSPATH')) {
    exit;
}

class WP_Custom_Endpoint_Setting
{
    private $options;

    public function __construct()
    {
        // Add settings page to the admin menu
        add_action('admin_menu', [$this, 'add_settings_page']);
        // Register settings and fields
        add_action('admin_init', [$this, 'register_settings']);
        // Retrieve plugin options
        $this->options = get_option('wp_custom_endpoint_options', []);
    }

    /**
     * Add the settings page to the WordPress admin menu.
     */
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

    /**
     * Register plugin settings and fields.
     */
    public function register_settings()
    {
        register_setting(
            'wp_custom_endpoint_options_group', // Option group
            'wp_custom_endpoint_options',      // Option name
            [$this, 'sanitize_settings']       // Sanitization callback
        );
    }

    /**
     * Sanitize settings before saving.
     *
     * @param array $input Unsanitized input from the form.
     * @return array Sanitized settings.
     */
    public function sanitize_settings($input)
    {
        $sanitized_input = [];

        if (isset($input['allowed_domains'])) {
            // If $input['allowed_domains'] is a string, convert it to an array
            if (is_string($input['allowed_domains'])) {
                $domains = array_map('trim', explode(',', $input['allowed_domains']));
            } else {
                $domains = (array) $input['allowed_domains'];
            }

            // Sanitize each domain
            $sanitized_input['allowed_domains'] = array_map('sanitize_text_field', $domains);
        }

        if (isset($input['rate_limits'])) {
            foreach ($input['rate_limits'] as $domain => $settings) {
                $sanitized_input['rate_limits'][$domain] = [
                    'enabled' => isset($settings['enabled']) ? (bool) $settings['enabled'] : false,
                    'limit' => isset($settings['limit']) ? absint($settings['limit']) : 100,
                    'window' => isset($settings['window']) ? absint($settings['window']) : 60,
                ];
            }
        }

        return $sanitized_input;
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page()
    {
        // Retrieve plugin options
        $options = get_option('wp_custom_endpoint_options', []);
        $allowed_domains = isset($options['allowed_domains']) ? (array) $options['allowed_domains'] : [];
        $rate_limits = isset($options['rate_limits']) ? $options['rate_limits'] : [];

        // Handle form submissions
        if (isset($_POST['wp_custom_endpoint_nonce']) && wp_verify_nonce($_POST['wp_custom_endpoint_nonce'], 'wp_custom_endpoint_settings')) {
            $this->handle_form_submission($allowed_domains, $rate_limits);
        }

        ?>
        <div class="wrap">
            <h1>WP Custom Endpoint Settings</h1>

            <!-- Allowed Domains Section -->
            <h2>Allowed Domains</h2>
            <form method="post" action="">
                <?php wp_nonce_field('wp_custom_endpoint_settings', 'wp_custom_endpoint_nonce'); ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all-domains"></th>
                            <th>Domain</th>
                            <th>Rate Limit Enabled</th>
                            <th>Rate Limit</th>
                            <th>Rate Limit Window (seconds)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($allowed_domains)): ?>
                                <?php foreach ($allowed_domains as $domain): ?>
                                        <tr>
                                            <td><input type="checkbox" name="selected_domains[]" value="<?php echo esc_attr($domain); ?>"></td>
                                            <td><?php echo esc_html($domain); ?></td>
                                            <td>
                                                <input type="checkbox" name="rate_limits[<?php echo esc_attr($domain); ?>][enabled]" value="1" <?php checked($rate_limits[$domain]['enabled'] ?? false, true); ?>>
                                            </td>
                                            <td>
                                                <input type="number" name="rate_limits[<?php echo esc_attr($domain); ?>][limit]" value="<?php echo esc_attr($rate_limits[$domain]['limit'] ?? 100); ?>" min="1">
                                            </td>
                                            <td>
                                                <input type="number" name="rate_limits[<?php echo esc_attr($domain); ?>][window]" value="<?php echo esc_attr($rate_limits[$domain]['window'] ?? 60); ?>" min="1">
                                            </td>
                                            <td>
                                                <button type="submit" name="edit_domain" value="<?php echo esc_attr($domain); ?>" class="button">Edit</button>
                                                <button type="submit" name="delete_domain" value="<?php echo esc_attr($domain); ?>" class="button">Delete</button>
                                            </td>
                                        </tr>
                                <?php endforeach; ?>
                        <?php else: ?>
                                <tr>
                                    <td colspan="6">No domains added yet.</td>
                                </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Batch Actions -->
                <div style="margin-top: 20px;">
                    <button type="submit" name="batch_enable_rate_limit" class="button">Enable Rate Limit for Selected</button>
                    <button type="submit" name="batch_disable_rate_limit" class="button">Disable Rate Limit for Selected</button>
                    <button type="submit" name="batch_delete_domains" class="button">Delete Selected Domains</button>
                </div>

                <!-- Add New Domain -->
                <h3>Add New Domain</h3>
                <input type="text" name="new_domain" placeholder="Enter a new domain" />
                <input type="submit" name="add_domain" value="Add Domain" class="button" />
            </form>
        </div>
        <?php
    }

    /**
     * Handle form submissions for adding, editing, deleting, and batch operations.
     *
     * @param array $allowed_domains Current list of allowed domains.
     * @param array $rate_limits Current rate limit settings for domains.
     */
    private function handle_form_submission(&$allowed_domains, &$rate_limits)
    {
        // Ensure $allowed_domains is an array
        if (!is_array($allowed_domains)) {
            $allowed_domains = [];
        }

        // Add new domain
        if (isset($_POST['add_domain']) && !empty($_POST['new_domain'])) {
            $new_domain = sanitize_text_field($_POST['new_domain']);
            if (!in_array($new_domain, $allowed_domains)) {
                $allowed_domains[] = $new_domain;
                $rate_limits[$new_domain] = ['enabled' => false, 'limit' => 100, 'window' => 60];
            }
        }

        // Delete domain
        if (isset($_POST['delete_domain'])) {
            $domain_to_delete = sanitize_text_field($_POST['delete_domain']);
            $allowed_domains = array_diff($allowed_domains, [$domain_to_delete]);
            unset($rate_limits[$domain_to_delete]);
        }

        // Batch delete domains
        if (isset($_POST['batch_delete_domains']) && !empty($_POST['selected_domains'])) {
            $selected_domains = array_map('sanitize_text_field', $_POST['selected_domains']);
            $allowed_domains = array_diff($allowed_domains, $selected_domains);
            foreach ($selected_domains as $domain) {
                unset($rate_limits[$domain]);
            }
        }

        // Batch enable rate limit
        if (isset($_POST['batch_enable_rate_limit']) && !empty($_POST['selected_domains'])) {
            $selected_domains = array_map('sanitize_text_field', $_POST['selected_domains']);
            foreach ($selected_domains as $domain) {
                $rate_limits[$domain]['enabled'] = true;
            }
        }

        // Batch disable rate limit
        if (isset($_POST['batch_disable_rate_limit']) && !empty($_POST['selected_domains'])) {
            $selected_domains = array_map('sanitize_text_field', $_POST['selected_domains']);
            foreach ($selected_domains as $domain) {
                $rate_limits[$domain]['enabled'] = false;
            }
        }

        // Save updated settings
        update_option('wp_custom_endpoint_options', [
            'allowed_domains' => $allowed_domains,
            'rate_limits' => $rate_limits,
        ]);
    }
}