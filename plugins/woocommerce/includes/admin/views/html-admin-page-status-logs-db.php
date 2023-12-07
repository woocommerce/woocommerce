<?php
/**
 * Admin View: Page - Status Database Logs
 *
 * @package WooCommerce\Admin\Logs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<form method="post" id="mainform" action="">
	<?php $log_table_list->search_box( __( 'Search logs', 'woocommerce' ), 'log' ); ?>
	<?php $log_table_list->display(); ?>

	<input type="hidden" name="page" value="wc-status" />
	<input type="hidden" name="tab" value="logs" />

	<?php submit_button( __( 'Flush all logs', 'woocommerce' ), 'delete', 'flush-logs' ); ?>
	<?php wp_nonce_field( 'woocommerce-status-logs' ); ?>
</form>
<script>
	document.addEventListener( 'DOMContentLoaded', function() {
		var contextToggles = Array.from( document.getElementsByClassName( 'log-toggle' ) );
		contextToggles.forEach( ( element ) => {
			element.addEventListener( 'click', ( event ) => {
				event.preventDefault();
				const button = event.currentTarget;
				const buttonLabel = button.querySelector( '.log-toggle-label' );
				const buttonIcon = button.querySelector( '.dashicons' );
				const context = document.getElementById( 'log-context-' + button.dataset.logId );

				switch ( button.dataset.toggleStatus ) {
					case 'off':
						context.style.display = 'table-row';
						buttonLabel.textContent = button.dataset.labelHide;
						buttonIcon.classList.replace( 'dashicons-arrow-down-alt2', 'dashicons-arrow-up-alt2' );
						button.dataset.toggleStatus = 'on';
						break;
					case 'on':
						context.style.display = 'none';
						buttonLabel.textContent = button.dataset.labelShow;
						buttonIcon.classList.replace( 'dashicons-arrow-up-alt2', 'dashicons-arrow-down-alt2' );
						button.dataset.toggleStatus = 'off';
						break;
				}
			} );
		} );
	} );
</script>
<?php
wc_enqueue_js(
	"jQuery( '#flush-logs' ).on( 'click', function() {
		if ( window.confirm('" . esc_js( __( 'Are you sure you want to clear all logs from the database?', 'woocommerce' ) ) . "') ) {
			return true;
		}
		return false;
	});"
);
