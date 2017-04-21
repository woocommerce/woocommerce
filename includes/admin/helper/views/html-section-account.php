<?php defined( 'ABSPATH' ) or exit(); ?>

<h2>Your Account</h2>

<div class="connect-wrapper">
	<div class="connected">
		<?php echo get_avatar( $auth_user_data['email'], 100 ); ?>
		<p>
			<?php printf( 'Connected as:<br /> <strong>%s</strong>', esc_html( $auth_user_data['email'] ) ); ?>
			<span class="buttons">
				<a class="button" href="<?php echo esc_url( $refresh_url ); ?>">Refresh</a>
				<a class="button" href="<?php echo esc_url( $disconnect_url ); ?>">Disconnect</a>
			</span>
		</p>
	</div>
</div>
