<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Menu {

	protected $wechat;
	protected $published;

	public function __construct( $wechat, $init_hooks = false ) {
		$this->wechat = $wechat;

		if ( $init_hooks ) {
			// Add WeChat Menu Item metabox
			add_action( 'admin_head-nav-menus.php', array( $this, 'add_meta_box' ), 10, 0 );
			// Add menu location for WeChat
			add_action( 'after_setup_theme', array( $this, 'add_menu_location' ), 10, 0 );
			// Publish the menu to WeChat on save
			add_action( 'wp_update_nav_menu', array( $this, 'publish' ), 10, 2 );
			// Add admin scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ), 99, 1 );
			// Add callback when adding a menu item: handle WeChat Menu Item menu type
			add_action( 'wp_ajax_add_wechat_menu_item', array( $this, 'wp_ajax_add_menu_item' ), 10, 0 );
			// Add save logic when adding a menu item: handle WeChat Menu Item menu type
			add_action( 'wpupdate_nav_menu_item', array( $this, 'update_nav_menu_item' ), 0, 3 );

			// Use a custom Walker class to handle WeChat Menu Item menu type
			add_filter( 'wp_edit_nav_menu_walker', array( $this, 'alter_menu_walker' ), 1, 2 );
			// Make sure the key value is saved when saving a WeChat event button menu item
			add_filter( 'update_post_metadata', array( $this, 'save_wechat_event_key' ), 10, 5 );
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public function add_admin_scripts( $hook ) {

		if ( 'nav-menus.php' === $hook ) {
			$debug   = apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) );
			$js_ext  = ( $debug ) ? '.js' : '.min.js';
			$version = filemtime( WP_WEIXIN_PLUGIN_PATH . 'js/admin/menu' . $js_ext );

			$parameters = array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'debug'    => $debug,
			);

			wp_enqueue_script(
				'wp-weixin-menu-script',
				WP_WEIXIN_PLUGIN_URL . 'js/admin/menu' . $js_ext,
				array( 'jquery' ),
				$version,
				true
			);
			wp_localize_script( 'wp-weixin-menu-script', 'WpWeixin', $parameters );
		}
	}

	public function add_menu_location() {
		register_nav_menu( 'weixin_oa_menu_location', __( 'WeChat Official Account menu', 'wp-weixin' ) );
	}

	public function alter_menu_walker( $walker_class_name, $menu_id ) {
		$theme_locations = get_nav_menu_locations();

		if ( isset( $theme_locations['weixin_oa_menu_location'] ) ) {

			$weixin_oa_menu = get_term( $theme_locations['weixin_oa_menu_location'], 'nav_menu' );

			if ( $weixin_oa_menu && absint( $menu_id ) === $weixin_oa_menu->term_id ) {
				$walker_class_name = 'Walker_Nav_Menu_Wechat_Edit';
			}
		}

		return $walker_class_name;
	}

	public function publish( $menu_id, $menu_data = array() ) {
		global $sitepress;

		$theme_locations = get_nav_menu_locations();

		if ( ! isset( $theme_locations['weixin_oa_menu_location'] ) ) {

			return;
		}

		$weixin_oa_menu = get_term( $theme_locations['weixin_oa_menu_location'], 'nav_menu' );
		$lang_rule      = false;

		if ( $weixin_oa_menu && $sitepress && WP_Weixin_Settings::get_option( 'lang_aware_menu' ) ) {
			$menu_lang = apply_filters( 'wpml_element_language_code', null, array(
				'element_id'   => (int) $weixin_oa_menu->term_id,
				'element_type' => 'nav_menu',
			) );

			if ( $menu_lang !== $sitepress->get_default_language() ) {
				$lang_rule = $sitepress->get_locale_from_language_code( $menu_lang );
			}
		}

		if ( $weixin_oa_menu && $weixin_oa_menu->term_id === $menu_id ) {
			$items = wp_get_nav_menu_items( $menu_id );
			$menus = array();

			foreach ( $items as $item_index => $item ) {

				if ( 'wechat' === $item->type ) {

					if ( absint( $item->menu_item_parent ) === 0 ) {
						$button = array(
							'temp_index' => $item->ID,
							'name'       => $item->title,
							'key'        => $item->url,
							'type'       => $item->attr_title,
							'sub_button' => array(),
						);

						if ( count( $menus ) < 3 ) {
							$menus[] = $button;
						}
					} else {

						foreach ( $menus as $menu_index => $parent_button ) {

							if ( absint( $item->menu_item_parent ) === $parent_button['temp_index'] ) {
								$button = array(
									'name' => $item->title,
									'key'  => $item->url,
									'type' => $item->attr_title,
								);

								if ( count( $menus[ $menu_index ]['sub_button'] ) < 5 ) {
									$menus[ $menu_index ]['sub_button'][] = $button;
								}
							}
						}
					}
				} elseif ( absint( $item->menu_item_parent ) === 0 ) {
					$button = array(
						'temp_index' => $item->ID,
						'name'       => $item->title,
						'url'        => $item->url,
						'type'       => 'view',
						'sub_button' => array(),
					);

					if ( count( $menus ) < 3 ) {
						$menus[] = $button;
					}
				} else {

					foreach ( $menus as $menu_index => $parent_button ) {

						if ( absint( $item->menu_item_parent ) === $parent_button['temp_index'] ) {
							$button = array(
								'name' => $item->title,
								'url'  => $item->url,
								'type' => 'view',
							);

							if ( count( $menus[ $menu_index ]['sub_button'] ) < 5 ) {
								$menus[ $menu_index ]['sub_button'][] = $button;
							}
						}
					}
				}
			}

			foreach ( $menus as $key => $button ) {
				unset( $menus[ $key ]['temp_index'] );

				if ( ! empty( $button['sub_button'] ) ) {
					unset( $menus[ $key ]['url'] );
					unset( $menus[ $key ]['type'] );
					unset( $menus[ $key ]['key'] );
				} else {
					unset( $menus[ $key ]['sub_button'] );
				}
			}

			if ( ! $lang_rule ) {
				$menus = array( 'button' => $menus );
			} else {
				$menus = array(
					'button'    => $menus,
					'matchrule' => array(
						'language' => $lang_rule,
					),
				);
			}

			if ( ! $this->published ) {
				$oa_menus = $this->wechat->menus();

				if ( $lang_rule ) {

					if ( isset( $oa_menus['conditionalmenu'] ) ) {

						foreach ( $oa_menus['conditionalmenu'] as $key => $menu ) {
							$condition = isset( $menu['matchrule'] ) && count( $menu['matchrule'] ) === 1;
							$condition = $condition && isset( $menu['matchrule']['language'] );
							$condition = $condition && $menu['matchrule']['language'] === $lang_rule;

							if ( $condition ) {
								$this->wechat->menu_delete( $menu['menuid'] );
							}
						}
					}
				}

				if ( apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) ) ) {
					WP_Weixin::log( $this->wechat->getError() );
				}

				if ( ! empty( $menus['button'] ) ) {

					if ( $this->wechat->menu_create( $menus, ( $lang_rule ) ) ) {
						$this->published = true;
					}
				} elseif ( ! $lang_rule ) {
					$this->wechat->menu_delete();
				}

				if ( apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) ) ) {
					WP_Weixin::log( $this->wechat->getError() );
				}
			}
		}
	}

	public function add_meta_box() {
		$menu_id         = absint( filter_input( INPUT_GET, 'menu', FILTER_SANITIZE_NUMBER_INT ) );
		$theme_locations = get_nav_menu_locations();

		if ( isset( $theme_locations['weixin_oa_menu_location'] ) ) {
			$weixin_oa_menu = get_term( $theme_locations['weixin_oa_menu_location'], 'nav_menu' );

			if ( $weixin_oa_menu && $weixin_oa_menu->term_id === $menu_id ) {
				add_meta_box(
					'add-wechat-links',
					__( 'WeChat Menu Item', 'wp-weixin' ),
					array( $this, 'nav_menu_item_wechat_meta_box' ),
					'nav-menus',
					'side',
					'default'
				);
			}
		}
	}

	public function nav_menu_item_wechat_meta_box() {
		global $_nav_menu_placeholder, $nav_menu_selected_id;

		$nav_menu_placeholder = ( 0 > $_nav_menu_placeholder ) ? $_nav_menu_placeholder - 1 : -1;

		ob_start();

		require WP_WEIXIN_PLUGIN_PATH . 'inc/templates/admin/menu-item-meta-box.php';

		echo ob_get_clean(); // WPCS: XSS ok
	}

	public function wp_ajax_add_menu_item() {
		check_ajax_referer( 'add-menu_item', 'menu-settings-column-nonce' );

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( -1 );
		}

		require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

		$menu_items_data = array();

		foreach ( (array) $_POST['menu-item'] as $menu_item_data ) {
			$menu_items_data[] = $menu_item_data;
		}

		$item_ids = $this->save_nav_menu_data( 0, $menu_items_data );

		if ( is_wp_error( $item_ids ) ) {

			wp_die( 0 );
		}

		$menu_items = array();

		foreach ( (array) $item_ids as $menu_item_id ) {
			$menu_obj = get_post( $menu_item_id );

			if ( ! empty( $menu_obj->ID ) ) {
				$menu_obj        = wp_setup_nav_menu_item( $menu_obj );
				$menu_obj->label = $menu_obj->title; // don't show "(pending)" in ajax-added items
				$menu_items[]    = $menu_obj;
			}
		}

		/** This filter is documented in wp-admin/includes/nav-menu.php */
		$walker_class_name = apply_filters( 'wp_edit_nav_menu_walker', 'Walker_Nav_Menu_Edit', $_POST['menu'] );

		if ( ! class_exists( $walker_class_name ) ) {
			wp_die( 0 );
		}

		if ( ! empty( $menu_items ) ) {
			$args = array(
				'after'       => '',
				'before'      => '',
				'link_after'  => '',
				'link_before' => '',
				'walker'      => new $walker_class_name(),
			);

			echo walk_nav_menu_tree( $menu_items, 0, (object) $args );
		}

		wp_die();
	}

	public function update_nav_menu_item( $menu_id, $menu_item_db_id, $args ) {

		if (
			'wechat' === $args['menu-item-type'] &&
			isset( $_POST['menu-item-url'], $_POST['menu-item-url'][ $menu_item_db_id ] ) // @codingStandardsIgnoreLine
		) {

			$args['menu-item-url'] = $_POST['menu-item-url'][ $menu_item_db_id ]; // @codingStandardsIgnoreLine

			update_post_meta( $menu_item_db_id, '_menu_item_url', $args['menu-item-url'] );
		}
	}

	public function save_wechat_event_key( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		$menu_item_post = get_post( $object_id );

		if ( 'nav_menu_item' !== $menu_item_post->post_type ) {

			return $check;
		}

		$is_wechat_menu = ( 'wechat' === get_post_meta( $object_id, '_menu_item_type', true ) );

		if ( $is_wechat_menu && '_menu_item_url' === $meta_key ) {
			$url_array = filter_input( INPUT_POST, 'menu-item-url', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

			if ( is_array( $url_array ) && isset( $url_array[ $object_id ] ) ) {
				$meta_value = filter_var( $url_array[ $object_id ], FILTER_SANITIZE_SPECIAL_CHARS );

				remove_filter( 'update_post_metadata', array( $this, 'save_wechat_event_key' ), 10, 5 );
				update_post_meta( $object_id, $meta_key, $meta_value, $prev_value );
				add_filter( 'update_post_metadata', array( $this, 'save_wechat_event_key' ), 10, 5 );

				return true;
			}
		}

		return $check;
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function save_nav_menu_data( $menu_id = 0, $menu_data = array() ) {
		$menu_id     = (int) $menu_id;
		$items_saved = array();

		if ( 0 === absint( $menu_id ) || is_nav_menu( $menu_id ) ) {

			foreach ( (array) $menu_data as $_possible_db_id => $_item_object_data ) {

				$is_custom = ( 'custom' === $_item_object_data['menu-item-type'] );
				$is_custom = $is_custom || ( 'wechat' === $_item_object_data['menu-item-type'] );
				$condition = ! isset( $_item_object_data['menu-item-type'] ) || 'wechat' === $_item_object_data['menu-item-type'];
				$condition = $condition && in_array( $_item_object_data['menu-item-url'], array( 'http://', '' ), true );
				$condition = $condition || ! ( $is_custom && ! isset( $_item_object_data['menu-item-db-id'] ) );
				$condition = $condition || ! empty( $_item_object_data['menu-item-db-id'] );

				if ( empty( $_item_object_data['menu-item-object-id'] ) && $condition ) {

					continue;
				}

				$condition = empty( $_item_object_data['menu-item-db-id'] ) || ( 0 > $_possible_db_id );
				$condition = $condition || absint( $_item_object_data['menu-item-db-id'] ) !== $_possible_db_id;

				if ( $condition ) {
					$_actual_db_id = 0;
				} else {
					$_actual_db_id = (int) $_item_object_data['menu-item-db-id'];
				}

				$args = array(
					'menu-item-db-id'       => ( isset( $_item_object_data['menu-item-db-id'] ) ? $_item_object_data['menu-item-db-id'] : '' ),
					'menu-item-object-id'   => ( isset( $_item_object_data['menu-item-object-id'] ) ? $_item_object_data['menu-item-object-id'] : '' ),
					'menu-item-object'      => ( isset( $_item_object_data['menu-item-object'] ) ? $_item_object_data['menu-item-object'] : '' ),
					'menu-item-parent-id'   => ( isset( $_item_object_data['menu-item-parent-id'] ) ? $_item_object_data['menu-item-parent-id'] : '' ),
					'menu-item-position'    => ( isset( $_item_object_data['menu-item-position'] ) ? $_item_object_data['menu-item-position'] : '' ),
					'menu-item-type'        => ( isset( $_item_object_data['menu-item-type'] ) ? $_item_object_data['menu-item-type'] : '' ),
					'menu-item-title'       => ( isset( $_item_object_data['menu-item-title'] ) ? $_item_object_data['menu-item-title'] : '' ),
					'menu-item-url'         => ( isset( $_item_object_data['menu-item-url'] ) ? $_item_object_data['menu-item-url'] : '' ),
					'menu-item-description' => ( isset( $_item_object_data['menu-item-description'] ) ? $_item_object_data['menu-item-description'] : '' ),
					'menu-item-attr-title'  => ( isset( $_item_object_data['menu-item-attr-title'] ) ? $_item_object_data['menu-item-attr-title'] : '' ),
					'menu-item-target'      => ( isset( $_item_object_data['menu-item-target'] ) ? $_item_object_data['menu-item-target'] : '' ),
					'menu-item-classes'     => ( isset( $_item_object_data['menu-item-classes'] ) ? $_item_object_data['menu-item-classes'] : '' ),
					'menu-item-xfn'         => ( isset( $_item_object_data['menu-item-xfn'] ) ? $_item_object_data['menu-item-xfn'] : '' ),
				);

				$items_saved[] = $this->save_nav_menu_item( $menu_id, $_actual_db_id, $args );
			}
		}

		return $items_saved;
	}

	protected function save_nav_menu_item( $menu_id = 0, $menu_item_db_id = 0, $menu_item_data = array() ) {
		$menu_id         = (int) $menu_id;
		$menu_item_db_id = (int) $menu_item_db_id;

		if ( ! empty( $menu_item_db_id ) && ! is_nav_menu_item( $menu_item_db_id ) ) {

			return new WP_Error( 'update_nav_menu_item_failed', __( 'The given object ID is not that of a menu item.' ) );
		}

		$menu = wp_get_nav_menu_object( $menu_id );

		if ( ! $menu && 0 !== $menu_id ) {

			return new WP_Error( 'invalid_menu_id', __( 'Invalid menu ID.' ) );
		}

		if ( is_wp_error( $menu ) ) {

			return $menu;
		}

		$defaults = array(
			'menu-item-db-id'       => $menu_item_db_id,
			'menu-item-object-id'   => 0,
			'menu-item-object'      => '',
			'menu-item-parent-id'   => 0,
			'menu-item-position'    => 0,
			'menu-item-type'        => 'custom',
			'menu-item-title'       => '',
			'menu-item-url'         => '',
			'menu-item-description' => '',
			'menu-item-attr-title'  => '',
			'menu-item-target'      => '',
			'menu-item-classes'     => '',
			'menu-item-xfn'         => '',
			'menu-item-status'      => '',
		);

		$args = wp_parse_args( $menu_item_data, $defaults );

		if ( 0 === absint( $menu_id ) ) {
			$args['menu-item-position'] = 1;
		} elseif ( 0 === (int) $args['menu-item-position'] ) {
			$menu_items                 = ( 0 === absint( $menu_id ) ) ? array() : (array) wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'publish,draft' ) );
			$last_item                  = array_pop( $menu_items );
			$args['menu-item-position'] = ( $last_item && isset( $last_item->menu_order ) ) ? 1 + $last_item->menu_order : count( $menu_items );
		}

		$original_parent = 0 < $menu_item_db_id ? get_post_field( 'post_parent', $menu_item_db_id ) : 0;

		if ( 'custom' !== $args['menu-item-type'] && 'wechat' !== $args['menu-item-type'] ) {
			$args['menu-item-url'] = '';
			$original_title        = '';

			if ( 'taxonomy' === $args['menu-item-type'] ) {
				$original_parent = get_term_field( 'parent', $args['menu-item-object-id'], $args['menu-item-object'], 'raw' );
				$original_title  = get_term_field( 'name', $args['menu-item-object-id'], $args['menu-item-object'], 'raw' );
			} elseif ( 'post_type' === $args['menu-item-type'] ) {
				$original_object = get_post( $args['menu-item-object-id'] );
				$original_parent = (int) $original_object->post_parent;
				$original_title  = $original_object->post_title;
			} elseif ( 'post_type_archive' === $args['menu-item-type'] ) {
				$original_object = get_post_type_object( $args['menu-item-object'] );

				if ( $original_object ) {
					$original_title = $original_object->labels->archives;
				}
			}

			if ( $args['menu-item-title'] === $original_title ) {
				$args['menu-item-title'] = '';
			}

			if ( '' === $args['menu-item-title'] && '' === $args['menu-item-description'] ) {
				$args['menu-item-description'] = ' ';
			}
		}

		// Populate the menu item object
		$post = array(
			'menu_order'   => $args['menu-item-position'],
			'ping_status'  => 0,
			'post_content' => $args['menu-item-description'],
			'post_excerpt' => $args['menu-item-attr-title'],
			'post_parent'  => $original_parent,
			'post_title'   => $args['menu-item-title'],
			'post_type'    => 'nav_menu_item',
		);

		$update = ( 0 !== absint( $menu_item_db_id ) );

		if ( ! $update ) {
			$post['ID']          = 0;
			$post['post_status'] = 'publish' === $args['menu-item-status'] ? 'publish' : 'draft';
			$menu_item_db_id     = wp_insert_post( $post );

			if ( ! $menu_item_db_id || is_wp_error( $menu_item_db_id ) ) {

				return $menu_item_db_id;
			}

			do_action( 'wp_add_nav_menu_item', $menu_id, $menu_item_db_id, $args );
		}

		if ( $menu_id && ( ! $update || ! is_object_in_term( $menu_item_db_id, 'nav_menu', (int) $menu->term_id ) ) ) {
			wp_set_object_terms( $menu_item_db_id, array( $menu->term_id ), 'nav_menu' );
		}

		if ( 'custom' === $args['menu-item-type'] ) {
			$args['menu-item-object-id'] = $menu_item_db_id;
			$args['menu-item-object']    = 'custom';
		}

		if ( 'wechat' === $args['menu-item-type'] ) {
			$args['menu-item-object-id'] = $menu_item_db_id;
			$args['menu-item-object']    = 'wechat';
		}

		$menu_item_db_id = (int) $menu_item_db_id;

		update_post_meta( $menu_item_db_id, '_menu_item_type', sanitize_key( $args['menu-item-type'] ) );
		update_post_meta( $menu_item_db_id, '_menu_item_menu_item_parent', strval( (int) $args['menu-item-parent-id'] ) );
		update_post_meta( $menu_item_db_id, '_menu_item_object_id', strval( (int) $args['menu-item-object-id'] ) );
		update_post_meta( $menu_item_db_id, '_menu_item_object', sanitize_key( $args['menu-item-object'] ) );

		if ( 'wechat' !== $args['menu-item-type'] ) {
			update_post_meta( $menu_item_db_id, '_menu_item_target', sanitize_key( $args['menu-item-target'] ) );
		} else {
			update_post_meta( $menu_item_db_id, '_menu_item_target', $args['menu-item-target'] );
		}

		$args['menu-item-classes'] = array_map( 'sanitize_html_class', explode( ' ', $args['menu-item-classes'] ) );
		$args['menu-item-xfn']     = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['menu-item-xfn'] ) ) );

		update_post_meta( $menu_item_db_id, '_menu_item_classes', $args['menu-item-classes'] );
		update_post_meta( $menu_item_db_id, '_menu_item_xfn', $args['menu-item-xfn'] );

		if ( 'wechat' !== $args['menu-item-type'] ) {
			update_post_meta( $menu_item_db_id, '_menu_item_url', esc_url_raw( $args['menu-item-url'] ) );
		} else {
			update_post_meta( $menu_item_db_id, '_menu_item_url', $args['menu-item-url'] );
		}

		if ( 0 === absint( $menu_id ) ) {
			update_post_meta( $menu_item_db_id, '_menu_item_orphaned', (string) time() );
		} elseif ( get_post_meta( $menu_item_db_id, '_menu_item_orphaned' ) ) {
			delete_post_meta( $menu_item_db_id, '_menu_item_orphaned' );
		}

		if ( $update ) {
			$post['ID']          = $menu_item_db_id;
			$post['post_status'] = ( 'draft' === $args['menu-item-status'] ) ? 'draft' : 'publish';

			wp_update_post( $post );
		}

		do_action( 'wpupdate_nav_menu_item', $menu_id, $menu_item_db_id, $args );

		return $menu_item_db_id;
	}
}
