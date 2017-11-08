<?php
/**
 * Admin View: Page - Status Database Logs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$s = '';
if ( isset( $_REQUEST['s'] ) ) {
	$s = wc_clean( wp_unslash( $_REQUEST['s'] ) );
}
?>
<form method="get" id="mainform" action="">
    <p class="search-box">
        <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search logs', 'woocommerce' ); ?></label>
        <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr( $s ); ?>">
        <input type="submit" id="search-submit" class="button" value="<?php _e( 'Search logs', 'woocommerce' ); ?>">
    </p>

	<?php $log_table_list->display(); ?>

	<input type="hidden" name="page" value="wc-status" />
	<input type="hidden" name="tab" value="logs" />

	<?php submit_button( __( 'Flush all logs', 'woocommerce' ), 'delete', 'flush-logs' ); ?>
	<?php wp_nonce_field( 'woocommerce-status-logs' ); ?>
</form>
<?php
wc_enqueue_js( "
	jQuery( '#flush-logs' ).click( function() {
		if ( window.confirm('" . esc_js( __( 'Are you sure you want to clear all logs from the database?', 'woocommerce' ) ) . "') ) {
			return true;
		}
		return false;
	});
" );
