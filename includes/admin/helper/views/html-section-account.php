<?php defined( 'ABSPATH' ) or exit(); ?>

<h2><?php _e( 'Your Account', 'woocommerce' ); ?></h2>

<div class="connect-wrapper">
	<div class="connected">
		<?php echo get_avatar( $auth_user_data['email'], 100 ); ?>
		<p>
			<?php /* translators: %s: connected user e-mail */ ?>
			<?php printf( __( 'Connected as:<br /> <strong>%s</strong>', 'woocommerce' ), esc_html( $auth_user_data['email'] ) ); ?>
			<span class="buttons">
				<a class="button" href="<?php echo esc_url( $refresh_url ); ?>"><?php _e( 'Refresh', 'woocommerce' ); ?></a>
				<a class="button" href="<?php echo esc_url( $disconnect_url ); ?>"><?php _e( 'Disconnect', 'woocommerce' ); ?></a>
			</span>
		</p>
	</div>
</div>
