<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Wechat_Singleton {

	protected static $wechat;
	protected static $error;

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public static function get_wechat() {

		if ( ! self::$wechat ) {
			self::$wechat = wp_cache_get( 'wechat', 'wp_weixin' );

			if ( ! self::$wechat ) {
				$access_info  = self::get_access_info();
				$config       = self::get_wechat_config( $access_info['token'], $access_info['expiry'] );
				$wechat_sdk   = new Wechat_SDK( $config );
				self::$wechat = new WP_Weixin_Wechat( $wechat_sdk );

				if (
					( time() + 1800 ) >= absint( self::$wechat->getAccessTokenExpiry() ) ||
					! $access_info['token'] ||
					! $access_info['expiry']
				) {
					WP_Weixin::log( 'renewing token !' );
					self::renew_access_token();
				}

				wp_cache_set( 'wechat', self::$wechat, 'wp_weixin' );
			}
		}

		return self::$wechat;
	}

	public static function renew_access_token() {
		$requesting_token = get_transient( 'wp_weixin_requesting_token' );

		if ( ! $requesting_token && ! is_ajax() ) {
			set_transient( 'wp_weixin_requesting_token', true, 60 );

			$access_token = self::$wechat->getAccessToken( true );
			$token_expiry = self::$wechat->getAccessTokenExpiry();

			self::save_access_info( $access_token, $token_expiry );
			set_transient( 'wp_weixin_requesting_token', false, 60 );
		}
	}

	public static function settings_error() {
		$class   = 'notice notice-error is-dismissible';
		$message = __( 'WP Weixin is not ready. ', 'wp-weixin' );
		$link    = '<a href="' . admin_url( '?page=wp-weixin' ) . '">' . __( 'Edit configuration', 'wp-weixin' ) . '</a>';

		echo sprintf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message . $link . self::$error ); // WPCS: XSS ok
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected static function get_access_info() {
		$settings    = get_option( 'wp_weixin_settings' );
		$access_info = array(
			'token'  => '',
			'expiry' => '',
		);

		if (
			isset( $settings['wp_weixin_custom_token_persistence'] ) &&
			$settings['wp_weixin_custom_token_persistence']
		) {

			if ( ! has_filter( 'wp_weixin_get_access_info' ) ) {

				if ( ! is_admin() ) {
					self::show_frontend_error();
				} else {
					self::set_error( 'custom_token_persistence' );
					add_action( 'admin_notices', array( 'WP_Weixin_Wechat_Singleton', 'settings_error' ) );
				}
			}
			$access_info = apply_filters( 'wp_weixin_get_access_info', $access_info );
		} else {
			$access_info['token']  = get_site_option( 'wp_weixin_access_token_' . $settings['wp_weixin_appid'] );
			$access_info['expiry'] = get_site_option( 'wp_weixin_access_token_expiry_' . $settings['wp_weixin_appid'] );
		}

		return $access_info;
	}

	protected static function save_access_info( $access_token, $token_expiry ) {
		$settings    = get_option( 'wp_weixin_settings' );
		$access_info = array(
			'token'  => $access_token,
			'expiry' => $token_expiry,
		);

		if ( isset( $settings['wp_weixin_custom_token_persistence'] ) && $settings['custom_token_persistence'] ) {

			if ( ! has_action( 'wp_weixin_save_access_info' ) ) {

				if ( ! is_admin() ) {
					self::show_frontend_error();
				} else {
					self::set_error( 'custom_token_persistence' );
					add_action( 'admin_notices', array( 'WP_Weixin_Wechat_Singleton', 'settings_error' ) );
				}
			}
			do_action( 'wp_weixin_save_access_info', $access_info );
		} else {
			update_site_option( 'wp_weixin_access_token_' . $settings['wp_weixin_appid'], $access_token, false );
			update_site_option( 'wp_weixin_access_token_expiry_' . $settings['wp_weixin_appid'], $token_expiry, false );
		}
	}

	protected static function get_wechat_config( $access_token, $token_expiry ) {
		$settings   = get_option( 'wp_weixin_settings' );
		$appid      = isset( $settings['wp_weixin_appid'] ) && ! empty( 'wp_weixin_appid' ) ? $settings['wp_weixin_appid'] : null;
		$secret     = isset( $settings['wp_weixin_secret'] ) && ! empty( 'wp_weixin_secret' ) ? $settings['wp_weixin_secret'] : null;
		$force_auth = isset( $settings['wp_weixin_force_auth'] ) ? (bool) $settings['wp_weixin_force_auth'] : null;
		$responder  = isset( $settings['wp_weixin_responder'] ) ? (bool) $settings['wp_weixin_responder'] : null;
		$token      = isset( $settings['wp_weixin_token'] ) && ! empty( 'wp_weixin_token' ) ? $settings['wp_weixin_token'] : null;
		$encode     = isset( $settings['wp_weixin_encode'] ) ? (bool) $settings['wp_weixin_encode'] : null;
		$aeskey     = isset( $settings['wp_weixin_aeskey'] ) && ! empty( 'wp_weixin_aeskey' ) ? $settings['wp_weixin_aeskey'] : null;
		$ecommerce  = isset( $settings['wp_weixin_ecommerce'] ) && ! empty( 'wp_weixin_ecommerce' ) ? $settings['wp_weixin_ecommerce'] : null;
		$mch_appid  = isset( $settings['wp_weixin_mch_appid'] ) && ! empty( 'wp_weixin_mch_appid' ) ? $settings['wp_weixin_mch_appid'] : null;
		$mch_id     = isset( $settings['wp_weixin_mch_id'] ) && ! empty( 'wp_weixin_mch_id' ) ? $settings['wp_weixin_mch_id'] : null;
		$mch_key    = isset( $settings['wp_weixin_mch_key'] ) && ! empty( 'wp_weixin_mch_key' ) ? $settings['wp_weixin_mch_key'] : null;
		$proxy      = isset( $settings['wp_weixin_proxy'] ) ? (bool) $settings['wp_weixin_proxy'] : null;
		$host       = isset( $settings['wp_weixin_proxy_host'] ) && ! empty( 'wp_weixin_proxy_host' ) ? $settings['wp_weixin_proxy_host'] : null;
		$port       = isset( $settings['wp_weixin_proxy_port'] ) && ! empty( 'wp_weixin_proxy_port' ) ? $settings['wp_weixin_proxy_port'] : null;
		$pem        = isset( $settings['wp_weixin_pem'] ) && ! empty( 'wp_weixin_pem' ) ? $settings['wp_weixin_pem'] : 'apiclient';
		$pem_path   = isset( $settings['wp_weixin_pem_path'] ) && ! empty( 'wp_weixin_pem_path' ) ? $settings['wp_weixin_pem_path'] : null;

		if ( ! isset( $settings['wp_weixin_pem'] ) ) {
			$settings['wp_weixin_pem'] = 'apiclient';

			update_option( 'wp_weixin_settings', $settings );
		}

		$configuration_fail = ! $appid || ! $secret || ( $encode && ! $aeskey ) || ( $responder && ! $token );
		$configuration_fail = $configuration_fail || ( $ecommerce && ( ! $mch_id || ! $mch_key ) );

		if ( $configuration_fail ) {

			if ( WP_Weixin_Auth::is_auth_needed() && ! is_admin() ) {
				self::show_frontend_error();
			} else {
				$error_vars = array(
					'appid'     => $appid,
					'secret'    => $secret,
					'encode'    => $encode,
					'aeskey'    => $aeskey,
					'responder' => $responder,
					'token'     => $token,
					'ecommerce' => $ecommerce,
					'mch_id'    => $mch_id,
					'ecommerce' => $ecommerce,
					'mch_key'   => $mch_key,
				);

				self::set_error( 'main_config', $error_vars );
				add_action( 'admin_notices', array( 'WP_Weixin_Wechat_Singleton', 'settings_error' ) );
			}
		}

		$options = [
			'token'               => $token,
			'appid'               => $appid,
			'secret'              => $secret,
			'access_token'        => $access_token,
			'access_token_expire' => absint( $token_expiry ),
			'encode'              => $encode,
			'aeskey'              => $aeskey,
			'mch_appid'           => $mch_appid,
			'mch_id'              => $mch_id,
			'payKey'              => $mch_key,
			'proxy'               => $proxy,
			'proxyHost'           => $host,
			'proxyPort'           => $port,
			'pem'                 => $pem,
			'pemPath'             => trailingslashit( $pem_path ),
		];

		return $options;
	}

	protected static function show_frontend_error() {
		$title    = '<h2>' . __( 'Configuration error', 'wp-weixin' ) . '</h2>';
		$message  = '<p>' . __( 'WP Weixin is not configured properly. ', 'wp-weixin' );
		$message .= __( 'If the problem persists, please contact an administrator.', 'wp-weixin' ) . '</p>';

		wp_die( $title . $message ); // WPCS: XSS ok
	}

	protected static function set_error( $context, $error_vars = null ) {

		if ( is_array( $error_vars ) && ! empty( $error_vars ) ) {
			extract( $error_vars ); // @codingStandardsIgnoreLine
		}

		$error = '<ul>';

		switch ( $context ) {
			case 'main_config':
				$error .= ( ! $appid ) ? '<li>' . __( 'Missing App ID', 'wp-weixin' ) . '</li>' : '';
				$error .= ( ! $secret ) ? '<li>' . __( 'Missing App Secret', 'wp-weixin' ) . '</li>' : '';
				$error .= ( $encode && ! $aeskey ) ? '<li>' . __( 'Missing AES Key', 'wp-weixin' ) . '</li>' : '';
				$error .= ( $responder && ! $token ) ? '<li>' . __( 'Missing Token', 'wp-weixin' ) . '</li>' : '';
				$error .= ( $ecommerce && ! $mch_id ) ? '<li>' . __( 'Missing Merchant ID', 'wp-weixin' ) . '</li>' : '';
				$error .= ( $ecommerce && ! $mch_key ) ? '<li>' . __( 'Missing Merchant Key', 'wp-weixin' ) . '</li>' : '';
				break;
			case 'custom_token_persistence':
				$message = 'Missing custom persistence hook. ';
				$message = __( 'Please make sure <code>wp_weixin_get_access_info</code> and <code>wp_weixin_save_access_info</code> are implemented', 'wp-weixin' );
				$error  .= '<li>' . $message . '</li>';
				break;
			default:
				$error .= '<li>' . __( 'An unexpected configuration error has occured.', 'wp-weixin' ) . '</li>';
				break;
		}

		$error .= '</ul>';

		self::$error = $error;
	}

}
