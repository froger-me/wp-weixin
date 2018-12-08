<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'wp_weixin_is_wechat' ) ) {

	function wp_weixin_is_wechat() {

		return WP_Weixin::is_wechat_mobile();
	}
}

if ( ! function_exists( 'wp_weixin_get_user_by_openid' ) ) {

	function wp_weixin_get_user_by_openid( $openid ) {

		return WP_Weixin::get_user_by_openid( $openid );
	}
}

if ( ! function_exists( 'wp_weixin_get_user_by_unionid' ) ) {

	function wp_weixin_get_user_by_unionid( $unionid, $blog_id = false ) {

		if ( false === $blog_id ) {
			$blog_id = get_current_blog_id();
		}

		return WP_Weixin::get_users_by_unionid( $unionid, $blog_id );
	}
}

if ( ! function_exists( 'is_ajax' ) ) {

	function is_ajax() {

		return defined( 'DOING_AJAX' );
	}
}

if ( ! function_exists( 'wp_weixin_get_wechat' ) ) {

	function wp_weixin_get_wechat() {

		return WP_Weixin_Wechat_Singleton::get_wechat();
	}
}

if ( ! function_exists( 'wp_weixin_get_options' ) ) {

	function wp_weixin_get_options() {

		return WP_Weixin_Settings::get_options();
	}
}

if ( ! function_exists( 'wp_weixin_get_option' ) ) {

	function wp_weixin_get_option( $key ) {

		return WP_Weixin_Settings::get_option( $key );
	}
}

if ( ! function_exists( 'wp_weixin_wpml_switch_lang' ) ) {

	function wp_weixin_wpml_switch_lang( $force = true ) {

		return WP_Weixin::switch_language( $force );
	}
}

if ( ! function_exists( 'wp_weixin_get_signed_package' ) ) {

	function wp_weixin_get_signed_package() {
		$wechat = wp_weixin_get_wechat();

		return $wechat->get_signed_package();
	}
}

if ( ! function_exists( 'wp_weixin_get_user_wechat_info' ) ) {

	function wp_weixin_get_user_wechat_info( $user_id = false, $output = false ) {
		$wp_weixin = new WP_Weixin( wp_weixin_get_wechat() );

		if ( $output ) {
			$user = get_user_by( 'ID', absint( $user_id ) );

			$wp_weixin->user_profile_wechat_info( $user, true );
		} else {

			return $wp_weixin->get_user_wechat_info( $user_id );
		}
	}
}

if ( ! function_exists( 'wp_weixin_get_user_wechat_openid' ) ) {

	function wp_weixin_get_user_wechat_openid( $user_id = false ) {
		$user_id      = ( $user_id ) ? absint( $user_id ) : get_current_user_id();
		$auth_blog_id = ( $user_id ) ? apply_filters( 'wp_weixin_ms_auth_blog_id', 1 ) : 1;

		wp_cache_delete( $user_id, 'user_meta' );

		$openid = ( $user_id ) ? get_user_meta( $user_id, 'wx_openid-' . $auth_blog_id, true ) : false;

		return $openid;
	}
}

if ( ! function_exists( 'wp_weixin_get_auth_link' ) ) {

	function wp_weixin_get_auth_link( $output = false, $target = '' ) {
		$link = '';

		if (
			wp_weixin_get_option( 'enabled' ) &&
			wp_weixin_get_option( 'enable_auth' ) &&
			! wp_weixin_is_wechat()
		) {
			$wp_weixin_auth = new WP_Weixin_Auth( wp_weixin_get_wechat() );
			$link           = $wp_weixin_auth->auth_link( $output, $class, $target );
		}

		return $link;
	}
}

if ( ! function_exists( 'wp_weixin_get_bind_link' ) ) {

	function wp_weixin_get_bind_link( $output = false, $target = '_blank' ) {
		$link = '';

		if (
			wp_weixin_get_option( 'enabled' ) &&
			wp_weixin_get_option( 'enable_auth' ) &&
			! wp_weixin_is_wechat()
		) {
			$wechat         = wp_weixin_get_wechat();
			$wp_weixin_auth = new WP_Weixin_Auth( $wechat );
			$wp_weixin_bind = new WP_Weixin_Bind( $wechat, $wp_weixin_auth );
			$user           = wp_get_current_user();

			wp_cache_delete( $user->ID, 'user_meta' );

			$auth_blog_id  = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
			$wechat_openid = get_user_meta( $user->ID, 'wx_openid-' . $auth_blog_id, true );
			$link          = $wp_weixin_bind->bind_link( $user, $wechat_openid, '', $output );
		}

		return $link;
	}
}

if ( ! function_exists( 'wp_weixin_unbind' ) ) {

	function wp_weixin_unbind( $user_id, $open_id = '' ) {
		$unbound = false;

		if ( wp_weixin_get_option( 'enabled' ) && wp_weixin_get_option( 'enable_auth' ) ) {
			$wechat         = wp_weixin_get_wechat();
			$wp_weixin_auth = new WP_Weixin_Auth( $wechat );
			$wp_weixin_bind = new WP_Weixin_Bind( $wechat, $wp_weixin_auth );

			if ( empty( $openid ) ) {
				wp_cache_delete( $user_id, 'user_meta' );

				$auth_blog_id  = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
				$wechat_openid = get_user_meta( $user_id, 'wx_openid-' . $auth_blog_id, true );
				$openid        = $wechat_openid;
			}

			$unbound = $wp_weixin_bind->process_unbind( false, $user_id, $openid );
		}

		return $unbound;
	}
}

if ( ! function_exists( 'wp_weixin_unbind' ) ) {

	function wp_weixin_bind( $user_id, $openid ) {
		$bound = false;

		if ( wp_weixin_get_option( 'enabled' ) && wp_weixin_get_option( 'enable_auth' ) ) {
			$wechat         = wp_weixin_get_wechat();
			$wp_weixin_auth = new WP_Weixin_Auth( $wechat );
			$wp_weixin_bind = new WP_Weixin_Bind( $wechat, $wp_weixin_auth );
			$unbound        = $wp_weixin_bind->process_bind( $user_id, $openid );
		}

		return $bound;
	}
}
