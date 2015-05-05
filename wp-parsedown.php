<?php
/*
Plugin Name: WP-Parsedown
Plugin URI: https://github.com/petermolnar/wp-parsedown
Description: [Parsedown Extra](www.parsedown.org/demo?extra=1) on-the-fly
Version: 0.3
Author: Peter Molnar <hello@petermolnar.eu>
Author URI: https://petermolnar.eu/
License: GPLv3
*/


if ( ! class_exists( 'WP_PARSEDOWN' ) ) :

include_once ( dirname(__FILE__) . '/lib/parsedown/Parsedown.php');
include_once ( dirname(__FILE__) . '/lib/parsedown-extra/ParsedownExtra.php');

/**
 * main wp-ghost class
 */
class WP_PARSEDOWN {

	public function __construct () {
		add_action( 'init', array(&$this,'init'));
	}

	public function init () {
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_excerpt', 'wpautop' );
		add_filter( 'the_content', array( &$this, 'parsedown'), 8, 1 );
	}

	public function parsedown ( $markdown ) {
		$post = get_post();

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG == true ) {
			$message = sprintf ( __('parsing post: %s', $this->plugin_constant),  $post->ID );
			error_log(  __CLASS__ . ": " . $message );
		}

		$parsedown = new ParsedownExtra();
		$parsedown->setBreaksEnabled(true);
		return $parsedown->text ( $markdown );
	}

}

$wp_parsedown = new WP_PARSEDOWN ();

endif;
