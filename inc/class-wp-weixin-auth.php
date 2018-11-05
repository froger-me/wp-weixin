<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Auth {

	const MAX_QR_LIFETIME = 600;

	protected $expire_length;
	protected $wechat;
	protected $qr_subscribe_src;
	protected $doing_wechat_auth = false;
	protected $auth_qr_data;
	protected $target_url;
	protected $target_blog_id;
	protected static $needs_auth;

	public function __construct( $wechat, $init_hooks = false ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$this->wechat = $wechat;

		if ( $init_hooks ) {
			// Logout when unsubscribed
			add_action( 'wp_weixin_responder', array( $this, 'force_logout' ), -99, 1 );

			if ( WP_Weixin_Settings::get_option( 'enable_auth' ) ) {
				// Manage WeChat authentication
				add_action( 'init', array( $this, 'manage_auth' ), PHP_INT_MIN + 10, 0 );
				// Parse the endpoint request
				add_action( 'parse_request', array( $this, 'parse_request' ), PHP_INT_MIN, 0 );
				// Add QR code generation ajax callback
				add_action( 'wp_ajax_nopriv_wp_weixin_get_auth_qr', array( $this, 'get_qr_src' ), 10, 0 );
				// Add QR code heartbeat ajax callback
				add_action( 'wp_ajax_nopriv_wp_weixin_auth_heartbeat_pulse', array( $this, 'heartbeat_pulse' ), 10, 0 );
				// Add link to WeChat qr authentication page
				add_action( 'login_footer', array( $this, 'login_form_link' ), 10, 1 );
				// Schedule WeChat auth qr cleanup
				add_action( 'init', array( $this, 'register_auth_qr_cleanup' ), 10, 0 );
				add_action( 'wp_weixin_auth_qr_cleanup', array( $this, 'auth_qr_cleanup' ), 10, 0 );
				// Remove WPML redirection script on auth page
				add_action( 'wpml_enqueue_browser_redirect_language', array( $this, 'disable_wpml_redirect' ), 99 );
				// Remove WooCommerce scripts on auth page
				add_action( 'wp_enqueue_scripts', array( $this, 'disable_woocommerce_scripts' ), 99 );

				// Detemine where WeChat authentication is needed
				add_filter( 'wp_weixin_auth_needed', array( $this, 'page_needs_wechat_auth' ), PHP_INT_MIN, 1 );
				// Get the QR code for browsers
				add_filter( 'wp_weixin_browser_page_qr_src', array( $this, 'get_browser_page_qr_src' ), -99, 1 );
			}

			// Add the API endpoints
			add_action( 'wp_weixin_endpoints', array( $this, 'add_endpoints' ), 0, 0 );
			// Add settings endpoints query vars
			add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0, 1 );
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

	public function disable_wpml_redirect( $redirect ) {
		$current_url = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$path        = wp_parse_url( $current_url, PHP_URL_PATH );

		if ( strpos( $path, 'wp-weixin/wechat-auth' ) !== false ) {
			$redirect = false;
		}

		return $redirect;
	}

	public function disable_woocommerce_scripts() {
		$current_url = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$path        = wp_parse_url( $current_url, PHP_URL_PATH );

		if ( function_exists( 'is_woocommerce' ) && strpos( $path, 'wp-weixin/wechat-auth' ) !== false ) {
			wp_dequeue_script( 'wc-cart-fragments' );
			wp_dequeue_script( 'woocommerce' );
			wp_dequeue_script( 'wc-add-to-cart' );
		}
	}

	public function add_endpoints() {
		add_rewrite_rule( '^wp-weixin/wechat-auth$', 'index.php?__wp_weixin_api=1&action=wechat-auth', 'top' );
		add_rewrite_rule( '^wp-weixin/wechat-auth-qr/hash/(.*)$', 'index.php?__wp_weixin_api=1&action=wechat-auth-qr&hash=$matches[1]', 'top' );
		add_rewrite_rule( '^wp-weixin/wechat-auth-validate/hash/(.*)$', 'index.php?__wp_weixin_api=1&action=wechat-auth-validate&hash=$matches[1]', 'top' );

		if ( is_multisite() ) {
			add_rewrite_rule( '^wp-weixin/ms-crossdomain/(.*)/?(.*)$', 'index.php?__wp_weixin_api=1&action=ms-crossdomain&hash=$matches[1]&$matches[2]', 'top' );
			add_rewrite_rule( '^wp-weixin/ms-nexus/(.*)/?(.*)$', 'index.php?__wp_weixin_api=1&action=ms-set-target&hash=$matches[1]&$matches[2]', 'top' );
		}
	}

	public function add_query_vars( $vars ) {
		$vars[] = 'code';
		$vars[] = 'state';

		return $vars;
	}

	public function parse_request() {
		global $wp;

		if ( ! $this->doing_wechat_auth && isset( $wp->query_vars['__wp_weixin_api'] ) ) {
			$action = $wp->query_vars['action'];

			if ( 'wechat-auth' === $action ) {
				add_action( 'template_redirect', array( $this, 'wechat_auth_page' ), 0, 0 );
			}

			if ( 'wechat-auth-qr' === $action ) {
				$hash    = isset( $wp->query_vars['hash'] ) ? $wp->query_vars['hash'] : false;
				$bundle  = explode( '|', base64_decode( $hash ) ); // @codingStandardsIgnoreLine
				$nonce   = array_pop( $bundle );
				$qr_id   = array_pop( $bundle );
				$blog_id = ( is_multisite() ) ? get_current_blog_id() : null;

				if ( ! wp_verify_nonce( $nonce, 'qr_code' ) ) {

					exit();
				}

				WP_Weixin_Settings::get_qrcode( get_home_url(
					$blog_id,
					'wp-weixin/wechat-auth-validate/hash/' . $qr_id
				) );
			}

			if ( 'wechat-auth-validate' === $action ) {
				$qr_id = isset( $wp->query_vars['hash'] ) ? $wp->query_vars['hash'] : false;

				$this->check_wechat_qr_auth( $qr_id );
			}

			if ( 'ms-set-target' === $action ) {
				$hash          = $wp->query_vars['hash'];
				$hash          = str_replace( '-', '=', $hash );
				$hash          = str_replace( '~', '/', $hash );
				$hash          = str_replace( '*', '+', $hash );
				$payload       = base64_decode( $hash );  // @codingStandardsIgnoreLine
				$payload_parts = explode( '|', $payload );

				$this->target_url     = reset( $payload_parts );
				$this->target_blog_id = absint( end( $payload_parts ) );
			}

			if ( 'ms-crossdomain' === $action ) {
				$hash         = $wp->query_vars['hash'];
				$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 0 );

				if ( ! $auth_blog_id ) {
					$auth_blog_url = network_site_url( 'wp-weixin/ms-nexus/' );
				} else {
					$auth_blog_url = get_home_url( $auth_blog_id, 'wp-weixin/ms-nexus/' );
				}

				$callback = $auth_blog_url . $hash;
				$scope    = 'snsapi_userinfo';
				$state    = wp_create_nonce( __FILE__ );
				$url      = $this->wechat->getOAuthRedirect( $callback, $state, $scope );

				header( 'Location: ' . $url );

				exit();
			}
		}
	}

	public function register_auth_qr_cleanup() {
		$hook = 'wp_weixin_auth_qr_cleanup';

		if ( ! wp_next_scheduled( $hook ) ) {
			$frequency = apply_filters( 'wp_weixin_auth_qr_cleanup_frequency', 'hourly' );
			$timestamp = current_time( 'timestamp' );

			wp_schedule_event( $timestamp, $frequency, $hook );
		}
	}

	public function page_needs_wechat_auth( $needs_auth ) {
		$current_url = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$path        = wp_parse_url( $current_url, PHP_URL_PATH );
		$is_admin    = current_user_can( 'administrator' );

		$needs_auth = $needs_auth && strpos( $path, 'wp-login' ) === false;
		$needs_auth = $needs_auth && strpos( $path, 'wp-admin' ) === false;
		$needs_auth = $needs_auth && strpos( $path, 'wc-api' ) === false;
		$needs_auth = $needs_auth && strpos( $path, 'wp-weixin/wechat-auth' ) === false;
		$needs_auth = $needs_auth && strpos( $path, 'weixin-responder' ) === false;
		$needs_auth = $needs_auth && strpos( $path, 'admin-ajax.php' ) === false;
		$needs_auth = $needs_auth && ! $is_admin;

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

				if ( wp_verify_nonce( $state, __FILE__ ) ) {
					$this->login( $oauth_access_token_info );
					$this->maybe_force_follow( WP_Weixin_Settings::get_option( 'force_follower' ) );
				} else {
					$title    = '<h2>' . __( 'System error.', 'wp-weixin' ) . '</h2>';
					$message  = '<p>' . __( 'Invalid CSRF token. Please refresh the page. ', 'wp-weixin' );
					$message .= __( 'If the problem persists, please contact an administrator.', 'wp-weixin' ) . '</p>';

					wp_die( $title . $message ); // @codingStandardsIgnoreLine
				}
			} else {
				$this->pre_oauth();
			}
		} else {
			$user = wp_get_current_user();

			$this->auth_refresh( $user->ID );

			if ( is_multisite() ) {
				$blog_id = get_current_blog_id();

				if (
					! is_user_member_of_blog( $user->ID, $blog_id ) &&
					apply_filters( 'wp_weixin_ms_auto_add_to_blog', true, $blog_id, $user->ID ) &&
					get_user_meta( $user->ID, 'wp_weixin_openid', true )
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
			$length = current_time( 'timestamp' ) + (int) $this->expire_length;
		}

		return $length;
	}

	public function maybe_force_follow( $force_follow = true ) {

		if ( ! $force_follow || ! WP_Weixin::is_wechat_mobile() ) {

			return;
		}

		$user_id               = get_current_user_id();
		$recently_unsubscribed = get_transient( 'wp_weixin_recent_unsub_' . $user_id );
		$follower_cookie       = $recently_unsubscribed ? false : filter_input( INPUT_COOKIE, 'wx_follower' );

		if ( ! $follower_cookie ) {
			$is_follower = $this->wechat->follower( get_user_meta( $user_id, 'wp_weixin_openid', true ) );

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
			$user = WP_Weixin::get_user_by_openid( $request_data['fromusername'] );

			if ( $user ) {
				$sessions = WP_Session_Tokens::get_instance( $user->ID );

				$sessions->destroy_all();
				set_transient( 'wp_weixin_recent_unsub_' . $user->ID, true, 3600 );
			}
		}
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

		$blog_id     = ( is_multisite() ) ? get_current_blog_id() : null;
		$current_url = get_home_url( $blog_id, $wp->request );
		$hash        = base64_encode( $current_url . '|' . wp_create_nonce( 'qr_code' ) ); // @codingStandardsIgnoreLine
		$page_qr_src = get_home_url( $blog_id, 'wp-weixin/get-qrcode/hash/' . $hash );

		return $page_qr_src;
	}

	public function auth_qr_cleanup() {
		global $wpdb;

		$transient_prefix = $wpdb->esc_like( '_transient_timeout_wp_weixin_auth_qr_' ) . '%';
		$sql              = "
			SELECT REPLACE(option_name, '_transient_timeout_', '') AS transient_name
			FROM {$wpdb->options}
			WHERE option_name LIKE %s
			AND option_value < %s;
		";

		$query         = $wpdb->prepare( $sql, $transient_prefix, current_time( 'timestamp', true ) );  // @codingStandardsIgnoreLine
		$transients    = $wpdb->get_col( $query ); // @codingStandardsIgnoreLine
		$options_names = array();

		if ( ! empty( $transients ) ) {

			foreach ( $transients as $transient ) {
				$options_names[] = '_transient_' . $transient;
				$options_names[] = '_transient_timeout_' . $transient;
			}
		}

		if ( $options_names ) {
			$options_names = array_map(
				'esc_sql',
				$options_names
			);
			$options_names = "'" . implode( "','", $options_names ) . "'";
			$query         = "DELETE FROM {$wpdb->options} WHERE option_name IN ({$options_names})";

			$wpdb->query( $query );  // @codingStandardsIgnoreLine
		}
	}

	public function login_form_link() {
		ob_start();

		$template = locate_template( array( 'wp-weixin-auth-form-link.php' ) );

		if ( empty( $template ) ) {
			$template = WP_WEIXIN_PLUGIN_PATH . 'inc/templates/wp-weixin-auth-form-link.php';

			require_once $template;
		}

		echo ob_get_clean(); // @codingStandardsIgnoreLine
	}

	public function heartbeat_pulse() {
		$hash   = filter_input( INPUT_POST, 'hash', FILTER_SANITIZE_STRING );
		$bundle = explode( '|', base64_decode( $hash ) ); // @codingStandardsIgnoreLine
		$nonce  = array_pop( $bundle );
		$qr_id  = array_pop( $bundle );

		if ( wp_verify_nonce( $nonce, 'qr_code' ) ) {
			$result = $this->do_wechat_qr_auth( $qr_id );

			if ( is_array( $result ) ) {
				wp_send_json_success( $result );
			} else {
				wp_send_json_success( false );
			}
		} else {
			$error = new WP_Error( 'WP_Weixin_Auth::heartbeat', __( 'Invalid parameters', 'wp-weixin' ) );

			wp_send_json_error( $error );
		}

		wp_die();
	}

	public function wechat_auth_result() {
		$auth_qr_data = $this->auth_qr_data;
		ob_start();

		$template = locate_template( array( 'wp-weixin-mobile-auth-check.php' ) );

		if ( empty( $template ) ) {
			$template = WP_WEIXIN_PLUGIN_PATH . 'inc/templates/wp-weixin-mobile-auth-check.php';

			require_once $template;
		}

		echo ob_get_clean(); // @codingStandardsIgnoreLine

		exit();
	}

	public function get_qr_src() {
		$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );

		if ( wp_verify_nonce( $nonce, 'qr_code' ) ) {
			$qr_id = bin2hex( openssl_random_pseudo_bytes( 10 ) );
			$hash  = base64_encode( $qr_id . '|' . wp_create_nonce( 'qr_code' ) ); // @codingStandardsIgnoreLine

			set_transient(
				'wp_weixin_auth_qr_' . $qr_id,
				current_time( 'timestamp' ) + apply_filters( 'wp_weixin_auth_qr_lifetime', self::MAX_QR_LIFETIME ),
				apply_filters( 'wp_weixin_auth_qr_lifetime', self::MAX_QR_LIFETIME )
			);

			$blog_id = ( is_multisite() ) ? get_current_blog_id() : null;
			$url     = get_home_url( $blog_id, 'wp-weixin/wechat-auth-qr/hash/' ) . $hash;

			wp_send_json_success(
				array(
					'qrSrc' => $url,
					'hash'  => $hash,
				)
			);
		} else {
			$error = new WP_Error( 'WP_Weixin_Auth::get_qr', __( 'Invalid parameters', 'wp-weixin' ) );

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

		$debug   = apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) );
		$js_ext  = ( $debug ) ? '.js' : '.min.js';
		$version = filemtime( WP_WEIXIN_PLUGIN_PATH . 'js/auth-heartbeat' . $js_ext );
		$params  = array(
			'heartbeatFreq' => apply_filters( 'wp_weixin_auth_heartbeat_frequency', 1000 ),
			'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'debug'         => $debug,
		);
		wp_enqueue_script( 'wp-weixin-auth-heartbeat', WP_WEIXIN_PLUGIN_URL . 'js/auth-heartbeat' . $js_ext, array( 'jquery' ), $version, true );
		wp_localize_script( 'wp-weixin-auth-heartbeat', 'WP_WeixinAuthHeartBeat', $params );
		add_action( 'template_include', array( $this, 'wechat_auth_template' ), 99, 0 );
	}

	public function wechat_auth_template() {
		$template = locate_template( array( 'wp-weixin-auth-page.php' ) );

		if ( empty( $template ) ) {
			$template = WP_WEIXIN_PLUGIN_PATH . 'inc/templates/wp-weixin-auth-page.php';
		}

		return $template;
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function pre_oauth() {
		$scope = 'snsapi_userinfo';
		$state = wp_create_nonce( __FILE__ );

		if ( is_multisite() ) {
			$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 0 );

			if ( ! $auth_blog_id ) {
				$auth_blog_url = network_site_url( 'wp-weixin/ms-crossdomain/' );
			} else {
				$auth_blog_url = get_home_url( $auth_blog_id, 'wp-weixin/ms-crossdomain/' );
			}

			$target_blog_id = get_current_blog_id();
			$destination    = base64_encode( ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '|' . $target_blog_id );  // @codingStandardsIgnoreLine
			$destination    = str_replace( '=', '-', $destination );
			$destination    = str_replace( '/', '~', $destination );
			$destination    = str_replace( '+', '*', $destination );
			$url            = $auth_blog_url . $destination;
		} else {
			$callback = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$url      = $this->wechat->getOAuthRedirect( $callback, $state, $scope );
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

			wp_die( $title . $message ); // @codingStandardsIgnoreLine
		}

		$access_token        = $oauth_access_token_info['access_token'];
		$refresh_token       = $oauth_access_token_info['refresh_token'];
		$openid              = $oauth_access_token_info['openid'];
		$this->expire_length = $oauth_access_token_info['expires_in'];
		$redirect            = false;
		$blog_id             = null;
		$do_login            = true;

		if ( WP_Weixin_Settings::get_option( 'enable_auth' ) ) {

			if ( $this->target_blog_id ) {
				switch_to_blog( $this->target_blog_id );
			}

			$user = WP_Weixin::get_user_by_openid( $openid );

			if ( ! $user ) {
				$user = $this->register_user( $openid, $access_token, $refresh_token );
			} else {
				$user = $this->update_user_info( $access_token, $openid, $user );
			}

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
				'wx_openId',
				$openid,
				current_time( 'timestamp' ) + (int) $this->expire_length,
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
			// fail silently
			error_log( 'System error, access to WeChat user information for ' . $openid . ' failed.' ); // @codingStandardsIgnoreLine
		} else {

			$avatar = $user_info['headimgurl'];

			if ( ! $avatar ) {
				$avatar = '';
			}

			if ( substr( $avatar, -2 ) === '/0' ) {
				$avatar = substr( $avatar, 0, -2 ) . '/132';
			}

			$user_data = array(
				'ID'           => $user->ID,
				'display_name' => WP_Weixin::format_emoji( $user_info['nickname'] ),
				'user_pass'    => $user_info['openid'],
				'nickname'     => WP_Weixin::format_emoji( $user_info['nickname'] ),
			);

			$user_id = wp_update_user( $user_data );

			if ( is_wp_error( $user_id ) ) {
				// fail silently
				error_log( 'System error, failed to update user' . $openid . '. Reason:' . $user_id->get_error_message() ); // @codingStandardsIgnoreLine
			}

			update_user_meta( $user_id, 'wp_weixin_rawdata', wp_json_encode( $user_info ) );
			update_user_meta( $user_id, 'wx_openid', $user_info['openid'] );
			update_user_meta( $user_id, 'wx_unionid', $user_info['unionid'] );

			$result = $user;
		}

		return $result;
	}

	protected function register_user( $openid, $access_token, $refresh_token ) {
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

			wp_die( $title . $message ); // @codingStandardsIgnoreLine
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

			wp_die( $title . $message ); // @codingStandardsIgnoreLine
		}

		update_user_meta( $user_id, 'wp_weixin_rawdata', wp_json_encode( $user_info ) );
		update_user_meta( $user_id, 'wx_openid', $user_info['openid'] );
		update_user_meta( $user_id, 'wx_unionid', $user_info['unionid'] );

		return get_user_by( 'ID', $user_id );
	}

	protected function auth_refresh( $user_id ) {
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

			update_user_meta( $user_id, 'wp_weixin_rawdata', wp_json_encode( $user_info ) );
			setcookie( 'wx_openId', $user_info['openid'], $this->expire_length );
			wp_set_auth_cookie( $user_id );
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
		$recorded_qr_value = get_transient( 'wp_weixin_auth_qr_' . $qr_id, false );
		$error             = false;
		$user_id           = 0;
		$redirect          = false;

		if (
			! $recorded_qr_value ||
			is_array( $recorded_qr_value )
		) {
			$error = __( 'The QR code is invalid', 'wp-weixin' );
		}

		if (
			is_numeric( $recorded_qr_value ) &&
			current_time( 'timestamp' ) > $recorded_qr_value
		) {
			$error = __( 'The QR code is expired', 'wp-weixin' );
		}

		if ( ! is_user_logged_in() ) {
			$error = __( 'Authentication error - check mobile WeChat authentication is enabled.', 'wp-weixin' );
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
			'wp_weixin_auth_qr_' . $qr_id,
			$this->auth_qr_data,
			apply_filters( 'wp_weixin_auth_qr_lifetime', self::MAX_QR_LIFETIME )
		);

		add_action( 'template_redirect', array( $this, 'wechat_auth_result' ), 0, 0 );
	}

	protected function do_wechat_qr_auth( $qr_id ) {
		$recorded_qr_value = get_transient( 'wp_weixin_auth_qr_' . $qr_id, false );
		$return            = array(
			'auth'     => false,
			'error'    => array( __( 'Unkown Error.', 'wp-weixin' ) ),
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
			$return['error'] = array( __( 'The QR code is expired.', 'wp-weixin' ) );
		}

		if ( false !== $return ) {
			delete_transient( 'wp_weixin_auth_qr_' . $qr_id );
		}

		return $return;
	}
}
