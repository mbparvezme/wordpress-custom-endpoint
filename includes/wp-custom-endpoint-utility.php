<?php
if (!defined('ABSPATH')) {
  exit;
}

class WP_Custom_Endpoint_Utility
{

  private $options;

  public function __construct()
  {
    // Retrieve plugin options
    $this->options = get_option('wp_custom_endpoint_options');
  }

  // Get allowed domains from settings
  public function get_allowed_domains()
  {
    $allowed_domains = isset($this->options['allowed_domains']) ? $this->options['allowed_domains'] : '';
    return array_map('trim', explode(',', $allowed_domains));
  }

  // Get rate limit from settings
  public function get_rate_limit()
  {
    return isset($this->options['rate_limit']) ? absint($this->options['rate_limit']) : 100;
  }

  // Get rate limit window from settings
  public function get_rate_limit_window()
  {
    return isset($this->options['rate_limit_window']) ? absint($this->options['rate_limit_window']) : 60;
  }

  // Check if rate limiting is enabled
  public function is_rate_limit_enabled()
  {
    return isset($this->options['rate_limit_enabled']) ? (bool) $this->options['rate_limit_enabled'] : false;
  }

  // Check if the request domain is allowed
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
      return new WP_Error(
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
    return new WP_Error('rest_forbidden', __('You are not allowed to access this endpoint.'), ['status' => 403]);
  }

  // Check rate limit
  public function check_rate_limit()
  {
    // Skip rate limiting if it's disabled
    if (!$this->is_rate_limit_enabled()) {
      return true;
    }

    $rate_limit = $this->get_rate_limit();
    $rate_limit_window = $this->get_rate_limit_window();

    $client_ip = $_SERVER['REMOTE_ADDR'];
    $transient_key = 'rate_limit_' . $client_ip;
    $request_count = get_transient($transient_key) ?: 0;

    if ($request_count >= $rate_limit) {
      return new WP_Error('rate_limit_exceeded', __('Too many requests. Please try again later.'), ['status' => 429]);
    }

    // Increment request count
    set_transient($transient_key, $request_count + 1, $rate_limit_window);
    return true;
  }
}
