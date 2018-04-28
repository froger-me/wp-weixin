<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<h2><?php esc_html_e( 'WeChat account information', 'wp-weixin' ); ?></h2>
<ul>
	<?php foreach ( $data as $key => $value ) : ?>
	<li><strong><?php print esc_html( $key ); ?></strong>: <?php print esc_html( $value ); ?></li>
	<?php endforeach; ?>
</ul>
