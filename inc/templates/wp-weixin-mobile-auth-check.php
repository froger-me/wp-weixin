<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<!doctype html>
<html <?php language_attributes(); ?>>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
		<?php wp_head(); ?>
	</head>
	<body class="wechat-auth-result">
		<div class="mobile-auth-inner">
			<div class="mobile-auth-content">
				<div class="mobile-auth-content-inner">
					<div class="mobile-auth-graphic-container">
					<?php if ( isset( $auth_qr_data['auth'] ) && $auth_qr_data['auth'] ) : ?>
						<i class="weui-icon-success"></i>
					<?php else : ?>
						<i class="weui-icon-warn"></i>
					<?php endif; ?>
					</div>
					<div class="message">
					<?php if ( isset( $auth_qr_data['auth'] ) && $auth_qr_data['auth'] ) : ?>
						<p class="success">
							<?php esc_html_e( 'Authentication successful', 'wp-weixin' ); ?><br/>
						</p>
					<?php else : ?>
						<p class="failure">
							<?php esc_html_e( 'Authentication failed', 'wp-weixin' ); ?><br/>
							<?php if ( isset( $auth_qr_data['error'] ) && is_array( $auth_qr_data['error'] ) ) : ?>
							<span class="error">
								<?php foreach ( $auth_qr_data['error'] as $error ) : ?>
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
