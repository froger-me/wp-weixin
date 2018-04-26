<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$qr_src  = apply_filters( 'wp_weixin_subscribe_src', '' );
$title   = apply_filters( 'wp_weixin_follower_notice_title', __( 'Follow Us!', 'wp-weixin' ) );
$message = __( 'Please scan this QR Code to follow us before accessing this content.', 'wp-weixin' );
$message = apply_filters( 'wp_weixin_follower_notice', $message );

get_header() ?>
	<div id="wxsubscribe">
	<h1><?php print esc_html( $title ); ?></h1>
	<p><?php print esc_html( $message ); ?></p>
	<img src="<?php print esc_attr( $qr_src ); ?>">
	</div>
<?php
get_footer();
