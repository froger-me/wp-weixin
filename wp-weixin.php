<?php
/*
Plugin Name: WP Weixin
Plugin URI: https://github.com/froger-me/wp-weixin
Description: WordPress WeChat integration
Version: 1.0
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

require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin.php';

register_activation_hook( __FILE__, array( 'WP_Weixin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WP_Weixin', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'WP_Weixin', 'uninstall' ) );

function wp_weixin_run() {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-settings.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-wechat.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-wechat-singleton.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-auth.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-responder.php';
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

		do_action( 'wp_weixin_extensions' );
	}
}
add_action( 'plugins_loaded', 'wp_weixin_run', 5, 0 );
