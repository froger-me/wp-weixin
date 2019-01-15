<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Bind {

	protected $wechat;
	protected $wp_weixin_auth;
	protected $bind_user_id;
	protected $bind_qr_data;

	public function __construct( $wechat, $wp_weixin_auth, $init_hooks = false ) {
		$this->wechat         = $wechat;
		$this->wp_weixin_auth = $wp_weixin_auth;

		if ( $init_hooks ) {
			// Parse the endpoint request
			add_action( 'parse_request', array( $this, 'parse_request' ), PHP_INT_MIN + 10, 0 );
			// Add the API endpoints
			add_action( 'wp_weixin_endpoints', array( $this, 'add_endpoints' ), 0, 0 );
			// Add QR code generation ajax callback
			add_action( 'wp_ajax_wp_weixin_get_bind_qr', array( $this, 'get_qr_src' ), 10, 0 );
			// Add QR code heartbeat ajax callback
			add_action( 'wp_ajax_wp_weixin_bind_heartbeat_pulse', array( $this, 'heartbeat_pulse' ), 10, 0 );
			// Add unbind ajax callback
			add_action( 'wp_ajax_wp_weixin_unbind', array( $this, 'process_unbind' ), 10, 0 );

			// Add the pay query vars
			add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0, 1 );

			if ( WP_Weixin_Settings::get_option( 'show_bind_link' ) && ! WP_Weixin::is_wechat_mobile() ) {
				// Add bind link on Ultimate Membership plugin edit account page
				add_action( 'um_after_account_general_button', array( $this, 'third_party_bind_link' ), 10, 0 );
				// Add bind link on WooCommerce plugin edit account page
				add_action( 'woocommerce_edit_account_form_end', array( $this, 'third_party_bind_link' ), 10, 0 );
				// Add WeChat public information to user profile
				add_action( 'show_user_profile', array( $this, 'bind_link' ), 10, 1 );
				add_action( 'edit_user_profile', array( $this, 'bind_link' ), 10, 1 );

			}
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public function add_query_vars( $vars ) {
		$vars[] = 'user_id';

		return $vars;
	}

	public function add_endpoints() {
		add_rewrite_rule(
			'^wp-weixin/(wechat-bind-qr|wechat-bind)/hash/([^\/\?\#]*)(\/)?(\?(.*))?$',
			'index.php?__wp_weixin_api=1&action=$matches[1]&hash=$matches[2]',
			'top'
		);
		add_rewrite_rule(
			'^wp-weixin/wechat-bind-edit/([0-9]*)(\/)?(\?(.*))?$',
			'index.php?__wp_weixin_api=1&action=wechat-bind-edit&user_id=$matches[1]',
			'top'
		);
	}

	public function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['__wp_weixin_api'] ) ) {
			$action = $wp->query_vars['action'];

			if ( 'wechat-bind' === $action && WP_Weixin::is_wechat_mobile() ) {
				$this->wp_weixin_auth->prevent_force_follow = true;

				$this->wp_weixin_auth->oauth();

				$qr_id = isset( $wp->query_vars['hash'] ) ? $wp->query_vars['hash'] : false;

				$this->check_wechat_qr_bind( $qr_id );
			}

			if ( 'wechat-bind-edit' === $action && ! WP_Weixin::is_wechat_mobile() ) {
				$this->bind_user_id = isset( $wp->query_vars['user_id'] ) ? absint( $wp->query_vars['user_id'] ) : 0;

				if ( $this->bind_user_id && get_current_user_id() !== $this->bind_user_id ) {

					wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 ); // WPCS: XSS ok
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

				WP_Weixin::add_scan_heartbeat_scripts( 'bind' );
				add_action( 'template_redirect', array( $this, 'edit_bind_page' ), 0, 0 );
			}

			if ( 'wechat-bind-qr' === $action && ! WP_Weixin::is_wechat_mobile() ) {
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
						'wp-weixin/wechat-bind/hash/' . $qr_id
					)
				);
			}
		}
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
			$url     = get_home_url( $blog_id, 'wp-weixin/wechat-bind-qr/hash/' . $hash );

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

	public function heartbeat_pulse() {
		$hash   = filter_input( INPUT_POST, 'hash', FILTER_SANITIZE_STRING );
		$bundle = explode( '|', base64_decode( $hash ) ); // @codingStandardsIgnoreLine
		$nonce  = array_pop( $bundle );
		$qr_id  = array_pop( $bundle );

		if ( wp_verify_nonce( $nonce, 'wp_weixin_qr_code' ) ) {
			$result = $this->do_wechat_qr_bind( $qr_id );

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

	public function edit_bind_page() {
		$auth_blog_id  = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
		$wechat_openid = get_user_meta( $this->bind_user_id, 'wx_openid-' . $auth_blog_id, true );

		set_query_var( 'user_id', $this->bind_user_id );
		set_query_var( 'wechat_info', wp_weixin_get_user_wechat_info( $this->bind_user_id ) );
		set_query_var( 'openid', $wechat_openid );
		WP_Weixin::locate_template( 'wp-weixin-bind-page.php', true );

		exit();
	}

	// public function register_disabled_slugs( $slugs ) {
	// 	$slugs[] = 'wechat-bind';

	// 	return $slugs;
	// }

	public function wechat_bind_result() {
		set_query_var( 'bind_qr_data', $this->bind_qr_data );
		WP_Weixin::locate_template( 'wp-weixin-mobile-bind-check.php', true );

		exit();
	}

	public function third_party_bind_link() {
		$user          = wp_get_current_user();
		$auth_blog_id  = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
		$wechat_openid = get_user_meta( $user->ID, 'wx_openid-' . $auth_blog_id, true );
		$class         = 'wp-wx-' . str_replace( '_', '-', current_filter() );

		$this->bind_link( $user, $wechat_openid, $class );
	}

	public function bind_link( $user, $wechat_openid = '', $class = '', $target = '_blank', $output = true ) {

		if ( ! $user || ! $user->ID ) {
			$user = wp_get_current_user();
		}

		if ( get_current_user_id() !== $user->ID ) {

			return;
		}

		if ( ! $wechat_openid ) {
			$auth_blog_id  = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
			$wechat_openid = get_user_meta( $user->ID, 'wx_openid-' . $auth_blog_id, true );
		}

		if ( $wechat_openid ) {
			$link_text = __( 'Unbind WeChat Account', 'wp-weixin' );
		} else {
			$link_text = __( 'Bind a WeChat Account', 'wp-weixin' );
		}

		if ( 'show_user_profile' === current_filter() || 'edit_user_profile' === current_filter() ) {
			$class = 'wp-wx-wp-account-edit';
		}

		set_query_var( 'openid', $wechat_openid );
		set_query_var( 'user', $user );
		set_query_var( 'class', $class );
		set_query_var( 'target', $target );
		set_query_var( 'link_text', $link_text );

		ob_start();
		WP_Weixin::locate_template( 'wp-weixin-bind-form-link.php', true );

		$html = ob_get_clean();

		if ( $output ) {
			echo $html; // WPCS: XSS ok
		}

		return ( $output ) ? $html : false;
	}

	public function process_unbind( $ajax = true, $user_id = 0, $openid = '' ) {
		$nonce   = ( $ajax ) ? filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING ) : false;
		$success = false;

		if ( ! $ajax || wp_verify_nonce( $nonce, 'wp_weixin_unbind' ) ) {
			$user_id = ( $ajax ) ? absint( filter_input( INPUT_POST, 'userid', FILTER_SANITIZE_STRING ) ) : $user_id;
			$openid  = ( $ajax ) ? filter_input( INPUT_POST, 'openid', FILTER_SANITIZE_STRING ) : $openid;

			wp_cache_delete( $user_id, 'user_meta' );

			$auth_blog_id  = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
			$wechat_openid = get_user_meta( $user_id, 'wx_openid-' . $auth_blog_id, true );

			if ( $openid === $wechat_openid ) {
				$success = $this->unbind( $user_id, $openid, $ajax );
			}
		}

		if ( $ajax ) {

			if ( $success ) {
				wp_send_json_success();
			}

			wp_send_json_error( new WP_Error( __METHOD__, __( 'Invalid parameters', 'wp-weixin' ) ) );
		}

		return $success;
	}

	public function process_bind( $user_id, $openid ) {
		$return      = false;
		$user        = get_user_by( 'ID', absint( $user_id ) );
		$wechat_user = ( $user ) ? wp_weixin_get_user_by_openid( $openid ) : false;

		if ( $wechat_user && $user ) {
			$openid_login_suffix = ( is_multisite() ) ? strtolower( $openid ) : $openid;

			if ( 'wx-' . $openid_login_suffix === $wechat_user->user_login ) {
				wp_cache_delete( $user->ID, 'user_meta' );
				wp_cache_delete( $wechat_user->ID, 'user_meta' );

				$return = $this->bind( $openid, $user->ID, false );
			}
		}

		return $return;
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function do_wechat_qr_bind( $qr_id ) {
		$recorded_qr_value = get_transient( 'wp_weixin_qr_' . $qr_id, false );
		$return            = array(
			'bind'     => false,
			'error'    => array( __( 'Unknown Error', 'wp-weixin' ) ),
			'redirect' => false,
		);

		if ( is_numeric( $recorded_qr_value ) ) {
			$return = false;
		} elseif (
			is_array( $recorded_qr_value ) &&
			isset( $recorded_qr_value['bind'], $recorded_qr_value['openid'] ) &&
			$recorded_qr_value['bind'] &&
			$recorded_qr_value['openid']
		) {

			if ( $this->bind( $recorded_qr_value['openid'] ) ) {
				$blog_id                       = ( is_multisite() ) ? get_current_blog_id() : null;
				$user_id                       = get_current_user_id();
				$recorded_qr_value['redirect'] = get_home_url( $blog_id, '/wp-weixin/wechat-bind-edit/' . $user_id );
				$return                        = $recorded_qr_value;
			} else {
				$return['error'] = array( __( 'Unexpected binding error', 'wp-weixin' ) );
			}
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

	protected function check_wechat_qr_bind( $qr_id ) {
		$recorded_qr_value = get_transient( 'wp_weixin_qr_' . $qr_id, false );
		$wechat_user       = wp_get_current_user();

		wp_cache_delete( $wechat_user->ID, 'user_meta' );

		$auth_blog_id        = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
		$wechat_openid       = get_user_meta( $wechat_user->ID, 'wx_openid-' . $auth_blog_id, true );
		$openid_login_suffix = ( is_multisite() ) ? strtolower( $wechat_openid ) : $wechat_openid;
		$error               = false;
		$wechat_user_id      = 0;
		$openid              = false;
		$redirect            = false;

		if (
			! $recorded_qr_value ||
			is_array( $recorded_qr_value )
		) {
			$error = __( 'The QR code is invalid', 'wp-weixin' );
		}

		if (
			is_numeric( $recorded_qr_value ) &&
			time() > $recorded_qr_value
		) {
			$error = __( 'The QR code is expired', 'wp-weixin' );
		}

		if ( ! is_user_logged_in() ) {
			$error = __( 'Authentication error - check mobile WeChat authentication is enabled', 'wp-weixin' );
		}

		if ( ! $error && 'wx-' . $openid_login_suffix !== $wechat_user->user_login ) {
			$error = __( 'The WeChat account has already been used for account binding', 'wp-weixin' );
		}

		if ( $error ) {
			$bind = false;
		} else {
			$bind           = true;
			$wechat_user_id = $wechat_user->ID;
			$openid         = $wechat_openid;
		}

		$this->bind_qr_data = array(
			'bind'     => $bind,
			'openid'   => $openid,
			'user_id'  => $wechat_user_id,
			'redirect' => $redirect,
		);

		if ( $error ) {
			$this->bind_qr_data['error'] = array( $error );
		} else {
			$this->bind_qr_data['error'] = false;
		}

		set_transient(
			'wp_weixin_qr_' . $qr_id,
			$this->bind_qr_data,
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

		add_action( 'template_redirect', array( $this, 'wechat_bind_result' ), 0, 0 );
	}

	protected function bind( $openid, $current_user_id = false, $reauth = true ) {
		$return               = false;
		$wechat_user          = wp_weixin_get_user_by_openid( $openid );
		$wechat_user_id       = ( $wechat_user ) ? $wechat_user->ID : false;
		$current_user_id      = ( $current_user_id ) ? $current_user_id : get_current_user_id();
		$current_user         = get_user_by( 'ID', $current_user_id );
		$wechat_user_blog_ids = array_keys( get_blogs_of_user( $wechat_user_id ) );
		$auth_blog_id         = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
		$openid_login_suffix  = ( is_multisite() ) ? strtolower( $openid ) : $openid;

		do_action(
			'wp_weixin_before_bind_account',
			$current_user_id,
			$wechat_user_id,
			$wechat_user_blog_ids,
			get_current_blog_id()
		);

		if ( $wechat_user_id === $current_user_id ) {
			$this->update_user_email( $current_user, $openid, true );
			$this->update_user_login( $current_user->user_login, 'wx-bound-' . $openid, $current_user_id, $reauth );

			$return = true;
		} elseif ( $wechat_user_id && $current_user_id ) {

			if ( 'wx-' . $openid_login_suffix === $wechat_user->user_login ) {
				$return       = true;
				$current_user = $this->merge_accounts(
					$wechat_user_id,
					$current_user_id,
					$wechat_user_blog_ids
				);
			}
		}

		if (
			$current_user_id &&
			$return &&
			(
				'wx-unbound-' . $openid_login_suffix === $current_user->user_login ||
				'wx-' . $openid_login_suffix === $current_user->user_login
			)
		) {
			$this->update_user_email( $current_user, $openid, true );
			$this->update_user_login(
				$current_user->user_login,
				'wx-bound-' . $openid_login_suffix,
				$current_user_id,
				$reauth
			);
		}

		do_action(
			'wp_weixin_after_bind_account',
			$return,
			$current_user_id,
			$wechat_user_id,
			$wechat_user_blog_ids,
			get_current_blog_id()
		);

		return $return;
	}

	protected function unbind( $user_id, $openid, $reauth = true ) {
		$openid_login_suffix = ( is_multisite() ) ? strtolower( $openid ) : $openid;
		$user                = get_user_by( 'ID', $user_id );
		$return              = true;

		clean_user_cache( $user );
		do_action( 'wp_weixin_before_unbind_account', $user_id, $openid );

		$auth_blog_id     = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
		$user_blog_ids    = array_keys( get_blogs_of_user( $user_id ) );
		$delete_all_metas = true;

		delete_user_meta( $user_id, 'wx_openid-' . $auth_blog_id );
		setcookie( 'wx_openId-' . $auth_blog_id, '', 0, '/', COOKIE_DOMAIN );

		foreach ( $user_blog_ids as $blog_id ) {

			if ( metadata_exists( 'user', $user_id, 'wx_openid-' . $blog_id ) ) {
				$delete_all_metas = false;

				break;
			}
		}

		if ( $delete_all_metas ) {
			delete_user_meta( $user_id, 'wx_unionid' );
			delete_user_meta( $user_id, 'wp_weixin_rawdata' );
		}

		if (
			$user && 'wx-' . $openid_login_suffix === $user->user_login ||
			'wx-bound-' . $openid_login_suffix === $user->user_login
		) {
			$user_login = 'wx-unbound-' . $openid_login_suffix;

			if ( username_exists( 'wx-unbound-' . $openid_login_suffix ) ) {
				$user_src = get_user_by( 'login', 'wx-unbound-' . $openid_login_suffix );
				$blog_ids = array_keys( get_blogs_of_user( $user_src->ID ) );
				$user     = $this->merge_accounts(
					$user_src->ID,
					$user->ID,
					$blog_ids
				);
				$return   = ( false !== $user );
			} else {
				$this->update_user_email( $user, $openid, false );

				$return = $this->update_user_login(
					$user->user_login,
					'wx-unbound-' . $openid_login_suffix,
					$user_id,
					$reauth
				);
			}
		} else {
			$this->update_user_email( $user, $openid, false );
		}

		clean_user_cache( $user );
		do_action( 'wp_weixin_after_unbind_account', $return, $user_id, $openid );

		return $return;
	}

	protected function merge_accounts( $user_id_source, $user_id_dest, $blog_ids ) {
		$meta = apply_filters(
			'wp_weixin_bind_merge_accounts_usermeta_keys',
			array(
				'wp_weixin_rawdata',
				'wx_openid-' . apply_filters( 'wp_weixin_ms_auth_blog_id', 1 ),
				'wx_unionid',
			)
		);

		foreach ( $meta as $key ) {
			update_user_meta(
				$user_id_dest,
				$key,
				get_user_meta( $user_id_source, $key, true )
			);
		}

		if ( is_multisite() ) {

			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );

				if ( ! is_user_member_of_blog( $user_id_dest, $blog_id ) ) {
					add_user_to_blog( $blog_id, $user_id_dest, '' );
					$current_user->for_site( $blog_id );

					$wechat_user_meta = get_userdata( $user_id_source );

					foreach ( $wechat_user_meta->roles as $role ) {
						$current_user->add_role( $role );
					}
				}

				remove_user_from_blog( $user_id_source, $blog_id, $user_id_dest );
				restore_current_blog();
			}

			wpmu_delete_user( $user_id_source );
		} else {
			wp_delete_user( $user_id_source, $user_id_dest );
		}

		return get_user_by( 'ID', $user_id_dest );
	}

	protected function update_user_login( $old, $new, $user_id, $reauth ) {
		global $wpdb;

		$result = $wpdb->update(
			$wpdb->users,
			array( 'user_login' => $new ),
			array( 'ID' => $user_id )
		);

		if ( $result ) {
			wp_cache_delete( $user_id, 'users' );
			wp_cache_delete( $old, 'userlogins' );

			$sessions = WP_Session_Tokens::get_instance( $user_id );

			$sessions->destroy_all();

			if ( $reauth ) {
				wp_set_current_user( $user_id, $new );
				wp_set_auth_cookie( $user_id );
			}
		}

		return (bool) $result;
	}

	protected function update_user_email( $user, $openid, $is_binding ) {
		$new_email = false;

		if ( ! $is_binding && 0 === strpos( $user->user_email, $openid ) ) {
			$new_email = str_replace( $openid, 'unbound-' . $openid, $user->user_email );
		} elseif ( ! $is_binding && 0 === strpos( $user->user_email, 'bound-' . $openid ) ) {
			$new_email = str_replace( 'bound-' . $openid, 'unbound-' . $openid, $user->user_email );
		} elseif ( $is_binding && 0 === strpos( $user->user_email, 'unbound-' . $openid ) ) {
			$new_email = str_replace( 'unbound-' . $openid, 'bound-' . $openid, $user->user_email );
		}

		if ( $new_email ) {
			global $wpdb;

			$wpdb->update(
				$wpdb->users,
				array( 'user_email' => $new_email ),
				array( 'ID' => $user->ID )
			);

			wp_cache_delete( $user->ID, 'users' );
			wp_cache_delete( $user->user_email, 'useremail' );
		}
	}

}
