<?php
/**
 * Back in stock notifications
 *
 * Shows the current user's back in stock notifications on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/stock-notifications.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.2.0
 *
 * @var array $notifications Array of Notification objects for the current user (one page).
 * @var bool  $has_items     Whether there are any notifications to render.
 * @var int   $current_page  1-indexed current page number.
 * @var int   $total_pages   Total number of pages of notifications.
 * @var int   $total_items   Total number of notifications across all pages.
 * @var int   $per_page      Notifications shown per page.
 */

use Automattic\WooCommerce\Internal\StockNotifications\Frontend\MyAccountEndpoint;

defined( 'ABSPATH' ) || exit;

$wp_button_class = wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '';

/**
 * Fires before the back in stock notifications table is rendered on My Account.
 *
 * @since 11.2.0
 *
 * @param bool $has_items Whether there are any notifications to render.
 */
do_action( 'woocommerce_before_account_customer_stock_notifications', $has_items );
?>

<?php if ( $has_items ) : ?>

	<table class="woocommerce-customer-stock-notifications-table woocommerce-MyAccount-customerStockNotifications shop_table shop_table_responsive">
		<thead>
			<tr>
				<th scope="col" class="woocommerce-customer-stock-notifications-table__header woocommerce-customer-stock-notifications-table__header-product"><span class="nobr"><?php esc_html_e( 'Product', 'woocommerce' ); ?></span></th>
				<th scope="col" class="woocommerce-customer-stock-notifications-table__header woocommerce-customer-stock-notifications-table__header-date"><span class="nobr"><?php esc_html_e( 'Date signed up', 'woocommerce' ); ?></span></th>
				<th scope="col" class="woocommerce-customer-stock-notifications-table__header woocommerce-customer-stock-notifications-table__header-actions"><span class="nobr"><?php esc_html_e( 'Actions', 'woocommerce' ); ?></span></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $notifications as $notification ) : ?>
			<?php
			$product_name = MyAccountEndpoint::get_display_product_name( $notification );
			$permalink    = $notification->get_product_permalink();
			$variation    = $notification->get_product_formatted_variation_list( true );
			$date_created = $notification->get_date_created();

			$cancel_label_name = '' !== $product_name ? $product_name : __( 'an unavailable product', 'woocommerce' );
			if ( '' !== $variation ) {
				$cancel_label_name .= ' ' . $variation;
			}
			/* translators: %s: product name, followed by its variation attributes when the sign-up is for a variation. */
			$cancel_label = sprintf( __( 'Cancel stock notification for %s', 'woocommerce' ), $cancel_label_name );
			?>
			<tr class="woocommerce-customer-stock-notifications-table__row woocommerce-customer-stock-notifications-table__row--status-<?php echo esc_attr( (string) $notification->get_status() ); ?>">
				<td class="woocommerce-customer-stock-notifications-table__cell woocommerce-customer-stock-notifications-table__cell-product" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>">
					<?php
					/*
					 * A deleted product still gets a row, rather than being skipped, so the
					 * customer can see the sign-up exists and cancel it.
					 */
					?>
					<?php if ( '' !== $product_name && '' !== $permalink ) : ?>
						<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $product_name ); ?></a>
					<?php elseif ( '' !== $product_name ) : ?>
						<?php echo esc_html( $product_name ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Product unavailable', 'woocommerce' ); ?>
					<?php endif; ?>

					<?php if ( '' !== $variation ) : ?>
						<div class="description"><?php echo esc_html( $variation ); ?></div>
					<?php endif; ?>
				</td>
				<td class="woocommerce-customer-stock-notifications-table__cell woocommerce-customer-stock-notifications-table__cell-date" data-title="<?php esc_attr_e( 'Date signed up', 'woocommerce' ); ?>">
					<?php if ( $date_created ) : ?>
						<time datetime="<?php echo esc_attr( $date_created->date( 'c' ) ); ?>"><?php echo esc_html( wc_format_datetime( $date_created ) ); ?></time>
					<?php else : ?>
						&mdash;
					<?php endif; ?>
				</td>
				<td class="woocommerce-customer-stock-notifications-table__cell woocommerce-customer-stock-notifications-table__cell-actions actions" data-title="<?php esc_attr_e( 'Actions', 'woocommerce' ); ?>">
					<?php if ( MyAccountEndpoint::is_cancellable( $notification ) ) : ?>
						<form method="post" action="<?php echo esc_url( wc_get_endpoint_url( MyAccountEndpoint::ENDPOINT, '', wc_get_page_permalink( 'myaccount' ) ) ); ?>" class="woocommerce-customer-stock-notifications-cancel-form">
							<input type="hidden" name="<?php echo esc_attr( MyAccountEndpoint::CANCEL_ACTION ); ?>" value="1" />
							<input type="hidden" name="notification_id" value="<?php echo esc_attr( (string) $notification->get_id() ); ?>" />
							<?php wp_nonce_field( MyAccountEndpoint::get_cancel_nonce_action( (int) $notification->get_id() ) ); ?>
							<button type="submit" class="woocommerce-button button<?php echo esc_attr( $wp_button_class ); ?>" aria-label="<?php echo esc_attr( $cancel_label ); ?>"><?php esc_html_e( 'Cancel', 'woocommerce' ); ?></button>
						</form>
					<?php else : ?>
						&mdash;
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<?php
	/**
	 * Fires before the stock notifications pagination is rendered on My Account.
	 *
	 * @since 11.2.0
	 */
	do_action( 'woocommerce_before_account_customer_stock_notifications_pagination' );
	?>

	<?php if ( $total_pages > 1 ) : ?>
		<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
			<?php if ( 1 !== $current_page ) : ?>
				<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button<?php echo esc_attr( $wp_button_class ); ?>" href="<?php echo esc_url( wc_get_endpoint_url( MyAccountEndpoint::ENDPOINT, (string) ( $current_page - 1 ), wc_get_page_permalink( 'myaccount' ) ) ); ?>"><?php esc_html_e( 'Previous', 'woocommerce' ); ?></a>
			<?php endif; ?>

			<?php if ( $total_pages !== $current_page ) : ?>
				<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button<?php echo esc_attr( $wp_button_class ); ?>" href="<?php echo esc_url( wc_get_endpoint_url( MyAccountEndpoint::ENDPOINT, (string) ( $current_page + 1 ), wc_get_page_permalink( 'myaccount' ) ) ); ?>"><?php esc_html_e( 'Next', 'woocommerce' ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

<?php else : ?>

	<?php wc_print_notice( esc_html__( "You haven't signed up for any back-in-stock notifications yet.", 'woocommerce' ) . ' <a class="woocommerce-Button wc-forward button' . esc_attr( $wp_button_class ) . '" href="' . esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ) . '">' . esc_html__( 'Browse products', 'woocommerce' ) . '</a>', 'notice' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment ?>

<?php endif; ?>

<?php
/**
 * Fires after the back in stock notifications table is rendered on My Account.
 *
 * @since 11.2.0
 *
 * @param bool $has_items Whether there were any notifications rendered.
 */
do_action( 'woocommerce_after_account_customer_stock_notifications', $has_items );
