<?php
/*
Plugin Name: WP Weixin
Plugin URI: https://github.com/froger-me/wp-weixin
Description: WordPress WeChat integration
Version: 1.3.6
Author: Alexandre Froger
Author URI: https://froger.me
Text Domain: wp-weixin
Domain Path: /languages
WC tested up to: 3.9.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'WP_WEIXIN_PLUGIN_FILE' ) ) {
	define( 'WP_WEIXIN_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'WP_WEIXIN_PLUGIN_PATH' ) ) {
	define( 'WP_WEIXIN_PLUGIN_PATH', plugin_dir_path( WP_WEIXIN_PLUGIN_FILE ) );
}

if ( ! defined( 'WP_WEIXIN_PLUGIN_URL' ) ) {
	define( 'WP_WEIXIN_PLUGIN_URL', plugin_dir_url( WP_WEIXIN_PLUGIN_FILE ) );
}

require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin.php';

register_activation_hook( WP_WEIXIN_PLUGIN_FILE, array( 'WP_Weixin', 'activate' ) );
register_deactivation_hook( WP_WEIXIN_PLUGIN_FILE, array( 'WP_Weixin', 'deactivate' ) );
register_uninstall_hook( WP_WEIXIN_PLUGIN_FILE, array( 'WP_Weixin', 'uninstall' ) );

function wp_weixin_run() {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-settings.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-wechat.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-wechat-singleton.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'lib/wechat-sdk/wechat-sdk.php';
	require_once WP_WEIXIN_PLUGIN_PATH . 'functions.php';

	if ( ! class_exists( 'QRcode' ) ) {
		require_once WP_WEIXIN_PLUGIN_PATH . '/lib/phpqrcode/phpqrcode.php';
	}

	$wp_weixin_settings = new WP_Weixin_Settings( true );

	if ( WP_Weixin_Settings::get_option( 'enabled' ) ) {
		require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-walker-nav-menu-wechat-edit.php';
		require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-menu.php';
		require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-responder.php';
		require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-auth.php';
		require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-bind.php';
		require_once WP_WEIXIN_PLUGIN_PATH . 'inc/class-wp-weixin-metabox.php';

		$wechat              = WP_Weixin_Wechat_Singleton::get_wechat();
		$use_responder       = WP_Weixin_Settings::get_option( 'responder' );
		$use_ecommerce       = WP_Weixin_Settings::get_option( 'ecommerce' );
		$use_auth            = WP_Weixin_Settings::get_option( 'enable_auth' );
		$wp_weixin_auth      = new WP_Weixin_Auth( $wechat, true );
		$wp_weixin_responder = ( $use_responder ) ? new WP_Weixin_Responder( $wechat, true ) : false;
		$wp_weixin_menu      = ( $use_responder ) ? new WP_Weixin_Menu( $wechat, true ) : false;
		$wp_weixin_metabox   = new WP_Weixin_Metabox( $wechat, true );
		$wp_weixin_bind      = ( $use_auth ) ? new WP_Weixin_Bind( $wechat, $wp_weixin_auth, true ) : false;
		$wp_weixin           = new WP_Weixin( $wechat, true );

		do_action(
			'wp_weixin_extensions',
			$wechat,
			$wp_weixin_settings,
			$wp_weixin,
			$wp_weixin_auth,
			$wp_weixin_responder,
			$wp_weixin_menu
		);
	}
}
add_action( 'plugins_loaded', 'wp_weixin_run', 6, 0 );
