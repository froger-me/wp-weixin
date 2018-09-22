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

	function wp_weixin_get_user_by_unionid( $unionid ) {

		return WP_Weixin::get_user_by_unionid( $unionid );
	}
}

if ( ! function_exists( 'is_ajax' ) ) {

	function is_ajax() {
		return defined( 'DOING_AJAX' );
	}
}
