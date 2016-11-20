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
	<?php $log_table_list->search_box( __( 'Search message', 'woocommerce' ), 'message' ); ?>
	<?php $log_table_list->display(); ?>

	<input type="hidden" name="page" value="wc-status" />
	<input type="hidden" name="tab" value="logs-db" />
</form>
