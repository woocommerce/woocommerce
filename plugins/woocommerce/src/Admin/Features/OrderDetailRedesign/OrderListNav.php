<?php
/**
 * Order list navigation for the Order detail page.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\Features\OrderDetailRedesign;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order;

/**
 * Renders a `< Prev   Next >` previous/next nav next to the H1 on the HPOS Order edit page.
 *
 * Direction follows list-traversal semantics, not chronological: the orders list defaults
 * to date DESC, so "Prev" (left) goes to the newer order (the one above in the list) and
 * "Next" (right) goes to the older order (the one below). Filters are read once from the
 * orders-list referer and carried forward on each link. When no list context is available,
 * falls back to all shop_orders sorted by `date_created` DESC.
 *
 * @since 10.9.0
 */
class OrderListNav {

	/**
	 * URL params forwarded between the orders list and the prev/next links.
	 */
	private const ALLOWED_LIST_PARAMS = array(
		'status',
		's',
		'm',
		'_customer_user',
	);

	/**
	 * SVG path for the chevron-left icon, sourced from `@wordpress/icons`.
	 */
	private const CHEVRON_LEFT_PATH = 'M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z';

	/**
	 * SVG path for the chevron-right icon, sourced from `@wordpress/icons`.
	 */
	private const CHEVRON_RIGHT_PATH = 'M10.6 6L9.4 7l5 5-5 5 1.2 1 6.4-6z';

	/**
	 * Outputs the navigation markup for the given order.
	 *
	 * @param WC_Order $order Order being edited.
	 *
	 * @since 10.9.0
	 */
	public function render( WC_Order $order ): void {
		if ( 'shop_order' !== $order->get_type() ) {
			return;
		}

		$data = $this->get_navigation_data( $order );

		$prev_url = $data['prev_id'] ? $this->build_edit_url( $data['prev_id'], $data['list_query'] ) : null;
		$next_url = $data['next_id'] ? $this->build_edit_url( $data['next_id'], $data['list_query'] ) : null;

		// Nothing to navigate to in either direction — there's only one order in the set.
		if ( null === $prev_url && null === $next_url ) {
			return;
		}

		?>
		<nav class="woocommerce-order-list-nav" aria-label="<?php esc_attr_e( 'Order navigation', 'woocommerce' ); ?>">
			<?php
			$this->render_link( $prev_url, __( 'Prev', 'woocommerce' ), self::CHEVRON_LEFT_PATH, true );
			$this->render_link( $next_url, __( 'Next', 'woocommerce' ), self::CHEVRON_RIGHT_PATH, false );
			?>
		</nav>
		<?php
	}

	/**
	 * Computes the prev_id / next_id / forwarded list filters for the given order.
	 *
	 * If the current order is excluded by the active filters (e.g. the user filtered the
	 * orders list to status=completed but is now viewing a processing order), the filters
	 * are silently dropped so the nav still shows usable links.
	 *
	 * @param WC_Order $order Order being edited.
	 * @return array{prev_id:?int,next_id:?int,list_query:array<string,string>}
	 *
	 * @since 10.9.0
	 */
	public function get_navigation_data( WC_Order $order ): array {
		$list_query = $this->get_list_query_from_request();
		$query_args = $this->build_query_args( $list_query );

		if ( ! empty( $list_query ) && ! $this->order_matches_filters( $order, $query_args ) ) {
			$list_query = array();
			$query_args = $this->build_query_args( $list_query );
		}

		$boundaries = $this->compute_prev_next_ids( $order, $query_args );

		return array(
			'prev_id'    => $boundaries['prev_id'],
			'next_id'    => $boundaries['next_id'],
			'list_query' => $list_query,
		);
	}

	/**
	 * Renders a single Prev / Next link — chevron + label, or a disabled span at boundaries.
	 *
	 * @param string|null $url        Destination URL, or null when disabled.
	 * @param string      $label      Visible link text (e.g. "Prev", "Next").
	 * @param string      $svg_path   Inline SVG `d` attribute for the chevron.
	 * @param bool        $icon_first Whether the chevron renders before the label (true for Prev, false for Next).
	 */
	private function render_link( ?string $url, string $label, string $svg_path, bool $icon_first ): void {
		$svg             = sprintf(
			'<svg class="woocommerce-order-list-nav__icon" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="%s"></path></svg>',
			esc_attr( $svg_path )
		);
		$text            = sprintf( '<span class="woocommerce-order-list-nav__label">%s</span>', esc_html( $label ) );
		$inner           = $icon_first ? $svg . $text : $text . $svg;
		$direction_class = $icon_first ? 'is-prev' : 'is-next';

		if ( null === $url ) {
			printf(
				'<span class="woocommerce-order-list-nav__link %s" aria-disabled="true">%s</span>',
				esc_attr( $direction_class ),
				$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG built from constant + escaped label.
			);
			return;
		}

		printf(
			'<a href="%s" class="woocommerce-order-list-nav__link %s">%s</a>',
			esc_url( $url ),
			esc_attr( $direction_class ),
			$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG built from constant + escaped label.
		);
	}

	/**
	 * Extracts forwarded list filter params from the request.
	 *
	 * Precedence: current URL params first (already forwarded by a prior prev/next click),
	 * then params parsed from the orders-list referer on first arrival.
	 *
	 * @return array<string,string>
	 */
	private function get_list_query_from_request(): array {
		$params = $this->extract_allowed_params( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! empty( $params ) ) {
			return $params;
		}

		$referer = wp_get_referer();
		if ( ! $referer ) {
			return array();
		}

		$parsed = wp_parse_url( $referer );
		if ( empty( $parsed['query'] ) ) {
			return array();
		}

		$referer_params = array();
		wp_parse_str( $parsed['query'], $referer_params );

		$is_orders_list = 'wc-orders' === ( $referer_params['page'] ?? '' )
			&& empty( $referer_params['action'] );

		if ( ! $is_orders_list ) {
			return array();
		}

		return $this->extract_allowed_params( $referer_params );
	}

	/**
	 * Picks whitelisted params from the given source and sanitizes them.
	 *
	 * @param array<int|string,mixed> $source Raw param source.
	 * @return array<string,string>
	 */
	private function extract_allowed_params( array $source ): array {
		$out = array();
		foreach ( self::ALLOWED_LIST_PARAMS as $key ) {
			if ( ! isset( $source[ $key ] ) || ! is_scalar( $source[ $key ] ) ) {
				continue;
			}
			$value = (string) $source[ $key ];
			if ( '' === $value || 'all' === $value ) {
				continue;
			}
			$out[ $key ] = sanitize_text_field( wp_unslash( $value ) );
		}
		return $out;
	}

	/**
	 * Translates orders-list URL params into `wc_get_orders` arguments.
	 *
	 * @param array<string,string> $list_query Whitelisted list params.
	 * @return array<string,mixed>
	 */
	private function build_query_args( array $list_query ): array {
		$args = array(
			'type'    => 'shop_order',
			'orderby' => 'date',
			'order'   => 'DESC',
		);

		if ( isset( $list_query['status'] ) ) {
			$args['status'] = $list_query['status'];
		} else {
			$args['status'] = $this->get_default_visible_statuses();
		}

		if ( isset( $list_query['s'] ) ) {
			$args['s'] = $list_query['s'];
		}

		if ( isset( $list_query['_customer_user'] ) && (int) $list_query['_customer_user'] > 0 ) {
			$args['customer'] = (int) $list_query['_customer_user'];
		}

		if ( isset( $list_query['m'] ) && preg_match( '/^[0-9]{6}$/', $list_query['m'] ) ) {
			$year      = substr( $list_query['m'], 0, 4 );
			$month     = substr( $list_query['m'], 4, 2 );
			$timestamp = strtotime( "{$year}-{$month}-01" );
			if ( false !== $timestamp ) {
				$last_day             = gmdate( 'Y-m-t', $timestamp );
				$args['date_created'] = "{$year}-{$month}-01...{$last_day}";
			}
		}

		return $args;
	}

	/**
	 * Mirrors the orders list table's default visible-status set.
	 *
	 * @return string[]
	 */
	private function get_default_visible_statuses(): array {
		return array_values(
			array_intersect(
				array_keys( wc_get_order_statuses() ),
				get_post_stati( array( 'show_in_admin_all_list' => true ), 'names' )
			)
		);
	}

	/**
	 * Whether the given order matches the active filter set (i.e. would appear in that list view).
	 *
	 * Used to decide whether to drop the filters before computing prev/next, so the nav still
	 * shows usable links when the user has navigated to an order outside their filtered view.
	 *
	 * @param WC_Order            $order      Order being edited.
	 * @param array<string,mixed> $query_args Filter set as `wc_get_orders` args.
	 */
	private function order_matches_filters( WC_Order $order, array $query_args ): bool {
		$match = wc_get_orders(
			array_merge(
				$query_args,
				array(
					'post__in' => array( $order->get_id() ),
					'limit'    => 1,
					'return'   => 'ids',
				)
			)
		);
		return is_array( $match ) && ! empty( $match );
	}

	/**
	 * Finds the IDs of the orders at position-1 (left chevron) and position+1 (right chevron).
	 *
	 * The orders list defaults to date DESC, so position 1 is the newest order. Decrementing
	 * the position number means moving to a newer order; incrementing means moving to an older
	 * one. `prev_id` (left chevron) therefore points to the newer order; `next_id` (right
	 * chevron) points to the older one.
	 *
	 * @param WC_Order            $order      Current order.
	 * @param array<string,mixed> $query_args Base query args.
	 * @return array{prev_id:?int,next_id:?int}
	 */
	private function compute_prev_next_ids( WC_Order $order, array $query_args ): array {
		$timestamp = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : time();

		// Left chevron: decrement position → newer order.
		$prev_ids = wc_get_orders(
			array_merge(
				$query_args,
				array(
					'date_created' => '>' . $timestamp,
					'orderby'      => 'date',
					'order'        => 'ASC',
					'limit'        => 1,
					'return'       => 'ids',
				)
			)
		);

		// Right chevron: increment position → older order.
		$next_ids = wc_get_orders(
			array_merge(
				$query_args,
				array(
					'date_created' => '<' . $timestamp,
					'orderby'      => 'date',
					'order'        => 'DESC',
					'limit'        => 1,
					'return'       => 'ids',
				)
			)
		);

		return array(
			'prev_id' => $this->first_id( $prev_ids ),
			'next_id' => $this->first_id( $next_ids ),
		);
	}

	/**
	 * Extracts the first ID from a `wc_get_orders( return => 'ids' )` result.
	 *
	 * @param mixed $result Result of wc_get_orders with `return=ids`.
	 */
	private function first_id( $result ): ?int {
		if ( ! is_array( $result ) || empty( $result ) ) {
			return null;
		}
		$first = $result[0];
		return is_numeric( $first ) ? (int) $first : null;
	}

	/**
	 * Builds the HPOS Order edit URL with forwarded list filters.
	 *
	 * @param int                  $order_id   Target order ID.
	 * @param array<string,string> $list_query Forwarded list params.
	 */
	private function build_edit_url( int $order_id, array $list_query ): string {
		$base = OrderUtil::get_order_admin_edit_url( $order_id );
		return $list_query ? add_query_arg( $list_query, $base ) : $base;
	}
}
