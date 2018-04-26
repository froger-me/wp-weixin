<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<div class="wechatlinkdiv" id="wechatlinkdiv">
	<input type="hidden" value="wechat" name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-type]" />
	<p id="menu-item-url-wrap" class="wp-clearfix">
		<label class="howto" for="wechat-menu-item-url"><?php esc_html_e( 'Key', 'wp-weixin' ); ?></label>
		<input id="wechat-menu-item-url" name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-url]" type="text" class="menu-item-textbox" value="" />
	</p>
	<p id="menu-item-name-wrap" class="wp-clearfix">
		<label class="howto" for="wechat-menu-item-name"><?php esc_html_e( 'Name', 'wp-weixin' ); ?></label>
		<input id="wechat-menu-item-name" name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-title]" type="text" class="regular-text menu-item-textbox" />
	</p>
	<p id="menu-item-target-wrap" class="wp-clearfix">
		<label class="howto" for="wechat-menu-item-target"><?php esc_html_e( 'Type', 'wp-weixin' ); ?></label>
		<select id="wechat-menu-item-attr-title" name="menu-item[<?php echo esc_attr( $nav_menu_placeholder ); ?>][menu-item-attr-title]">
			<option value="click">click</option>
			<option value="scancode_push">scancode_push</option>
			<option value="scancode_waitmsg">scancode_waitmsg</option>
			<option value="pic_sysphoto">pic_sysphoto</option>
			<option value="pic_photo_or_album">pic_photo_or_album</option>
			<option value="pic_weixin">pic_weixin</option>
			<option value="location_select">location_select</option>
		</select>
	</p>
	<p class="button-controls wp-clearfix">
		<span class="add-to-menu">
			<input type="submit"<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu' ); ?>" name="add-wechat-menu-item" id="submit-wechatlinkdiv" />
			<span class="spinner"></span>
		</span>
	</p>

</div><!-- /.wechatlinkdiv -->
