<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<li id="menu-item-<?php echo esc_attr( $item_id ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<div class="menu-item-bar">
		<div class="menu-item-handle">
			<span class="item-title"><span class="menu-item-title"><?php echo esc_html( $title ); ?></span> <span class="is-submenu" <?php echo esc_attr( $submenu_text ); ?>><?php esc_html_e( 'sub item' ); ?></span></span>
			<span class="item-controls">
				<?php
					$label = ( 'wechat' === $item->type ) ? __( 'WeChat Menu Item', 'wp-weixin' ) : $item->type_label;
				?>
				<span class="item-type"><?php echo esc_html( $label ); ?></span>
				<span class="item-order hide-if-js">
					<?php
						$nonce_url_up = wp_nonce_url(
							add_query_arg(
								array(
									'action'    => 'move-up-menu-item',
									'menu-item' => $item_id,
								),
								remove_query_arg( $removed_args, admin_url( 'nav-menus.php' ) )
							),
							'move-menu_item'
						);

						$nonce_url_down = wp_nonce_url(
							add_query_arg(
								array(
									'action'    => 'move-down-menu-item',
									'menu-item' => $item_id,
								),
								remove_query_arg( $removed_args, admin_url( 'nav-menus.php' ) )
							),
							'move-menu_item'
						);
					?>
					<a href="<?php echo esc_url( $nonce_url_up ); ?>" class="item-move-up" aria-label="<?php esc_attr_e( 'Move up' ); ?>">&#8593;</a>
					|
					<a href="<?php echo esc_url( $nonce_url_down ); ?>" class="item-move-down" aria-label="<?php esc_attr_e( 'Move down' ); ?>">&#8595;</a>
				</span>
				<?php
					$edit_link = ( isset( $_GET['edit-menu-item'] ) && $item_id == $_GET['edit-menu-item'] ) ? admin_url( 'nav-menus.php' ) : add_query_arg( 'edit-menu-item', $item_id, remove_query_arg( $removed_args, admin_url( 'nav-menus.php#menu-item-settings-' . $item_id ) ) ); // @codingStandardsIgnoreLine
				?>
				<a class="item-edit" id="edit-<?php echo esc_attr( $item_id ); ?>" href="<?php echo esc_url( $edit_link ); ?>" aria-label="<?php esc_attr_e( 'Edit menu item' ); ?>"><?php esc_html_e( 'Edit' ); ?></a>
			</span>
		</div>
	</div>

	<div class="menu-item-settings wp-clearfix" id="menu-item-settings-<?php echo esc_attr( $item_id ); ?>">
		<?php if ( 'custom' === $item->type || 'wechat' === $item->type ) : ?>
			<p class="field-url description description-wide">
				<label for="edit-menu-item-url-<?php echo esc_attr( $item_id ); ?>">
					<?php if ( 'wechat' !== $item->type ) : ?>
					<?php esc_html_e( 'URL' ); ?><br />
					<?php else : ?>
					<?php esc_html_e( 'Key', 'wp-weixin' ); ?><br />
					<?php endif; ?>
					<?php
						$class     = ( 'wechat' !== $item->type ) ? 'code' : '';
						$item->url = ( ! isset( $item->url ) ) ? get_post_meta( $item->ID, '_menu_item_url', true ) : $item->url;
					?>
					<input type="text" id="edit-menu-item-url-<?php esc_attr( $item_id ); ?>" class="widefat <?php echo esc_attr( $class ); ?> edit-menu-item-url" name="menu-item-url[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->url ); ?>" />
				</label>
			</p>
		<?php endif; ?>
		<p class="description description-wide">
			<label for="edit-menu-item-title-<?php echo esc_attr( $item_id ); ?>">
				<?php if ( 'wechat' !== $item->type ) : ?>
				<?php esc_html_e( 'Navigation Label' ); ?><br />
				<?php else : ?>
				<?php esc_html_e( 'Name', 'wp-weixin' ); ?><br />
				<?php endif; ?>
				<input type="text" id="edit-menu-item-title-<?php echo esc_attr( $item_id ); ?>" class="widefat edit-menu-item-title" name="menu-item-title[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->title ); ?>" />
			</label>
		</p>
		<?php if ( 'wechat' === $item->type ) : ?>
		<p class="field-title-attribute field-attr-title description description-wide">
			<label for="edit-menu-item-attr-title-<?php echo esc_attr( $item_id ); ?>">
				<?php esc_html_e( 'Type', 'wp-weixin' ); ?><br />
				<select id="edit-menu-item-attr-title-<?php echo esc_attr( $item_id ); ?>" name="menu-item-attr-title[<?php echo esc_attr( $item_id ); ?>]">
					<option value="click" <?php echo ( 'click' === $item->post_excerpt ) ? 'selected' : ''; ?>>click</option>
					<option value="scancode_push" <?php echo ( 'scancode_push' === $item->post_excerpt ) ? 'selected' : ''; ?>>scancode_push</option>
					<option value="scancode_waitmsg" <?php echo ( 'scancode_waitmsg' === $item->post_excerpt ) ? 'selected' : ''; ?>>scancode_waitmsg</option>
					<option value="pic_sysphoto" <?php echo ( 'pic_sysphoto' === $item->post_excerpt ) ? 'selected' : ''; ?>>pic_sysphoto</option>
					<option value="pic_photo_or_album" <?php echo ( 'pic_photo_or_album' === $item->post_excerpt ) ? 'selected' : ''; ?>>pic_photo_or_album</option>
					<option value="pic_weixin" <?php echo ( 'pic_weixin' === $item->post_excerpt ) ? 'selected' : ''; ?>>pic_weixin</option>
					<option value="location_select" <?php echo ( 'location_select' === $item->post_excerpt ) ? 'selected' : ''; ?>>location_select</option>
				</select>
			</label>
		</p>
		<?php else : ?>
		<p class="field-title-attribute field-attr-title description description-wide">
			<label for="edit-menu-item-attr-title-<?php echo esc_attr( $item_id ); ?>">
				<?php esc_html_e( 'Title Attribute' ); ?><br />
				<input type="text" id="edit-menu-item-attr-title-<?php echo esc_attr( $item_id ); ?>" class="widefat edit-menu-item-attr-title" name="menu-item-attr-title[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->post_excerpt ); ?>" />
			</label>
		</p>
		<?php endif; ?>
		<?php if ( 'wechat' !== $item->type ) : ?>
		<p class="field-link-target description">
			<label for="edit-menu-item-target-<?php echo esc_attr( $item_id ); ?>">
				<input type="checkbox" id="edit-menu-item-target-<?php echo esc_attr( $item_id ); ?>" value="_blank" name="menu-item-target[<?php echo esc_attr( $item_id ); ?>]"<?php checked( $item->target, '_blank' ); ?> />
				<?php esc_html_e( 'Open link in a new tab' ); ?>
			</label>
		</p>
		<p class="field-css-classes description description-thin">
			<label for="edit-menu-item-classes-<?php echo esc_attr( $item_id ); ?>">
				<?php esc_html_e( 'CSS Classes (optional)' ); ?><br />
				<input type="text" id="edit-menu-item-classes-<?php echo esc_attr( $item_id ); ?>" class="widefat code edit-menu-item-classes" name="menu-item-classes[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( implode( ' ', $item->classes ) ); ?>" />
			</label>
		</p>
		<p class="field-xfn description description-thin">
			<label for="edit-menu-item-xfn-<?php echo esc_attr( $item_id ); ?>">
				<?php esc_html_e( 'Link Relationship (XFN)' ); ?><br />
				<input type="text" id="edit-menu-item-xfn-<?php echo esc_attr( $item_id ); ?>" class="widefat code edit-menu-item-xfn" name="menu-item-xfn[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->xfn ); ?>" />
			</label>
		</p>
		<p class="field-description description description-wide">
			<label for="edit-menu-item-description-<?php echo esc_attr( $item_id ); ?>">
				<?php esc_html_e( 'Description' ); ?><br />
				<textarea id="edit-menu-item-description-<?php echo esc_attr( $item_id ); ?>" class="widefat edit-menu-item-description" rows="3" cols="20" name="menu-item-description[<?php echo esc_attr( $item_id ); ?>]"><?php echo esc_html( $item->description ); // textarea_escaped ?></textarea>
				<span class="description"><?php esc_html_e( 'The description will be displayed in the menu if the current theme supports it.' ); ?></span>
			</label>
		</p>
		<?php endif; ?>
		<fieldset class="field-move hide-if-no-js description description-wide">
			<span class="field-move-visual-label" aria-hidden="true"><?php esc_html_e( 'Move' ); ?></span>
			<button type="button" class="button-link menus-move menus-move-up" data-dir="up"><?php esc_html_e( 'Up one' ); ?></button>
			<button type="button" class="button-link menus-move menus-move-down" data-dir="down"><?php esc_html_e( 'Down one' ); ?></button>
			<button type="button" class="button-link menus-move menus-move-left" data-dir="left"></button>
			<button type="button" class="button-link menus-move menus-move-right" data-dir="right"></button>
			<button type="button" class="button-link menus-move menus-move-top" data-dir="top"><?php esc_html_e( 'To the top' ); ?></button>
		</fieldset>

		<div class="menu-item-actions description-wide submitbox">
			<?php if ( 'custom' !== $item->type && false !== $original_title ) : ?>
				<p class="link-to-original">
					<?php printf( __( 'Original: %s'), '<a href="' . esc_attr( $item->url ) . '">' . esc_html( $original_title ) . '</a>' ); ?><?php // @codingStandardsIgnoreLine ?>
				</p>
			<?php endif; ?>
			<?php
				$remove_link = wp_nonce_url(
					add_query_arg(
						array(
							'action'    => 'delete-menu-item',
							'menu-item' => $item_id,
						),
						admin_url( 'nav-menus.php' )
					),
					'delete-menu_item_' . $item_id
				);
				$cancel_link = add_query_arg(
					array(
						'edit-menu-item' => $item_id,
						'cancel'         => time(),
					),
					admin_url( 'nav-menus.php' )
				);
			?>
			<a class="item-delete submitdelete deletion" id="delete-<?php echo esc_attr( $item_id ); ?>" href="<?php echo esc_url( $remove_link ); ?>"><?php esc_html_e( 'Remove' ); ?></a> <span class="meta-sep hide-if-no-js"> | </span> <a class="item-cancel submitcancel hide-if-no-js" id="cancel-<?php echo esc_attr( $item_id ); ?>" href="<?php echo esc_url( $cancel_link ); ?>#menu-item-settings-<?php echo esc_attr( $item_id ); ?>"><?php esc_html_e( 'Cancel' ); ?></a>
		</div>

		<input class="menu-item-data-db-id" type="hidden" name="menu-item-db-id[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item_id ); ?>" />
		<input class="menu-item-data-object-id" type="hidden" name="menu-item-object-id[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->object_id ); ?>" />
		<input class="menu-item-data-object" type="hidden" name="menu-item-object[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->object ); ?>" />
		<input class="menu-item-data-parent-id" type="hidden" name="menu-item-parent-id[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->menu_item_parent ); ?>" />
		<input class="menu-item-data-position" type="hidden" name="menu-item-position[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->menu_order ); ?>" />
		<input class="menu-item-data-type" type="hidden" name="menu-item-type[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->type ); ?>" />
	</div><!-- .menu-item-settings-->
	<ul class="menu-item-transport"></ul>
