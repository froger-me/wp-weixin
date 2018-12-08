<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$active_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
$active_tab = ( $active_tab ) ? $active_tab : 'settings';

if ( ! current_user_can( 'manage_options' ) ) {
	$active_tab = 'qrs';
}

$base_qr_url = home_url( 'wp-weixin/get-qrcode/hash/' );

?>
<div class="wrap">
	<h1><?php esc_html_e( 'WP Weixin', 'wp-weixin' ); ?></h1>
	<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<h2 class="nav-tab-wrapper">
			<?php do_action( 'wp_weixin_before_tabs_settings' ); ?>
			<?php do_action( 'wp_weixin_before_main_tab_settings' ); ?>
			<a href="?page=wp-weixin&tab=settings" class="nav-tab<?php echo ( 'settings' === $active_tab ) ? ' nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'WP Weixin Settings', 'wp-weixin' ); ?>
			</a>
			<?php do_action( 'wp_weixin_after_main_tab_settings' ); ?>
			<?php do_action( 'wp_weixin_before_qr_tab_settings' ); ?>
			<a href="?page=wp-weixin&tab=qrs" class="nav-tab<?php echo ( 'qrs' === $active_tab ) ? ' nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Generate QR codes', 'wp-weixin' ); ?>
			</a>
			<?php do_action( 'wp_weixin_after_qr_tab_settings' ); ?>
			<?php do_action( 'wp_weixin_after_tabs_settings' ); ?>
		</h2>
	<?php endif; ?>
	<?php do_action( 'wp_weixin_before_settings' ); ?>
	<?php do_action( 'wp_weixin_before_main_settings' ); ?>
	<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<?php if ( 'settings' === $active_tab ) : ?>
			<?php do_action( 'wp_weixin_before_main_settings_inner' ); ?>
		<div class="stuffbox">
			<div class="inside">
				<form action="options.php" method="post">
					<?php settings_fields( 'wpWeixinSettings' ); ?>
					<?php do_settings_sections( 'wpWeixinSettings' ); ?>
					<?php submit_button(); ?>
				</form>
			</div>
		</div>
		<?php do_action( 'wp_weixin_after_main_settings_inner' ); ?>
		<?php endif; ?>
	<?php endif; ?>	
	<?php do_action( 'wp_weixin_after_main_settings' ); ?>
	<?php do_action( 'wp_weixin_before_qr_settings' ); ?>
	<?php if ( 'qrs' === $active_tab ) : ?>
		<?php do_action( 'wp_weixin_before_qr_settings_inner' ); ?>
		<div class="stuffbox qr-box">
			<div class="inside">
				<h2><?php esc_html_e( 'Custom QR code', 'wp-weixin' ); ?></h2>
				<div class="qr-wrapper">
					<img data-base_url="<?php echo esc_url( $base_qr_url ); ?>" id="custom_qr" src="" />
					<span class="error"><?php esc_html_e( 'Impossible to generate the QR code', 'wp-weixin' ); ?></span>
				</div>	
				<table class="form-table">
					<tbody>
						<tr class="wp_weixin-qr-custom-section">
							<th scope="row"><?php esc_html_e( 'URL', 'wp-weixin' ); ?></th>
							<td>
								<input type="text" id="qr_url" name="qr_url" value="">
								<p class="description">
									<?php esc_html_e( 'The URL the user will get to after scanning the code.', 'wp-weixin' ); ?> 
									<?php esc_html_e( 'Has to include the protocol ("http://" or "https://").', 'wp-weixin' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<button data-img="custom_qr" class="qr-button button button-primary">
						<?php esc_html_e( 'Get QR code', 'wp-weixin' ); ?>
					</button>
				</p>
			</div>
		</div>
		<?php do_action( 'wp_weixin_after_qr_settings_inner' ); ?>
	<?php endif; ?>
	<?php do_action( 'wp_weixin_after_qr_settings' ); ?>
	<?php do_action( 'wp_weixin_after_settings' ); ?>
</div>
