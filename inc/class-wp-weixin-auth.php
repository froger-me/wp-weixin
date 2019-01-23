<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Auth {

	public $prevent_force_follow = false;

	protected static $needs_auth;

	protected $expire_length;
	protected $wechat;
	protected $qr_subscribe_src;
	protected $doing_wechat_auth = false;
	protected $auth_qr_data;
	protected $target_url;
	protected $target_blog_id;

	public function __construct( $wechat, $init_hooks = false ) {
		$this->wechat = $wechat;

		if ( $init_hooks ) {
			// Logout when unsubscribed
			add_action( 'wp_weixin_responder', array( $this, 'force_logout' ), PHP_INT_MIN + 10, 1 );

			if ( WP_Weixin_Settings::get_option( 'enable_auth' ) ) {
				// Manage WeChat authentication
				add_action( 'init', array( $this, 'manage_auth' ), PHP_INT_MIN + 10, 0 );
				// Parse the endpoint request
				add_action( 'parse_request', array( $this, 'parse_request' ), PHP_INT_MIN + 10, 0 );
				// Add admin scripts
				add_action( 'login_enqueue_scripts', array( $this, 'add_login_scripts' ), 99, 1 );
				// Add QR code generation ajax callback
				add_action( 'wp_ajax_nopriv_wp_weixin_get_auth_qr', array( $this, 'get_qr_src' ), 10, 0 );
				// Add QR code heartbeat ajax callback
				add_action( 'wp_ajax_nopriv_wp_weixin_auth_heartbeat_pulse', array( $this, 'heartbeat_pulse' ), 10, 0 );
				// Clear cookie on logout
				add_action( 'wp_logout', array( $this, 'logout' ), PHP_INT_MIN + 10, 0 );

				// Determine where WeChat authentication is needed
				add_filter( 'wp_weixin_auth_needed', array( $this, 'page_needs_wechat_auth' ), PHP_INT_MIN + 10, 1 );

				if ( WP_Weixin_Settings::get_option( 'show_auth_link' ) && ! WP_Weixin::is_wechat_mobile() ) {
					// Add link to WeChat qr authentication page
					add_action( 'login_footer', array( $this, 'auth_link' ), 10, 0 );
					// Add auth link on Ultimate Membership plugin login forms
					add_action( 'um_after_form', array( $this, 'third_party_auth_link' ), 0, 1 );
					// Add auth link on WooCommerce plugin login forms
					add_action( 'woocommerce_login_form_end', array( $this, 'third_party_auth_link' ), 0, 0 );
				}
			}

			// Add the API endpoints
			add_action( 'wp_weixin_endpoints', array( $this, 'add_endpoints' ), 0, 0 );
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public static function is_auth_needed() {
		$needs_auth = WP_Weixin_Settings::get_option( 'force_wechat' );
		$needs_auth = $needs_auth || WP_Weixin::is_wechat_mobile();

		self::$needs_auth = apply_filters( 'wp_weixin_auth_needed', $needs_auth );

		return self::$needs_auth;
	}

	public function add_endpoints() {
		add_rewrite_rule(
			'^wp-weixin/(wechat-auth-validate|auth-redirect|wechat-auth-qr)/hash/([^\/\?\#]*)(\/)?(\?(.*))?$',
			'index.php?__wp_weixin_api=1&action=$matches[1]&hash=$matches[2]',
			'top'
		);
		add_rewrite_rule(
			'^wp-weixin/wechat-auth$',
			'index.php?__wp_weixin_api=1&action=wechat-auth',
			'top'
		);

		if ( is_multisite() ) {
			add_rewrite_rule(
				'^wp-weixin/(ms-crossdomain|ms-set-target)/hash/([^\/\?\#]*)(\/)?(\?(.*))?$',
				'index.php?__wp_weixin_api=1&action=$matches[1]&hash=$matches[2]',
				'top'
			);
		}
	}

	public function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['__wp_weixin_api'] ) ) {
			$action = $wp->query_vars['action'];

			if ( 'wechat-auth' === $action && ! WP_Weixin::is_wechat_mobile() ) {
				add_action( 'template_redirect', array( $this, 'wechat_auth_page' ), 0, 0 );
			}

			if ( 'wechat-auth-qr' === $action && ! WP_Weixin::is_wechat_mobile() ) {
				$hash    = isset( $wp->query_vars['hash'] ) ? $wp->query_vars['hash'] : false;
				$bundle  = explode( '|', base64_decode( $hash ) ); // @codingStandardsIgnoreLine
				$nonce   = array_pop( $bundle );
				$qr_id   = array_pop( $bundle );
				$blog_id = ( is_multisite() ) ? get_current_blog_id() : null;

				if ( ! wp_verify_nonce( $nonce, 'wp_weixin_qr_code' ) ) {

					exit();
				}

				WP_Weixin_Settings::get_qrcode(
					get_home_url(
						$blog_id,
						'wp-weixin/wechat-auth-validate/hash/' . $qr_id
					)
				);
			}

			if ( 'wechat-auth-validate' === $action && WP_Weixin::is_wechat_mobile() ) {
				$this->prevent_force_follow = true;

				$this->oauth();

				$qr_id = isset( $wp->query_vars['hash'] ) ? $wp->query_vars['hash'] : false;

				$this->check_wechat_qr_auth( $qr_id );
			}

			if ( 'ms-set-target' === $action && WP_Weixin::is_wechat_mobile() ) {
				$hash          = $wp->query_vars['hash'];
				$payload       = WP_Weixin_Settings::decode_url( $hash );
				$payload_parts = explode( '|', $payload );

				$this->prevent_force_follow = true;
				$this->target_url           = reset( $payload_parts );
				$this->target_blog_id       = absint( end( $payload_parts ) );
			}

			if ( 'ms-crossdomain' === $action && WP_Weixin::is_wechat_mobile() ) {
				$hash         = $wp->query_vars['hash'];
				$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );

				if ( ! $auth_blog_id ) {
					$callback = network_home_url( 'wp-weixin/ms-set-target/hash/' . $hash );
				} else {
					$callback = get_home_url( $auth_blog_id, 'wp-weixin/ms-set-target/hash/' . $hash );
				}

				$scope = 'snsapi_userinfo';
				$state = wp_create_nonce( 'wp_weixin_auth_state' );
				$url   = $this->wechat->getOAuthRedirect( $callback, $state, $scope );

				header( 'Location: ' . $url );

				exit();
			}

			if ( 'auth-redirect' === $action && WP_Weixin::is_wechat_mobile() ) {
				$hash = $wp->query_vars['hash'];
				$url  = WP_Weixin_Settings::decode_url( $hash );

				$this->oauth();

				header( 'Location: ' . $url );

				exit();
			}
		}
	}

	public function page_needs_wechat_auth( $needs_auth ) {
		$protocol    = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://';
		$current_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$path        = wp_parse_url( $current_url, PHP_URL_PATH );

		$needs_auth = $needs_auth && strpos( $path, 'wp-login' ) === false;
		$needs_auth = $needs_auth && strpos( $path, 'wp-admin' ) === false;
		$needs_auth = $needs_auth && strpos( $path, 'wc-api' ) === false;
		$needs_auth = $needs_auth && strpos( $path, 'wp-weixin/wechat-auth' ) === false;
		$needs_auth = $needs_auth && strpos( $path, 'weixin-responder' ) === false;
		$needs_auth = $needs_auth && strpos( $path, 'admin-ajax.php' ) === false;
		$needs_auth = $needs_auth && ! current_user_can( 'administrator' );

		return $needs_auth;
	}

	public function manage_auth() {
		$is_wechat_mobile = WP_Weixin::is_wechat_mobile();
		$is_auth_needed   = self::is_auth_needed();

		if ( $is_wechat_mobile && $is_auth_needed ) {
			add_action( 'wp', array( $this, 'oauth' ), 0, 0 );
			add_filter( 'auth_cookie_expiration', array( $this, 'oauth_cookie' ), 1, 3 );
		} elseif ( ! $is_wechat_mobile && $is_auth_needed ) {
			add_action( 'template_redirect', array( $this, 'show_browser_qr' ), 0, 0 );
		}

		if ( is_plugin_active( 'open-social/open-social.php' ) ) {

			if ( ! isset( $_GET['connect'] ) || 'wechat' === $_GET['connect'] ) { //@codingStandardsIgnoreLine

				if ( isset( $_GET['connect'] ) ) { //@codingStandardsIgnoreLine
					unset( $_GET['connect'] ); //@codingStandardsIgnoreLine
				}

				if ( isset( $_GET['code'] ) ) { //@codingStandardsIgnoreLine
					unset( $_GET['code'] );
				}

				if ( isset( $_GET['state'] ) ) { //@codingStandardsIgnoreLine
					unset( $_GET['state'] );
				}
			}
		}
	}

	public function oauth() {
		$user_id = false;

		if ( ! is_user_logged_in() ) {
			$this->doing_wechat_auth = true;
			$oauth_access_token_info = $this->wechat->getOauthAccessToken();

			if ( false !== $oauth_access_token_info ) {
				$state = filter_input( INPUT_GET, 'state', FILTER_SANITIZE_STRING );

				if ( wp_verify_nonce( $state, 'wp_weixin_auth_state' ) ) {
					$this->login( $oauth_access_token_info );
					$this->maybe_force_follow( WP_Weixin_Settings::get_option( 'force_follower' ) );
				} else {
					$title    = '<h2>' . __( 'System error.', 'wp-weixin' ) . '</h2>';
					$message  = '<p>' . __( 'Invalid CSRF token. Please refresh the page. ', 'wp-weixin' );
					$message .= __( 'If the problem persists, please contact an administrator.', 'wp-weixin' ) . '</p>';

					wp_die( $title . $message ); // WPCS: XSS ok
				}
			} else {
				$this->pre_oauth();
			}
		} else {
			$user = wp_get_current_user();

			$this->auth_refresh( $user->ID );

			if ( is_multisite() ) {
				$blog_id      = get_current_blog_id();
				$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );

				if (
					! is_user_member_of_blog( $user->ID, $blog_id ) &&
					apply_filters( 'wp_weixin_ms_auto_add_to_blog', true, $blog_id, $user->ID ) &&
					get_user_meta( $user->ID, 'wx_openid-' . $auth_blog_id, true )
				) {
					$role = get_option( 'default_role' );

					add_user_to_blog( $blog_id, $user->ID, $role );
				}
			}

			$this->maybe_force_follow( WP_Weixin_Settings::get_option( 'force_follower' ) );
		}
	}

	public function oauth_cookie( $length, $user_id, $remember ) {

		if ( $this->expire_length ) {
			$length = time() + (int) $this->expire_length;
		}

		return $length;
	}

	public function maybe_force_follow( $force_follow = true ) {

		if ( ! $force_follow || ! WP_Weixin::is_wechat_mobile() || $this->prevent_force_follow ) {

			return;
		}

		$user_id               = get_current_user_id();
		$recently_unsubscribed = get_transient( 'wp_weixin_recent_unsub_' . $user_id );
		$follower_cookie       = $recently_unsubscribed ? false : filter_input( INPUT_COOKIE, 'wx_follower' );

		if ( ! $follower_cookie ) {
			$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
			$openid       = get_user_meta( $user_id, 'wx_openid-' . $auth_blog_id, true );
			$is_follower  = $this->wechat->follower( $openid );

			if ( ! $is_follower ) {
				global $wp;

				$queried_object = get_queried_object();

				if ( $queried_object instanceof WP_Post ) {
					update_user_meta( $user_id, 'wp_weixin_before_subscription', $queried_object->ID );
				} else {
					$blog_id     = ( is_multisite() ) ? get_current_blog_id() : null;
					$current_url = get_home_url( $blog_id, add_query_arg( array(), $wp->request ) );

					update_user_meta( $user_id, 'wp_weixin_before_subscription', $current_url );
				}

				$qr_url = get_transient( 'wp_weixin_subscribe_url' );

				if ( ! $qr_url ) {
					$qr_url = $this->wechat->getQRUrl( 1, false, 0, 'wp_wexin_subscribe' );

					set_transient( 'wp_weixin_subscribe_url', $qr_url, 3600 );
				}

				$this->set_subscribe_image_base64_src( $qr_url );
				add_action( 'template_redirect', array( $this, 'subscribe_oa' ), 0, 0 );

			} else {
				setcookie( 'wx_follower', 1, time() + 3600 );
				delete_transient( 'wp_weixin_recent_unsub_' . $user_id );
			}
		}
	}

	public function force_logout( $request_data ) {

		if ( isset( $request_data['event'], $request_data['fromusername'] ) && 'unsubscribe' === $request_data['event'] ) {
			$user = WP_Weixin::get_user_by_openid( $request_data['fromusername'] );

			if ( $user ) {
				$sessions = WP_Session_Tokens::get_instance( $user->ID );

				$sessions->destroy_all();
				set_transient( 'wp_weixin_recent_unsub_' . $user->ID, true, 3600 );
			}
		}
	}

	public function get_subscribe_src() {

		if ( empty( $src ) ) {
			$src = $this->qr_subscribe_src;
		}

		return $src;
	}

	public function subscribe_oa() {
		set_query_var( 'qr_src', apply_filters( 'wp_weixin_subscribe_src', $this->get_subscribe_src() ) );
		set_query_var( 'title', apply_filters( 'wp_weixin_follower_notice_title', __( 'Follow Us!', 'wp-weixin' ) ) );
		set_query_var(
			'message',
			apply_filters(
				'wp_weixin_follower_notice',
				__( 'Please scan this QR Code to follow us before accessing this content.', 'wp-weixin' )
			)
		);
		WP_Weixin::locate_template( 'wp-weixin-subscribe.php', true );

		exit();
	}

	public function show_browser_qr() {
		set_query_var( 'page_qr_src', apply_filters( 'wp_weixin_browser_page_qr_src', $this->get_browser_page_qr_src() ) );
		WP_Weixin::locate_template( 'wp-weixin-browser-qr.php', true );

		exit();
	}

	public function get_browser_page_qr_src() {
		global $wp;

		$blog_id     = ( is_multisite() ) ? get_current_blog_id() : null;
		$current_url = get_home_url( $blog_id, $wp->request );
		$hash        = base64_encode( $current_url . '|' . wp_create_nonce( 'wp_weixin_qr_code' ) ); // @codingStandardsIgnoreLine
		$page_qr_src = get_home_url( $blog_id, 'wp-weixin/get-qrcode/hash/' . $hash );

		return $page_qr_src;
	}

	public function third_party_auth_link( $args = null ) {

		if ( ! empty( $args ) && is_array( $args ) ) {

			if (
				isset( $args['template'], $args['template'] ) &&
				'login' === $args['template'] &&
				'login' === $args['mode']
			) {
				$display = true;
			}
		} else {
			$display = true;
		}

		$class = 'wp-wx-' . str_replace( '_', '-', current_filter() );

		$this->auth_link( true, $class );
	}

	public function auth_link( $output = true, $class = '', $target = '' ) {

		if ( get_current_user_id() ) {

			return;
		}

		$class = ( 'login_footer' === current_filter() ) ? 'wp-wx-login-form' : $class;

		set_query_var( 'class', $class );
		set_query_var( 'target', $target );

		ob_start();
		WP_Weixin::locate_template( 'wp-weixin-auth-form-link.php', true );

		$html = ob_get_clean();

		if ( $output ) {
			echo $html; // WPCS: XSS ok
		}

		return ( $output ) ? $html : false;
	}

	public function heartbeat_pulse() {
		$hash   = filter_input( INPUT_POST, 'hash', FILTER_SANITIZE_STRING );
		$bundle = explode( '|', base64_decode( $hash ) ); // @codingStandardsIgnoreLine
		$nonce  = array_pop( $bundle );
		$qr_id  = array_pop( $bundle );

		if ( wp_verify_nonce( $nonce, 'wp_weixin_qr_code' ) ) {
			$result = $this->do_wechat_qr_auth( $qr_id );

			if ( is_array( $result ) ) {
				wp_send_json_success( $result );
			} else {
				wp_send_json_success( false );
			}
		} else {
			$error = new WP_Error( __METHOD__, __( 'Invalid parameters', 'wp-weixin' ) );

			wp_send_json_error( $error );
		}

		wp_die();
	}

	public function wechat_auth_result() {
		set_query_var( 'auth_qr_data', $this->auth_qr_data );
		WP_Weixin::locate_template( 'wp-weixin-mobile-auth-check.php', true );

		exit();
	}

	public function get_qr_src() {
		$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );

		if ( wp_verify_nonce( $nonce, 'wp_weixin_qr_code' ) ) {
			$qr_id = bin2hex( openssl_random_pseudo_bytes( 10 ) );
			$hash  = base64_encode( $qr_id . '|' . wp_create_nonce( 'wp_weixin_qr_code' ) ); // @codingStandardsIgnoreLine

			set_transient(
				'wp_weixin_qr_' . $qr_id,
				time() + apply_filters( 'wp_weixin_qr_lifetime', WP_Weixin::MAX_QR_LIFETIME ),
				apply_filters( 'wp_weixin_qr_lifetime', WP_Weixin::MAX_QR_LIFETIME )
			);

			$blog_id = ( is_multisite() ) ? get_current_blog_id() : null;
			$url     = get_home_url( $blog_id, 'wp-weixin/wechat-auth-qr/hash/' . $hash );

			wp_send_json_success(
				array(
					'qrSrc' => $url,
					'hash'  => $hash,
				)
			);
		} else {
			$error = new WP_Error( __METHOD__, __( 'Invalid parameters', 'wp-weixin' ) );

			wp_send_json_error( $error );
		}

		wp_die();
	}

	public function wechat_auth_page() {

		if ( is_user_logged_in() ) {
			$blog_id  = ( is_multisite() ) ? get_current_blog_id() : null;
			$redirect = apply_filters( 'wp_weixin_auth_redirect', get_home_url( $blog_id, '/' ), true, false );

			wp_redirect( $redirect );

			exit();
		}

		remove_all_actions( 'wp_footer' );
		remove_all_actions( 'shutdown' );

		add_action( 'wp_footer', 'wp_print_footer_scripts', 20 );
		add_action( 'shutdown', 'wp_ob_end_flush_all', 1 );

		WP_Weixin::$scripts[] = 'wp-weixin-main-script';
		WP_Weixin::$scripts[] = 'wechat-api-script';
		WP_Weixin::$scripts[] = 'wp-weixin-scan-heartbeat';
		WP_Weixin::$styles[]  = 'wp-weixin-main-style';

		add_action( 'wp_print_scripts', array( 'WP_Weixin', 'remove_all_scripts' ), 100 );
		add_action( 'wp_print_styles', array( 'WP_Weixin', 'remove_all_styles' ), 100 );

		WP_Weixin::add_scan_heartbeat_scripts( 'auth' );
		add_filter( 'template_include', array( $this, 'wechat_auth_template' ), 99, 1 );
	}

	public function wechat_auth_template( $template ) {
		$template = WP_Weixin::locate_template( 'wp-weixin-auth-page.php' );

		return $template;
	}

	public function add_login_scripts( $hook ) {
		$debug   = apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) );
		$css_ext = ( $debug ) ? '.css' : '.min.css';
		$version = filemtime( WP_WEIXIN_PLUGIN_PATH . 'css/admin/login' . $css_ext );

		wp_enqueue_style( 'wp-weixin-login-style', WP_WEIXIN_PLUGIN_URL . 'css/admin/login' . $css_ext, array(), $version );
	}

	public function logout() {
		$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );

		setcookie(
			'wx_openId-' . $auth_blog_id,
			'',
			time() - 3600,
			'/',
			COOKIE_DOMAIN
		);

		unset( $_COOKIE[ 'wx_openId-' . $auth_blog_id ] );
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function pre_oauth() {
		$scope = 'snsapi_userinfo';
		$state = wp_create_nonce( 'wp_weixin_auth_state' );

		if ( is_multisite() ) {
			$auth_blog_id   = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
			$target_blog_id = get_current_blog_id();
			$protocol       = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://';
			$destination    = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$destination    = WP_Weixin_Settings::encode_url( $destination . '|' . $target_blog_id );

			if ( ! $auth_blog_id ) {
				$url = network_home_url( 'wp-weixin/ms-crossdomain/hash/' . $destination );
			} else {
				$url = get_home_url( $auth_blog_id, 'wp-weixin/ms-crossdomain/hash/' . $destination );
			}
		} else {
			$protocol    = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://';
			$destination = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$destination = WP_Weixin_Settings::encode_url( $destination );
			$callback    = home_url( 'wp-weixin/auth-redirect/hash/' . $destination );
			$url         = $this->wechat->getOAuthRedirect( $callback, $state, $scope );
		}

		header( 'Location: ' . $url );

		exit();
	}

	protected function login( $oauth_access_token_info ) {
		$error = $this->wechat->getError();
		$error = ( $error ) ? $error['code'] . ': ' . $error['message'] : false;

		if ( $error ) {
			$title = '<h2>' . __( 'System error.', 'wp-weixin' ) . '</h2>';
			// translators: Error message
			$message = '<p>' . sprintf( __( 'Access to user information failed: %1$s. ', 'wp-weixin' ), $error );

			$message .= __( 'If the problem persists, please contact an administrator.', 'wp-weixin' ) . '</p>';

			wp_die( $title . $message ); // WPCS: XSS ok
		}

		$access_token        = $oauth_access_token_info['access_token'];
		$refresh_token       = $oauth_access_token_info['refresh_token'];
		$openid              = $oauth_access_token_info['openid'];
		$this->expire_length = $oauth_access_token_info['expires_in'];
		$redirect            = false;

		if ( WP_Weixin_Settings::get_option( 'enable_auth' ) ) {

			if ( $this->target_blog_id ) {
				switch_to_blog( $this->target_blog_id );
			}

			$user         = WP_Weixin::get_user_by_openid( $openid );
			$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );

			if ( ! $user ) {
				$email = $this->get_default_email( $openid );

				wp_cache_delete( $email, 'useremail' );

				$user = get_user_by( 'email', $email );
			}

			if ( ! $user ) {
				$user = $this->register_user( $openid, $access_token, $refresh_token );
			} else {
				$user = $this->update_user_info( $access_token, $openid, $user );
			}

			clean_user_cache( $user );

			if ( $this->target_blog_id ) {

				if ( ! is_user_member_of_blog( $user->ID, $this->target_blog_id ) ) {
					$role = get_option( 'default_role' );

					add_user_to_blog( $this->target_blog_id, $user->ID, $role );
				}

				$redirect = $this->target_url;
			}

			wp_set_current_user( $user->ID, $user->user_login );
			wp_set_auth_cookie( $user->ID );
			setcookie(
				'wx_openId-' . $auth_blog_id,
				$openid,
				time() + (int) $this->expire_length,
				'/',
				COOKIE_DOMAIN
			);

			if ( $this->target_blog_id ) {
				restore_current_blog();
			}
		}

		if ( $redirect ) {
			wp_redirect( $redirect );

			exit();
		}
	}

	protected function update_user_info( $access_token, $openid, $user ) {
		$user_info = $this->wechat->getOauthUserInfo( $access_token, $openid );
		$result    = false;

		if ( ! $user_info || isset( $user_info['errcode'] ) ) {
			$message = 'System error, access to WeChat user information for ' . $openid . ' failed (silently failed).';

			WP_Weixin::log( $message );
		} else {
			$auth_blog_id        = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
			$openid_login_suffix = ( is_multisite() ) ? strtolower( $openid ) : $openid;

			if ( ! $user_info['headimgurl'] ) {
				$user_info['headimgurl'] = '';
			}

			if ( substr( $user_info['headimgurl'], -2 ) === '/0' ) {
				$user_info['headimgurl'] = substr( $user_info['headimgurl'], 0, -2 ) . '/132';
			}

			if ( 'wx-' . $openid_login_suffix === $user->user_login ) {
				$user_data = array(
					'ID'           => $user->ID,
					'display_name' => $this->format_emoji( $user_info['nickname'] ),
					'nickname'     => $this->format_emoji( $user_info['nickname'] ),
				);

				if ( empty( $user->user_email ) ) {
					$user_data['user_email'] = $this->get_default_email( $user_info['openid'] );
				}

				$user_id = wp_update_user( $user_data );

				if ( is_wp_error( $user_id ) ) {
					$reason  = $user_id->get_error_message();
					$message = 'System error, failed to update user' . $openid . '. Reason:' . $reason . ' (silently failed).';

					WP_Weixin::log( $message );
				}
			} else {
				$user_id = $user->ID;
			}

			update_user_meta( $user_id, 'wx_openid-' . $auth_blog_id, $user_info['openid'] );
			update_user_meta( $user_id, 'wx_unionid', $user_info['unionid'] );

			if ( is_multisite() ) {
				$user_info['openid'] = array( $auth_blog_id => $user_info['openid'] );
			}

			unset( $user_info['openid'] );
			update_user_meta( $user_id, 'wp_weixin_rawdata', wp_json_encode( $user_info ) );

			$result = $user;
		}

		return $result;
	}

	protected function register_user( $openid, $access_token, $refresh_token ) {
		$user_info = $this->wechat->getOauthUserInfo( $access_token, $openid );
		$error     = $this->wechat->getError();

		if ( ! $user_info || $error ) {
			$title = '<h2>' . __( 'System error.', 'wp-weixin' ) . '</h2>';

			if ( ! empty( $error ) ) {
				$error = $error['code'] . ': ' . $error['message'];

				// translators: Error message
				$message = '<p>' . sprintf( __( 'Access to user basic information failed: %1$s. ', 'wp-weixin' ), esc_html( $error ) );
			} else {
				$message = '<p>' . __( 'Access to user basic information failed. ', 'wp-weixin' );
			}

			$message .= __( 'If the problem persists, please contact an administrator.', 'wp-weixin' ) . '</p>';

			wp_die( $title . $message ); // WPCS: XSS ok
		}

		$user_info['access_token']   = $access_token;
		$user_info['refresh_token']  = $refresh_token;
		$user_info['refresh_expire'] = time() + (int) $this->expire_length;
		$openid_login_suffix         = ( is_multisite() ) ? strtolower( $user_info['openid'] ) : $user_info['openid'];

		$user_data = array(
			'user_login'   => 'wx-' . $openid_login_suffix,
			'display_name' => $this->format_emoji( $user_info['nickname'] ),
			'user_pass'    => wp_generate_password(),
			'user_email'   => $this->get_default_email( $user_info['openid'] ),
			'nickname'     => $this->format_emoji( $user_info['nickname'] ),
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
		update_user_meta( $user_id, 'wx_unionid', $user_info['unionid'] );
		unset( $user_info['openid'] );
		update_user_meta( $user_id, 'wp_weixin_rawdata', wp_json_encode( $user_info ) );

		return get_user_by( 'ID', $user_id );
	}

	protected function auth_refresh( $user_id ) {
		wp_cache_delete( $user_id, 'user_meta' );

		$json_user_info = get_user_meta( $user_id, 'wp_weixin_rawdata', true );
		$user_info      = json_decode( $json_user_info, true );
		$refresh_token  = isset( $user_info['refresh_token'] ) ? $user_info['refresh_token'] : false;
		$refresh_expire = isset( $user_info['refresh_expire'] ) ? $user_info['refresh_expire'] : false;

		if ( $refresh_expire > 0 && $refresh_expire < time() + 30 ) {
			$refresh_info = $this->wechat->refreshOauthAccessToken( $refresh_token );
			$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
			$openid       = get_user_meta( $user_id, 'wx_openid-' . $auth_blog_id, true );

			$this->expire_length = $refresh_info['expires_in'];

			$user_info['access_token']   = isset( $refresh_info['access_token'] ) ? $refresh_info['access_token'] : false;
			$user_info['refresh_token']  = isset( $refresh_info['refresh_token'] ) ? $refresh_info['refresh_token'] : false;
			$user_info['refresh_expire'] = isset( $refresh_info['refresh_expire'] ) ? $refresh_info['refresh_expire'] : false;

			wp_set_auth_cookie( $user_id );
			setcookie(
				'wx_openId-' . $auth_blog_id,
				$openid,
				time() + (int) $this->expire_length,
				'/',
				COOKIE_DOMAIN
			);

			if (
				! get_user_meta( $user_id, 'wx_openid-' . $auth_blog_id, true ) &&
				isset( $user_info['openid'] )
			) {
				update_user_meta( $user_id, 'wx_openid-' . $auth_blog_id, $user_info['openid'] );
				unset( $user_info['openid'] );
			}

			update_user_meta( $user_id, 'wp_weixin_rawdata', wp_json_encode( $user_info ) );
		}
	}

	protected function set_subscribe_image_base64_src( $url ) {
		$src   = false;
		$image = false;

		if ( ! empty( $url ) ) {
			$image = wp_remote_retrieve_body( wp_remote_get( $url ) );
		}

		if ( false !== $image ) {
			$src = 'data:image/jpg;base64,' . base64_encode( $image ); // @codingStandardsIgnoreLine
		}

		$this->qr_subscribe_src = $src;
	}

	protected function check_wechat_qr_auth( $qr_id ) {
		$recorded_qr_value = get_transient( 'wp_weixin_qr_' . $qr_id, false );
		$error             = false;
		$user_id           = 0;
		$redirect          = false;

		if ( ! $recorded_qr_value || is_array( $recorded_qr_value ) ) {
			$error = __( 'The QR code is invalid', 'wp-weixin' );
		}

		if ( is_numeric( $recorded_qr_value ) && time() > $recorded_qr_value ) {
			$error = __( 'The QR code is expired', 'wp-weixin' );
		}

		if ( ! is_user_logged_in() ) {
			$error = __( 'Authentication error - check mobile WeChat authentication is enabled', 'wp-weixin' );
		}

		if ( $error ) {
			$auth = false;
		} else {
			$blog_id  = ( is_multisite() ) ? get_current_blog_id() : null;
			$auth     = true;
			$user     = wp_get_current_user();
			$user_id  = $user->ID;
			$redirect = get_home_url( $blog_id, '/' );
		}

		$this->auth_qr_data = array(
			'auth'     => $auth,
			'user_id'  => $user_id,
			'redirect' => apply_filters( 'wp_weixin_auth_redirect', $redirect, $auth, (bool) $error ),
		);

		if ( $error ) {
			$this->auth_qr_data['error'] = array( $error );
		} else {
			$this->auth_qr_data['error'] = false;
		}

		set_transient(
			'wp_weixin_qr_' . $qr_id,
			$this->auth_qr_data,
			apply_filters( 'wp_weixin_qr_lifetime', WP_Weixin::MAX_QR_LIFETIME )
		);

		remove_all_actions( 'wp_footer' );
		remove_all_actions( 'shutdown' );

		add_action( 'wp_footer', 'wp_print_footer_scripts', 20 );
		add_action( 'shutdown', 'wp_ob_end_flush_all', 1 );

		WP_Weixin::$scripts[] = 'wp-weixin-main-script';
		WP_Weixin::$scripts[] = 'wechat-api-script';
		WP_Weixin::$styles[]  = 'wp-weixin-main-style';

		add_action( 'wp_print_scripts', array( 'WP_Weixin', 'remove_all_scripts' ), 100 );
		add_action( 'wp_print_styles', array( 'WP_Weixin', 'remove_all_styles' ), 100 );

		add_action( 'template_redirect', array( $this, 'wechat_auth_result' ), 0, 0 );
	}

	protected function do_wechat_qr_auth( $qr_id ) {
		$recorded_qr_value = get_transient( 'wp_weixin_qr_' . $qr_id, false );
		$return            = array(
			'auth'     => false,
			'error'    => array( __( 'Unknown Error', 'wp-weixin' ) ),
			'redirect' => apply_filters( 'wp_weixin_auth_redirect', false, false, true ),
		);

		if ( is_numeric( $recorded_qr_value ) ) {
			$return = false;
		} elseif (
			is_array( $recorded_qr_value ) &&
			isset( $recorded_qr_value['auth'], $recorded_qr_value['user_id'] ) &&
			$recorded_qr_value['auth'] &&
			$recorded_qr_value['user_id']
		) {
			wp_set_current_user( $recorded_qr_value['user_id'] );
			wp_set_auth_cookie( $recorded_qr_value['user_id'] );

			$return = $recorded_qr_value;
		} elseif ( is_array( $recorded_qr_value ) ) {
			$return = $recorded_qr_value;
		} elseif ( false === $recorded_qr_value ) {
			$return['error'] = array( __( 'The QR code is expired', 'wp-weixin' ) );
		}

		if ( false !== $return ) {
			delete_transient( 'wp_weixin_qr_' . $qr_id );
		}

		return $return;
	}

	protected function get_default_email( $base ) {
		$parsed_url = wp_parse_url( home_url() );
		$domain     = ( isset( $parsed_url['host'] ) ) ? $parsed_url['host'] : 'example.com';

		return $base . '@' . $domain;
	}

	protected function format_emoji( $text ) {
		$clean_text = '';
		// Match Emoticons
		$regex_emoticons = '/[\x{1F600}-\x{1F64F}]/u';
		$clean_text      = preg_replace( $regex_emoticons, '', $text );
		// Match Miscellaneous Symbols and Pictographs
		$regex_symbols = '/[\x{1F300}-\x{1F5FF}]/u';
		$clean_text    = preg_replace( $regex_symbols, '', $clean_text );
		// Match Transport And Map Symbols
		$regex_transport = '/[\x{1F680}-\x{1F6FF}]/u';
		$clean_text      = preg_replace( $regex_transport, '', $clean_text );
		// Match Miscellaneous Symbols
		$regex_misc = '/[\x{2600}-\x{26FF}]/u';
		$clean_text = preg_replace( $regex_misc, '', $clean_text );
		// Match Dingbats
		$regex_dingbats = '/[\x{2700}-\x{27BF}]/u';
		$clean_text     = preg_replace( $regex_dingbats, '', $clean_text );
		return $clean_text;
	}

}
