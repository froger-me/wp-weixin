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
			// Default actions when subscribing to the Official Account
			add_action( 'wp_weixin_responder', array( $this, 'oa_subscribe_event' ), PHP_INT_MIN + 100, 1 );
			// Default actions when unsubscribing the Official Account
			add_action( 'wp_weixin_responder', array( $this, 'oa_unsubscribe_event' ), PHP_INT_MIN + 100, 1 );
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

	public function oa_unsubscribe_event( $request_data ) {

		if ( isset( $request_data['event'], $request_data['fromusername'] ) && 'unsubscribe' === $request_data['event'] ) {
			$user = WP_Weixin::get_user_by_openid( $request_data['fromusername'] );

			if ( $user ) {
				$sessions = WP_Session_Tokens::get_instance( $user->ID );

				$sessions->destroy_all();
				update_user_meta(
					$user->ID,
					'wx_follower',
					array(
						'follower'  => false,
						'timestamp' => time(),
					)
				);
			}
		}
	}

	public function oa_subscribe_event( $request_data ) {

		if (
			isset( $request_data['event'], $request_data['fromusername'] ) &&
			'subscribe' === $request_data['event']
		) {
			$openid        = $request_data['fromusername'];
			$follower_info = $this->wechat->follower( $openid );
			$error         = $this->wechat->getError();
			$user          = WP_Weixin::get_user_by_openid( $openid );

			if ( ! $error && ! $user && $follower_info ) {
				$user = $this->register_user( $openid, $follower_info );

				if ( $user ) {

					if ( apply_filters( 'wp_weixin_follow_welcome', WP_Weixin_Settings::get_option( 'follow_welcome' ), $request_data ) ) {
						$this->send_subscribe_message( $user );
					}

					update_user_meta(
						$user->ID,
						'wx_follower',
						array(
							'follower'  => true,
							'timestamp' => time() + 3600,
						)
					);
				}
			} elseif ( $error ) {
				WP_Weixin::log( $error );
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
			WP_Weixin::log( $request_data );
		}

		do_action( 'wp_weixin_responder', $request_data, $this->wechat );

		exit();
	}

	protected function send_subscribe_message( $user ) {
		$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
		$openid       = get_user_meta( $user->ID, 'wx_openid-' . $auth_blog_id, true );

		$follower_info = $this->wechat->follower( $openid );
		$error         = $this->wechat->getError();

		if ( ! $follower_info || $error ) {
			WP_Weixin::log( $error );

			return;
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

		delete_user_meta( $user->ID, 'wp_weixin_before_subscription' );

		if ( ! empty( $before_subscription ) ) {

			if ( is_numeric( $before_subscription ) ) {
				$post = get_post( absint( $before_subscription ) );

				if ( $post instanceof WP_Post ) {
					/* translators: 1:title of the post */
					$description    .= sprintf( __( ' Open to go back to "%1$s".', 'wp-weixin' ), $post->post_title );
					$url             = get_permalink( $post );
					$img_url         = get_the_post_thumbnail_url( $post->ID );
					$default_pic_url = ( $img_url ) ? $img_url : $default_pic_url;
				}
			} else {
				$description .= __( ' You may now use our services. Open to go back.', 'wp-weixin' );
				$url          = $before_subscription;
			}
		} else {

			if ( function_exists( 'wc_get_endpoint_url' ) ) {
				$description .= __( ' Open now to access your personal account.', 'wp-weixin' );
				$url          = home_url( wc_get_endpoint_url( 'my-account' ) );
			} else {
				$description .= __( ' Open now to access our services.', 'wp-weixin' );
			}
		}

		$title           = apply_filters( 'wp_weixin_follower_welcome_title', $title, $before_subscription );
		$description     = apply_filters( 'wp_weixin_follower_welcome_description', $description, $before_subscription );
		$url             = apply_filters( 'wp_weixin_follower_welcome_url', $url, $before_subscription );
		$default_pic_url = apply_filters( 'wp_weixin_follower_default_welcome_pic_url', $default_pic_url, $before_subscription );
		$message         = array(
			'title'       => $title,
			'description' => $description,
			'picurl'      => $default_pic_url,
			'url'         => $url,
		);

		$this->wechat->response( 'news', array( $message ) );

		$error = $this->wechat->getError();

		if ( $error ) {
			WP_Weixin::log( $error );
		}
	}

	protected function register_user( $openid, $follower_info ) {
		$user_info['openid']         = $openid;
		$user_info['access_token']   = '';
		$user_info['refresh_token']  = 0;
		$user_info['refresh_expire'] = time() - 1000;
		$openid_login_suffix         = ( is_multisite() ) ? strtolower( $user_info['openid'] ) : $user_info['openid'];
		$parsed_url                  = wp_parse_url( home_url() );
		$domain                      = ( isset( $parsed_url['host'] ) ) ? $parsed_url['host'] : 'example.com';
		$email                       = $user_info['openid'] . '@' . $domain;

		$user_data = array(
			'user_login'   => 'wx-' . $openid_login_suffix,
			'display_name' => $follower_info['nickname'],
			'user_pass'    => wp_generate_password(),
			'user_email'   => $email,
			'nickname'     => $follower_info['nickname'],
		);

		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) || ! $user_id ) {
			$title    = '<h2>' . __( 'System error.', 'wp-weixin' ) . '</h2>';
			$message  = '<p>' . __( 'Failed to create user, please refresh the page. ', 'wp-weixin' );
			$message .= __( 'If the problem persists, please contact an administrator.', 'wp-weixin' ) . '</p>';

			if ( is_wp_error( $user_id ) ) {
				$message .= '<p> => ' . implode( '<br/> => ', esc_html( $user_id->get_error_messages() ) ) . '</p>';

				WP_Weixin::log( $user_id->get_error_messages(), 'User creation failed' );
			} else {
				WP_Weixin::log( $user_data, 'User creation failed' );
			}

			wp_die( $title . $message ); // WPCS: XSS ok
		}

		$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );

		update_user_meta( $user_id, 'wx_openid-' . $auth_blog_id, $user_info['openid'] );
		update_user_meta( $user_id, 'wx_unionid', '' );
		unset( $user_info['openid'] );
		update_user_meta( $user_id, 'wp_weixin_rawdata', wp_json_encode( $user_info ) );

		return get_user_by( 'ID', $user_id );
	}
}
