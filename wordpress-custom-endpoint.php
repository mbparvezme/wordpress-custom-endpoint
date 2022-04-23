<?php

    /**
     * Custom Endpoints
     *
     * @author          M B Parvez
     * @copyright       M B Parvez & Gosoft.io
     * @license         GPL-2.0-or-later
     * 
     * Plugin Name:     Custom Endpoints
     * Plugin URI:      https://github.com/mbparvezme/wordpress-custom-endpoint
     * Description:     Custom Wordpress rest API endpoints
     * Version:         0.0.1
     * Author:          M B Parvez
     * Author URI:      https://www.mbparvez.me
     * License:         GPL v2 or later
     * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
    */

    function go_posts( $request ){
        $args['post_type']      = 'post';
        $args['post_status']    = 'publish';
        if(isset($request['slug'])){
            $args['name']       = $request['slug'];
        }else{
            $args['numberposts']    = $request['per_page']??12;
            $args['category']       = $request['categories']??0;
            $args['exclude']        = $request['exclude']??[];
            if(isset($request['sticky'])){
                $sticky = get_option('sticky_posts');
                if(empty($sticky)) return [];
                $args['post__in']   = $sticky;
                $args['ignore_sticky_posts']    = 1;
            }
        }

		$result = get_posts( $args );
		$data = [];
		if(!isset($request['slug'])){
    		$i = 0;
    		foreach($result as $post){
                $data[$i] = create_post_snip($post, isset($request['slug']));
    			$i++;
    		}
		}else{
		    setPostViews($result[0]->ID);
		    $data = create_post_snip($result[0], isset($request['slug']));
		}
		return $data;
    }

    function go_categories( $request ){
        $args['taxonomy']       = 'category';
        $args['post_status']    = 'publish';
        $args['number']         = $request['per_page']??0;
        $args['orderby']        = $request['orderby']??'id';
        $args['order']          = $request['exclude']??'ASC';
        $args['hide_empty']     = $request['hide_empty']??0;
        $args['include']        = $request['include']??[];
        $args['exclude']        = $request['exclude']??[];
        $args['count']          = $request['count']??false;
        $args['object_ids']     = $request['ids']??null;
        $args['slug']           = $request['slug']??'';
        $args['fields']         = $request['fields']??'all';
        $args['offset']         = $request['offset']??'';

		$categories             = get_categories($args);

		$data                   = [];
		$i                      = 0;
		foreach($categories as $category){
            $data[$i]['id']     = $category->cat_ID;
            $data[$i]['name']   = $category->name;
            $data[$i]['slug']   = $category->slug;
            $data[$i]['count']  = $category->category_count;
			$i++;
		}

		return $data;
    }

    function go_search( $request ){
        $data = [];
        if(isset($request['s']) AND $request['s'] !== ""){
            $args = [
    			'post_type'              => 'post',
    			'post_status'            => 'publish',
    			's' => $request['s'],
    		];
    		$posts = get_posts( $args );
            $i = 0;

    		foreach($posts as $post){
    		    $data[$i] = create_post_snip($post, isset($request['slug']));
    			$i++;
    		}
        }
    	return $data;
    }

    function go_pages( $request ){
        $args['post_type']      = 'page';
        $args['post_status']    = 'publish';
        if(isset($request['slug'])){
            $args['name']       = $request['slug'];
        }else{
            $args['number']         = $request['per_page']??8;
        }

		$pages = get_pages( $args );
		$data = [];
		if(!isset($request['slug'])){
    		$i = 0;
    		foreach($pages as $page){
                $data[$i]['title'] = $page->post_title;
                $data[$i]['slug'] = $page->post_name;
    			$i++;
    		}
		}else{
		    $data['title']      = $pages[0]->post_title;
            $data['content']    = $pages[0]->post_content;
            $data['slug']       = $pages[0]->post_name;
		}

		return $data;
    }

    function create_post_snip($post, $content = FALSE){
        $author_id          = get_post_field( 'post_author', $post->ID );
	    $data['id']         = $post->ID;
	    $data['title']      = $post->post_title;
	    
	    if($content)
	    $data['content']    = $post->post_content;
	    
	    $data['slug']       = $post->post_name;
	    $data['views']      = getPostViews($post->ID);
	    $data['duration']   = ceil(str_word_count($post->post_content) / 225);
	    $data['date']       = date('d M, Y', strtotime($post->post_date));
	    $data['img']        = [
			'thumb'             => get_the_post_thumbnail_url( $post->ID, 'thumbnail' ),
			'medium'            => get_the_post_thumbnail_url( $post->ID, 'medium' ),
			'original'          => get_the_post_thumbnail_url( $post->ID, 'large' ),
		];

		$categories = get_the_category( $post->ID );
		$j = 0;
		foreach($categories as $category){
		    $data['categories'][$j]['id']       = $category->term_id;
		    $data['categories'][$j]['name']     = $category->name;
		    $data['categories'][$j]['slug']     = $category->slug;
		    $data['categories'][$j]['count']    = $category->category_count;
		    $j++;
		}

		$data['meta']       = [
			'author_id'         => $author_id,
			'author_name'       => get_the_author_meta( 'display_name', $author_id )
		];
		return $data;
    }

    function namespace_get_search_args() {
        $args = [];
        $args['s'] = [
           'description' => esc_html__( 'The search term.', 'namespace' ),
           'type'        => 'string',
       ];
    }

    function getPostViews($postID){
        $count_key = 'post_views_count';
        $count = get_post_meta($postID, $count_key, true);
        if($count==''){
            delete_post_meta($postID, $count_key);
            add_post_meta($postID, $count_key, '0');
            return "0";
        }
        return $count < 1000 ? (int)$count : ( $count < 1000000 ? ($count / 1000).'k' : ($count / 1000000).'m' );
    }

    function setPostViews($postID) {
        $count_key = 'post_views_count';
        $count = get_post_meta($postID, $count_key, true);
        if($count==''){
            $count = 0;
            delete_post_meta($postID, $count_key);
            add_post_meta($postID, $count_key, '0');
        }else{
            $count++;
            update_post_meta($postID, $count_key, $count);
        }
    }

    // Remove issues with prefetching adding extra views
    remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);

    add_action( 'rest_api_init', function() {
        // POST ENDPOINTS
        register_rest_route( 'go/v1', 'posts', ['method' => 'GET', 'callback' => 'go_posts' ]);
        register_rest_route( 'go/v1', 'posts/(?P<slug>.+)', ['method' => 'GET', 'callback' => 'go_posts' ]);
        // CATEGORY ENDPOINTS
        register_rest_route( 'go/v1', 'categories', ['method' => 'GET', 'callback' => 'go_categories' ]);
        // PAGE ENDPOINTS
        register_rest_route( 'go/v1', 'pages', ['method' => 'GET', 'callback' => 'go_pages' ]);
        register_rest_route( 'go/v1', 'pages/(?P<slug>.+)', ['method' => 'GET', 'callback' => 'go_pages' ]);
        // SEARCH ENDPOINTS
        register_rest_route( 'go/v1', 'search', ['method' => 'GET', 'callback' => 'go_search', 'args' => namespace_get_search_args() ]);
    });
