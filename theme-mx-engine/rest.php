<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
* Menus
*/
if ( ! function_exists( 'theme_mx_get_menus' ) ) :

	function theme_mx_get_menus( $request ) {

		$menu_id = $request['menu'];

		$locations = get_nav_menu_locations();

		if( $locations && isset( $locations[$menu_id] ) ) {

			return wp_get_nav_menu_items( $locations[$menu_id] );

		} else {

			return new WP_Error( 'menu_not_found', 'Menu "' . $menu_id . '" not found', [ 'status' => 404 ] );

		}

	}

	add_action( 'rest_api_init', function () {

	    register_rest_route( 'theme_mx/v1', '/menus/(?P<menu>[a-zA-Z0-9_-]+)', array(
	        'methods' => 'GET',
	        'callback' => 'theme_mx_get_menus',
	        'permission_callback' => '__return_true'
	    ) );

	} );

endif;

/*
*  Get Thumbnails
*/
add_action( 'rest_api_init', function(){

	register_rest_field( [

		'post',
		'page',
		'mxtpfmt_books'
		
	], 'thumbnails', array(

		'get_callback' => function( $post, $field_name, $request ) {

			$post_id = $post['id'];

			$thumbnails = [];

			$the_thumbnail_full = get_the_post_thumbnail_url( $post_id );

			if( $the_thumbnail_full ) {

				// full
				$thumbnails['full'] = $the_thumbnail_full;
				
				// thumbnail
				$the_thumbnail_thumbnail = get_the_post_thumbnail_url( $post_id, 'thumbnail' );

				$thumbnails['thumbnail'] = $the_thumbnail_thumbnail;

				// medium
				$the_thumbnail_medium = get_the_post_thumbnail_url( $post_id, 'medium' );

				$thumbnails['medium'] = $the_thumbnail_medium;

				// large
				$the_thumbnail_large = get_the_post_thumbnail_url( $post_id, 'large' );

				$thumbnails['large'] = $the_thumbnail_large;

			}

			return $thumbnails;
		},
		'update_callback' => null

	) );

} );

/*
* Get CPT posts
*/
if ( ! function_exists( 'theme_mx_get_cpt_posts' ) ) :

	function theme_mx_get_cpt_posts( $route, $post ) {

        if ( $post->post_type !== 'post' AND $post->post_type !== 'page' ) {

	        $route = '/wp/v2/' . $post->post_type . '/' . $post->ID;

	    }
	 
	    return $route;

	}
	add_filter( 'rest_route_for_post', 'theme_mx_get_cpt_posts', 10, 2 );

endif;

/*
* Get number of posts
*/
if ( ! function_exists( 'theme_mx_get_posts_count' ) ) :

	
	function theme_mx_get_posts_count( $request ) {

		$post_type = $request['post_type'];

		if( post_type_exists( $post_type ) ) {

			$post_type = sanitize_text_field( $post_type );

			global $wpdb;

			$posts_table = $wpdb->prefix . 'posts';

			$number_posts = $wpdb->get_var(
				"SELECT COUNT(ID)
					FROM $posts_table
					WHERE post_type = '$post_type'
					AND post_status = 'publish'
				"
			);

			return intval( $number_posts );

		} else {

			return new WP_Error( 'post_type_not_found', 'Post Type not found', [ 'status' => 404 ] );

		}

	}

	add_action( 'rest_api_init', function () {

	    register_rest_route( 'theme_mx/v1', '/count/post_type=(?P<post_type>[a-zA-Z0-9_-]+)', array(
	        'methods' => 'GET',
	        'callback' => 'theme_mx_get_posts_count',
	        'permission_callback' => '__return_true'
	    ) );

	} );
	

endif;

/*
* Get Archive
*/
if ( ! function_exists( 'theme_mx_get_archive_data' ) ) :

	function theme_mx_get_archive_data( $request ) {

		$post_type = $request['post_type'];

		if( post_type_exists( $post_type ) ) {

			$get_archives = get_post_type_object( $post_type );

			$archive_data = [];

			if( $get_archives ) {

				$archive_title = $get_archives->label;

				$archive_description = $get_archives->description;

				$archive_data = [

					'archive_title' 		=> $archive_title,
					'archive_description' 	=> $archive_description

				];

			}

			return $archive_data;

		} else {

			return new WP_Error( 'archive_not_found', 'Archive not found', [ 'status' => 404 ] );

		}

	}

	add_action( 'rest_api_init', function () {

	    register_rest_route( 'theme_mx/v1', '/archive/post_type=(?P<post_type>[a-zA-Z0-9_-]+)', array(
	        'methods' => 'GET',
	        'callback' => 'theme_mx_get_archive_data',
	        'permission_callback' => '__return_true'
	    ) );

	} );

endif;

/*
* Get Sidebars (Widgets)
*/
if ( ! function_exists( 'mx_get_sidebars' ) AND ! function_exists( 'mx_get_sidebar' ) AND ! function_exists( 'mx_valid_sidebar' ) ) :

	function mx_get_sidebars( $request ) {

		if ( ! $request instanceof WP_REST_Request ) {

	        throw new InvalidArgumentException( __METHOD__ . ' expects an instance of WP_REST_Request' );
	    
	    }

	    global $wp_registered_sidebars;

	    $sidebars = [];

	    foreach ( (array) $wp_registered_sidebars as $slug => $sidebar ) {

	        $sidebars[] = $sidebar;

	    }

	    return new WP_REST_Response( $sidebars, 200 );

	}

	function mx_get_sidebar( $request ) {

	    if ( ! $request instanceof WP_REST_Request ) {

	        throw new InvalidArgumentException( __METHOD__ . ' expects an instance of WP_REST_Request' );
	   
	    }

	    $sidebar_id = $request['sidebar'];

	    $sidebar = mx_valid_sidebar( $sidebar_id );

	    ob_start();

	    dynamic_sidebar( $sidebar_id );

	    $sidebar['rendered'] = ob_get_clean();

	    if( $sidebar['rendered'] == '' ) {

	    	return new WP_Error( 'sidebar_not_found', 'Sidebar not found', [ 'status' => 404 ] );

	    } else {

	    	return $sidebar;

	    }

	}

	function mx_valid_sidebar( $sidebar ) {

	    global $wp_registered_sidebars;

	    $sidebar_id = sanitize_title( $sidebar );

	    foreach ( (array) $wp_registered_sidebars as $key => $sidebar ) {

	        if ( sanitize_title( $sidebar['name'] ) == $sidebar_id ) {

	            return $sidebar;

	        }

	    }

	    foreach ( (array) $wp_registered_sidebars as $key => $sidebar ) {

	        if ( $key === $sidebar_id ) {

	            return $sidebar;

	        }

	    }

	    return null;
	    
	}

	add_action( 'rest_api_init', function () {

		register_rest_route( 'theme_mx/v1', '/sidebars', [
		    [
		        'methods'             => WP_REST_Server::READABLE,
		        'callback'            => 'mx_get_sidebars',
	            'permission_callback' => '__return_true'
		    ],
		] );

		register_rest_route( 'theme_mx/v1', '/sidebars/(?P<sidebar>[a-zA-Z0-9_-]+)', [
	        [
	            'methods'             => 'GET',
	            'callback'            => 'mx_get_sidebar',
	            'permission_callback' => '__return_true'
	        ],
	    ] );

	} );

endif;