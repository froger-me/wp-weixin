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
	<body class="wp-weixin-wechat-auth wechat-layout">
		<div class="weui-desktop-head">
			<div class="weui-desktop-account"></div>
		</div>
		<div class="desktop-qr-inner">
			<h2><?php esc_html_e( 'Authenticate with WeChat', 'wp-weixin' ); ?></h2>
			<div class="desktop-qr-content">
				<div class="desktop-qr-content-inner">
					<div class="desktop-qr-code-container">
						<img src="" class="wp-weixin-qr-code">
					</div>
					<div class="message">
						<p>
							<?php esc_html_e( 'Scan the QR code with WeChat to authenticate.', 'wp-weixin' ); ?><br/><?php esc_html_e( 'If you do not have an account yet, you will be registered automatically.', 'wp-weixin' ); ?>
						</p>
						<p class="waiting">
							<span class="network-on"><i class="weui-icon-info-circle"></i><?php esc_html_e( 'Network available - waiting...', 'wp-weixin' ); ?></span>
							<span class="network-off"><i class="weui-icon-cancel"></i><?php esc_html_e( 'Network unavailable - attempting to reconnect...', 'wp-weixin' ); ?></span>
						</p>
						<p class="success">
							<i class="weui-icon-success"></i><?php esc_html_e( 'Authentication successful', 'wp-weixin' ); ?><br/>
							<span class="redirect-message"><br/><?php esc_html_e( 'Redirecting...', 'wp-weixin' ); ?></span>
						</p>
						<p class="failure">
							<i class="weui-icon-warn"></i><?php esc_html_e( 'Authentication failed', 'wp-weixin' ); ?><br/>
							<span class="error"></span>
							<span class="redirect-message"><br/><?php esc_html_e( 'Redirecting...', 'wp-weixin' ); ?></span>
						</p>
						<?php wp_nonce_field( 'wp_weixin_qr_code', 'wp_weixin_qr_nonce' ); ?>
						<input type="hidden" id="wp_weixin_hash" value="">
					</div>
				</div>
			</div>
		</div>
		<?php wp_footer(); ?>
	</body>
</html>
