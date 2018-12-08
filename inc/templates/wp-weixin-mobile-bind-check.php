<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<!doctype html>
<html <?php language_attributes(); ?> class="wechat">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
		<?php wp_head(); ?>
	</head>
	<body class="wechat-scan-result wechat-layout">
		<div class="mobile-scan-inner">
			<div class="mobile-scan-content">
				<div class="mobile-scan-content-inner">
					<div class="mobile-scan-graphic-container">
					<?php if ( isset( $bind_qr_data['bind'] ) && $bind_qr_data['bind'] ) : ?>
						<i class="weui-icon-success"></i>
					<?php else : ?>
						<i class="weui-icon-warn"></i>
					<?php endif; ?>
					</div>
					<div class="message">
					<?php if ( isset( $bind_qr_data['bind'] ) && $bind_qr_data['bind'] ) : ?>
						<p class="success">
							<?php esc_html_e( 'Account bound successfully', 'wp-weixin' ); ?><br/>
						</p>
					<?php else : ?>
						<p class="failure">
							<?php esc_html_e( 'Account binding failed', 'wp-weixin' ); ?><br/>
							<?php if ( isset( $bind_qr_data['error'] ) && is_array( $bind_qr_data['error'] ) ) : ?>
							<span class="error">
								<?php foreach ( $bind_qr_data['error'] as $error ) : ?>
									<?php echo esc_html( $error ); ?><br/>
								<?php endforeach; ?>
							</span>
							<?php endif; ?>
						</p>
					<?php endif; ?>
						<a href="#" class="weui-btn weui-btn_mini weui-btn_default wechat-close">
							<?php esc_html_e( 'Close', 'wp-weixin' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>
		<?php wp_footer(); ?>
	</body>
</html>
