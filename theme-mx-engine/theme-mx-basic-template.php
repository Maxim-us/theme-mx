<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class ThemeMXBasicTemplate {

	public $post_type = 'page';

	public $post_id = 0;

	public function __construct( $args = [] )
	{

		// Set post type
		$this->set_post_type( $args['post_type'] ?? NULL );

		// Set post id
		$this->set_post_id( $args['post_id'] ?? NULL );

	}

	/*
	* Set post type
	*/
	public function set_post_type( $post_type = NULL )
	{

		if( get_post_type() AND $post_type !== NULL ) {

			$this->post_type = $post_type;

		} else {

			$this->post_type = get_post_type();

		}

	}

	/*
	* Set post id
	*/
	public function set_post_id( $post_id = NULL )
	{

		if( get_the_ID() AND $post_id !== NULL ) {

			$this->post_id = $post_id;

		} else {

			$this->post_id = get_the_ID();

		}

	}

	/*
	* Set Global JS variable
	*/
	public function mx_global_javascript_vars()
	{
		$script = '<script>';

			$script .= 'window.theme_mx_data = {';

				$script .= '"post_type":"' 	. $this->post_type . '",';

				$script .= '"post_id":"' 	. $this->post_id . '",';

				$script .= '"ajax_url":"' 	. admin_url( "admin-ajax.php" ) . '"';

			$script .= '};';

		$script .= '</script>';

		return $script;
	}

	/*
	* Render template
	*/
	public function render()
	{
		// Set Global JS variable
		echo $this->mx_global_javascript_vars();

		// Display app container
		echo '<div id="app"></div>';

	}

}