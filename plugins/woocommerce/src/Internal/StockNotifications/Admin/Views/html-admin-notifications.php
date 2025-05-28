<?php
/**
 * Admin View: Stock Notifications list
 *
 * @since    0.0.0
 */

 declare( strict_types = 1 );

 if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap woocommerce woocommerce-customer-stock-notifications">

	<h1 class="wp-heading-inline"><?php esc_html_e( 'Stock Notifications', 'woocommerce' ); ?></h1>
	<a href="<?php echo esc_url( add_query_arg( array( 'section' => 'create' ), admin_url( 'customer_stock_notifications' ) ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'woocommerce' ); ?></a>

	<hr class="wp-header-end">
	<?php
	if ( $table->total_items > 0 || $table->has_stock_notifications ) {
		$table->views();
		?>

		<form id="customer-stock-notifications-table" class="customer-stock-notifications-select2" method="GET">
			<p class="search-box">
				<label for="post-search-input" class="screen-reader-text"><?php esc_html_e( 'Search Notifications', 'woocommerce' ); ?>:</label>
				<input type="search" value="<?php echo esc_attr( $search ); ?>" name="s" id="customer-stock-notifications-search-input">
				<input type="submit" value="<?php echo esc_attr__( 'Search', 'woocommerce' ); ?>" class="button" id="search-submit" name="">
			</p>
			<input type="hidden" name="page" value="<?php echo isset( $_REQUEST['page'] ) ? esc_attr( wc_clean( $_REQUEST['page'] ) ) : ''; ?>"/>
			<?php $table->display(); ?>
		</form>

	<?php } else { ?>

		<div class="woocommerce-BlankState">
			<h2 class="woocommerce-BlankState-message">
				<?php esc_html_e( 'No customers have signed up to receive stock notifications from you just yet.', 'woocommerce' ); ?>
			</h2>
			<a class="woocommerce-BlankState-cta button" target="_blank" href="https://woocommerce.com/document/back-in-stock-notifications"><?php esc_html_e( 'Learn more', 'woocommerce' ); ?></a>
		</div>

	<?php } ?>
</div>
