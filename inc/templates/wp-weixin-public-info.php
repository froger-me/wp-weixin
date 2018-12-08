<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>

<?php if ( $wechat_info ) : ?>
<div class="wp-weixin-public-info">
	<h3><?php esc_html_e( 'WeChat account information', 'wp-weixin' ); ?></h3>
	<?php
	if (
		isset( $wechat_info['headimgurl'] ) &&
		false !== filter_var( $wechat_info['headimgurl'], FILTER_VALIDATE_URL )
	) :
	?>
	<p>
		<img src="<?php echo esc_url( $wechat_info['headimgurl'] ); ?>" />
	</p>
	<?php endif; ?>
	<table>
		<tr>
			<th>
				<?php esc_html_e( 'Name' ); ?>: 
			</th>
			<td>
				<?php echo esc_html( $wechat_info['nickname'] ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<?php esc_html_e( 'Selected gender', 'wp-weixin' ); ?>: 
			</th>
			<td>
				<?php echo esc_html( $wechat_info['sex'] ); ?>
			</td>
		<tr>
			<th>
				<?php esc_html_e( 'Language' ); ?>: 
			</th>
			<td>
				<?php echo esc_html( $wechat_info['language'] ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<?php esc_html_e( 'City', 'wp-weixin' ); ?>: 
			</th>
			<td>
				<?php echo esc_html( $wechat_info['city'] ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<?php esc_html_e( 'Province', 'wp-weixin' ); ?>: 
			</th>
			<td>
				<?php echo esc_html( $wechat_info['province'] ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<?php esc_html_e( 'Country', 'wp-weixin' ); ?>: 
			</th>
			<td>
				<?php echo esc_html( $wechat_info['country'] ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<?php esc_html_e( 'UnionID', 'wp-weixin' ); ?>: 
			</th>
			<td>
				<?php echo esc_html( $wechat_info['unionid'] ); ?> 
			</td>
		</tr>
		<tr>
			<th>
				<?php esc_html_e( 'OpenID', 'wp-weixin' ); ?>: 
			</th>
			<td>
				<?php echo esc_html( $wechat_info['openid'] ); ?> 
			</td>
		</tr>
	</table>
</div>
<?php endif; ?>
