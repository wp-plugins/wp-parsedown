<?php
if (!class_exists('PluginUtils')):

/* __ only availabe if we're running from the inside of wordpress, not in advanced-cache.php phase */
if ( !function_exists ('__translate__') ) {
	/* __ only availabe if we're running from the inside of wordpress, not in advanced-cache.php phase */
	if ( function_exists ( '__' ) ) {
		function __translate__ ( $text, $domain ) { return __($text, $domain); }
	}
	else {
		function __translate__ ( $text, $domain ) { return $text; }
	}
}

class PluginUtils {
	public function __construct() {
	}

	/* easily redefine serializer, if needed */
	public function _serialize ( $mixed ) {
		return json_encode ( $mixed );
	}

	/**
	 * option update; will handle network wide or standalone site options
	 *
	 */
	public function _update_option ( $optionID, $data, $network = false ) {
		if ( $network ) {
			$this->log ( 'PluginUtils', sprintf( __( ' – updating network option %s', 'PluginUtils' ), $optionID ) );
			update_site_option( $optionID , $data );
		}
		else {
			$this->log ( 'PluginUtils', sprintf( __( '- updating option %s', 'PluginUtils' ), $optionID ) );
			update_option( $optionID , $data );
		}
	}

	/**
	 * read option; will handle network wide or standalone site options
	 *
	 */
	public function _get_option ( $optionID, $network = false ) {
		if ( $network ) {
			$this->log ( 'PluginUtils', sprintf( __( '- getting network option %s', 'PluginUtils' ), $optionID ) );
			$options = get_site_option( $optionID );
		}
		else {
			$this->log ( 'PluginUtils', sprintf( __( ' – getting option %s', 'PluginUtils' ), $optionID ) );
			$options = get_option( $optionID );
		}

		return $options;
	}

	/**
	 * clear option; will handle network wide or standalone site options
	 *
	 */
	public function _delete_option ( $optionID, $network = false ) {
		if ( $network ) {
			$this->log ( 'PluginUtils', sprintf( __( ' – deleting network option %s', 'PluginUtils' ), $optionID ) );
			delete_site_option( $optionID );
		}
		else {
			$this->log ( 'PluginUtils' , sprintf( __( ' – deleting option %s', 'PluginUtils' ), $optionID ) );
			delete_option( $optionID );
		}
	}

	/**
	 * read option; will handle network wide or standalone site options
	 *
	 */
	public function _site_url ( $site = '', $network = false ) {
		if ( $network && !empty( $site ) )
			$url = get_blog_option ( $site, 'siteurl' );
		else
			$url = get_bloginfo ( 'url' );

		return $url;
	}


	/**
	 * replaces http:// with https:// in an url if server is currently running on https
	 *
	 * @param string $url URL to check
	 *
	 * @return string URL with correct protocol
	 *
	 */
	public function replace_if_ssl ( $url ) {
		if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' )
			$_SERVER['HTTPS'] = 'on';

		if ( isset($_SERVER['HTTPS']) && (( strtolower($_SERVER['HTTPS']) == 'on' )  || ( $_SERVER['HTTPS'] == '1' ) ))
			$url = str_replace ( 'http://' , 'https://' , $url );

		return $url;
	}

	/*
	 ## LOGGING ##
	 */

	/**
	 * standard log message
	 *
	 * @param string $identifier process identifier, falls back to FILE is empty
	 * @param string $message message to add besides basic info, falls back to LINE if empty
	 * @param int $log_level [optional] Level of log, falls back to LOG_WARNING if empty
	 *
	 */
	public function log ( $identifier = __FILE__ , $message = __LINE__ , $log_level = LOG_NOTICE ) {

		if ( function_exists( 'trigger_error' ) ) {
			if ( @is_array( $message ) || @is_object ( $message ) )
				$message = $this->_serialize($message);

			switch ( $log_level ) {
				case LOG_ERR:
					trigger_error ( $identifier . " " . $message, E_USER_ERROR );
					break;
				case LOG_WARNING:
					trigger_error ( $identifier . " " . $message, E_USER_WARNING );
					break;
				default:
					/* info level will only be fired if WP_DEBUG is active */
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG == true  )
						trigger_error ( $identifier . " " . $message, E_USER_NOTICE );
					break;
			}
		}

	}

	/**
	 * syslog log message
	 *
	 * @param string $identifier process identifier, falls back to FILE is empty
	 * @param string $message message to add besides basic info, falls back to LINE if empty
	 * @param int $log_level [optional] Level of log, info by default
	 *
	 */
	public function syslog ( $identifier = __FILE__ , $message = __LINE__ , $log_level = LOG_INFO ) {

		if ( function_exists( 'syslog' ) && function_exists ( 'openlog' ) ) {
			if ( @is_array( $message ) || @is_object ( $message ) )
				$message = $this->_serialize($message);

			switch ( $log_level ) {
				case LOG_ERR :
					openlog('wordpress('.$_SERVER['HTTP_HOST'].')',LOG_NDELAY|LOG_PERROR,LOG_SYSLOG);
					break;
				default:
					openlog('wordpress(' .$_SERVER['HTTP_HOST']. ')', LOG_NDELAY,LOG_SYSLOG);
					break;
			}

			syslog( $log_level , $identifier . $message );
		}
	}


	public function alert ( $msg, $level='error', $network=false ) {
		if ( empty($msg)) return false;
		$r = '<div class="'. $level .'">'. sprintf ( __('<strong>Error:</strong> %s', 'PluginUtils' ),  $msg ) .'</div>';

		if ( $network )
			add_action( 'network_admin_notices', array( &$this, 'display_errors') );
		else
			add_action( 'admin_notices', array( &$this, 'display_errors') );
	}

	public function valid_url ( &$str ) {
		return preg_match('/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $str );
	}

}

endif;
