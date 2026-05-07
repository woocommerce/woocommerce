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
 * Renders a `< 1 / 23 >` previous/next nav next to the H1 on the HPOS Order edit page.
 *
 * Position and total reflect the orders-list filter set the user came from
 * (read once from the referer, then carried forward via URL params on
 * prev/next links). When no list context is available, falls back to all
 * shop_orders sorted by `date_created` DESC.
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

		if ( $data['total'] < 1 ) {
			return;
		}

		$prev_url = $data['prev_id'] ? $this->build_edit_url( $data['prev_id'], $data['list_query'] ) : null;
		$next_url = $data['next_id'] ? $this->build_edit_url( $data['next_id'], $data['list_query'] ) : null;

		$count_label = sprintf(
			/* translators: 1: position of the current order in the list, 2: total orders in the list. */
			__( 'Order %1$s of %2$s', 'woocommerce' ),
			number_format_i18n( $data['position'] ),
			number_format_i18n( $data['total'] )
		);

		?>
		<nav class="woocommerce-order-list-nav" aria-label="<?php esc_attr_e( 'Order navigation', 'woocommerce' ); ?>">
			<?php $this->render_chevron( $prev_url, __( 'Previous order', 'woocommerce' ), self::CHEVRON_LEFT_PATH ); ?>
			<span class="woocommerce-order-list-nav__count" aria-label="<?php echo esc_attr( $count_label ); ?>">
				<span class="is-current"><?php echo esc_html( number_format_i18n( $data['position'] ) ); ?></span><span class="is-muted"> / <?php echo esc_html( number_format_i18n( $data['total'] ) ); ?></span>
			</span>
			<?php $this->render_chevron( $next_url, __( 'Next order', 'woocommerce' ), self::CHEVRON_RIGHT_PATH ); ?>
		</nav>
		<?php
	}

	/**
	 * Computes the position/total/prev_id/next_id for the given order.
	 *
	 * @param WC_Order $order Order being edited.
	 * @return array{position:int,total:int,prev_id:?int,next_id:?int,list_query:array<string,string>}
	 *
	 * @since 10.9.0
	 */
	public function get_navigation_data( WC_Order $order ): array {
		$list_query = $this->get_list_query_from_request();
		$query_args = $this->build_query_args( $list_query );

		$position_data = $this->compute_position_and_total( $order, $query_args );

		// If the current order doesn't match the active filters, drop the filters and
		// recompute against the unfiltered set so the nav stays useful.
		if ( ! $position_data['in_set'] && ! empty( $list_query ) ) {
			$list_query    = array();
			$query_args    = $this->build_query_args( $list_query );
			$position_data = $this->compute_position_and_total( $order, $query_args );
		}

		$boundaries = $this->compute_prev_next_ids( $order, $query_args );

		return array(
			'position'   => $position_data['position'],
			'total'      => $position_data['total'],
			'prev_id'    => $boundaries['prev_id'],
			'next_id'    => $boundaries['next_id'],
			'list_query' => $list_query,
		);
	}

	/**
	 * Renders a single chevron — as a link when navigable, or a disabled span at boundaries.
	 *
	 * @param string|null $url       Destination URL, or null when disabled.
	 * @param string      $label     Accessible label.
	 * @param string      $svg_path  Inline SVG `d` attribute.
	 */
	private function render_chevron( ?string $url, string $label, string $svg_path ): void {
		$svg = sprintf(
			'<svg class="woocommerce-order-list-nav__icon" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="%s"></path></svg>',
			esc_attr( $svg_path )
		);

		if ( null === $url ) {
			printf(
				'<span class="woocommerce-order-list-nav__chevron" aria-disabled="true" aria-label="%s">%s</span>',
				esc_attr( $label ),
				$svg // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG built from constant + escaped attr.
			);
			return;
		}

		printf(
			'<a href="%s" class="woocommerce-order-list-nav__chevron" aria-label="%s">%s</a>',
			esc_url( $url ),
			esc_attr( $label ),
			$svg // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG built from constant + escaped attr.
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
	 * Computes total count + position via two paginated count queries.
	 *
	 * Position is one-based. `in_set` is false when the current order doesn't match
	 * the active filters (e.g. the user filtered to status=completed but is viewing
	 * a processing order).
	 *
	 * @param WC_Order            $order      Current order.
	 * @param array<string,mixed> $query_args Base query args (type/status/etc).
	 * @return array{position:int,total:int,in_set:bool}
	 */
	private function compute_position_and_total( WC_Order $order, array $query_args ): array {
		$total_query = wc_get_orders(
			array_merge(
				$query_args,
				array(
					'limit'    => 1,
					'paginate' => true,
					'return'   => 'ids',
				)
			)
		);
		$total       = isset( $total_query->total ) ? (int) $total_query->total : 0;

		if ( $total < 1 ) {
			return array(
				'position' => 0,
				'total'    => 0,
				'in_set'   => false,
			);
		}

		$timestamp   = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : time();
		$newer_query = wc_get_orders(
			array_merge(
				$query_args,
				array(
					'date_created' => '>' . $timestamp,
					'limit'        => 1,
					'paginate'     => true,
					'return'       => 'ids',
				)
			)
		);
		$newer_count = isset( $newer_query->total ) ? (int) $newer_query->total : 0;
		$position    = $newer_count + 1;

		return array(
			'position' => $position,
			'total'    => $total,
			'in_set'   => $position <= $total,
		);
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
