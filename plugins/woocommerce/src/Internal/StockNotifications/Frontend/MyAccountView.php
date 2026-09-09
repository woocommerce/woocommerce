<?php
/**
 * MyAccountView class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;

/**
 * Prepares the data the My Account stock notifications template renders.
 *
 * Resolves every endpoint constant, URL and label to a plain value so the
 * template, which themes copy and override, never references this namespace.
 *
 * @internal
 */
final class MyAccountView {

	/**
	 * Get template arguments for the stock notifications tables.
	 *
	 * @param array<Notification> $pending Pending notifications, newest first.
	 * @param array<Notification> $active  Active notifications for the current page.
	 * @param array<string,int>   $page    Pagination state: current_page, total_pages, total_items, per_page.
	 * @return array<string,mixed>
	 *
	 * @since 11.2.0
	 */
	public function get_template_args( array $pending, array $active, array $page ): array {
		$current_page = max( 1, (int) ( $page['current_page'] ?? 1 ) );
		$total_pages  = max( 1, (int) ( $page['total_pages'] ?? 1 ) );

		return array(
			'pending_rows'      => $this->get_rows( $pending, $current_page ),
			'active_rows'       => $this->get_rows( $active, $current_page ),
			'has_pending'       => ! empty( $pending ),
			'has_items'         => ! empty( $pending ) || ! empty( $active ),
			'current_page'      => $current_page,
			'total_pages'       => $total_pages,
			'total_items'       => max( 0, (int) ( $page['total_items'] ?? 0 ) ),
			'per_page'          => max( 1, (int) ( $page['per_page'] ?? MyAccountEndpoint::DEFAULT_PER_PAGE ) ),
			'previous_page_url' => $current_page > 1 ? MyAccountEndpoint::get_endpoint_url( $current_page - 1 ) : '',
			'next_page_url'     => $current_page < $total_pages ? MyAccountEndpoint::get_endpoint_url( $current_page + 1 ) : '',
			'shop_url'          => \wc_get_page_permalink( 'shop' ),
		);
	}

	/**
	 * Flatten notifications into display rows.
	 *
	 * Action URLs are empty strings when the action does not apply to the row,
	 * so the template only has to test for a non-empty value.
	 *
	 * @param array<Notification> $notifications Notifications to flatten.
	 * @param int                 $current_page  1-indexed page the rows render on, carried by the action URLs.
	 * @return array<int,array<string,mixed>>
	 */
	private function get_rows( array $notifications, int $current_page ): array {
		$rows = array();

		foreach ( $notifications as $notification ) {
			if ( ! $notification instanceof Notification ) {
				continue;
			}

			$id           = (int) $notification->get_id();
			$product_name = MyAccountEndpoint::get_display_product_name( $notification );
			$variation    = (string) $notification->get_product_formatted_variation_list( true );
			$date_created = $notification->get_date_created();
			$can_resend   = MyAccountEndpoint::can_resend( $notification );
			$can_cancel   = MyAccountEndpoint::is_cancellable( $notification );

			$label_name = '' !== $product_name ? $product_name : __( 'an unavailable product', 'woocommerce' );
			if ( '' !== $variation ) {
				$label_name .= ' ' . $variation;
			}

			$rows[] = array(
				'id'           => $id,
				'status'       => (string) $notification->get_status(),
				'product_name' => $product_name,
				'product_url'  => (string) $notification->get_product_permalink(),
				'variation'    => $variation,
				'date_iso'     => $date_created ? $date_created->date( 'c' ) : '',
				'date_display' => $date_created ? \wc_format_datetime( $date_created ) : '',
				'resend_url'   => $can_resend ? MyAccountEndpoint::get_action_url( MyAccountEndpoint::ACTION_RESEND, $id, $current_page ) : '',
				/* translators: %s: product name, followed by its variation attributes when the sign-up is for a variation. */
				'resend_label' => $can_resend ? sprintf( __( 'Resend verification email for %s', 'woocommerce' ), $label_name ) : '',
				'cancel_url'   => $can_cancel ? MyAccountEndpoint::get_action_url( MyAccountEndpoint::ACTION_CANCEL, $id, $current_page ) : '',
				/* translators: %s: product name, followed by its variation attributes when the sign-up is for a variation. */
				'cancel_label' => $can_cancel ? sprintf( __( 'Cancel stock notification for %s', 'woocommerce' ), $label_name ) : '',
				'notification' => $notification,
			);
		}

		return $rows;
	}
}
