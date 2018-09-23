<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$page_qr_src = apply_filters( 'wp_weixin_browser_page_qr_src', '' );

?>
<!doctype html>
<html <?php language_attributes(); ?>>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
		<?php wp_head(); ?>
	</head>
	<body class="force-wechat">
		<div class="weui-desktop-head">
			<div class="weui-desktop-account"></div>
		</div>
		<div class="desktop-qr-inner">
			<h2><?php esc_html_e( 'Open with WeChat', 'wp-wexin' ); ?></h2>
			<div class="desktop-qr-content">
				<div class="desktop-qr-content-inner">
					<div class="desktop-qr-code-container">
						<img src="<?php print esc_attr( $page_qr_src ); ?>" class="force-wechat-qr-code">
					</div>
					<div class="message">
						<p><?php esc_html_e( 'Please scan the QR code with WeChat to access this page.', 'wp-wexin' ); ?></p>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
