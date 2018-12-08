<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

get_header() ?>
<div id="wp_weixin_oa_subscribe">
	<h1><?php print esc_html( $title ); ?></h1>
	<p><?php print esc_html( $message ); ?></p>
	<img src="<?php print esc_attr( $qr_src ); ?>">
	<p><input type="button" value="<?php esc_html_e( 'Refresh', 'wp-weixin' ); ?>"></p>
</div>
<?php
get_footer();
