<?php
/*
Plugin Name: WP Weixin
Plugin URI: https://truerun.com/
Description: Main WP Weixin plugin - flexibly handles authentication, handles Wechat message responses, holds the weixin SDK.
Version: 2
Author: Alexandre Froger
Author URI: https://froger.me
Text Domain: wp-weixin
Domain Path: /languages
WC tested up to: 3.3.4
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'WP_WEIXIN_PLUGIN_PATH' ) ) {
	define( 'WP_WEIXIN_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WP_WEIXIN_PLUGIN_URL' ) ) {
	define( 'WP_WEIXIN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

function wp_weixin_run() {
	require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-settings.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-wechat.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-wechat-singleton.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-auth.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-responder.php';
	// require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-pay.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-walker-nav-menu-wechat-edit.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-menu.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'lib/wechat-sdk/wechat-sdk.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'functions.php';

	if ( ! class_exists( 'QRcode' ) ) {
		require_once WP_WEIXIN_PLUGIN_PATH . '/lib/phpqrcode/phpqrcode.php';
	}

	$wp_weixin_settings = new WP_Weixin_Settings( true );

	if ( WP_Weixin_Settings::get_option( 'enabled' ) ) {
		$wechat        = WP_Weixin_Wechat_Singleton::get_wechat();
		$use_responder = WP_Weixin_Settings::get_option( 'responder' );
		$use_ecommerce = WP_Weixin_Settings::get_option( 'ecommerce' );

		$wp_weixin           = new WP_Weixin( $wechat, true );
		$wp_weixin_auth      = new WP_Weixin_Auth( $wechat, true );
		$wp_weixin_responder = ( $use_responder ) ? new WP_Weixin_Responder( $wechat, true ) : false;
		$wp_weixin_menu      = ( $use_responder ) ? new WP_Weixin_Menu( $wechat, true ) : false;
		// $wp_weixin_pay       = ( $use_ecommerce ) ? new WP_Weixin_Pay( $wechat, $wp_weixin_auth, true ) : false;

		do_action( 'wp_weixin_extensions' );
	}
}
add_action( 'plugins_loaded', 'wp_weixin_run', 5, 0 );
