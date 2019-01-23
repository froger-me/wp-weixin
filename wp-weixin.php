<?php
/*
Plugin Name: WP Weixin
Plugin URI: https://github.com/froger-me/wp-weixin
Description: WordPress WeChat integration
Version: 1.3.2
Author: Alexandre Froger
Author URI: https://froger.me
Text Domain: wp-weixin
Domain Path: /languages
WC tested up to: 3.5.2
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

		$wechat              = WP_Weixin_Wechat_Singleton::get_wechat();
		$use_responder       = WP_Weixin_Settings::get_option( 'responder' );
		$use_ecommerce       = WP_Weixin_Settings::get_option( 'ecommerce' );
		$use_auth            = WP_Weixin_Settings::get_option( 'enable_auth' );
		$wp_weixin_auth      = new WP_Weixin_Auth( $wechat, true );
		$wp_weixin_responder = ( $use_responder ) ? new WP_Weixin_Responder( $wechat, true ) : false;
		$wp_weixin_menu      = ( $use_responder ) ? new WP_Weixin_Menu( $wechat, true ) : false;
		$wp_weixin           = new WP_Weixin( $wechat, true );
		$wp_weixin_bind      = ( $use_auth ) ? new WP_Weixin_Bind( $wechat, $wp_weixin_auth, true ) : false;

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

/***************************************************
 *             Remove in 1.4 or above              *
 ***************************************************/

add_filter( 'wp_weixin_get_user_by_openid', function( $user, $openid ) {

	if ( $user || get_option( 'wp_weixin_plugin_version' ) ) {

		return $user;
	}

	$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );

	if ( empty( $maybe_users ) ) {
		$maybe_users = get_users(
			array(
				'meta_key'    => 'wx_openid',
				'meta_value'  => $openid,
				'number'      => 1,
				'count_total' => false,
			)
		);

		if ( ! empty( $maybe_users ) ) {
			$user = reset( $maybe_users );

			update_user_meta( $user->ID, 'wx_openid-' . $auth_blog_id, $openid );
			delete_user_meta( $user->ID, 'wx_openid' );
		}
	}

	if ( ! $user ) {

		if ( username_exists( 'wx-' . $openid ) ) {
			$user = get_user_by( 'login', 'wx-' . $openid );
		} elseif ( username_exists( 'wx-bound-' . $openid ) ) {
			$user = get_user_by( 'login', 'wx-bound-' . $openid );
		}

		if ( $user ) {
			global $wpdb;

			$old              = $user->user_login;
			$user->user_login = strtolower( $user->user_login );
			$result           = $wpdb->update(
				$wpdb->users,
				array( 'user_login' => $user->user_login ),
				array( 'ID' => $user->ID )
			);

			if ( $result ) {
				wp_cache_delete( $user->ID, 'users' );
				wp_cache_delete( $old, 'userlogins' );

				$sessions = WP_Session_Tokens::get_instance( $user->ID );

				$sessions->destroy_all();

				if ( $reauth ) {
					wp_set_current_user( $user->ID, $user->user_login );
					wp_set_auth_cookie( $user->ID );
				}
			}
		}
	}

	return $user;
}, 10, 2 );

add_action( 'admin_init', function() {

	if ( ! wp_doing_ajax() ) {
		global $wpdb;

		$plugin_version = get_option( 'wp_weixin_plugin_version' );

		if ( ! $plugin_version ) {
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE `meta_key` = 'wx_openid'" ); // @codingStandardsIgnoreLine

			if ( 0 < absint( $count ) ) {
				$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
				$sql          = "UPDATE $wpdb->usermeta SET `meta_key` = %s WHERE `meta_key` = 'wx_openid'";

				$wpdb->query( $wpdb->prepare( $sql, 'wx_openid-' . $auth_blog_id ) ); // @codingStandardsIgnoreLine
			}

			update_option( 'wp_weixin_plugin_version', '1.3' );
		}
	}
}, 10, 0 );

/***************************************************
 *                WP Update Migrate                *
 ***************************************************/

// require_once plugin_dir_path( WP_WEIXIN_PLUGIN_FILE ) . 'lib/wp-update-migrate/class-wp-update-migrate.php';

// $hook = 'plugins_loaded';

// add_action( $hook, function() {
// 	$update_migrate = WP_Update_Migrate::get_instance( WP_WEIXIN_PLUGIN_FILE, 'wp_weixin' );

// 	if ( false === $update_migrate->get_result() && '1.3' !== get_option( 'wp_weixin_plugin_version' ) ) {

// 		if ( false !== has_action( 'plugins_loaded', 'wp_weixin_run' ) ) {
// 			remove_action( 'plugins_loaded', 'wp_weixin_run', 6 );
// 		}
// 	}
// }, PHP_INT_MIN );
