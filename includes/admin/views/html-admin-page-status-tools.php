<?php
/**
 * Admin View: Page - Status Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<form method="post" action="options.php">
	<?php settings_fields( 'woocommerce_status_settings_fields' ); ?>
	<table class="wc_status_table widefat" cellspacing="0">
		<tbody class="tools">
			<?php foreach ( $tools as $action => $tool ) : ?>
				<tr class="<?php echo sanitize_html_class( $action ); ?>">
					<td><?php echo esc_html( $tool['name'] ); ?></td>
					<td>
						<p>
							<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=wc-status&tab=tools&action=' . $action ), 'debug_action' ); ?>" class="button <?php echo esc_attr( $action ); ?>"><?php echo esc_html( $tool['button'] ); ?></a>
							<span class="description"><?php echo wp_kses_post( $tool['desc'] ); ?></span>
						</p>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p class="submit">
		<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ) ?>" />
	</p>
</form>
