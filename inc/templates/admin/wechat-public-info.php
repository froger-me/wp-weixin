<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>

<?php if ( $data ) : ?>
<h2><?php esc_html_e( 'WeChat account information', 'wp-weixin' ); ?></h2>
<ul class="wp-weixin-raw-info">
	<?php foreach ( $data as $key => $value ) : ?>
		<li><strong><?php echo esc_html( $key ); ?></strong>: <span>
			<?php if ( 'headimgurl' === $key ) : ?>
				<a target="_blank" href="<?php echo esc_url( $value ); ?>">
			<?php endif; ?>
			<?php echo esc_html( $value ); ?>
			<?php if ( 'headimgurl' === $key ) : ?>
				</a>
			<?php endif; ?>
		</span></li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>
