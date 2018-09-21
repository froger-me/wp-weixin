<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Wechat {

	private $wechat;

	public function __construct( $wechat ) {
		$this->wechat = $wechat;
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public function __call( $method_name, $args ) {

		if ( method_exists( $this->wechat, $method_name ) ) {
			$result = call_user_func_array( array( $this->wechat, $method_name ), $args );
			$error  = $this->wechat->getError();

			if ( $error && absint( $error['code'] ) === 40001 ) {
				error_log( __CLASS__ . ': Error - access token is most likely expired ; retry after renewing' ); // @codingStandardsIgnoreLine
				WP_Weixin_Wechat_Singleton::renew_access_token( $this->wechat );

				$result = call_user_func_array( array( $this->wechat, $method_name ), $args );
				error_log( __CLASS__ . ': End retry after renewing' ); // @codingStandardsIgnoreLine
			}

			return $result;
		} else {
			trigger_error( 'Call to undefined method ' . __CLASS__ . '::' . $method_name . '()', E_USER_ERROR ); // @codingStandardsIgnoreLine
		}
	}

	public function get_signed_package() {
		$ticket = get_transient( 'wp_weixin_jsapi_ticket' );

		if ( ! $ticket ) {
			$ticket = $this->getJsapiTicket();

			set_transient( 'wp_weixin_jsapi_ticket', $ticket, 7000 );
		}

		$protocol       = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] || 443 === absint( $_SERVER['SERVER_PORT'] ) ) ? 'https://' : 'http://';
		$url            = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$timestamp      = current_time( 'timestamp' );
		$nonce_str      = $this->getNonceStr();
		$string         = 'jsapi_ticket=' . $ticket . '&noncestr=' . $nonce_str . '&timestamp=' . $timestamp . '&url=' . $url;
		$signature      = sha1( $string );
		$signed_package = array(
			'appid'     => WP_Weixin_Settings::get_option( 'appid' ),
			'nonceStr'  => $nonce_str,
			'timestamp' => $timestamp,
			'url'       => $url,
			'signature' => $signature,
			'rawString' => $string,
		);

		return $signed_package;
	}

	/*******************************************************************
	 * Private methods
	 *******************************************************************/
}
