<?php
/**
 * Renders Newer / Older navigation in the Order edit page header.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Orders;

use WC_Abstract_Order;

/**
 * Outputs links to the next newer and next older order, by date created,
 * across all orders.
 *
 * @since 11.4.0
 */
class OrderNavigation {

	/**
	 * Chevron-left path data, sourced from @wordpress/icons (24×24 viewBox).
	 */
	private const CHEVRON_LEFT_PATH = 'M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z';

	/**
	 * Chevron-right path data, sourced from @wordpress/icons (24×24 viewBox).
	 */
	private const CHEVRON_RIGHT_PATH = 'M10.6 6 9.4 7.1 14.3 12l-4.9 4.9 1.2 1.1 6-6z';

	/**
	 * Outputs the navigation markup for the given order.
	 *
	 * No-op for non-`shop_order` types (e.g. refunds).
	 *
	 * @since 11.4.0
	 *
	 * @param WC_Abstract_Order $order The current order.
	 * @return void
	 */
	public static function render( WC_Abstract_Order $order ): void {
		if ( 'shop_order' !== $order->get_type() ) {
			return;
		}

		$neighbors = self::get_neighbors( $order );

		?>
		<nav class="woocommerce-order-navigation" aria-label="<?php esc_attr_e( 'Order navigation', 'woocommerce' ); ?>">
			<?php
			self::render_button(
				self::get_order_url( $neighbors['newer_id'] ),
				__( 'Newer', 'woocommerce' ),
				__( 'Newer order', 'woocommerce' ),
				self::CHEVRON_LEFT_PATH,
				false
			);
			self::render_button(
				self::get_order_url( $neighbors['older_id'] ),
				__( 'Older', 'woocommerce' ),
				__( 'Older order', 'woocommerce' ),
				self::CHEVRON_RIGHT_PATH,
				true
			);
			?>
		</nav>
		<?php
	}

	/**
	 * Finds the orders directly before and after the given order, using the
	 * same date then ID sequence as the default orders list.
	 *
	 * @since 11.4.0
	 *
	 * @param WC_Abstract_Order $order The current order.
	 * @return array{newer_id: int|null, older_id: int|null}
	 */
	public static function get_neighbors( WC_Abstract_Order $order ): array {
		$date = $order->get_date_created();
		if ( null === $date ) {
			return array(
				'newer_id' => null,
				'older_id' => null,
			);
		}

		$timestamp = $date->getTimestamp();

		// Imports and bursts of sales can share a second, so a date comparison alone would skip orders.
		$same_second = self::query_order_ids( $timestamp . '...' . $timestamp, 'ID', 'ASC', -1 );
		$position    = array_search( $order->get_id(), $same_second, true );
		$newer_id    = false === $position ? null : ( $same_second[ $position + 1 ] ?? null );
		$older_id    = false === $position ? null : ( $same_second[ $position - 1 ] ?? null );

		return array(
			'newer_id' => $newer_id ?? ( self::query_order_ids( '>' . $timestamp, 'date ID', 'ASC', 1 )[0] ?? null ),
			'older_id' => $older_id ?? ( self::query_order_ids( '<' . $timestamp, 'date ID', 'DESC', 1 )[0] ?? null ),
		);
	}

	/**
	 * Returns the IDs of orders matching a date created condition.
	 *
	 * @param string $date_created Date condition, in `wc_get_orders` shorthand.
	 * @param string $orderby      Sort columns.
	 * @param string $order        Sort direction.
	 * @param int    $limit        Maximum number of results, or -1 for all.
	 * @return list<int>
	 */
	private static function query_order_ids( string $date_created, string $orderby, string $order, int $limit ): array {
		$ids = wc_get_orders(
			array(
				'type'         => 'shop_order',
				'date_created' => $date_created,
				'orderby'      => $orderby,
				'order'        => $order,
				'limit'        => $limit,
				'return'       => 'ids',
			)
		);

		return is_array( $ids ) ? array_values( wp_parse_id_list( $ids ) ) : array();
	}

	/**
	 * Returns the edit URL for an order, or null when there is no order to link to.
	 *
	 * @param int|null $order_id Order ID.
	 * @return string|null
	 */
	private static function get_order_url( ?int $order_id ): ?string {
		if ( null === $order_id ) {
			return null;
		}

		return add_query_arg(
			array(
				'action' => 'edit',
				'id'     => $order_id,
			),
			wc_get_container()->get( PageController::class )->get_base_page_url( 'shop_order' )
		);
	}

	/**
	 * Renders a single button: an active link, or a disabled button at the end of the sequence.
	 *
	 * @param string|null $url        URL for the button, or null when disabled.
	 * @param string      $text       Visible text.
	 * @param string      $aria_label Accessible label.
	 * @param string      $icon_path  SVG path data for the chevron.
	 * @param bool        $icon_right Whether the icon follows the text.
	 */
	private static function render_button( ?string $url, string $text, string $aria_label, string $icon_path, bool $icon_right ): void {
		$class = 'components-button is-tertiary has-icon has-text woocommerce-order-navigation__button';
		if ( $icon_right ) {
			$class .= ' has-icon-right';
		}

		$icon  = '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="' . esc_attr( $icon_path ) . '"></path></svg>';
		$label = '<span>' . esc_html( $text ) . '</span>';

		$content = $icon_right ? $label . $icon : $icon . $label;

		if ( null === $url ) {
			?>
			<button type="button" class="<?php echo esc_attr( $class ); ?>" disabled aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Both parts are escaped above. ?></button>
			<?php
		} else {
			?>
			<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $class ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Both parts are escaped above. ?></a>
			<?php
		}
	}
}
