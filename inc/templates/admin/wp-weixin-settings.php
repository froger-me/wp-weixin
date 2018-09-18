<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$active_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
$active_tab = ( $active_tab ) ? $active_tab : 'settings';

if ( ! current_user_can( 'manage_options' ) ) {
	$active_tab = 'qrs';
}

$base_payment_qr_url = site_url( 'wp-weixin-pay/transfer/' );
$base_qr_url         = site_url( 'wp-weixin/get-qrcode/hash/' );
$url_nonce           = wp_create_nonce( 'qr_code' );
$base_payment_qr_src = site_url( 'wp-weixin/get-qrcode/hash/' . base64_encode( $base_payment_qr_url . '|' . $url_nonce ) );// @codingStandardsIgnoreLine

?>
<div class="wrap">
	<h1><?php esc_html_e( 'WP Weixin', 'wp-weixin' ); ?></h1>
	<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<h2 class="nav-tab-wrapper">
			<a href="?page=wp-weixin&tab=settings" class="nav-tab<?php echo ( 'settings' === $active_tab ) ? ' nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'WP Weixin Settings', 'wp-weixin' ); ?>
			</a>
			<a href="?page=wp-weixin&tab=qrs" class="nav-tab<?php echo ( 'qrs' === $active_tab ) ? ' nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Generate QR codes', 'wp-weixin' ); ?>
			</a>
		</h2>
	<?php endif; ?>
	<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<?php if ( 'settings' === $active_tab ) : ?>
			<form action="options.php" method="post">
				<?php settings_fields( 'wpWeixinSettings' ); ?>
				<?php do_settings_sections( 'wpWeixinSettings' ); ?>
				<?php submit_button(); ?>
			</form>
		<?php endif; ?>
	<?php endif; ?>	
	<?php if ( 'qrs' === $active_tab ) : ?>
		<?php if ( $custom_transfer ) : ?>
			<h2><?php esc_html_e( 'Payment QR code', 'wp-weixin' ); ?></h2>
			<div class="qr-wrapper">
				<img data-base_url="<?php echo esc_url( $base_qr_url ); ?>" data-default_url="<?php echo esc_url( $base_payment_qr_url ); ?>" id="payment_qr" src="<?php echo esc_url( $base_payment_qr_src ); ?>" />
				<span class="error"><?php esc_html_e( 'Impossible to generate the QR code', 'wp-weixin' ); ?></span>
			</div>	
			<table class="form-table">
				<tbody>
					<tr class="wp_weixin-qr-amount-section">
						<th scope="row"><?php esc_html_e( 'Pre-filled amount', 'wp-weixin' ); ?></th>
						<td>
							<label>￥</label><input type="number" step="any" id="wp_weixin_qr_amount" name="wp_weixin-qr-amount" value="">
							<p class="description">
								<?php esc_html_e( 'Used to pre-fill the amount on the money transfer screen.', 'wp-weixin' ); ?>
							</p>
						</td>
					</tr>
					<tr class="wp_weixin-qr-amount-fixed-section">
						<th scope="row"><?php esc_html_e( 'Fixed amount', 'wp-weixin' ); ?></th>
						<td>
							<input id="wp_weixin_qr_amount_fixed" type="checkbox" name="wp_weixin-qr-amount-fixed" value="">
							<p class="description">
								<?php esc_html_e( 'Prevent the user from changing the amount on the money transfer screen.', 'wp-weixin' ); ?>
							</p>
						</td>
					</tr>
					<tr class="wp_weixin-qr-product-name-section">
						<th scope="row"><?php esc_html_e( 'Product Name', 'wp-weixin' ); ?></th>
						<td>
							<input id="wp_weixin_qr_product_name" type="text" name="wp_weixin_qr_product_name" value="">
							<p class="description">
								<?php esc_html_e( 'Product name that will appear on the Wechat payment details.', 'wp-weixin' ); ?><br>
								<?php esc_html_e( 'If filled, the value will use the notes field of the money transfer screen and therefore the "Add Note" link will not be displayed.', 'wp-weixin' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<button data-img="payment_qr" class="qr-button qr-payment-button button button-primary">
					<?php esc_html_e( 'Get QR code', 'wp-weixin' ); ?>
				</button>
			</p>
		<?php endif; ?>
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
							<br/>
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
	<?php endif; ?>
</div>
