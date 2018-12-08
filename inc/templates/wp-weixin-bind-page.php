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
	<body class="wechat-layout">
		<div class="weui-desktop-head">
			<div class="weui-desktop-account"></div>
		</div>
		<div class="wp-weixin-wechat-bind desktop-qr-inner">
			<?php if ( $openid ) : ?>
				<h2><?php esc_html_e( 'WeChat Account Bound', 'wp-weixin' ); ?></h2>
				<div class="desktop-account-content">
					<div class="desktop-account-content-inner">
						<div class="desktop-headimg-container">
							<img src="<?php echo esc_url( $wechat_info['headimgurl'] ); ?>" class="wp-weixin-headimg">
						</div>
						<div class="message">
							<p class="nickname">
								<?php echo esc_html( $wechat_info['nickname'] ); ?>
							</p>
						</div>
						<a href="#" class="weui-btn weui-btn_warn wx-unbind">
							<?php esc_html_e( 'Unbind WeChat account', 'wp-weixin' ); ?>
						</a>
						<div class="message">
							<p class="info">
								<?php
									esc_html_e(
										'This action cannot be canceled, but the WeChat account may be bound again afterward',
										'wp-weixin'
									);
								?>
							</p>
						</div>
					</div>
					<?php wp_nonce_field( 'wp_weixin_unbind', 'wp_weixin_unbind_nonce' ); ?>
					<input type="hidden" id="wp_weixin_openid" value="<?php echo esc_attr( $openid ); ?>">
					<input type="hidden" id="wp_weixin_user_id" value="<?php echo esc_attr( $user_id ); ?>">
				</div>
			<?php else : ?>
				<h2><?php esc_html_e( 'Bind a WeChat Account', 'wp-weixin' ); ?></h2>
				<div class="wp-weixin-wechat-bind desktop-qr-content">
					<div class="desktop-qr-content-inner">
						<div class="desktop-qr-code-container">
							<img src="" class="wp-weixin-qr-code">
						</div>
						<div class="message">
							<p>
								<?php esc_html_e( 'Scan the QR code with WeChat to bind your account.', 'wp-weixin' ); ?>
							</p>
							<p class="waiting">
								<span class="network-on"><i class="weui-icon-info-circle"></i><?php esc_html_e( 'Network available - waiting...', 'wp-weixin' ); ?></span>
								<span class="network-off"><i class="weui-icon-cancel"></i><?php esc_html_e( 'Network unavailable - attempting to reconnect...', 'wp-weixin' ); ?></span>
							</p>
							<p class="success">
								<i class="weui-icon-success"></i><?php esc_html_e( 'Account bound successfully', 'wp-weixin' ); ?><br/>
								<span class="redirect-message"><br/><?php esc_html_e( 'Redirecting...', 'wp-weixin' ); ?></span>
							</p>
							<p class="failure">
								<i class="weui-icon-warn"></i><?php esc_html_e( 'Account binding failed', 'wp-weixin' ); ?><br/>
								<span class="error"></span>
								<span class="redirect-message"><br/><?php esc_html_e( 'Redirecting...', 'wp-weixin' ); ?></span>
							</p>
							<?php wp_nonce_field( 'wp_weixin_qr_code', 'wp_weixin_qr_nonce' ); ?>
							<input type="hidden" id="wp_weixin_hash" value="">
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php wp_footer(); ?>
	</body>
</html>
