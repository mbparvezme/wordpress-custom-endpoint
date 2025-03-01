<?php
namespace WP_Custom_Endpoint;

if (!defined('ABSPATH')) {
    exit;
}

class WP_Custom_Endpoint_Utility
{
    private $options;

    public function __construct()
    {
        // Retrieve plugin options
        $this->options = get_option('wp_custom_endpoint_options', []);
    }

    /**
     * Get the list of allowed domains.
     *
     * @return array List of allowed domains.
     */
    public function get_allowed_domains()
    {
        return isset($this->options['allowed_domains']) ? $this->options['allowed_domains'] : [];
    }

    /**
     * Get the rate limit settings for a specific domain.
     *
     * @param string $domain The domain to check.
     * @return array Rate limit settings for the domain.
     */
    public function get_rate_limit_settings($domain)
    {
        $rate_limits = isset($this->options['rate_limits']) ? $this->options['rate_limits'] : [];
        return isset($rate_limits[$domain]) ? $rate_limits[$domain] : ['enabled' => false, 'limit' => 100, 'window' => 60];
    }

    /**
     * Check if rate limiting is enabled for a specific domain.
     *
     * @param string $domain The domain to check.
     * @return bool Whether rate limiting is enabled for the domain.
     */
    public function is_rate_limit_enabled($domain)
    {
        $settings = $this->get_rate_limit_settings($domain);
        return $settings['enabled'];
    }

    /**
     * Get the rate limit for a specific domain.
     *
     * @param string $domain The domain to check.
     * @return int Rate limit for the domain.
     */
    public function get_rate_limit($domain)
    {
        $settings = $this->get_rate_limit_settings($domain);
        return $settings['limit'];
    }

    /**
     * Get the rate limit window for a specific domain.
     *
     * @param string $domain The domain to check.
     * @return int Rate limit window (in seconds) for the domain.
     */
    public function get_rate_limit_window($domain)
    {
        $settings = $this->get_rate_limit_settings($domain);
        return $settings['window'];
    }

    /**
     * Check if the request domain is allowed to access the API.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool|\WP_Error True if allowed, WP_Error if not allowed.
     */
    public function check_domain_access($request)
    {
        $allowed_domains = $this->get_allowed_domains();
        $origin = $request->get_header('origin');
        $referer = $request->get_header('referer');

        // Extract the domain from the origin or referer header
        $domain = '';
        if (!empty($origin)) {
            $domain = parse_url($origin, PHP_URL_HOST);
        } elseif (!empty($referer)) {
            $domain = parse_url($referer, PHP_URL_HOST);
        }

        // If no domain is provided (e.g., direct access), deny access
        if (empty($domain)) {
            return new \WP_Error(
                'rest_forbidden',
                __('You are not allowed to access this endpoint.'),
                ['status' => 403]
            );
        }

        // Allow access only if the domain is in the allowed list
        if (in_array($domain, $allowed_domains)) {
            return true;
        }

        // Deny access if the domain is not allowed
        return new \WP_Error('rest_forbidden', __('You are not allowed to access this endpoint.'), ['status' => 403]);
    }

    /**
     * Check the rate limit for a specific domain.
     *
     * @param string $domain The domain to check.
     * @return bool|\WP_Error True if within rate limit, WP_Error if rate limit is exceeded.
     */
    public function check_rate_limit($domain)
    {
        // Skip rate limiting if it's disabled for the domain
        if (!$this->is_rate_limit_enabled($domain)) {
            return true;
        }

        $rate_limit = $this->get_rate_limit($domain);
        $rate_limit_window = $this->get_rate_limit_window($domain);

        // Use the domain as part of the transient key to make it domain-specific
        $transient_key = 'rate_limit_' . sanitize_key($domain);
        $request_count = get_transient($transient_key) ?: 0;

        // Check if the rate limit is exceeded
        if ($request_count >= $rate_limit) {
            return new \WP_Error('rate_limit_exceeded', __('Too many requests. Please try again later.'), ['status' => 429]);
        }

        // Increment request count
        set_transient($transient_key, $request_count + 1, $rate_limit_window);
        return true;
    }
}