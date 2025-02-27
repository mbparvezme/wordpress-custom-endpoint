<?php
if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

class WP_Custom_Endpoint
{

  private $utility;

  public function __construct()
  {
    // Initialize utility class for domain and rate-limit checking
    $this->utility = new WP_Custom_Endpoint_Utility();
    // Register REST API routes
    add_action('rest_api_init', [$this, 'register_routes']);
  }

  // Register REST API routes
  public function register_routes()
  {
    // Endpoint to fetch posts
    register_rest_route('wp/v1', 'posts', [
      'methods' => 'GET',
      'callback' => [$this, 'get_posts'],
      'permission_callback' => [$this->utility, 'check_domain_access']
    ]);

    // Endpoint to fetch a single post by slug
    register_rest_route('wp/v1', 'posts/(?P<slug>.+)', [
      'methods' => 'GET',
      'callback' => [$this, 'get_posts'],
      'permission_callback' => [$this->utility, 'check_domain_access']
    ]);

    // Endpoint to fetch categories
    register_rest_route('wp/v1', 'categories', [
      'methods' => 'GET',
      'callback' => [$this, 'get_categories'],
      'permission_callback' => [$this->utility, 'check_domain_access']
    ]);

    // Endpoint to fetch pages
    register_rest_route('wp/v1', 'pages', [
      'methods' => 'GET',
      'callback' => [$this, 'get_pages'],
      'permission_callback' => [$this->utility, 'check_domain_access']
    ]);

    // Endpoint to fetch a single page by slug
    register_rest_route('wp/v1', 'pages/(?P<slug>.+)', [
      'methods' => 'GET',
      'callback' => [$this, 'get_pages'],
      'permission_callback' => [$this->utility, 'check_domain_access']
    ]);

    // Endpoint to search posts
    register_rest_route('wp/v1', 'search', [
      'methods' => 'GET',
      'callback' => [$this, 'get_search'],
      'args' => $this->get_search_args(),
      'permission_callback' => [$this->utility, 'check_domain_access']
    ]);
  }

  // Fetch posts based on request parameters
  public function get_posts($request)
  {
    // Check rate limit
    $rate_limit_check = $this->utility->check_rate_limit();
    if (is_wp_error($rate_limit_check)) {
      return $rate_limit_check;
    }

    // Prepare query arguments
    $args = [
      'post_type'      => 'post',
      'posts_per_page' => $request['per_page'] ?? 12,
      'paged'          => $request['page'] ?? 1,
      'orderby'        => $request['orderby'] ?? 'date',
      'order'          => $request['order'] ?? 'DESC',
      'post_status'    => 'publish',
    ];

    // Check if a slug is provided
    $include_content = !empty($request['slug']);

    if ($include_content) {
      $args['name'] = $request['slug'];
    }
    if (!empty($request['category_id'])) {
      $args['cat'] = $request['category_id'];
    }
    if (!empty($request['exclude'])) {
      $args['post__not_in'] = explode(',', $request['exclude']);
    }

    // Fetch posts
    $posts = get_posts($args);

    // Handle no posts found
    if (empty($posts)) {
      return new WP_Error('no_posts_found', __('No posts found.'), ['status' => 404]);
    }

    // Increment post views for single post requests
    if ($include_content) {
      $this->set_post_views($posts[0]->ID);
    }

    // Format and return posts
    return array_map(function ($post) use ($include_content) {
      return $this->create_post_snip($post, $include_content);
    }, $posts);
  }

  // Fetch categories based on request parameters
  public function get_categories($request)
  {
    // Check rate limit
    $rate_limit_check = $this->utility->check_rate_limit();
    if (is_wp_error($rate_limit_check)) {
      return $rate_limit_check;
    }

    // Prepare query arguments
    $args = [
      'taxonomy'   => 'category',
      'hide_empty' => true,
      'orderby'    => $request['orderby'] ?? 'name',
      'order'      => $request['order'] ?? 'ASC',
      'fields'     => 'all',
    ];

    // Fetch categories
    $categories = get_categories($args);

    // Handle no categories found
    if (empty($categories)) {
      return new WP_Error('no_categories_found', __('No categories found.'), ['status' => 404]);
    }

    // Format and return categories
    return array_map(function ($cat) {
      return [
        'id' => $cat->term_id,
        'name' => $cat->name,
        'slug' => $cat->slug,
        'count' => $cat->count,
      ];
    }, $categories);
  }

  // Fetch pages based on request parameters
  public function get_pages($request)
  {
    // Check rate limit
    $rate_limit_check = $this->utility->check_rate_limit();
    if (is_wp_error($rate_limit_check)) {
      return $rate_limit_check;
    }

    // Prepare query arguments
    $args = [
      'post_type'      => 'page',
      'posts_per_page' => $request['per_page'] ?? 12,
      'paged'          => $request['page'] ?? 1,
      'orderby'        => $request['orderby'] ?? 'date',
      'order'          => $request['order'] ?? 'DESC',
      'post_status'    => 'publish',
    ];

    // Check if a slug is provided
    if (!empty($request['slug'])) {
      $args['name'] = $request['slug'];
    }

    // Fetch pages
    $pages = get_posts($args);

    // Handle no pages found
    if (empty($pages)) {
      return new WP_Error('no_pages_found', __('No pages found.'), ['status' => 404]);
    }

    // Increment post views for single page requests
    if (!empty($request['slug'])) {
      $this->set_post_views($pages[0]->ID);
    }

    // Format and return pages
    return array_map([$this, 'create_post_snip'], $pages);
  }

  // Search posts based on a search query
  public function get_search($request)
  {
    // Check rate limit
    $rate_limit_check = $this->utility->check_rate_limit();
    if (is_wp_error($rate_limit_check)) {
      return $rate_limit_check;
    }

    // Check if search query is provided
    if (empty($request['s'])) {
      return new WP_Error('missing_search_query', __('Search query is required.'), ['status' => 400]);
    }

    // Prepare query arguments
    $args = [
      'post_type'      => 'post',
      'posts_per_page' => $request['per_page'] ?? 12,
      'paged'          => $request['page'] ?? 1,
      's'              => $request['s'], // Search query
      'post_status'    => 'publish',
    ];

    // Fetch posts
    $posts = get_posts($args);

    // Handle no posts found
    if (empty($posts)) {
      return new WP_Error('no_posts_found', __('No posts found.'), ['status' => 404]);
    }

    // Format and return posts
    return array_map([$this, 'create_post_snip'], $posts);
  }

  // Create a post snippet for the API response
  private function create_post_snip($post, $include_content = false)
  {
    $author_id = $post->post_author;
    $data = [
      'id' => $post->ID,
      'title' => get_the_title($post->ID),
      'slug' => $post->post_name,
      'excerpt' => get_the_excerpt($post->ID),
      'image' => get_the_post_thumbnail_url($post->ID, 'full') ?: '',
      'published_at' => get_the_date('Y-m-d H:i:s', $post->ID),
      'views' => get_post_meta($post->ID, 'post_views_count', true) ?: 0,
      'read_duration' => ceil(str_word_count(strip_tags($post->post_content)) / 200),
      'categories' => array_map(function ($cat) {
        return [
          'id' => $cat->term_id,
          'name' => $cat->name,
          'slug' => $cat->slug,
        ];
      }, get_the_category($post->ID) ?: []),
    ];

    $data['meta'] = [
      'views'     => $this->get_post_views($post->ID),
      'duration'  => ceil(str_word_count($post->post_content) / 225),
      'date'      => date('d M, Y', strtotime($post->post_date)),
      'author'    => [
        'id'    => $author_id,
        'name'  => get_the_author_meta('display_name', $author_id),
        'img'   => get_avatar_url($author_id),
      ]
    ];

    // Include post content if requested
    if ($include_content) {
      $data['content'] = $post->post_content;
      $data['comments'] = $this->get_post_comments($post->ID);
    }

    return $data;
  }

  // Get post views count
  private function get_post_views($postID)
  {
    $count_key = 'post_views_count';
    $count = get_post_meta($postID, $count_key, true);
    if ($count == '') {
      delete_post_meta($postID, $count_key);
      add_post_meta($postID, $count_key, '0');
      return "0";
    }
    return $count < 1000 ? (int)$count : ($count < 1000000 ? ($count / 1000) . 'k' : ($count / 1000000) . 'm');
  }

  // Increment post views count
  private function set_post_views($postID)
  {
    $count_key = 'post_views_count';
    $count = (int) get_post_meta($postID, $count_key, true);
    update_post_meta($postID, $count_key, $count + 1);
  }

  // Get comments for a post
  private function get_post_comments($post_id)
  {
    $comments = get_comments([
      'post_id' => $post_id, // Fetch comments for this post
      'status'  => 'approve', // Only fetch approved comments
      'order'   => 'ASC',    // Order comments by date (oldest first)
    ]);

    return array_map(function ($comment) {
      return [
        'id'           => $comment->comment_ID,
        'author_name'  => $comment->comment_author,
        'author_email' => $comment->comment_author_email,
        'author_url'   => $comment->comment_author_url,
        'date'         => $comment->comment_date,
        'content'      => $comment->comment_content,
        'avatar'       => get_avatar_url($comment->comment_author_email),
      ];
    }, $comments);
  }

  // Define search endpoint arguments
  private function get_search_args()
  {
    $args = [];
    $args['s'] = [
      'description' => esc_html__('The search term.', 'wp-custom-endpoint'),
      'type' => 'string',
      'validate' => function ($term) {
        return sanitize_text_field($term);
      },
    ];
    return $args;
  }
}
