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
 * @version 10.9.0
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
 * @since 10.8.0
 *
 * @param bool $has_items Whether there are any notifications to render.
 */
do_action( 'woocommerce_before_account_back_in_stock_notifications', $has_items );
?>

<?php if ( $has_items ) : ?>

	<table class="woocommerce-back-in-stock-notifications-table woocommerce-MyAccount-back-in-stock-notifications shop_table shop_table_responsive">
		<thead>
			<tr>
				<th scope="col" class="woocommerce-back-in-stock-notifications-table__header woocommerce-back-in-stock-notifications-table__header-product"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
				<th scope="col" class="woocommerce-back-in-stock-notifications-table__header woocommerce-back-in-stock-notifications-table__header-variation"><?php esc_html_e( 'Variation', 'woocommerce' ); ?></th>
				<th scope="col" class="woocommerce-back-in-stock-notifications-table__header woocommerce-back-in-stock-notifications-table__header-date"><?php esc_html_e( 'Date signed up', 'woocommerce' ); ?></th>
				<th scope="col" class="woocommerce-back-in-stock-notifications-table__header woocommerce-back-in-stock-notifications-table__header-actions"><?php esc_html_e( 'Actions', 'woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $notifications as $notification ) : ?>
			<?php
			$product_name = $notification->get_product_name();
			$permalink    = $notification->get_product_permalink();
			$variation    = $notification->get_product_formatted_variation_list( true );
			$date_created = $notification->get_date_created();
			?>
			<tr class="woocommerce-back-in-stock-notifications-table__row woocommerce-back-in-stock-notifications-table__row--status-<?php echo esc_attr( (string) $notification->get_status() ); ?>">
				<td class="woocommerce-back-in-stock-notifications-table__cell woocommerce-back-in-stock-notifications-table__cell-product" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>">
					<?php if ( '' !== $product_name && '' !== $permalink ) : ?>
						<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $product_name ); ?></a>
					<?php elseif ( '' !== $product_name ) : ?>
						<?php echo esc_html( $product_name ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Product unavailable', 'woocommerce' ); ?>
					<?php endif; ?>
				</td>
				<td class="woocommerce-back-in-stock-notifications-table__cell woocommerce-back-in-stock-notifications-table__cell-variation" data-title="<?php esc_attr_e( 'Variation', 'woocommerce' ); ?>">
					<?php echo '' !== $variation ? esc_html( $variation ) : '&mdash;'; ?>
				</td>
				<td class="woocommerce-back-in-stock-notifications-table__cell woocommerce-back-in-stock-notifications-table__cell-date" data-title="<?php esc_attr_e( 'Date signed up', 'woocommerce' ); ?>">
					<?php if ( $date_created ) : ?>
						<time datetime="<?php echo esc_attr( $date_created->date( 'c' ) ); ?>"><?php echo esc_html( wc_format_datetime( $date_created ) ); ?></time>
					<?php else : ?>
						&mdash;
					<?php endif; ?>
				</td>
				<td class="woocommerce-back-in-stock-notifications-table__cell woocommerce-back-in-stock-notifications-table__cell-actions" data-title="<?php esc_attr_e( 'Actions', 'woocommerce' ); ?>">
					<form method="post" action="<?php echo esc_url( wc_get_endpoint_url( MyAccountEndpoint::ENDPOINT, '', wc_get_page_permalink( 'myaccount' ) ) ); ?>" class="woocommerce-back-in-stock-notifications-cancel-form">
						<input type="hidden" name="<?php echo esc_attr( MyAccountEndpoint::CANCEL_ACTION ); ?>" value="1" />
						<input type="hidden" name="notification_id" value="<?php echo esc_attr( (string) $notification->get_id() ); ?>" />
						<?php wp_nonce_field( MyAccountEndpoint::get_cancel_nonce_action( (int) $notification->get_id() ) ); ?>
						<button type="submit" class="woocommerce-button button<?php echo esc_attr( $wp_button_class ); ?>"><?php esc_html_e( 'Cancel', 'woocommerce' ); ?></button>
					</form>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( $total_pages > 1 ) : ?>
		<?php
		$endpoint_url = wc_get_endpoint_url( MyAccountEndpoint::ENDPOINT, '', wc_get_page_permalink( 'myaccount' ) );
		// `format` uses %#% as the page-number placeholder; WC's pretty endpoints
		// rewrite to `<endpoint>/<page>/`, ugly fallback uses ?<endpoint>=<page>.
		$pretty = get_option( 'permalink_structure' );
		$format = $pretty ? '%#%/' : '?' . MyAccountEndpoint::ENDPOINT . '=%#%';
		$base   = $pretty ? trailingslashit( $endpoint_url ) . '%_%' : $endpoint_url . '%_%';
		?>
		<nav class="woocommerce-pagination woocommerce-back-in-stock-notifications-pagination" aria-label="<?php esc_attr_e( 'Stock notifications pagination', 'woocommerce' ); ?>">
			<?php
			echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				array(
					'base'      => $base,
					'format'    => $format,
					'current'   => $current_page,
					'total'     => $total_pages,
					'prev_text' => esc_html_x( '&larr; Previous', 'pagination', 'woocommerce' ),
					'next_text' => esc_html_x( 'Next &rarr;', 'pagination', 'woocommerce' ),
					'type'      => 'list',
				)
			);
			?>
		</nav>
	<?php endif; ?>

<?php else : ?>

	<?php wc_print_notice( esc_html__( "You haven't signed up for any back-in-stock notifications yet.", 'woocommerce' ) . ' <a class="woocommerce-Button wc-forward button' . esc_attr( $wp_button_class ) . '" href="' . esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ) . '">' . esc_html__( 'Browse products', 'woocommerce' ) . '</a>', 'notice' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment ?>

<?php endif; ?>

<?php
/**
 * Fires after the back in stock notifications table is rendered on My Account.
 *
 * @since 10.8.0
 *
 * @param bool $has_items Whether there were any notifications rendered.
 */
do_action( 'woocommerce_after_account_back_in_stock_notifications', $has_items );
