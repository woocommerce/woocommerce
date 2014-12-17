<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php echo WC_Meta_Box_Webhook_Logs::get_navigation( $total ); ?>

<table id="webhook-logs" class="widefat">
	<thead>
		<tr>
			<th><?php _e( 'Date', 'woocommerce' ); ?></th>
			<th><?php _e( 'URL', 'woocommerce' ); ?></th>
			<th><?php _e( 'Request', 'woocommerce' ); ?></th>
			<th><?php _e( 'Response', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th><?php _e( 'Date', 'woocommerce' ); ?></th>
			<th><?php _e( 'URL', 'woocommerce' ); ?></th>
			<th><?php _e( 'Request', 'woocommerce' ); ?></th>
			<th><?php _e( 'Response', 'woocommerce' ); ?></th>
		</tr>
	</tfoot>
	<tbody>
		<?php
			foreach ( $logs as $log ) {
				$log = $webhook->get_delivery_log( $log->comment_ID );

				include( 'html-webhook-log.php' );
			}
		?>
	</tbody>
</table>

<?php echo WC_Meta_Box_Webhook_Logs::get_navigation( $total ); ?>
