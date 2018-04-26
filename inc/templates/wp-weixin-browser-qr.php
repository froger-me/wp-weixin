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
		<style>

			body {
				line-height: 1.6;
				font-family: -apple-system-font, BlinkMacSystemFont, "Helvetica Neue", "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei UI", "Microsoft YaHei", Arial, sans-serif;
				color: #353535;
				font-size: 14px;
				background-color: #F6F8F9;
				min-width: 768px;
				text-align: center;
			}

			.desktop-qr-inner {
				width: 50%;
				max-width: 650px;
				margin-left: auto;
				margin-right: auto;
				padding: 40px 0 80px;
			}

			h2 {
				font-size: 26px;
				font-weight: 400;
				line-height: 1;
				margin-top: 80px;
				margin-bottom: 60px;
			}

			.desktop-qr-content {
				margin-bottom: 30px;
				background-color: #FFFFFF;
				border-radius: 3px;
				-moz-border-radius: 3px;
				-webkit-border-radius: 3px;
				box-shadow: 0 1px 5px 0 rgba(0, 0, 0, 0.05);
				padding: 75px 75px 90px;
				position: relative;
				min-height: 450px;
			}

			.desktop-qr-code {
				margin: 0 auto;
				display: block;
				width: 220px;
				height: 220px;
			}

			.desktop-qr-content .message {
				margin-top: 30px;
				color: #9A9A9A;
			}

		</style>
	</head>
	<body <?php body_class(); ?>>
		<div class="desktop-qr-inner">
			<h2><?php esc_html_e( 'Open with WeChat', 'wp-wexin' ); ?></h2>
			<div class="desktop-qr-content">
				<div class="desktop-qr-content-inner">
					<div class="desktop-qr-code-container">
						<img src="<?php print esc_attr( $page_qr_src ); ?>" class="desktop-qr-code">
					</div>
					<div class="message">
						<p><?php esc_html_e( 'Please scan the QR code with WeChat to access this page', 'wp-wexin' ); ?></p>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
