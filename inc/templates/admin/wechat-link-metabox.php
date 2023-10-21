<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

wp_nonce_field( '_wechat_link_nonce', 'wechat_link_nonce' );
?>
<p class="howto">
	<?php esc_html_e( 'Override the link title, description and thumbnail when sharing the Post on Wechat.', 'wp-weixin' ); ?>
</p>
<p>
	<label for="wechat_link_title"><?php esc_html_e( 'Title', 'wp-weixin' ); ?></label><br>
	<input type="text" name="wechat_link_title" id="wechat_link_title" value="<?php echo esc_html( $title ); ?>"><br/>
	<span class="howto"><?php esc_html_e( 'Leave empty to use the Post title.', 'wp-weixin' ); ?></span>
</p>
<p>
	<label for="wechat_link_description"><?php esc_html_e( 'Description', 'wp-weixin' ); ?></label><br>
	<textarea name="wechat_link_description" id="wechat_link_description" ><?php echo esc_html( $description ); ?></textarea><br/>
	<span class="howto"><?php esc_html_e( 'Leave empty to use the Post excerpt.', 'wp-weixin' ); ?></span>
</p>
<p>
	<label for="wechat_link_thumb_url"><?php esc_html_e( 'Thumbnail URL', 'wp-weixin' ); ?></label><br>
	<input type="text" name="wechat_link_thumb_url" id="wechat_link_thumb_url" value="<?php echo esc_html( $thumb_url ); ?>"><br/>
	<span class="howto"><?php esc_html_e( 'Leave empty to use the featured image.', 'wp-weixin' ); ?></span>
</p>
