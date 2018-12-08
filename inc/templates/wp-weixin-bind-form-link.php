<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$blog_id = ( is_multisite() ) ? get_current_blog_id() : null;
?>
<?php if ( empty( $class ) ) : ?>
	<a href="<?php echo esc_attr( get_home_url( $blog_id, 'wp-weixin/wechat-bind-edit/' . $user->ID ) ); ?>" target="<?php echo esc_attr( $target ); ?>"><?php echo esc_html( $link_text ); ?></a>
<?php else : ?>
	<div class="<?php echo esc_attr( $class ); ?>">
		<?php if ( 'wp-wx-wp-account-edit' === $class ) : ?>
			<table class="form-table">
				<tbody>
					<tr class="">
						<th>
							<svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd"><style type="text/css">.st0{fill:#44B549;}</style><path class="st0" d="M12.7,16H3.3C1.5,16,0,14.5,0,12.7V3.3C0,1.5,1.5,0,3.3,0h9.3C14.5,0,16,1.5,16,3.3v9.3 C16,14.5,14.5,16,12.7,16z M12.2,11.3c0.7-0.5,1.1-1.2,1.1-2c0-1.5-1.5-2.7-3.2-2.7c-1.8,0-3.2,1.2-3.2,2.7S8.4,12,10.1,12 c0.4,0,0.7-0.1,1.1-0.1h0.1c0.1,0,0.1,0,0.2,0.1l0.7,0.4h0.1c0.1,0,0.1-0.1,0.1-0.1v-0.1l-0.1-0.5v-0.1 C12.1,11.5,12.2,11.4,12.2,11.3z M6.5,3.7C4.4,3.7,2.6,5.2,2.6,7c0,1,0.5,1.9,1.3,2.4C4,9.5,4,9.5,4,9.6v0.1l-0.2,0.7v0.1 c0,0.1,0.1,0.1,0.1,0.1H4L4.9,10C5,9.9,5,9.9,5.1,9.9h0.1C5.6,10,6,10.1,6.5,10.1h0.2C6.6,9.8,6.6,9.6,6.6,9.3 c0-1.6,1.6-2.9,3.5-2.9h0.2C10.1,4.9,8.5,3.7,6.5,3.7z M9.1,8.9c-0.3,0-0.4-0.2-0.4-0.4c0-0.3,0.2-0.4,0.4-0.4 c0.3,0,0.4,0.2,0.4,0.4S9.3,8.9,9.1,8.9z M11.2,8.9c-0.3,0-0.4-0.2-0.4-0.4c0-0.3,0.2-0.4,0.4-0.4s0.4,0.2,0.4,0.4 C11.6,8.7,11.4,8.9,11.2,8.9z M5.3,6.4C5,6.4,4.8,6.2,4.8,5.9S5,5.4,5.3,5.4s0.5,0.2,0.5,0.5S5.5,6.4,5.3,6.4z M7.8,6.4 c-0.3,0-0.5-0.2-0.5-0.5s0.3-0.5,0.5-0.5s0.5,0.2,0.5,0.5S8.1,6.4,7.8,6.4z"></path></svg> <?php esc_html_e( 'Account Binding', 'wp-weixin' ); ?>
						</th>
						<td>
							<a class="button" href="<?php echo esc_attr( get_home_url( $blog_id, 'wp-weixin/wechat-bind-edit/' . $user->ID ) ); ?>" target="<?php echo esc_attr( $target ); ?>">
								<?php echo esc_html( $link_text ); ?>
							</a>
							<p class="description">
								<?php esc_html_e( 'A page will open in a new tab.', 'wp-weixin' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		<?php else : ?>
			<svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd"><style type="text/css">.st0{fill:#44B549;}</style><path class="st0" d="M12.7,16H3.3C1.5,16,0,14.5,0,12.7V3.3C0,1.5,1.5,0,3.3,0h9.3C14.5,0,16,1.5,16,3.3v9.3 C16,14.5,14.5,16,12.7,16z M12.2,11.3c0.7-0.5,1.1-1.2,1.1-2c0-1.5-1.5-2.7-3.2-2.7c-1.8,0-3.2,1.2-3.2,2.7S8.4,12,10.1,12 c0.4,0,0.7-0.1,1.1-0.1h0.1c0.1,0,0.1,0,0.2,0.1l0.7,0.4h0.1c0.1,0,0.1-0.1,0.1-0.1v-0.1l-0.1-0.5v-0.1 C12.1,11.5,12.2,11.4,12.2,11.3z M6.5,3.7C4.4,3.7,2.6,5.2,2.6,7c0,1,0.5,1.9,1.3,2.4C4,9.5,4,9.5,4,9.6v0.1l-0.2,0.7v0.1 c0,0.1,0.1,0.1,0.1,0.1H4L4.9,10C5,9.9,5,9.9,5.1,9.9h0.1C5.6,10,6,10.1,6.5,10.1h0.2C6.6,9.8,6.6,9.6,6.6,9.3 c0-1.6,1.6-2.9,3.5-2.9h0.2C10.1,4.9,8.5,3.7,6.5,3.7z M9.1,8.9c-0.3,0-0.4-0.2-0.4-0.4c0-0.3,0.2-0.4,0.4-0.4 c0.3,0,0.4,0.2,0.4,0.4S9.3,8.9,9.1,8.9z M11.2,8.9c-0.3,0-0.4-0.2-0.4-0.4c0-0.3,0.2-0.4,0.4-0.4s0.4,0.2,0.4,0.4 C11.6,8.7,11.4,8.9,11.2,8.9z M5.3,6.4C5,6.4,4.8,6.2,4.8,5.9S5,5.4,5.3,5.4s0.5,0.2,0.5,0.5S5.5,6.4,5.3,6.4z M7.8,6.4 c-0.3,0-0.5-0.2-0.5-0.5s0.3-0.5,0.5-0.5s0.5,0.2,0.5,0.5S8.1,6.4,7.8,6.4z"/></svg> <a href="<?php echo esc_attr( get_home_url( $blog_id, 'wp-weixin/wechat-bind-edit/' . $user->ID ) ); ?>" target="<?php echo esc_attr( $target ); ?>"><?php echo esc_html( $link_text ); ?></a>
		<?php endif; ?>
	</div>
<?php endif; ?>
