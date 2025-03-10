<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Metabox {
	protected $wechat;

	public function __construct( $wechat, $init_hooks = false ) {
		$this->wechat = $wechat;

		if ( $init_hooks ) {
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 0 );
			add_action( 'save_post', array( $this, 'wechat_link_save' ), 10, 1 );
		}
	}

	public static function get_meta( $value, $post = null ) {

		if ( ! $post ) {
			global $post;
		}

		$field = get_post_meta( $post->ID, $value, true );

		if ( ! empty( $field ) ) {

			return is_array( $field ) ? stripslashes_deep( $field ) : stripslashes( wp_kses_decode_entities( $field ) );
		} else {

			return false;
		}
	}

	public function add_meta_box() {
		$metaboxes = array(
			'wechat_link' => array(
				'title'      => __( 'Wechat Links', 'wp-weixin' ),
				'post_types' => apply_filters(
					'wp_weixin_metabox_post_types',
					array(
						'product',
						'page',
						'post',
					),
					'wechat_link'
				),
				'context'    => 'side',
				'callback'   => array( $this, 'wechat_link_metabox' ),
				'priority'   => 'default',
			),
		);

		foreach ( $metaboxes as $id => $metabox ) {
			add_meta_box(
				$id,
				$metabox['title'],
				$metabox['callback'],
				$metabox['post_types'],
				$metabox['context'],
				$metabox['priority']
			);
		}
	}

	public function wechat_link_save( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {

			return;
		}

		if (
			! isset( $_POST['wechat_link_nonce'] ) ||
			! wp_verify_nonce( $_POST['wechat_link_nonce'], '_wechat_link_nonce' )
		) {

			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {

			return;
		}

		if ( isset( $_POST['wechat_link_title'] ) ) {
			update_post_meta( $post_id, 'wechat_link_title', esc_attr( $_POST['wechat_link_title'] ) );
		}

		if ( isset( $_POST['wechat_link_description'] ) ) {
			update_post_meta( $post_id, 'wechat_link_description', esc_attr( $_POST['wechat_link_description'] ) );
		}

		if ( isset( $_POST['wechat_link_thumb_url'] ) ) {
			update_post_meta( $post_id, 'wechat_link_thumb_url', esc_attr( $_POST['wechat_link_thumb_url'] ) );
		}
	}

	public function wechat_link_metabox( $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		ob_start();

		$title       = self::get_meta( 'wechat_link_title' );
		$description = self::get_meta( 'wechat_link_description' );
		$thumb_url   = self::get_meta( 'wechat_link_thumb_url' );

		require_once WP_WEIXIN_PLUGIN_PATH . 'inc/templates/admin/wechat-link-metabox.php';

		$html = ob_get_clean();

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
