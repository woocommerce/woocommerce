<?php defined( 'ABSPATH' ) or exit(); ?>

<div class="connect-wrapper">
	<div class="connected active">
		<div class="user-info">
			<?php echo get_avatar( $auth_user_data['email'], 100 ); ?>
			<p><?php printf( __( 'Connected to: <strong>%s</strong>', 'woocommerce' ), esc_html( $auth_user_data['email'] ) ); ?></p>
			<span class="chevron dashicons dashicons-arrow-up-alt2"></span>
		</div>
		<div class="buttons active">
			<a class="button button-secondary" href="<?php echo esc_url( $disconnect_url ); ?>">Disconnect</a>
			<a class="button" href="<?php echo esc_url( $refresh_url ); ?>">Refresh</a>
		</div>
	</div>
</div>
