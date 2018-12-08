<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Responder {

	protected $wechat;

	public function __construct( $wechat, $init_hooks = false ) {
		$this->wechat = $wechat;

		if ( $init_hooks ) {
			// Parse responder request
			add_action( 'parse_request', array( $this, 'parse_request' ), 0, 0 );
			// Add responder endpoint
			add_action( 'wp_weixin_endpoints', array( $this, 'add_endpoints' ), 0, 0 );

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
		add_rewrite_rule(
			'^weixin-responder',
			'index.php?__wp_weixin_api=1&action=responder',
			'top'
		);
	}

	public function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['__wp_weixin_api'] ) ) {

			if ( isset( $wp->query_vars['action'] ) && 'responder' === $wp->query_vars['action'] ) {
				$this->wechat->checkBind();
				$this->handle_request();

				exit();
			}
		}
	}

	public function send_subscribe_message( $request_data ) {

		if (
			isset( $request_data['event'], $request_data['fromusername'] ) &&
			'subscribe' === $request_data['event']
		) {
			$user = WP_Weixin::get_user_by_openid( $request_data['fromusername'] );

			if ( $user ) {
				$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
				$openid       = get_user_meta( $user->ID, 'wx_openid-' . $auth_blog_id, true );

				$follower_info = $this->wechat->follower( $openid );
				$error         = $this->wechat->getError();

				if ( ! $follower_info || $error ) {
					WP_Weixin::log( __METHOD__, $error );

					return;
				}

				$message = $this->get_follower_welcome_message( $follower_info, $user );

				$this->wechat->response( 'news', $message );
			}
		}
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function handle_request() {

		if ( ! $this->wechat->checkSignature() ) {

			if ( ! isset( $_SERVER['SERVER_PROTOCOL'] ) || '' === $_SERVER['SERVER_PROTOCOL'] ) {
				$protocol = 'HTTP/1.1';
			} else {
				$protocol = $_SERVER['SERVER_PROTOCOL'];
			}

			$output = '
				<html>
					<head>
						<title>401 Unauthorized</title>
					</head>
					<body>
						<h1>401 Unauthorized</h1>
						<p>Invalid signature</p>
					</body>
				</html>
			';

			header( $protocol . ' 401 Unauthorized' );

			echo $output; // WPCS: XSS ok

			exit( -1 );
		}

		$request_data = $this->wechat->request();

		if ( apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) ) ) {
			WP_Weixin::log( __METHOD__, $request_data );
		}

		do_action( 'wp_weixin_responder', $request_data, $this->wechat );

		exit();
	}

	protected function get_follower_welcome_message( $follower_info, $user ) {
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

		/* translators: 1:WeChat Name */
		$title           = sprintf( __( 'Welcome %1$s!', 'wp-weixin' ), $name );
		$description     = __( 'Thank you for subscribing our official account!', 'wp-weixin' );
		$url             = home_url();
		$default_pic_url = WP_Weixin_Settings::get_option( 'welcome_image_url' );
		$default_pic_url = filter_var( $default_pic_url, FILTER_VALIDATE_URL );
		$default_pic_url = ( ! $default_pic_url ) ? WP_WEIXIN_PLUGIN_URL . 'images/default-welcome.png' : $default_pic_url;
		$default_pic_url = apply_filters( 'wp_weixin_follower_default_welcome_pic_url', $default_pic_url, $before_subscription );

		delete_user_meta( $user->ID, 'wp_weixin_before_subscription' );

		if ( ! empty( $before_subscription ) ) {

			if ( is_numeric( $before_subscription ) ) {
				$post = get_post( absint( $before_subscription ) );

				/* translators: 1:title of the post */
				$description .= sprintf( __( ' Open to go back to "%1$s".', 'wp-weixin' ), $post->post_title );
				$url          = get_permalink( $post );
			} else {
				$description .= __( ' You may now use our services. Open to go back.', 'wp-weixin' );
				$url          = $before_subscription;
			}
		} else {

			if ( function_exists( 'wc_get_endpoint_url' ) ) {
				$description .= __( ' Open now to access your personal account.', 'wp-weixin' );
				$url          = home_url( wc_get_endpoint_url( 'my-account/' ) );
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
