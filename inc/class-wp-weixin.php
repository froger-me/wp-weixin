<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin {

	const MAX_QR_LIFETIME = 600;

	public static $scripts = array();
	public static $styles  = array();

	protected $wechat;
	protected $meta_keys;

	public function __construct( $wechat, $init_hooks = false ) {
		$this->wechat = $wechat;

		if ( $init_hooks ) {
			$this->meta_keys = array(
				'wp_weixin_openid',
				'wp_weixin_unionid',
				'wp_weixin_headimgurl',
				'wp_weixin_subscribe',
				'wp_weixin_sex',
				'wp_weixin_language',
				'wp_weixin_city',
				'wp_weixin_province',
				'wp_weixin_country',
				'wp_weixin_subscribe_time',
			);

			// Add translation
			add_action( 'init', array( $this, 'load_textdomain' ), 0, 0 );
			// Add main scripts & styles
			add_action( 'wp_enqueue_scripts', array( $this, 'add_frontend_scripts' ), 5, 0 );
			// Add admin scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ), 99, 1 );
			// Add sort by WeChat name logic
			add_action( 'pre_user_query', array( $this, 'alter_user_sort_query' ), 10, 1 );
			// Flush rewrite rules if necessary - do it late
			add_action( 'init', array( $this, 'maybe_flush' ), PHP_INT_MIN + 5, 0 );
			// Schedule WeChat auth qr cleanup
			add_action( 'init', array( $this, 'register_qr_cleanup' ), 10, 0 );
			add_action( 'wp_weixin_qr_cleanup', array( $this, 'qr_cleanup' ), 10, 0 );

			// Filter WeChat get meta - add better getters for raw rata
			add_filter( 'get_user_metadata', array( $this, 'filter_wechat_get_user_meta' ), 1, 4 );
			// Filter WeChat update meta - add better setters for raw data
			add_filter( 'update_user_metadata', array( $this, 'filter_wechat_update_user_meta' ), 1, 5 );
			// Add main query vars
			add_filter( 'query_vars', array( $this, 'add_query_vars' ), PHP_INT_MIN + 5, 1 );

			if ( WP_Weixin_Settings::get_option( 'alter_userscreen' ) ) {
				// Filter avatar - use the WeChat headimg if exists
				add_filter( 'get_avatar', array( $this, 'avatar' ), PHP_INT_MAX - 10, 5 );
				// Filter avatar description
				add_filter( 'user_profile_picture_description', array( $this, 'avatar_description' ), 10, 2 );
				// Add WeChat name column
				add_filter( 'manage_users_columns', array( $this, 'alter_user_table_columns' ), 10, 1 );
				// Add content of the WeChat name column
				add_filter( 'manage_users_custom_column', array( $this, 'alter_user_table_rows' ), 10, 3 );
				// Add sort by WeChat name on user screen
				add_filter( 'manage_users_sortable_columns', array( $this, 'alter_user_sortable_columns' ), 10, 1 );
			}

			if ( WP_Weixin_Settings::get_option( 'show_public_info' ) ) {
				// Show WeChat public info on Ultimate Membership plugin edit account page
				add_action( 'um_after_account_general_button', array( $this, 'user_profile_wechat_info' ), 10, 1 );
				// Show WeChat public info on WooCommerce plugin edit account page
				add_action( 'woocommerce_edit_account_form_end', array( $this, 'user_profile_wechat_info' ), 10, 1 );
				// Add WeChat public information to user profile
				add_action( 'show_user_profile', array( $this, 'user_profile_wechat_info' ), 10, 1 );
				add_action( 'edit_user_profile', array( $this, 'user_profile_wechat_info' ), 10, 1 );
			}

			if ( WP_Weixin_settings::get_option( 'ecommerce' ) ) {
				// Add default payment notification handler foe WeChat Pay integration
				add_action( 'wp_weixin_handle_payment_notification', array( $this, 'handle_pay_notify' ), PHP_INT_MAX, 0 );
			}
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public static function activate() {
		set_transient( 'wp_weixin_flush', 1, 60 );
		wp_cache_flush();

		if ( ! get_option( 'wp_weixin_plugin_version' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			$plugin_data = get_plugin_data( WP_WEIXIN_PLUGIN_FILE );
			$version     = $plugin_data['Version'];

			update_option( 'wp_weixin_plugin_version', $version );
		}
	}

	public static function deactivate() {
		global $wpdb;

		$sql = "DELETE FROM $wpdb->options WHERE `option_name` LIKE '_transient_wp_weixin_%'";

		$wpdb->query( $sql ); // @codingStandardsIgnoreLine
		flush_rewrite_rules();
	}

	public static function uninstall() {
		include_once WP_WEIXIN_PLUGIN_PATH . 'uninstall.php';
	}

	public static function is_wechat_mobile() {
		$is_wechat_mobile = false;

		if ( false !== strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger' ) ) {

			foreach ( explode( ' ', $_SERVER['HTTP_USER_AGENT'] ) as $key => $value ) {

				if ( false !== strpos( $value, 'MicroMessenger' ) ) {
					$version_parts = explode( '/', $value );
					$version       = end( $version_parts );
					$version_parts = explode( '.', $version );
					$condition     = ( 6 <= ( (int) $version_parts[0] ) );

					if ( ! $condition ) {
						$title    = '<h2>' . __( 'WeChat version deprecated.', 'wp-weixin' ) . '</h2>';
						$message  = '<p>' . __( 'Please update WeChat to a more recent version ', 'wp-weixin' );
						$message .= __( '(minimum compatible version: 6.5.8)', 'wp-weixin' );

						wp_die( $title . $message ); // WPCS: XSS ok
					} else {
						$is_wechat_mobile = true;
					}
				}
			}
		}

		return $is_wechat_mobile;
	}

	public static function get_user_by_openid( $openid ) {
		$user                = false;
		$openid_login_suffix = ( is_multisite() ) ? strtolower( $openid ) : $openid;
		$auth_blog_id        = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );

		if ( username_exists( 'wx-' . $openid_login_suffix ) ) {
			$user = get_user_by( 'login', 'wx-' . $openid_login_suffix );
		} elseif ( username_exists( 'wx-bound-' . $openid_login_suffix ) ) {
			$user = get_user_by( 'login', 'wx-bound-' . $openid_login_suffix );
		} else {
			$maybe_users = get_users(
				array(
					'meta_key'    => 'wx_openid-' . $auth_blog_id,
					'meta_value'  => $openid,
					'number'      => 1,
					'count_total' => false,
				)
			);

			if ( ! empty( $maybe_users ) ) {
				$user = reset( $maybe_users );
			}
		}

		return apply_filters( 'wp_weixin_get_user_by_openid', $user, $openid );
	}

	public static function get_users_by_unionid( $unionid, $blog_id, $number = -1 ) {
		$users       = false;
		$maybe_users = get_users(
			array(
				'blog_id'     => $blog_id,
				'meta_key'    => 'wx_unionid',
				'meta_value'  => $unionid,
				'number'      => $number,
				'count_total' => false,
			)
		);

		if ( ! empty( $maybe_users ) ) {

			if ( 1 === count( $maybe_users ) ) {
				$users = reset( $maybe_users );
			} else {
				$users = $maybe_users;
			}
		}

		return $users;
	}

	public static function switch_language( $force ) {
		global $sitepress;

		$user     = wp_get_current_user();
		$language = get_user_meta( $user->ID, 'wp_weixin_language', true );

		if (
			$sitepress &&
			! empty( $language ) &&
			( absint( $sitepress->get_setting( 'automatic_redirect' ) ) === 1 || $force )
		) {
			$language = $sitepress->get_language_code_from_locale( $language );

			if ( $language ) {
				$sitepress->switch_lang( $language );

				return true;
			}
		}

		return false;
	}

	public static function add_scan_heartbeat_scripts( $action ) {
		$debug   = apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) );
		$js_ext  = ( $debug ) ? '.js' : '.min.js';
		$version = filemtime( WP_WEIXIN_PLUGIN_PATH . 'js/scan-heartbeat' . $js_ext );
		$params  = array(
			'heartbeatFreq' => apply_filters( 'wp_weixin_scan_heartbeat_frequency', 1000 ),
			'action'        => 'wp_weixin_' . $action . '_heartbeat_pulse',
			'dataIndex'     => $action,
			'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'debug'         => $debug,
		);
		wp_enqueue_script(
			'wp-weixin-scan-heartbeat',
			WP_WEIXIN_PLUGIN_URL . 'js/scan-heartbeat' . $js_ext,
			array( 'jquery' ),
			$version,
			true
		);
		wp_localize_script( 'wp-weixin-scan-heartbeat', 'WP_WeixinScanHeartBeat', $params );
	}

	public static function remove_all_scripts() {
		global $wp_scripts;

		$wp_scripts->queue = self::$scripts;
	}

	public static function remove_all_styles() {
		global $wp_styles;

		$wp_styles->queue = self::$styles;
	}

	public static function locate_template( $template_name, $load = false, $require_once = true, $plugin_name = '' ) {

		if ( ! empty( $plugin_name ) ) {
			$plugin_name = trailingslashit( $plugin_name );
		}

		$paths       = array(
			'plugins/wp-weixin/' . $plugin_name . $template_name,
			'wp-weixin/' . $plugin_name . $template_name,
			'plugins/' . $plugin_name . $template_name,
			$plugin_name . $template_name,
			'wp-weixin/' . $template_name,
			$template_name,
		);
		$plugin_name = ( empty( $plugin_name ) ) ? 'wp-weixin' : untrailingslashit( $plugin_name );
		$template    = locate_template(
			apply_filters( 'wp_weixin_locate_template_paths', $paths, $plugin_name ),
			$load,
			$require_once
		);

		if ( empty( $template ) ) {
			$constant_name = strtoupper( str_replace( '-', '_', $plugin_name ) . '_PLUGIN_PATH' );
			$template      = ( defined( $constant_name ) ) ? constant( $constant_name ) . 'inc/templates/' . $template_name : '';

			if ( $load && '' !== $template ) {
				load_template( $template, $require_once );
			}
		}

		return $template;
	}

	public static function log( $expression, $extend_context = '') {

		if ( ! is_string( $expression ) ) {
			$alternatives = array(
				array(
					'func' => 'print_r',
					'args' => array( $expression, true ),
				),
				array(
					'func' => 'var_export',
					'args' => array( $expression, true ),
				),
				array(
					'func' => 'json_encode',
					'args' => array( $expression ),
				),
				array(
					'func' => 'serialize',
					'args' => array( $expression ),
				),
			);

			foreach ( $alternatives as $alternative ) {

				if ( function_exists( $alternative['func'] ) ) {
					$expression = call_user_func_array( $alternative['func'], $alternative['args'] );

					break;
				}
			}
		}

		$extend_context = ( $extend_context ) ? ' - ' . $extend_context : '';
		$trace          = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 ); // @codingStandardsIgnoreLine
		$caller         = end( $trace );
		$class          = isset( $caller['class'] ) ? $caller['class'] : '';
		$type           = isset( $caller['type'] ) ? $caller['type'] : '';
		$function       = isset( $caller['function'] ) ? $caller['function'] : '';
		$context        = $class . $type . $function . ' on line ' . $caller['line'] . $extend_context . ': ';

		error_log( $context .  $expression ); // @codingStandardsIgnoreLine
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'wp-weixin', false, 'wp-weixin/languages' );
	}

	public function register_qr_cleanup() {
		$hook = 'wp_weixin_qr_cleanup';

		if ( ! wp_next_scheduled( $hook ) ) {
			$frequency = apply_filters( 'wp_weixin_qr_cleanup_frequency', 'hourly' );
			$timestamp = time();

			wp_schedule_event( $timestamp, $frequency, $hook );
		}
	}

	public function qr_cleanup() {
		global $wpdb;

		$transient_prefix = $wpdb->esc_like( '_transient_timeout_wp_weixin_qr_' ) . '%';
		$sql              = "
			SELECT REPLACE(option_name, '_transient_timeout_', '') AS transient_name
			FROM {$wpdb->options}
			WHERE option_name LIKE %s
			AND option_value < %s;
		";

		$query         = $wpdb->prepare( $sql, $transient_prefix, time() ); // @codingStandardsIgnoreLine
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

			$wpdb->query( $query ); // @codingStandardsIgnoreLine
		}
	}

	public function add_frontend_scripts() {

		if ( ! is_admin() ) {
			$debug   = apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) );
			$css_ext = ( $debug ) ? '.css' : '.min.css';
			$version = filemtime( WP_WEIXIN_PLUGIN_PATH . 'css/main' . $css_ext );
			$key     = 'wp-weixin-main-style';

			wp_enqueue_style( $key, WP_WEIXIN_PLUGIN_URL . 'css/main' . $css_ext, array(), $version );

			$included  = get_included_files();
			$abspath   = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, ABSPATH );
			$condition = in_array( $abspath . 'wp-login.php', $included, true );
			$condition = $condition || in_array( $abspath . 'wp-register.php', $included, true );
			$condition = $condition || 'wp-login.php' === $GLOBALS['pagenow'] || '/wp-login.php' === $_SERVER['PHP_SELF'];

			if ( ! $condition && apply_filters( 'wp_weixin_include_script', true ) ) {
				$js_ext  = ( $debug ) ? '.js' : '.min.js';
				$version = filemtime( WP_WEIXIN_PLUGIN_PATH . 'js/main' . $js_ext );
				$params  = array(
					'weixin'   => $this->wechat->get_signed_package(),
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'debug'    => $debug,
					'share'    => false,
				);

				$queried_object = get_queried_object();

				if ( $queried_object instanceof WP_Post ) {
					$title           = $queried_object->post_title;
					$description     = $queried_object->post_excerpt;
					$img_url         = get_the_post_thumbnail_url( $queried_object->ID );
					$img_url         = ( $img_url ) ? $img_url : WP_Weixin_Settings::get_option( 'logo_url' );
					$params['share'] = array(
						'title'  => $title . ' - ' . get_bloginfo( 'name' ),
						'desc'   => strip_tags( $description ),
						'link'   => get_permalink( $queried_object->ID ),
						'imgUrl' => $img_url,
					);

					$params['share'] = apply_filters( 'wp_weixin_wechat_share_params', $params['share'], $queried_object );
				}

				wp_enqueue_script( 'wechat-api-script', '//res.wx.qq.com/open/js/jweixin-1.0.0.js', false, false );
				wp_enqueue_script(
					'wp-weixin-main-script',
					WP_WEIXIN_PLUGIN_URL . 'js/main' . $js_ext,
					array( 'jquery', 'wechat-api-script' ),
					$version,
					true
				);
				wp_localize_script( 'wp-weixin-main-script', 'WP_Weixin', $params );
			}
		}
	}

	public function add_admin_scripts( $hook ) {
		$debug   = apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) );
		$css_ext = ( $debug ) ? '.css' : '.min.css';
		$version = filemtime( WP_WEIXIN_PLUGIN_PATH . 'css/admin/main' . $css_ext );

		wp_enqueue_style( 'wp-weixin-main-style', WP_WEIXIN_PLUGIN_URL . 'css/admin/main' . $css_ext, array(), $version );
	}

	public function maybe_flush() {

		if ( get_transient( 'wp_weixin_flush' ) ) {
			delete_transient( 'wp_weixin_flush' );
			flush_rewrite_rules();
		}
	}

	public function add_query_vars( $vars ) {
		$vars[] = '__wp_weixin_api';
		$vars[] = 'action';
		$vars[] = 'hash';

		return $vars;
	}

	public function avatar( $avatar, $id_or_email, $size, $default, $alt ) {
		$user = false;

		if ( is_numeric( $id_or_email ) ) {
			$id   = absint( $id_or_email );
			$user = get_user_by( 'id', $id );
		} elseif ( is_object( $id_or_email ) && $id_or_email instanceof WP_User ) {

			if ( ! empty( $id_or_email->user_id ) ) {
				$id   = absint( $id_or_email )->user_id;
				$user = get_user_by( 'id', $id );
			}
		} elseif ( is_string( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
		}

		if ( $user && is_object( $user ) ) {

			if ( 1 !== absint( $user->ID ) ) {
				$headimgurl = get_user_meta( $user->ID, 'wp_weixin_headimgurl', true );
				$avatar     = ( is_ssl() ) ? str_replace( 'http://', 'https://', $headimgurl ) : $headimgurl;

				if ( empty( $avatar ) ) {
					$image  = WP_WEIXIN_PLUGIN_URL . 'images/default-avatar.png';
					$type   = pathinfo( $image, PATHINFO_EXTENSION );
					$data   = wp_remote_retrieve_body( wp_remote_get( $image ) );
					$avatar = 'data:image/' . $type . ';base64,' . base64_encode( $data ); // @codingStandardsIgnoreLine
				}

				$avatar  = "<img alt='{$alt}' src='{$avatar}'";
				$avatar .= "class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
			}
		}

		return $avatar;
	}

	public function avatar_description( $description, $profileuser ) {
		$description = __( 'Latest known WeChat profile photo.', 'wp-weixin' );

		return $description;
	}

	public function user_profile_wechat_info( $user = false, $output = true ) {

		if ( ! $user || ! $user instanceof WP_User || ! $user->ID ) {
			$user = wp_get_current_user();
		}

		$data = $this->get_user_wechat_info( $user->ID );

		if (
			! $output ||
			'show_user_profile' === current_filter() ||
			'edit_user_profile' === current_filter()
		) {
			do_action( 'wp_weixin_before_user_profile_wechat_info', $data, $user );

			ob_start();

			require_once WP_WEIXIN_PLUGIN_PATH . 'inc/templates/admin/wechat-public-info.php';

			echo ob_get_clean(); // WPCS: XSS ok

			do_action( 'wp_weixin_after_user_profile_wechat_info', $data, $user );
		} else {
			ob_start();
			set_query_var( 'wechat_info', $data );
			self::locate_template( 'wp-weixin-public-info.php', true );
			echo ob_get_clean(); // WPCS: XSS ok
		}
	}

	public function get_user_wechat_info( $user_id = false ) {
		$data = false;

		if ( false === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( $user_id ) {
			$data = json_decode( get_user_meta( $user_id, 'wp_weixin_rawdata', true ), true );

			if ( ! empty( $data ) ) {
				$data['headimgurl'] = ( is_ssl() ) ? str_replace( 'http://', 'https://', $data['headimgurl'] ) : $data['headimgurl'];
				$sex                = absint( $data['sex'] );
				$auth_blog_id       = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );

				if ( is_admin() ) {

					if ( 0 === $sex ) {
						$sex = __( '0 (N/A)', 'wp-weixin' );
					} elseif ( 1 === $sex ) {
						$sex = __( '1 (M)', 'wp-weixin' );
					} elseif ( 2 === $sex ) {
						$sex = __( '2 (F)', 'wp-weixin' );
					}
				} else {

					if ( 0 === $sex ) {
						$sex = __( 'N/A', 'wp-weixin' );
					} elseif ( 1 === $sex ) {
						$sex = __( 'Male', 'wp-weixin' );
					} elseif ( 2 === $sex ) {
						$sex = __( 'Female', 'wp-weixin' );
					}
				}

				$data = array(
					'nickname'   => $this->cleanup_wechat_info( $data['nickname'] ),
					'headimgurl' => $data['headimgurl'],
					'sex'        => $sex,
					'language'   => $data['language'],
					'city'       => $this->cleanup_wechat_info( $data['city'] ),
					'province'   => $this->cleanup_wechat_info( $data['province'] ),
					'country'    => $this->cleanup_wechat_info( $data['country'] ),
					'unionid'    => get_user_meta( $user_id, 'wx_unionid', true ),
				);

				if ( is_network_admin() ) {
					$openids = array();
					$sites   = get_sites();

					foreach ( $sites as $site ) {
						$openid = get_user_meta( $user_id, 'wx_openid-' . $site->blog_id, true );

						if ( $openid && ! in_array( $openid, $openids, true ) ) {
							$openids[] = $openid;
						}
					}

					$data['openid'] = implode( ', ', $openids );
				} else {
					$openid         = get_user_meta( $user_id, 'wx_openid-' . $auth_blog_id, true );
					$data['openid'] = $openid;
				}
			}
		}

		if ( ! $data['openid'] ) {
			$data = false;
		}

		return apply_filters( 'wp_weixin_user_wechat_info', $data, $user_id );
	}

	public function filter_wechat_get_user_meta( $check, $object_id, $meta_key, $single ) {

		if ( 'wp_weixin_openid' === $meta_key ) {
			$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );

			if ( ! WP_Weixin_Settings::get_option( 'enable_auth' ) ) {
				$meta = filter_input( INPUT_COOKIE, 'wx_openId-' . $auth_blog_id, FILTER_SANITIZE_STRING );
			} else {
				remove_filter( 'get_user_metadata', array( $this, 'filter_wechat_get_user_meta' ), 1 );

				$meta = get_user_meta( $object_id, 'wx_openid-' . $auth_blog_id, true );

				add_filter( 'get_user_metadata', array( $this, 'filter_wechat_get_user_meta' ), 1, 4 );
			}

			return $meta;
		}

		foreach ( $this->meta_keys as $key ) {

			if ( $meta_key === $key ) {
				$raw_data = json_decode( get_user_meta( $object_id, 'wp_weixin_rawdata', true ), true );
				$raw_key  = str_replace( 'wp_weixin_', '', $key );
				$check    = ( $single ) ? $raw_data[ $raw_key ] : array( $raw_data[ $raw_key ] );

				break;
			}
		}

		return $check;
	}

	public function filter_wechat_update_user_meta( $check, $object_id, $meta_key, $meta_value, $prev_value ) {

		if ( 'wp_weixin_openid' === $meta_key ) {
			$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );

			if ( ! WP_Weixin_Settings::get_option( 'enable_auth' ) ) {
				$result = setcookie( 'wx_openId-' . $auth_blog_id, $meta_value );
			} else {
				remove_filter( 'update_user_metadata', array( $this, 'filter_wechat_update_user_meta' ), 1 );

				$result = update_user_meta( $user_id, 'wx_openid-' . $auth_blog_id, $meta_value );

				add_filter( 'update_user_metadata', array( $this, 'filter_wechat_update_user_meta' ), 1, 5 );
			}

			return $result;
		}

		foreach ( $this->meta_keys as $key ) {

			if ( $meta_key === $key ) {
				$raw_data = json_decode( get_user_meta( $object_id, 'wp_weixin_rawdata', true ), true );
				$raw_key  = str_replace( 'wp_weixin_', '', $key );

				$raw_data[ $raw_key ] = $meta_value;

				$check = update_user_meta( $object_id, 'wp_weixin_rawdata', wp_json_encode( $raw_data ) );

				break;
			}
		}

		return $check;
	}

	public function alter_user_table_columns( $columns ) {
		$pristine = $columns;
		$columns  = array_slice( $pristine, 0, 1, true );
		$columns += array( 'wechat_name' => __( 'WeChat Username', 'wp-weixin' ) );
		$columns += array_slice( $pristine, 1, count( $pristine ) - 1, true );

		unset( $columns['username'] );

		return $columns;
	}

	public function alter_user_table_rows( $val, $column_name, $user_id ) {

		if ( 'wechat_name' === $column_name ) {
			$val = $this->build_username_cell( $user_id );
		}

		return $val;
	}

	public function alter_user_sortable_columns( $columns ) {
		$columns['wechat_name'] = 'wechat_name';

		return $columns;
	}

	public function alter_user_sort_query( $userquery ) {

		if ( 'wechat_name' === $userquery->query_vars['orderby'] ) {
			$userquery->query_orderby  = ' ORDER BY display_name ';
			$userquery->query_orderby .= ( 'ASC' === $userquery->query_vars['order'] ? 'asc ' : 'desc ' );
		}
	}

	public function handle_pay_notify() {
		$error       = true;
		$refund      = true;
		$success     = false;
		$pay_results = apply_filters( 'wp_weixin_pay_notify_results', array() );
		$result      = false;

		if ( ! empty( $pay_results ) ) {

			foreach ( $pay_results as $pay_result ) {
				$refund = isset( $result['refund'] ) && $result['refund'];
				$error  = isset( $result['notify_error'] ) && $result['notify_error'];

				if ( is_array( $pay_result ) && isset( $pay_result['success'] ) && $pay_result['success'] ) {
					$result = $pay_result;

					break;
				}
			}
		}

		if ( $refund || $error ) {
			$result = ( $result ) ? $result : array(
				'success'      => false,
				'data'         => $this->wechat->getNotify(),
				'refund'       => __( 'Unknown Error', 'wp-weixin' ) . ' - pay_result_handler_not_found',
				'notify_error' => '',
				'blog_id'      => get_current_blog_id(),
				'pay_handler'  => false,
			);

			if ( $refund && $this->wechat->cert_files_exist() ) {

				if ( true === $result['refund'] || empty( $result['refund'] ) ) {
					$result['refund'] = __( 'Unknown Error', 'wp-weixin' );
				}

				$refund_result = $this->wechat->refundOrder(
					$result['data']['out_trade_no'],
					str_pad( $result['data']['out_trade_no'], 64, '0', STR_PAD_LEFT ),
					round( ( $result['data']['total_fee'] / 100 ), 2 ),
					round( ( $result['data']['total_fee'] / 100 ), 2 ),
					array( 'refund_desc' => $result['refund'] )
				);

				if ( is_multisite() && isset( $result['blog_id'] ) ) {
					switch_to_blog( absint( $result['blog_id'] ) );
				}

				do_action( 'wp_weixin_handle_auto_refund', $refund_result, $result );

				if ( is_multisite() && isset( $result['blog_id'] ) ) {
					restore_current_blog();
				}
			}

			if ( $error ) {

				if ( true === $result['notify_error'] ) {
					$result['notify_error'] = __( 'Unknown Error', 'wp-weixin' );
				}
			}
		}

		do_action( 'wp_weixin_payment_notification_handled', $result );

		$this->wechat->returnNotify( $error );
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function build_username_cell( $user_id ) {
		$user_object = get_user_by( 'ID', $user_id );
		$edit_link   = esc_url(
			add_query_arg(
				'wp_http_referer',
				rawurlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
				get_edit_user_link( $user_object->ID )
			)
		);

		if ( current_user_can( 'edit_user', $user_object->ID ) ) {
			$edit = '<strong><a href="' . $edit_link . '">' . $user_object->display_name . '</a></strong><br />';
		} else {
			$edit = '<strong>' . $user_object->display_name . '</strong><br />';
		}

		$avatar = '<div style="float: left; margin-right: 10px; margin-top: 1px;">' . get_avatar( $user_object->ID, 32 ) . '</div>';
		$cell   = $avatar . ' ' . $edit;

		return $cell;
	}

	protected function cleanup_wechat_info( $string ) {
		$regex  = '/u[[:xdigit:]]{4}/';
		$string = preg_replace( '/(u[[:xdigit:]]{4})/', '\\\$1', $string );
		$string = json_decode( sprintf( '"%s"', $string ) );

		return $string;
	}

}
