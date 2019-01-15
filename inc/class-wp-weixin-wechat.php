<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Wechat {

	protected $wechat;

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
				$message = 'Error - access token is most likely expired ; will retry after renewing the token';

				WP_Weixin::log( $message, get_class( $this->wechat ) . '::' . $method_name );
				WP_Weixin_Wechat_Singleton::renew_access_token( $this->wechat );

				$result  = call_user_func_array( array( $this->wechat, $method_name ), $args );
				$message = 'Call retried after renewing the access token';

				WP_Weixin::log( $message, get_class( $this->wechat ) . '::' . $method_name );
			} elseif ( $error ) {
				WP_Weixin::log( $error, get_class( $this->wechat ) . '::' . $method_name );
			}

			return $result;
		} else {
			trigger_error( // @codingStandardsIgnoreLine
				'Call to undefined method ' . __CLASS__ . '::' . esc_html( $method_name ) . '()', // @codingStandardsIgnoreLine
				E_USER_ERROR
			);
		}
	}

	public function get_signed_package() {
		$ticket = get_transient( 'wp_weixin_jsapi_ticket' );

		if ( ! $ticket ) {
			$ticket = $this->getJsapiTicket();

			set_transient( 'wp_weixin_jsapi_ticket', $ticket, 7000 );
		}

		if ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] || 443 === absint( $_SERVER['SERVER_PORT'] ) ) {
			$protocol = 'https://';
		} else {
			$protocol = 'http://';
		}

		$url            = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$timestamp      = time();
		$nonce_str      = $this->getNonceStr();
		$string         = 'jsapi_ticket=' . $ticket . '&noncestr=' . $nonce_str;
		$string        .= '&timestamp=' . $timestamp . '&url=' . $url;
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

}
