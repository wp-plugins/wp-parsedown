<?php
/*
Plugin Name: WP-Parsedown
Plugin URI: https://github.com/petermolnar/wp-parsedown
Description: [Parsedown Extra](www.parsedown.org/demo?extra=1) on-the-fly
Version: 0.1
Author: Peter Molnar <hello@petermolnar.eu>
Author URI: https://petermolnar.eu/
License: GPLv3
*/


if ( ! class_exists( 'WP_PARSEDOWN' ) ) :

/* get the plugin abstract class*/
include_once ( dirname(__FILE__) . '/wp-common/plugin_abstract.php' );
include_once ( dirname(__FILE__) . '/lib/parsedown/Parsedown.php');
include_once ( dirname(__FILE__) . '/lib/parsedown-extra/ParsedownExtra.php');

/**
 * main wp-ghost class
 */
class WP_PARSEDOWN extends PluginAbstract {
	const key_save = 'saved';
	const key_delete = 'deleted';
	private $parsedown = null;

	/**
	 *
	 */
	public function plugin_post_construct () {
		$this->plugin_url = plugin_dir_url( __FILE__ );
		$this->plugin_dir = plugin_dir_path( __FILE__ );

		$this->common_url = $this->plugin_url . self::common_slug;
		$this->common_dir = $this->plugin_dir . self::common_slug;

		$this->admin_css_handle = $this->plugin_constant . '-admin-css';
		$this->admin_css_url = $this->common_url . 'wp-admin.css';

		$this->parsedown = new ParsedownExtra();
	}

	/**
	 * init hook function runs before admin panel hook, themeing and options read
	 */
	public function plugin_pre_init() {

	}

	/**
	 * additional init, steps that needs the plugin options
	 *
	 */
	public function plugin_post_init () {
		/* display markdown */
		add_filter( 'the_content', array(&$this, 'markdown_on_the_fly'), 1 );

	}


	/**
	 * activation hook function, to be extended
	 */
	public function plugin_activate() {
		/* we leave this empty to avoid not detecting WP network correctly */
	}

	/**
	 * deactivation hook function, to be extended
	 */
	public function plugin_deactivate () {
	}

	/**
	 * uninstall hook function, to be extended
	 */
	public function plugin_uninstall( $delete_options = true ) {
	}

	/**
	 * extending admin init
	 *
	 */
	public function plugin_extend_admin_init () {
	}

	/**
	 * admin help panel
	 */
	public function plugin_admin_help($contextual_help, $screen_id ) {

		/* add our page only if the screenid is correct */
		if ( strpos( $screen_id, $this->plugin_settings_page ) ) {
			$contextual_help = __('<p>Please visit <a href="http://wordpress.org/support/plugin/wp-ghost">the official support forum of the plugin</a> for help.</p>', $this->plugin_constant );
		}

		return $contextual_help;
	}

	/**
	 * admin panel, the admin page displayed for plugin settings
	 */
	public function plugin_admin_panel() {
		/**
		 * security, if somehow we're running without WordPress security functions
		 */
		if( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ){
			die( );
		}
		?>

		<div class="wrap">

		<script>
			jQuery(document).ready(function($) {
				jQuery( "#<?php echo $this->plugin_constant ?>-settings" ).tabs();
			});
		</script>

		<?php

		/* display donation form */
		//$this->plugin_donation_form();

		/**
		 * if options were saved, display saved message
		 */
		if (isset($_GET[ self::key_save ]) && $_GET[ self::key_save ]=='true' || $this->status == 1) { ?>
			<div class='updated settings-error'><p><strong><?php _e( 'Settings saved.' , $this->plugin_constant ) ?></strong></p></div>
		<?php }

		/**
		 * if options were delete, display delete message
		 */
		if (isset($_GET[ self::key_delete ]) && $_GET[ self::key_delete ]=='true' || $this->status == 2) { ?>
			<div class='error'><p><strong><?php _e( 'Plugin options deleted.' , $this->plugin_constant ) ?></strong></p></div>
		<?php }

		/**
		 * the admin panel itself
		 */
		?>

		<h2><?php printf ( __( '%s settings', $this->plugin_constant ), $this->plugin_name ) ; ?></h2>

		<form autocomplete="off" method="post" action="#" id="<?php echo $this->plugin_constant ?>-settings" class="plugin-admin">

			<?php wp_nonce_field( $this->plugin_constant ); ?>
			<ul class="tabs">
				<li><a href="#<?php echo $this->plugin_constant ?>-general" class="wp-switch-editor"><?php _e( 'Generic settings', $this->plugin_constant ); ?></a></li>
			</ul>

			<fieldset id="<?php echo $this->plugin_constant ?>-general">
			<legend><?php _e( 'General settings', $this->plugin_constant ); ?></legend>
			<dl>
				<dt>
					<label for="debug"><?php _e("Enable debug logging?", $this->plugin_constant); ?></label>
				</dt>
				<dd>
					<input type="checkbox" name="debug" id="debug" value="1" <?php checked($this->options['debug'],true); ?> />
					<span class="description"><?php _e('Enables log messages; if <a href="http://codex.wordpress.org/WP_DEBUG">WP_DEBUG</a> is enabled, notices and info level is displayed as well, otherwie only ERRORS are logged.', $this->plugin_constant); ?></span>
				</dd>
			</dl>
			</fieldset>
			<p class="clear">
				<input class="button-primary" type="submit" name="<?php echo $this->button_save ?>" id="<?php echo $this->button_save ?>" value="<?php _e('Save Changes', $this->plugin_constant ) ?>" />
			</p>
		</form>
		</div>
		<?php
	}

	/**
	 * extending options_save
	 *
	 */
	public function plugin_extend_options_save( $activating ) {
	}

	/**
	 * read hook; needs to be implemented
	 */
	public function plugin_extend_options_read( &$options ) {
	}

	/**
	 * options delete hook; needs to be implemented
	 */
	public function plugin_extend_options_delete(  ) {
	}

	/**
	 * need to do migrations from previous versions of the plugin
	 *
	 */
	public function plugin_options_migrate( &$options ) {
	}



	/**
	 * log wrapper to include options
	 *
	 */
	public function log ( $message, $log_level = LOG_WARNING ) {
		if ( !isset ( $this->options['debug'] ) || $this->options['debug'] != 1 )
			return false;
		else
			$this->utils->log ( $this->plugin_constant, $message, $log_level );
	}

	/**
	 *
	 */
	public function markdown_on_the_fly ( $markdown ) {
		$post = get_post();
		$this->log ( sprintf ( __('parsing post: %s', $this->plugin_constant),  $post->ID ) );
		return $this->parsedown->text ( $markdown );
	}

}

endif;


$wp_parsedown_defaults = array (
	'debug' => 0,
);

$wp_parsedown = new WP_PARSEDOWN ( 'wp-parsedown', '0.1', 'WP-Parsedown', $wp_parsedown_defaults );


?>
