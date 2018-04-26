<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Responder {

	private $wechat;

	public function __construct( $wechat, $init_hooks = false ) {

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$this->wechat = $wechat;

		if ( $init_hooks && ! is_admin() ) {
			// Parse responder request
			add_action( 'parse_request', array( $this, 'parse_request' ), 0, 0 );
			// Add responder endpoint
			add_action( 'init', array( $this, 'add_endpoints' ), 0, 0 );

			if ( WP_Weixin_Settings::get_option( 'follow_welcome' ) ) {
				// Send templated message on subscription to the official account
				add_action( 'wp_weixin_responder', array( $this, 'send_subscribe_message' ), 10, 1 );
			}
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public function add_endpoints() {
		add_rewrite_rule( '^weixin-responder', 'index.php?__wp_weixin_api=1&action=responder', 'top' );
	}

	public function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['__wp_weixin_api'] ) ) {

			if ( isset( $wp->query_vars['action'] ) && 'responder' === $wp->query_vars['action'] ) {
				$this->wechat->checkBind();
				$this->_handle_request();

				exit();
			}
		}
	}

	public function send_subscribe_message( $request_data ) {

		if ( isset( $request_data['event'], $request_data['fromusername'] ) && 'subscribe' === $request_data['event'] ) {
			$user = get_user_by( 'login', 'wx-' . $request_data['fromusername'] );

			if ( $user ) {
				$openid = get_user_meta( $user->ID, 'wx_openid', true );
				$openid = $openid ? $openid : $_COOKIE['wx_openId'];
				$openid = $openid ? $openid : '';

				$follower_info = $this->wechat->follower( $openid );
				$error         = $this->wechat->getError();

				if ( $error && 40001 === absint( $error['code'] ) ) {
					WP_Weixin_Wechat_Singleton::renew_access_token( $this->wechat );

					$follower_info = $this->wechat->follower( $openid );
				}

				if ( ! $follower_info ) {

					return;
				}

				$message = $this->_get_follower_welcome_message( $follower_info, $user );

				$this->wechat->response( 'news', $message );
			}
		}
	}

	/*******************************************************************
	 * Private methods
	 *******************************************************************/

	private function _handle_request() {// @codingStandardsIgnoreLine
		global $wp;

		if ( ! $this->wechat->checkSignature() ) {

			exit( 'wuut?' );
		}

		$request_data = $this->wechat->request();

		if ( apply_filters( 'wp_weixin_debug', false ) ) {
			error_log( print_r( $request_data, true ) );// @codingStandardsIgnoreLine
		}

		do_action( 'wp_weixin_responder', $request_data );

		exit();
	}

	private function _get_follower_welcome_message( $follower_info, $user ) {// @codingStandardsIgnoreLine
		global $sitepress;

		$language = isset( $follower_info['language'] ) ? $follower_info['language'] : false;

		if ( $sitepress && $language ) {
			$language = $sitepress->get_language_code_from_locale( $language );

			if ( $language ) {
				$sitepress->switch_lang( $language );
			}
		}

		$name                = isset( $follower_info['nickname'] ) ? $follower_info['nickname'] : '';
		$before_subscription = get_user_meta( $user->ID, 'wp_weixin_before_subscription', true );

		/* translators: 1:wechat nickame */
		$title           = sprintf( __( 'Welcome %1$s!', 'wp-weixin' ), $name );
		$description     = __( 'Thank you for subscribing our official account!', 'wp-weixin' );
		$url             = site_url();
		$default_pic_url = WP_Weixin_Settings::get_option( 'welcome_image_url' );
		$default_pic_url = filter_var( $default_pic_url, FILTER_VALIDATE_URL );
		$default_pic_url = ( ! $default_pic_url ) ? WP_WEIXIN_PLUGIN_URL . 'images/default-welcome.png' : $default_pic_url;
		$default_pic_url = apply_filters( 'wp_weixin_follower_default_welcome_pic_url', $default_pic_url, $before_subscription );

		delete_user_meta( $user->ID, 'wp_weixin_before_subscription' );

		if ( ! empty( $before_subscription ) ) {

			if ( is_numeric( $before_subscription ) ) {
				$post = get_post( absint( $before_subscription ) );
				$url  = get_permalink( $post );

				/* translators: 1:title of the post */
				$description .= sprintf( __( ' Open to go back to "%1$s".', 'wp-weixin' ), $post->post_title );
			} else {
				$url = $before_subscription;

				$description .= __( ' You may now use our services. Open to go back.', 'wp-weixin' );
			}
		} else {

			if ( function_exists( 'wc_get_endpoint_url' ) ) {
				$description .= __( ' Open now to access your personal account.', 'wp-weixin' );

				$url = site_url( wc_get_endpoint_url( 'my-account/' ) );
			} else {
				$description .= __( ' Open now to access our services.', 'wp-weixin' );
			}
		}

		$title       = apply_filters( 'wp_weixin_follower_welcome_title', $title, $before_subscription );
		$description = apply_filters( 'wp_weixin_follower_welcome_description', $description, $before_subscription );
		$url         = apply_filters( 'wp_weixin_follower_welcome_url', $url, $before_subscription );
		$news        = array(
			'Title'       => $title,
			'Description' => $description,
			'PicUrl'      => $default_pic_url,
			'Url'         => $url,
		);

		return array( $news );
	}
}
