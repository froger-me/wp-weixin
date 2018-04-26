<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'wp_weixin_is_wechat' ) ) {

	function wp_weixin_is_wechat() {

		return WP_Weixin::is_wechat_mobile();
	}
}