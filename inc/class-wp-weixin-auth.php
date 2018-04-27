<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Auth {

	private $expire_length;
	private $wechat;
	private $qr_subscribe_src;

	public function __construct( $wechat, $init_hooks = false ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$this->wechat = $wechat;

		if ( $init_hooks ) {
			// Manage wechat authentication
			add_action( 'init', array( $this, 'manage_auth' ), 10, 0 );
			// Logout when unsubscribed
			add_action( 'wp_weixin_responder', array( $this, 'force_logout' ), 10, 1 );

			// Detemine where wechat authentication is needed
			add_filter( 'wp_weixin_auth_needed', array( $this, 'page_needs_wechat_auth' ), 10, 1 );
			// Get the QR code for browsers
			add_filter( 'wp_weixin_browser_page_qr_src', array( $this, 'get_browser_page_qr_src' ), 10, 1 );
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public static function is_auth_needed() {
		$needs_auth = WP_Weixin_Settings::get_option( 'force_wechat' );
		$needs_auth = apply_filters( 'wp_weixin_auth_needed', $needs_auth );

		return $needs_auth;
	}

	public function manage_auth() {
		$is_wechat_mobile = WP_Weixin::is_wechat_mobile();
		$is_auth_needed   = self::is_auth_needed();

		if ( $is_wechat_mobile && $is_auth_needed ) {
			add_action( 'template_redirect', array( $this, 'oauth' ), 0, 0 );
			add_filter( 'auth_cookie_expiration', array( $this, 'oauth_cookie' ), 1, 3 );
		} elseif ( ! $is_wechat_mobile && $is_auth_needed ) {
			add_action( 'template_redirect', array( $this, 'show_browser_qr' ), 0, 0 );
		}
	}

	public function show_browser_qr() {
		add_action( 'template_include', array( $this, 'browser_qr_template' ), 99, 0 );
	}

	public function browser_qr_template() {
		$template = locate_template( array( 'wp-weixin-browser-qr.php' ) );

		if ( empty( $template ) ) {
			$template = WP_WEIXIN_PLUGIN_PATH . 'inc/templates/wp-weixin-browser-qr.php';
		}

		return $template;
	}

	public function get_browser_page_qr_src( $page_qr_src = '' ) {
		global $wp;
		$current_url = home_url( $wp->request );

		$hash = base64_encode( $current_url . '|' . wp_create_nonce( 'qr_code' ) );// @codingStandardsIgnoreLine

		$page_qr_src = home_url( 'wp-weixin/get-qrcode/hash/' . $hash );

		return $page_qr_src;
	}

	public function oauth() {
		$user_id = false;

		if ( ! is_user_logged_in() ) {
			$oauth_access_token_info = $this->wechat->getOauthAccessToken();

			if ( false !== $oauth_access_token_info ) {
				$state = filter_input( INPUT_GET, 'state', FILTER_SANITIZE_STRING );

				if ( wp_verify_nonce( $state, __FILE__ ) ) {
					$this->_login( $oauth_access_token_info );
					$this->maybe_force_follow( WP_Weixin_Settings::get_option( 'force_follower' ) );
				} else {
					$title    = '<h2>' . __( 'System error.', 'wp-weixin' ) . '</h2>';
					$message  = '<p>' . __( 'Invalid CSRF token. Please refresh the page. ', 'wp-weixin' );
					$message .= __( 'If the problem persists, please contact an administrator.', 'wp-weixin' ) . '</p>';

					wp_die( $title . $message );// @codingStandardsIgnoreLine
				}
			} else {
				$this->_pre_oauth();
			}
		} else {
			$user    = wp_get_current_user();
			$user_id = $user->ID;
			$user_id = $this->_auth_refresh( $user_id );

			$this->maybe_force_follow( WP_Weixin_Settings::get_option( 'force_follower' ) );
		}
	}

	public function oauth_cookie( $length, $user_id, $remember ) {

		if ( $this->expire_length ) {
			$length = current_time( 'timestamp' ) + (int) $this->expire_length;
		}

		return $length;
	}

	public function get_subscribe_src( $src = '' ) {

		if ( empty( $src ) ) {
			$src = $this->qr_subscribe_src;
		}

		return $src;
	}

	public function subscribe_template() {
		$template = locate_template( array( 'wp-weixin-subscribe.php' ) );

		if ( empty( $template ) ) {
			$template = WP_WEIXIN_PLUGIN_PATH . 'inc/templates/wp-weixin-subscribe.php';
		}

		return $template;
	}

	public function page_needs_wechat_auth( $needs_auth ) {
		$current_url = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$path        = wp_parse_url( $current_url, PHP_URL_PATH );
		$is_admin    = current_user_can( 'administrator' );

		$needs_auth = $needs_auth && strpos( $path, 'wp-login' ) === false;
		$needs_auth = $needs_auth && strpos( $path, 'wp-admin' ) === false;
		$needs_auth = $needs_auth && strpos( $path, 'wc-api' ) === false;
		$needs_auth = $needs_auth && strpos( $path, 'weixin-responder' ) === false;
		$needs_auth = $needs_auth && ! $is_admin;

		return $needs_auth;
	}

	public function maybe_force_follow( $force_follow = true ) {

		if ( ! $force_follow || ! WP_Weixin::is_wechat_mobile() ) {

			return;
		}

		$user_id               = get_current_user_id();
		$recently_unsubscribed = get_transient( 'wp_weixin_recent_unsub_' . $user_id );
		$follower_cookie       = $recently_unsubscribed ? false : filter_input( INPUT_COOKIE, 'wx_follower' );

		if ( ! $follower_cookie ) {
			$is_follower = $this->wechat->follower( get_user_meta( $user_id, 'wx_openid', true ) );

			if ( ! $is_follower ) {
				global $wp;

				$queried_object = get_queried_object();

				if ( $queried_object instanceof WP_Post ) {
					update_user_meta( $user_id, 'wp_weixin_before_subscription', $queried_object->ID );
				} else {
					$current_url = home_url( add_query_arg( array(), $wp->request ) );

					update_user_meta( $user_id, 'wp_weixin_before_subscription', $current_url );
				}

				$qr_url = get_transient( 'wp_weixin_subscribe_url' );

				if ( ! $qr_url ) {
					$qr_url = $this->wechat->getQRUrl( 1, false, 0, 'wp_wexin_subscribe' );

					set_transient( 'wp_weixin_subscribe_url', $qr_url );
				}

				$this->_set_subscribe_image_base64_src( $qr_url );
				add_filter( 'wp_weixin_subscribe_src', array( $this, 'get_subscribe_src' ), 0, 1 );
				add_action( 'template_include', array( $this, 'subscribe_template' ), 99, 0 );

			} else {
				setcookie( 'wx_follower', 1, current_time( 'timestamp' ) + 3600 );
				delete_transient( 'wp_weixin_recent_unsub_' . $user_id );
			}
		}
	}

	public function force_logout( $request_data ) {

		if ( isset( $request_data['event'], $request_data['fromusername'] ) && 'unsubscribe' === $request_data['event'] ) {
			$user = get_user_by( 'login', 'wx-' . $request_data['fromusername'] );

			if ( $user ) {
				$sessions = WP_Session_Tokens::get_instance( $user->ID );

				$sessions->destroy_all();
				set_transient( 'wp_weixin_recent_unsub_' . $user->ID, true );
			}
		}
	}

	/*******************************************************************
	 * Private methods
	 *******************************************************************/

	private function _pre_oauth() {// @codingStandardsIgnoreLine
		$current_url = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$scope = 'snsapi_userinfo';
		$state = wp_create_nonce( __FILE__ );
		$url   = $this->wechat->getOAuthRedirect( $current_url, $state, $scope );

		header( 'Location: ' . $url );

		exit();
	}


	private function _login( $oauth_access_token_info ) {// @codingStandardsIgnoreLine
		$error = $this->wechat->getError();
		$error = ( $error ) ? $error['code'] . ': ' . $error['message'] : false;

		if ( $error ) {
			$title = '<h2>' . __( 'System error.', 'wp-weixin' ) . '</h2>';
			// translators: Error message
			$message = '<p>' . sprintf( __( 'Access to user information failed: %1$s. ', 'wp-weixin' ), $error );

			$message .= __( 'If the problem persists, please contact an administrator.', 'wp-weixin' ) . '</p>';

			wp_die( $title . $message );// @codingStandardsIgnoreLine
		}

		$access_token        = $oauth_access_token_info['access_token'];
		$refresh_token       = $oauth_access_token_info['refresh_token'];
		$openid              = $oauth_access_token_info['openid'];
		$this->expire_length = $oauth_access_token_info['expires_in'];

		if ( WP_Weixin_Settings::get_option( 'enable_auth' ) ) {
			$user = get_user_by( 'login', 'wx-' . $openid );

			if ( ! username_exists( 'wx-' . $openid ) ) {
				$user_id = $this->_register_user( $openid, $access_token, $refresh_token );
			} else {
				$user_id = $this->_update_user_info( $access_token, $openid );
			}

			wp_set_current_user( $user_id, 'wx-' . $openid );
			wp_set_auth_cookie( $user_id );
		}

		setcookie( 'wx_openId', $openid, current_time( 'timestamp' ) + (int) $this->expire_length );
	}

	private function _update_user_info( $access_token, $openid ) {// @codingStandardsIgnoreLine
		$user_info = $this->wechat->getOauthUserInfo( $access_token, $openid );
		$result    = false;

		if ( ! $user_info || isset( $user_info['errcode'] ) ) {
			// fail silently
			error_log( 'System error, access to wechat user information for ' . $openid . ' failed.' );// @codingStandardsIgnoreLine// @codingStandardsIgnoreLine
		} else {

			$avatar = $user_info['headimgurl'];

			if ( ! $avatar ) {
				$avatar = '';
			}

			if ( substr( $avatar, -2 ) === '/0' ) {
				$avatar = substr( $avatar, 0, -2 ) . '/132';
			}

			$user = get_user_by( 'login', 'wx-' . $user_info['openid'] );

			$user_data = array(
				'ID'           => $user->ID,
				'user_login'   => 'wx-' . $user_info['openid'],
				'display_name' => WP_Weixin::format_emoji( $user_info['nickname'] ),
				'user_pass'    => $user_info['openid'],
				'nickname'     => WP_Weixin::format_emoji( $user_info['nickname'] ),
			);

			$user_id = wp_update_user( $user_data );

			if ( is_wp_error( $user_id ) ) {
				// fail silently
				error_log( 'System error, failed to update user' . $openid . '. Reason:' . $user_id->get_error_message() );// @codingStandardsIgnoreLine
			}

			update_user_meta( $user_id, 'wp_weixin_rawdata', wp_json_encode( $user_info ) );

			$result = $user_id;
		}

		return $result;
	}

	private function _register_user( $openid, $access_token, $refresh_token ) {// @codingStandardsIgnoreLine
		$user_info = $this->wechat->getOauthUserInfo( $access_token, $openid );

		$error = $this->wechat->getError();
		$error = ( $error ) ? $error['code'] . ': ' . $error['message'] : false;

		if ( ! $user_info || $error ) {
			$title = '<h2>' . __( 'System error.', 'wp-weixin' ) . '</h2>';

			if ( ! empty( $error ) ) {
				// translators: Error message
				$message = '<p>' . sprintf( __( 'Access to user basic information failed: %1$s. ', 'wp-weixin' ), $error );
			} else {
				$message = '<p>' . __( 'Access to user basic information failed. ', 'wp-weixin' );
			}

			$message .= __( 'If the problem persists, please contact an administrator.', 'wp-weixin' ) . '</p>';

			wp_die( $title . $message );// @codingStandardsIgnoreLine
		}

		$user_info['access_token']   = $access_token;
		$user_info['refresh_token']  = $refresh_token;
		$user_info['refresh_expire'] = current_time( 'timestamp' ) + (int) $this->expire_length;

		$user_data = array(
			'user_login'   => 'wx-' . $user_info['openid'],
			'display_name' => WP_Weixin::format_emoji( $user_info['nickname'] ),
			'user_pass'    => $user_info['openid'],
			'nickname'     => WP_Weixin::format_emoji( $user_info['nickname'] ),
		);

		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) || ! $user_id ) {
			$title   = '<h2>' . __( 'System error.', 'wp-weixin' ) . '</h2>';
			$message = '<p>' . __( 'Failed to create user, please refresh the page. ', 'wp-weixin' );

			$message .= __( 'If the problem persists, please contact an administrator.', 'wp-weixin' ) . '</p>';

			wp_die( $title . $message );// @codingStandardsIgnoreLine
		}

		update_user_meta( $user_id, 'wp_weixin_rawdata', wp_json_encode( $user_info ) );

		return $user_id;
	}

	private function _auth_refresh( $user_id ) {// @codingStandardsIgnoreLine
		$json_user_info = get_user_meta( $user_id, 'wp_weixin_rawdata', true );
		$user_info      = json_decode( $json_user_info, true );
		$refresh_token  = isset( $user_info['refresh_token'] ) ? $user_info['refresh_token'] : false;
		$refresh_expire = isset( $user_info['refresh_expire'] ) ? $user_info['refresh_expire'] : false;

		if ( $refresh_expire > 0 && $refresh_expire < current_time( 'timestamp' ) + 30 ) {
			$refresh_info = $this->wechat->refreshOauthAccessToken( $refresh_token );

			$this->expire_length = $refresh_info['expires_in'];

			$user_info['access_token']   = isset( $refresh_info['access_token'] ) ? $refresh_info['access_token'] : false;
			$user_info['refresh_token']  = isset( $refresh_info['refresh_token'] ) ? $refresh_info['refresh_token'] : false;
			$user_info['refresh_expire'] = isset( $refresh_info['refresh_expire'] ) ? $refresh_info['refresh_expire'] : false;

			$result = update_user_meta( $user_id, 'wp_weixin_rawdata', wp_json_encode( $user_info ) );

			setcookie( 'wx_openId', $user_info['openid'], $this->expire_length );
			wp_set_auth_cookie( $user_id );
		}
	}

	private function _set_subscribe_image_base64_src( $url ) {// @codingStandardsIgnoreLine
		$src   = false;
		$image = false;

		if ( ! empty( $url ) ) {
			$image = wp_remote_retrieve_body( wp_remote_get( $url ) );
		}

		if ( false !== $image ) {
			$src = 'data:image/jpg;base64,' . base64_encode( $image );// @codingStandardsIgnoreLine
		}

		$this->qr_subscribe_src = $src;
	}
}
