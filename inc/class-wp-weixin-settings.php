<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Settings {

	protected $settings_fields;
	protected $settings;

	protected static $error;

	const MAX_JSAPI_URLS = 5;

	public function __construct( $init_hooks = false ) {

		$this->settings = self::get_options();

		if ( $init_hooks ) {

			// Make sure we're not caching in a persistent object cache if a persistent plugin is installed
			$this->set_cache_policy();
			// Init settings definition
			add_action( 'wp_loaded', array( $this, 'init_settings_definition' ), 10, 0 );
			// Add admin scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ), 99, 1 );
			// Add main WP Weixin settings menu
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			// Build WP Weixin settings page
			add_action( 'admin_init', array( $this, 'build_settings_page' ) );
			// Add the API endpoints
			add_action( 'init', array( $this, 'add_endpoints' ), PHP_INT_MIN, 0 );
			// Parse the endpoint request
			add_action( 'parse_request', array( $this, 'parse_request' ), 0, 0 );
			// Add QR code generation ajax callback
			add_action( 'wp_ajax_wp_weixin_get_settings_qr', array( $this, 'get_qr_hash' ), 10, 0 );
			// set transient flush on option save
			add_action( 'update_option_wp_weixin_settings', array( $this, 'set_wp_weixin_flush' ), 10, 3 );

			// Add settings pages query vars
			add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0, 1 );
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public function __call( $method_name, $args ) {
		$field_key      = str_replace( 'wp_weixin_', '', str_replace( '_render', '', $method_name ) );
		$isfield_render = ( strpos( $method_name, 'wp_weixin_' ) !== false );
		$isfield_render = $isfield_render && ( strpos( $method_name, '_render' ) !== false );
		$isfield_render = $isfield_render && $this->get_field_attr( $field_key, 'id' );

		$section_key         = str_replace( '_settings_section_callback', '', $method_name );
		$is_section_callback = ( strpos( $method_name, '_settings_section_callback' ) !== false );
		$is_section_callback = $is_section_callback && in_array( $section_key, array_keys( $this->settings_fields ) ); // @codingStandardsIgnoreLine

		if ( $isfield_render ) {
			call_user_func_array( array( $this, 'field_render' ), array( $field_key ) );
		} elseif ( $is_section_callback ) {
			call_user_func_array( array( $this, 'section_render' ), array( $section_key ) );
		} else {
			trigger_error( 'Call to undefined method ' . __CLASS__ . '::' . $method_name . '()', E_USER_ERROR ); // @codingStandardsIgnoreLine
		}
	}

	public static function get_options() {
		$settings                       = get_option( 'wp_weixin_settings' );
		$filtered                       = array();
		$force_custom_transfer_inactive = isset( $settings['wp_weixin_custom_transfer'] ) && $settings['wp_weixin_custom_transfer'];
		$force_custom_transfer_inactive = $force_custom_transfer_inactive && ! is_plugin_active( 'wp-weixin-pay/wp-weixin-pay.php' );
		$force_custom_transfer_inactive = apply_filters( 'wp_weixin_force_custom_transfer_inactive', $force_custom_transfer_inactive );

		if ( $force_custom_transfer_inactive ) {
			$settings['wp_weixin_custom_transfer'] = false;

			update_option( 'wp_weixin_settings', $settings );
		}

		if ( empty( $settings ) ) {

			return $settings;
		}

		foreach ( $settings as $key => $value ) {
			$filtered_key = str_replace( 'wp_weixin_', '', $key );
			$bools        = array(
				'enable',
				'enable_auth',
				'force_wechat',
				'force_follower',
				'follow_welcome',
				'responder',
				'encode',
				'ecommerce',
				'custom_transfer',
				'ecommerce_force_follower',
				'proxy',
				'alter_userscreen',
			);

			if ( in_array( $filtered_key, $bools ) ) { // @codingStandardsIgnoreLine
				$filtered[ $filtered_key ] = (bool) $value;
			} else {
				$filtered[ $filtered_key ] = $value;
			}
		}

		return $filtered;
	}

	public static function get_option( $key ) {
		$options = self::get_options();
		$value   = isset( $options[ $key ] ) ? $options[ $key ] : false;

		if ( 'ecommerce' === $key ) {
			$value = $value || is_plugin_active( 'woo-wechatpay/woo-wechatpay.php' ) || is_plugin_active( 'wp-weixin-pay/wp-weixin-pay.php' );
		}

		$value = apply_filters( 'wp_weixin_get_' . $key . '_option', $value, $key );

		return $value;
	}

	public function set_cache_policy() {
		wp_cache_add_non_persistent_groups( 'wp_weixin' );
	}

	public function set_wp_weixin_flush( $old_value, $value, $option ) {
		set_transient( 'wp_weixin_flush', 1, 60 );
	}

	public function add_admin_scripts( $hook ) {

		if ( 'toplevel_page_wp-weixin' === $hook ) {
			$debug   = apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) );
			$js_ext  = ( $debug ) ? '.js' : '.min.js';
			$css_ext = ( $debug ) ? '.css' : '.min.css';
			$version = filemtime( WP_WEIXIN_PLUGIN_PATH . 'js/admin/settings' . $js_ext );

			$parameters = array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'debug'    => $debug,
			);

			wp_enqueue_script( 'wp-weixin-settings-script', WP_WEIXIN_PLUGIN_URL . 'js/admin/settings' . $js_ext, array( 'jquery' ), $version, true );
			wp_localize_script( 'wp-weixin-settings-script', 'WpWeixin', $parameters );

			$version = filemtime( WP_WEIXIN_PLUGIN_PATH . 'css/admin/settings' . $css_ext );

			wp_enqueue_style( 'wp-weixin-settings-style', WP_WEIXIN_PLUGIN_URL . 'css/admin/settings' . $css_ext, array(), $version );
		}
	}

	public function add_admin_menu() {
		$title    = __( 'WP Weixin Settings', 'wp-weixin' );
		$icon_url = WP_WEIXIN_PLUGIN_URL . '/images/wechat.png';

		add_menu_page( $title, 'WP Weixin', 'publish_posts', 'wp-weixin', array( $this, 'wp_weixin_options_page' ), $icon_url );
	}

	public function build_settings_page() {
		register_setting( 'wpWeixinSettings', 'wp_weixin_settings' );

		foreach ( $this->settings_fields as $section_name => $section ) {
			add_settings_section(
				'wp_weixin_' . $section_name . '_section',
				$section['title'],
				array( $this, $section_name . '_settings_section_callback' ),
				'wpWeixinSettings'
			);

			foreach ( $section as $field ) {

				if ( is_array( $field ) ) {
					$id       = 'wp_weixin_' . $field['id'];
					$title    = $field['label'];
					$callback = array( $this, 'wp_weixin_' . $field['id'] . '_render' );
					$page     = 'wpWeixinSettings';
					$section  = 'wp_weixin_' . $section_name . '_section';
					$args     = array(
						'class' => 'wp_weixin-' . $section_name . '-section wp_weixin-' . $field['id'] . '-field',
					);

					add_settings_field( $id, $title, $callback, $page, $section, $args );
				}
			}
		}
	}

	public function wp_weixin_options_page() {
		$custom_transfer = self::get_option( 'custom_transfer' );

		include WP_WEIXIN_PLUGIN_PATH . 'inc/templates/admin/wp-weixin-settings.php';
	}

	public function add_query_vars( $vars ) {
		$vars[] = 'hash';

		return $vars;
	}

	public function add_endpoints() {
		do_action( 'wp_weixin_endpoints' );
		add_rewrite_rule( '^wp-weixin/get-qrcode/hash/(.*)$', 'index.php?__wp_weixin_api=1&action=get-qrcode&hash=$matches[1]', 'top' );
	}

	public function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['__wp_weixin_api'] ) ) {
			$action = $wp->query_vars['action'];

			if ( 'get-qrcode' === $action ) {
				$hash   = isset( $wp->query_vars['hash'] ) ? $wp->query_vars['hash'] : false;
				$bundle = explode( '|', base64_decode( $hash ) ); // @codingStandardsIgnoreLine
				$url    = reset( $bundle );
				$nonce  = end( $bundle );

				if ( ! wp_verify_nonce( $nonce, 'qr_code' ) ) {

					exit();
				}

				self::output_get_qrcode( $url, 5, 2, QR_ECLEVEL_L );
			}
		}
	}

	public static function get_qrcode( $url ) {
		self::output_get_qrcode( $url, 5, 2, QR_ECLEVEL_L );
	}

	public function get_qr_hash() {
		$amount           = filter_input( INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
		$fixed            = filter_input( INPUT_POST, 'fixed', FILTER_VALIDATE_BOOLEAN );
		$product_name     = filter_input( INPUT_POST, 'productName', FILTER_SANITIZE_STRING );
		$url              = filter_input( INPUT_POST, 'url', FILTER_VALIDATE_URL );
		$base_payment_url = site_url( 'wp-weixin-pay/transfer/' );
		$hash             = false;

		if ( ! $amount && $fixed ) {
			$fixed = false;
		} elseif ( $amount ) {

			if ( $product_name ) {
				$product_name = '&note=' . $product_name;
			}

			$fixed  = ( $fixed ) ? '&fixed=1' : '';
			$amount = '?amount=' . $amount;
			$hash   = base64_encode( $base_payment_url . $amount . $product_name . $fixed . '|' . wp_create_nonce( 'qr_code' ) ); // @codingStandardsIgnoreLine
		} elseif ( $url ) {

			if ( $product_name ) {
				$product_name = '?note=' . $product_name;
			}

			$hash = base64_encode( $url . $product_name . '|' . wp_create_nonce( 'qr_code' ) ); // @codingStandardsIgnoreLine
		}

		if ( $hash ) {
			wp_send_json_success( $hash );
		} else {
			$error = new WP_Error( 'WP_Weixin_Settings::get_qr', __( 'Invalid parameters', 'wp-weixin' ) );

			wp_send_json_error( $error );
		}

		wp_die();
	}

	public function init_settings_definition() {
		$this->build_settings_fields();
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function build_settings_fields() {
		global $sitepress;

		$jsapi_urls       = array();
		$notify           = '';
		$custom_transfer  = self::get_option( 'custom_transfer' );
		$default_language = '';

		if ( $custom_transfer ) {
			$jsapi_urls[] = strtok( site_url( 'wp-weixin-pay/transfer/' ), '?' );

			if ( $sitepress && ( WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY === (int) $sitepress->get_setting( 'language_negotiation_type' ) ) ) {
				$languages        = apply_filters( 'wpml_active_languages', null, '' );
				$default_language = apply_filters( 'wpml_default_language', null );

				foreach ( $languages as $code => $language ) {

					if ( $default_language !== $code ) {
						$jsapi_urls[] = strtok( site_url( $code . '/wp-weixin-pay/transfer/' ), '?' );
					}
				}
			}
		}

		if ( is_plugin_active( 'woo-wechatpay/woo-wechatpay.php' ) && function_exists( 'wc_get_endpoint_url' ) ) {
			$checkout_url = strtok( site_url( wc_get_endpoint_url( 'checkout' ) ), '?' );
			$jsapi_urls[] = $checkout_url;

			if ( $sitepress ) {
				$page_id      = wc_get_page_id( 'checkout' );
				$trid         = $sitepress->get_element_trid( $page_id );
				$translations = ( $trid ) ? $sitepress->get_element_translations( $trid, 'post_post', true ) : false;

				if ( ! empty( $translations ) ) {

					foreach ( $translations as $key => $translation ) {

						if ( $translation->language_code !== $default_language ) {
							$url = urldecode( strtok( get_permalink( absint( $translation->element_id ) ), '?' ) );

							if ( $url !== $checkout_url ) {
								$jsapi_urls[] = $url;
							}
						}
					}
				}
			}

			$jsapi_urls[] = site_url( 'wxpayagain/' );
		}

		$jsapi_urls = apply_filters( 'wp_weixin_jsapi_urls', $jsapi_urls );

		$ecommerce_description  = __( 'Settings to use with a WeChat Service Account.', 'wp-weixin' );
		$ecommerce_description .= '<br/>';
		// translators: %s is "permalinks structure" with link to admin page
		$ecommerce_description .= '<span style="font-weight: bold;">' . sprintf( __( 'Make sure the %s ends with a forward slash ("/") to ensure compatibility with WeChat Pay API.', 'wp-weixin' ), '<a href="' . admin_url( 'options-permalink.php' ) . '" target="_blank">permalinks structure</a>' ) . '</span><br/>';
		// translators: %s is backend URL
		$ecommerce_description .= sprintf( __( 'The URLs in the merchant platform backend at %s should be configured as follows:', 'wp-weixin' ), '<a href="https://pay.weixin.qq.com/index.php/extend/pay_setting" target="_blank">https://pay.weixin.qq.com/index.php/extend/pay_setting</a>' );
		$ecommerce_description .= '<br/>';

		if ( ! empty( $jsapi_urls ) ) {
			$ecommerce_description .= '<br/>';
			// translators: CN is JSAPI支付授权目录, %1$d is the number of URLs
			$ecommerce_description .= '<strong>' . sprintf( __( 'JSAPI Payment Authorization URLs (max. %1$d URLs allowed):', 'wp-weixin' ), self::MAX_JSAPI_URLS ) . '</strong>';
			$ecommerce_description .= '<ul>';

			foreach ( $jsapi_urls as $url ) {
				$ecommerce_description .= '<li>' . $url . '</li>';
			}

			$count_jsapi_urls = count( $jsapi_urls );

			$ecommerce_description .= '</ul>';

			if ( self::MAX_JSAPI_URLS === $count_jsapi_urls ) {
				// translators: %1$d is the number of URLs
				$ecommerce_description .= '<span style="font-weight: bold;">' . sprintf( __( 'Warning: Maximum amount of URLs reached. With the current settings, %1$d URLs need to be registered in the merchant platform backend.', 'wp-weixin' ), $count_jsapi_urls ) . '</span>';
				$ecommerce_description .= '<br/>';

				if ( $sitepress && ( WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY === (int) $sitepress->get_setting( 'language_negotiation_type' ) ) ) {
					$ecommerce_description .= __( 'To free up slots if needed by other services, it is recommended to use the "Language name added as a parameter" option for the "Language URL format" setting in WPML.', 'wp-weixin' );
					$ecommerce_description .= '<br/>';
				}
			}

			if ( $count_jsapi_urls > self::MAX_JSAPI_URLS ) {
				// translators: %1$d is the number of URLs
				$ecommerce_description .= '<span style="color: red; font-weight: bold;">' . sprintf( __( 'With the current settings, it is not possible to register the %1$d URLs in the merchant platform backend.', 'wp-weixin' ), $count_jsapi_urls ) . '</span>';
				$ecommerce_description .= '<br/>';

				if ( $sitepress && ( WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY === (int) $sitepress->get_setting( 'language_negotiation_type' ) ) ) {
					// translators: %1$d is the number of URLs
					$ecommerce_description .= sprintf( __( 'To free up slots and make sure the maximum amount of %1$d URLs is not exceeded, use the "Language name added as a parameter" option for the "Language URL format" setting in WPML.', 'wp-weixin' ), $count_jsapi_urls );
					$ecommerce_description .= '<br/>';
				}
			}

			if ( $sitepress && ( WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN === (int) $sitepress->get_setting( 'language_negotiation_type' ) ) ) {
				$ecommerce_description     .= '<span style="color: red; font-weight: bold;">' . sprintf( __( 'Multiple domains detected. With the current WPML configuration, WP Weixin will work only with the language of the main domain registered in the WeChat backends.', 'wp-weixin' ), $count_jsapi_urls ) . '</span>';
				$ecommerce_description     .= '<br/>';
				$ecommerce_description     .= __( 'Please select one of the "Language name added as a parameter" or "Different languages in directories" options for the "Language URL format" setting in WPML.', 'wp-weixin' );
					$ecommerce_description .= '<br/>';
			}
		}

		if ( is_plugin_active( 'woo-wechatpay/woo-wechatpay.php' ) ) {
			$notify = 'wc-api/WC_WechatPay/';
		} elseif ( $custom_transfer ) {
			$notify = 'wp-weixin-pay/notify/';
		}

		$ecommerce_description .= '<br/>';
		// translators: CN is 扫码回调链接
		$ecommerce_description .= '<strong>' . __( 'QR Payment callback URL: ', 'wp-weixin' ) . '</strong>';
		$ecommerce_description .= '<ul>';
		$ecommerce_description .= '<li>' . site_url( $notify ) . '</li>';
		$ecommerce_description .= '</ul>';

		$this->settings_fields = array(
			'main'      => array(
				array(
					'id'    => 'enabled',
					'label' => __( 'Enable', 'wp-weixin' ),
					'type'  => 'checkbox',
					'class' => '',
					'help'  => __( 'Enable WP Weixin - requires a valid configuration.', 'wp-weixin' ),
				),
				array(
					'id'    => 'appid',
					'label' => __( 'WeChat App ID', 'wp-weixin' ),
					'type'  => 'text',
					'class' => 'regular-text',
					'help'  => __( 'The AppId in the backend at <a href="https://mp.weixin.qq.com" target="_blank">https://mp.weixin.qq.com/</a> under Development > Basic configuration.', 'wp-weixin' ),
				),
				array(
					'id'    => 'secret',
					'label' => __( 'WeChat App Secret', 'wp-weixin' ),
					'type'  => 'text',
					'class' => 'regular-text',
					'help'  => __( 'The AppSecret in the backend at <a href="https://mp.weixin.qq.com" target="_blank">https://mp.weixin.qq.com/</a> under Development > Basic configuration.', 'wp-weixin' ),
				),
				array(
					'id'    => 'name',
					'label' => __( 'WeChat OA Name', 'wp-weixin' ),
					'type'  => 'text',
					'class' => 'regular-text',
					'help'  => __( 'The name of the Official Account - you may enter any value, it is recommended to enter the actual name of the Official Account.', 'wp-weixin' ),
				),
				array(
					'id'    => 'logo_url',
					'label' => __( 'WeChat OA Logo URL', 'wp-weixin' ),
					'type'  => 'text',
					'class' => 'regular-text',
					'help'  => __( 'A URL to the logo of the Official Account - you may enter any image URL, it is recommended to enter a URL of the actual square logo of the Official Account (external or from the Media Library).', 'wp-weixin' ),
				),
				array(
					'id'    => 'enable_auth',
					'label' => __( 'Enable WeChat mobile authentication', 'wp-weixin' ),
					'type'  => 'checkbox',
					'class' => '',
					'help'  => __( 'If enabled, users will be authenticated with their WeChat account.<br/>An account will be created in Wordpress with their openID if they do not have one already.<br/>If disabled, users will simply be identified with a cookie using their WeChat public information during their session, but not authenticated in Wordpress.', 'wp-weixin' ),
				),
				array(
					'id'    => 'force_wechat',
					'label' => __( 'Force WeChat mobile', 'wp-weixin' ),
					'type'  => 'checkbox',
					'class' => '',
					'help'  => __( 'Make the website accessible only through the WeChat browser (except administrators and admin interface).', 'wp-weixin' ),
				),
				array(
					'id'    => 'force_follower',
					'label' => __( 'Force follow (any page)', 'wp-weixin' ),
					'type'  => 'checkbox',
					'class' => '',
					'help'  => __( 'Require the user to follow the Official Account before accessing the site with the WeChat browser (except administrators and admin interface).', 'wp-weixin' ),
				),
				array(
					'id'    => 'follow_welcome',
					'label' => __( 'Send welcome message', 'wp-weixin' ),
					'type'  => 'checkbox',
					'class' => '',
					'help'  => __( 'Send a welcome message when a user follows the Official Account. Requires using the WeChat Responder.', 'wp-weixin' ),
				),
				array(
					'id'    => 'welcome_image_url',
					'label' => __( 'Welcome message image URL', 'wp-weixin' ),
					'type'  => 'text',
					'class' => 'regular-text',
					// translators: %1$s is the default welcom image link
					'help'  => sprintf( __( 'A URL to the image used for the welcome message sent after a user follows the Official Account (external or from the Media Library).<br/>Default is %1$s', 'wp-weixin' ), '<a href="' . WP_WEIXIN_PLUGIN_URL . 'images/default-welcome.png">' . WP_WEIXIN_PLUGIN_URL . 'images/default-welcome.png</a>' ),
				),
				'title'       => __( 'Main Settings', 'wp-weixin' ),
				'description' => __( 'Minimal required configuration to enable WP Weixin: "WeChat App ID", "WeChat App Secret".', 'wp-weixin' ),
			),
			'responder' => array(
				array(
					'id'    => 'responder',
					'label' => __( 'Use WeChat Responder', 'wp-weixin' ),
					'type'  => 'checkbox',
					'class' => '',
					// translators: %1s is site_url( '/weixin-responder', 'https' )
					'help'  => sprintf( __( 'Allow the website to receive messages from the WeChat API and respond to them.<br/>Server configuration must be enabled and URL must be set to "%1$s" in <a href="https://mp.weixin.qq.com" target="_blank">https://mp.weixin.qq.com/</a> under Development > Basic configuration.<br/>Required if using "Force follow" option.', 'wp-weixin' ), site_url( '/weixin-responder', 'https' ) ),
				),
				array(
					'id'    => 'token',
					'label' => __( 'WeChat Token', 'wp-weixin' ),
					'type'  => 'text',
					'class' => 'regular-text',
					'help'  => __( 'The Token in the backend at <a href="https://mp.weixin.qq.com" target="_blank">https://mp.weixin.qq.com/</a> under Development > Basic configuration.', 'wp-weixin' ),
				),
				array(
					'id'    => 'encode',
					'label' => __( 'Encode messages', 'wp-weixin' ),
					'type'  => 'checkbox',
					'class' => '',
					'help'  => __( 'Encode the communication between the website and the WeChat API (recommended).', 'wp-weixin' ),
				),
				array(
					'id'    => 'aeskey',
					'label' => __( 'WeChat AES Key', 'wp-weixin' ),
					'type'  => 'text',
					'class' => 'regular-text',
					'help'  => __( 'The EncodingAESKey in the backend at <a href="https://mp.weixin.qq.com" target="_blank">https://mp.weixin.qq.com/</a> under Development > Basic configuration.', 'wp-weixin' ),
				),
				'title'       => __( 'WeChat Responder Settings', 'wp-weixin' ),
				'description' => __( 'Settings for the website to interact with the WeChat API.<br/>Enabling the Responder will remove the Official Account menu set in <a href="https://pay.weixin.qq.com" target="_blank">https://mp.weixin.qq.com/</a>, and the Official Account menu can be set with "Wechat menu (WeChat Official Account menu)" in Appearance > Menu in WordPress.', 'wp-weixin' ),
			),
			'ecommerce' => array(
				array(
					'id'    => 'ecommerce',
					'label' => __( 'Use merchant platform', 'wp-weixin' ),
					'type'  => 'checkbox',
					'class' => '',
					'help'  => __( 'Allow users to send money to the Service Account with WeChat - an account at <a href="https://pay.weixin.qq.com" target="_blank">https://pay.weixin.qq.com/</a> is necessary.', 'wp-weixin' ),
				),
				array(
					'id'    => 'custom_transfer',
					'label' => __( 'Custom amount transfer', 'wp-weixin' ),
					'type'  => 'checkbox',
					'class' => '',
					'help'  => __( 'Allow users to transfer custom amounts and admins to create payment QR codes.', 'wp-weixin' ),
				),
				array(
					'id'    => 'ecommerce_force_follower',
					'label' => __( 'Force follow (user account and checkout pages)', 'wp-weixin' ),
					'type'  => 'checkbox',
					'class' => '',
					'help'  => __( 'Require the user to follow the Official Account before accessing the checkout and user account pages with the WeChat browser (except administrators and admin interface).', 'wp-weixin' ),
				),
				array(
					'id'    => 'mch_appid',
					'label' => __( 'WeChat Merchant App ID', 'wp-weixin' ),
					'type'  => 'text',
					'class' => 'regular-text',
					'help'  => __( 'The AppID in the backend at <a href="https://pay.weixin.qq.com" target="_blank">https://pay.weixin.qq.com/</a> - can be different from the WeChat App ID as the WeChat Pay account may be linked to a different AppID. Leave empty to use the WeChat App ID.', 'wp-weixin' ),
				),
				array(
					'id'    => 'mch_id',
					'label' => __( 'WeChat Merchant ID', 'wp-weixin' ),
					'type'  => 'text',
					'class' => 'regular-text',
					'help'  => __( 'The Merchant ID in the backend at <a href="https://pay.weixin.qq.com/index.php/extend/pay_setting" target="_blank">https://pay.weixin.qq.com/index.php/extend/pay_setting</a>.', 'wp-weixin' ),
				),
				array(
					'id'    => 'mch_key',
					'label' => __( 'WeChat Merchant Key', 'wp-weixin' ),
					'type'  => 'text',
					'class' => 'regular-text',
					'help'  => __( 'The Merchant Key in the backend at <a href="https://pay.weixin.qq.com/index.php/core/cert/api_cert" target="_blank">https://pay.weixin.qq.com/index.php/core/cert/api_cert</a>.', 'wp-weixin' ),
				),
				array(
					'id'    => 'pem',
					'label' => __( 'PEM certificate prefix', 'wp-weixin' ),
					'type'  => 'text',
					'class' => 'regular-text',
					'help'  => __( 'The prefix of the certificate files downloaded from <a href="https://pay.weixin.qq.com/index.php/extend/pay_setting" target="_blank">https://pay.weixin.qq.com/index.php/core/cert/api_cert</a> - certificate files default prefix is <code>apiclient</code> (for <code>apiclient_cert.pem</code> and <code>apiclient_key.pem</code> files). Required notably to handle refunds through WeChat Pay.', 'wp-weixin' ),
				),
				array(
					'id'    => 'pem_path',
					'label' => __( 'PEM certificate files path', 'wp-weixin' ),
					'type'  => 'text',
					'class' => 'regular-text',
					'help'  => __( 'The absolute path to the containing folder of the certificate files downloaded from <a href="https://pay.weixin.qq.com/index.php/core/cert/api_cert" target="_blank">https://pay.weixin.qq.com/index.php/core/cert/api_cert</a> on the current file system. Example: <code>/home/user/wechat-certificates</code>. Must have read permissions for the user running PHP, and located outside of the web root. Required notably to handle refunds through WeChat Pay.', 'wp-weixin' ),
				),
				'title'       => __( 'WeChat Pay Settings', 'wp-weixin' ),
				'description' => $ecommerce_description,
			),
			'proxy'     => array(
				array(
					'id'    => 'proxy',
					'label' => __( 'Use a proxy', 'wp-weixin' ),
					'type'  => 'checkbox',
					'class' => '',
				),
				array(
					'id'    => 'proxy_host',
					'label' => __( 'Proxy Host', 'wp-weixin' ),
					'type'  => 'text',
					'class' => 'regular-text',
				),
				array(
					'id'    => 'proxy_port',
					'label' => __( 'Proxy Port', 'wp-weixin' ),
					'type'  => 'text',
					'class' => 'regular-text',
				),
				'title'       => __( 'Proxy Settings', 'wp-weixin' ),
				'description' => __( 'A proxy may be needed if Wordpress is behind a firewall or within a company network.', 'wp-weixin' ),
			),
			'misc'      => array(
				array(
					'id'    => 'alter_userscreen',
					'label' => __( 'Show WeChat name and pictures in Users list page', 'wp-weixin' ),
					'type'  => 'checkbox',
					'class' => '',
					'help'  => __( 'Instead of the default Wordpress account names and avatars', 'wp-weixin' ),
				),
				array(
					'id'    => 'lang_aware_menu',
					'label' => __( 'Official Account menu language awareness', 'wp-weixin' ),
					'type'  => 'checkbox',
					'class' => '',
					'help'  => __( 'Customise the menu of the Official Account depending on user\'s language.<br/>By default, the language of the menu corresponding to the website\'s default language is used.', 'wp-weixin' ),
				),
				array(
					'id'    => 'custom_token_persistence',
					'label' => __( 'Use custom persistence for access_token', 'wp-weixin' ),
					'type'  => 'checkbox',
					'class' => '',
					'help'  => __( 'Use a custom persistence method for the Official Account access_token and its expiry timestamp.<br>Warning - requires the implementation of:<br>- <code>add_filter(\'wp_weixin_get_access_info\', $access_info, 10, 0);</code><br>- <code>add_action(\'wp_weixin_save_access_info\', $access_info, 10, 1);</code><br/>The parameter <code>$access_info</code> is an array with the keys <code>token</code> and <code>expiry</code>.<br/>Add the hooks above in a <code>plugins_loaded</code> action with a priority of <code>4</code> or less.<br/>Useful to avoid a race condition if the access_token information need to be shared between multiple platforms.<br>When unchecked, access_token &amp; expiry timestamp are stored in the Wordpress options table in the database.', 'wp-weixin' ),
				),
				'title'       => __( 'Miscellaneous Settings', 'wp-weixin' ),
				'description' => __( 'Other configuration values.', 'wp-weixin' ),
			),
		);

		$this->settings_fields = apply_filters( 'wp_weixin_settings_fields', $this->settings_fields );

		foreach ( $this->settings_fields as $section_name => $section ) {

			foreach ( $section as $index => $value ) {
				$include_setting = true;

				if ( 'ecommerce' === $section_name && is_numeric( $index ) ) {

					if (
						( 0 === $index && is_plugin_active( 'woo-wechatpay/woo-wechatpay.php' ) ) ||
						( 2 === $index && ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) ||
						( 1 === $index && ! is_plugin_active( 'wp-weixin-pay/wp-weixin-pay.php' ) ) ||
						( ! is_plugin_active( 'wp-weixin-pay/wp-weixin-pay.php' ) && ! is_plugin_active( 'woo-wechatpay/woo-wechatpay.php' ) )
					) {
						$include_setting = false;
					}
				}

				$include_setting = apply_filters( 'wp_weixin_show_setting', $include_setting, $section_name, $index, $value );

				if ( ! $include_setting ) {
					unset( $this->settings_fields[ $section_name ][ $index ] );
				}
			}

			if ( 2 === count( $this->settings_fields[ $section_name ] ) ) {
				unset( $this->settings_fields[ $section_name ] );
			}
		}
	}

	protected function get_input_text_option( $key, $class ) {
		$class  = empty( $class ) ? ' ' : ' class="' . $class . '" ';
		$input  = '<input type="text" name="wp_weixin_settings[wp_weixin_' . $key . ']" value="';
		$input .= isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : '';
		$input .= '"' . $class . '>';

		return $input;
	}

	protected function get_input_checkbox_option( $key, $class ) {
		$class  = empty( $class ) ? ' ' : ' class="' . $class . '" ';
		$input  = '<input type="checkbox" name="wp_weixin_settings[wp_weixin_' . $key . ']" value="1" ';
		$input .= ( isset( $this->settings[ $key ] ) && $this->settings[ $key ] ) ? 'checked' : '';
		$input .= $class . '>';

		return $input;
	}

	protected function field_render( $key ) {

		if ( $this->get_field_attr( $key, 'type' ) === 'text' ) {
			echo $this->get_input_text_option( $key, $this->get_field_attr( $key, 'class' ) ); // @codingStandardsIgnoreLine
		}

		if ( $this->get_field_attr( $key, 'type' ) === 'checkbox' ) {
			echo $this->get_input_checkbox_option( $key, $this->get_field_attr( $key, 'class' ) ); // @codingStandardsIgnoreLine
		}

		if ( $this->get_field_attr( $key, 'help' ) ) {
			echo '<p class="description">' . $this->get_field_attr( $key, 'help' ) . '</p>'; // @codingStandardsIgnoreLine
		}
	}

	protected function section_render( $key ) {
		echo $this->settings_fields[ $key ]['description']; // @codingStandardsIgnoreLine
	}

	protected function get_field_attr( $key, $attr ) {

		foreach ( $this->settings_fields as $section ) {

			foreach ( $section as $field ) {

				if ( is_array( $field ) && $field['id'] === $key && isset( $field[ $attr ] ) ) {

					return $field[ $attr ];
				}
			}
		}

		return false;
	}

	protected static function output_get_qrcode( $url, $size = 4, $margin = 4, $quality = QR_ECLEVEL_H ) {

		if ( ! empty( $url ) ) {
			ob_start();

			QRCode::png( $url, null, $quality, $size, $margin );
			$qr = ob_get_contents();

			ob_end_clean();

			$qr = imagecreatefromstring( $qr );

			header( 'Content-type: image/png' );
			imagepng( $qr );
			imagedestroy( $qr );
		}

		exit();
	}
}
