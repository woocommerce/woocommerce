<?php
/**
 * Admin View: Page - Status Database Logs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form method="get" id="mainform" action="">

	<?php $log_table_list->views(); ?>
	<?php $log_table_list->search_box( __( 'Search tag', 'woocommerce' ), 'tag' ); ?>
	<?php $log_table_list->display(); ?>

	<input type="hidden" name="page" value="wc-status" />
	<input type="hidden" name="tab" value="logs-db" />

</form>
<form method="post" action="">
	<?php
		wp_nonce_field( 'flush-logs' );
		submit_button( __( 'Flush all logs', 'woocommerce' ), 'delete', 'flush-logs' );
	?>
</form>
