<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin {

	protected $wechat;
	protected $meta_keys;

	public function __construct( $wechat, $init_hooks = false ) {

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

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
			// Allow user profile update with empty email
			add_action( 'user_profile_update_errors', array( $this, 'remove_empty_email_error' ), 10, 1 );
			// Add sort by WeChat name logic
			add_action( 'pre_user_query', array( $this, 'alter_user_sort_query' ), 10, 1 );
			// Flush rewrite rules if necessary - do it late
			add_action( 'init', array( $this, 'maybe_flush' ), PHP_INT_MIN + 5, 0 );
			// Add WeChat public information to user profile
			add_action( 'show_user_profile', array( $this, 'user_profile_wechat_info' ), 10, 1 );
			add_action( 'edit_user_profile', array( $this, 'user_profile_wechat_info' ), 10, 1 );

			// Get WeChat avatar if exists
			add_filter( 'get_avatar', array( $this, 'avatar' ), PHP_INT_MAX, 5 );
			// Filter WeChat get meta - add better getters for raw rata
			add_filter( 'get_user_metadata', array( $this, 'filter_wechat_get_user_meta' ), 1, 4 );
			// Filter WeChat update meta - add better setters for raw data
			add_filter( 'update_user_metadata', array( $this, 'filter_wechat_update_user_meta' ), 1, 5 );
			// Add main query vars
			add_filter( 'query_vars', array( $this, 'add_query_vars' ), PHP_INT_MIN, 1 );

			if ( WP_Weixin_Settings::get_option( 'alter_userscreen' ) ) {
				// Add WeChat name column
				add_filter( 'manage_users_columns', array( $this, 'alter_user_table_columns' ), 10, 1 );
				// Add content of the WeChat name column
				add_filter( 'manage_users_custom_column', array( $this, 'alter_user_table_rows' ), 10, 3 );
				// Add sort by WeChat name on user screen
				add_filter( 'manage_users_sortable_columns', array( $this, 'alter_user_sortable_columns' ), 10, 1 );
			}
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public static function activate() {
		set_transient( 'wp_weixin_flush', 1, 60 );
	}

	public static function deactivate() {
		global $wpdb;

		$prefix = $wpdb->esc_like( '_transient_wp_weixin_' );
		$sql    = "DELETE FROM $wpdb->options WHERE `option_name` LIKE '%s'";

		$wpdb->query( $wpdb->prepare( $sql, $prefix . '%' ) ); // @codingStandardsIgnoreLine
		flush_rewrite_rules();
	}

	public static function uninstall() {
		include_once WP_WEIXIN_PLUGIN_PATH . 'uninstall.php';
	}

	public static function is_wechat_mobile() {
		$is_wechat_mobile = false;

		if ( strpos( $_SERVER['HTTP_USER_AGENT'], 'MicroMessenger' ) !== false ) {

			foreach ( explode( ' ', $_SERVER['HTTP_USER_AGENT'] ) as $key => $value ) {

				if ( strpos( $value, 'MicroMessenger' ) !== false ) {
					$version_parts = explode( '/', $value );
					$version       = end( $version_parts );
					$version_parts = explode( '.', $version );
					$condition     = ( ( (int) $version_parts[0] ) >= 6 );

					if ( ! $condition ) {
						$title   = '<h2>' . __( 'WeChat version deprecated.', 'wp-weixin' ) . '</h2>';
						$message = '<p>' . __( 'Please update WeChat to a more recent version ', 'wp-weixin' );

						$message .= __( '(minimum compatible version: 6.5.8)', 'wp-weixin' );

						wp_die( $title . $message ); // @codingStandardsIgnoreLine
					} else {
						$is_wechat_mobile = true;
					}
				}
			}
		}

		return $is_wechat_mobile;
	}

	public static function get_user_by_openid( $openid ) {
		$user = false;

		if ( ! username_exists( 'wx-' . $openid ) ) {
			$maybe_users = get_users(
				array(
					'meta_key'    => 'wx_openid',
					'meta_value'  => $openid,
					'number'      => 1,
					'count_total' => false,
				)
			);

			if ( ! empty( $maybe_users ) ) {
				$user = reset( $maybe_users );
			}
		} else {
			$user = get_user_by( 'login', 'wx-' . $openid );
		}

		return $user;
	}

	public static function get_user_by_unionid( $unionid ) {
		$user        = false;
		$maybe_users = get_users(
			array(
				'meta_key'    => 'wx_unionid',
				'meta_value'  => $unionid,
				'number'      => 1,
				'count_total' => false,
			)
		);

		if ( ! empty( $maybe_users ) ) {
			$user = reset( $maybe_users );
		}

		return $user;
	}

	public static function format_emoji( $text ) {
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

	public static function maybe_switch_language() {
		global $sitepress;

		$user     = wp_get_current_user();
		$language = get_user_meta( $user->ID, 'wp_weixin_language', true );

		if ( $sitepress && ! empty( $language ) && absint( $sitepress->get_setting( 'automatic_redirect' ) ) === 1 ) {
			$language = $sitepress->get_language_code_from_locale( $language );

			if ( $language ) {
				$sitepress->switch_lang( $language );
			}
		}
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'wp-weixin', false, 'wp-weixin/languages' );
	}

	public function add_frontend_scripts() {

		if ( ! is_admin() ) {
			$debug   = apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) );
			$css_ext = ( $debug ) ? '.css' : '.min.css';

			$version = filemtime( WP_WEIXIN_PLUGIN_PATH . 'css/main' . $css_ext );
			$key     = 'wp-weixin-main-style';

			wp_enqueue_style( $key, WP_WEIXIN_PLUGIN_URL . 'css/main' . $css_ext, array(), $version );

			$abspath   = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, ABSPATH );
			$condition = ( in_array( $abspath . 'wp-login.php', get_included_files() ) || in_array( $abspath . 'wp-register.php', get_included_files() ) ); // @codingStandardsIgnoreLine
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

				wp_enqueue_script( 'wechat-api', '//res.wx.qq.com/open/js/jweixin-1.0.0.js', false, false );
				wp_enqueue_script( 'wp-weixin-main-script', WP_WEIXIN_PLUGIN_URL . 'js/main' . $js_ext, array( 'jquery', 'wechat-api' ), $version, true );
				wp_localize_script( 'wp-weixin-main-script', 'WP_Weixin', $params );
			}
		}
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

		return $vars;
	}

	public function avatar( $avatar, $id_or_email, $size, $default, $alt ) {
		$user = false;

		if ( is_numeric( $id_or_email ) ) {
			$id   = (int) $id_or_email;
			$user = get_user_by( 'id', $id );
		} elseif ( is_object( $id_or_email ) ) {

			if ( ! empty( $id_or_email->user_id ) ) {
				$id   = (int) $id_or_email->user_id;
				$user = get_user_by( 'id', $id );
			}
		} else {
			$user = get_user_by( 'email', $id_or_email );
		}

		if ( $user && is_object( $user ) ) {

			if ( 1 !== absint( $user->data->ID ) ) {
				$avatar = get_user_meta( $id, 'wp_weixin_headimgurl', true );

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

	public function user_profile_wechat_info( $user ) {
		$data = json_decode( get_user_meta( $user->ID, 'wp_weixin_rawdata', true ), true );

		if ( ! empty( $data ) ) {
			$sex = absint( $data['sex'] );

			if ( 0 === $sex ) {
				$sex = __( '0 (N/A)', 'wp-weixin' );
			} elseif ( 1 === $sex ) {
				$sex = __( '1 (M)', 'wp-weixin' );
			} elseif ( 2 === $sex ) {
				$sex = __( '2 (F)', 'wp-weixin' );
			}

			$data = array(
				'openid'     => $data['openid'],
				'nickname'   => $this->cleanup_wechat_info( $data['nickname'] ),
				'headimgurl' => '<a target="_blank" href="' . $data['headimgurl'] . '">' . $data['headimgurl'] . '</a>',
				'sex'        => $sex,
				'language'   => $data['language'],
				'city'       => $this->cleanup_wechat_info( $data['city'] ),
				'province'   => $this->cleanup_wechat_info( $data['province'] ),
				'country'    => $this->cleanup_wechat_info( $data['country'] ),
				'unionid'    => $data['unionid'],
			);

			ob_start();

			require_once WP_WEIXIN_PLUGIN_PATH . 'inc/templates/admin/wechat-public-info.php';

			echo ob_get_clean(); // @codingStandardsIgnoreLine
		}
	}

	public function remove_empty_email_error( $arg ) {

		if ( ! empty( $arg->errors['empty_email'] ) ) {
			unset( $arg->errors['empty_email'] );
		}
	}

	public function filter_wechat_get_user_meta( $check, $object_id, $meta_key, $single ) {

		if ( 'wp_weixin_openid' === $meta_key && ! WP_Weixin_Settings::get_option( 'enable_auth' ) ) {

			$meta = filter_input( INPUT_COOKIE, 'wx_openId', FILTER_SANITIZE_STRING );

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

		if ( 'wp_weixin_openid' === $meta_key && ! WP_Weixin_Settings::get_option( 'enable_auth' ) ) {
			$result = setcookie( 'wx_openId', $meta_value );

			return $result;
		}

		foreach ( $this->meta_keys as $key ) {

			if ( $meta_key === $key ) {
				$raw_data             = json_decode( get_user_meta( $object_id, 'wp_weixin_rawdata', true ), true );
				$raw_key              = str_replace( 'wp_weixin_', '', $key );
				$raw_data[ $raw_key ] = $meta_value;
				$check                = update_user_meta( $object_id, 'wp_weixin_rawdata', wp_json_encode( $raw_data ) );

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

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function build_username_cell( $user_id ) { // @codingStandardsIgnoreLine
		$user_object = get_user_by( 'ID', $user_id );
		$edit_link   = esc_url( add_query_arg( 'wp_http_referer', rawurlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ), get_edit_user_link( $user_object->ID ) ) );

		if ( current_user_can( 'edit_user', $user_object->ID ) ) {
			$edit = '<strong><a href="' . $edit_link . '">' . $user_object->display_name . '</a></strong><br />';
		} else {
			$edit = '<strong>' . $user_object->display_name . '</strong><br />';
		}

		$avatar = '<div style="float: left; margin-right: 10px; margin-top: 1px;">' . get_avatar( $user_object->ID, 32 ) . '</div>';
		$cell   = $avatar . ' ' . $edit;

		return $cell;
	}

	protected function cleanup_wechat_info( $string ) { // @codingStandardsIgnoreLine
		$regex  = '/u[[:xdigit:]]{4}/';
		$string = preg_replace( '/(u[[:xdigit:]]{4})/', '\\\$1', $string );
		$string = json_decode( sprintf( '"%s"', $string ) );

		return $string;
	}

}
