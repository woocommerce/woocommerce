<?php
/**
 * Renders prev/next order navigation in the Order detail page header.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\Features\OrderDetailRedesign;

use WC_Order;

/**
 * Outputs the right-aligned `< 1 / 23 >` navigation that lets users walk
 * between adjacent orders without going back to the list. Server-rendered;
 * positions are computed against the orders-list filter set the user came
 * from (carried via `wp_get_referer()` and forwarded in subsequent links).
 *
 * Gated behind the `order-detail-redesign` feature flag — caller checks
 * `Init::is_enabled()` before invoking.
 *
 * @since 10.9.0
 */
class OrderListNav {

	/**
	 * Chevron-left path data, sourced from @wordpress/icons (24×24 viewBox).
	 */
	private const CHEVRON_LEFT_PATH = 'M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z';

	/**
	 * Chevron-right path data, sourced from @wordpress/icons (24×24 viewBox).
	 */
	private const CHEVRON_RIGHT_PATH = 'M10.6 6 9.4 7.1 14.3 12l-4.9 4.9 1.2 1.1 6-6z';

	/**
	 * URL params from the orders list page that we forward to keep the
	 * prev/next walk scoped to the user's filter set.
	 */
	private const FILTER_WHITELIST = array( 'status', 's', '_customer_user', '_created_via' );

	/**
	 * Outputs the navigation markup for the given order.
	 *
	 * No-op for non-`shop_order` types (e.g. refunds).
	 *
	 * @since 10.9.0
	 *
	 * @param WC_Order $order The current order.
	 * @return void
	 */
	public static function render( WC_Order $order ): void {
		if ( 'shop_order' !== $order->get_type() ) {
			return;
		}

		$data       = self::get_navigation_data( $order );
		$list_query = $data['list_query'];

		$prev_url = self::build_order_url( $data['prev_id'], $list_query );
		$next_url = self::build_order_url( $data['next_id'], $list_query );

		?>
		<nav class="woocommerce-order-list-nav" aria-label="<?php esc_attr_e( 'Order navigation', 'woocommerce' ); ?>">
			<?php
			self::render_button(
				$prev_url,
				__( 'Prev', 'woocommerce' ),
				__( 'Previous order', 'woocommerce' ),
				self::CHEVRON_LEFT_PATH,
				'is-prev'
			);
			self::render_button(
				$next_url,
				__( 'Next', 'woocommerce' ),
				__( 'Next order', 'woocommerce' ),
				self::CHEVRON_RIGHT_PATH,
				'is-next'
			);
			?>
		</nav>
		<?php
	}

	/**
	 * Resolves the prev/next order IDs for the order within the current
	 * orders-list filter set.
	 *
	 * Falls back to the unfiltered set (all orders by date DESC) if the
	 * filters exclude the current order.
	 *
	 * @since 10.9.0
	 *
	 * @param WC_Order $order The current order.
	 * @return array{prev_id: int|null, next_id: int|null, list_query: array<string, mixed>}
	 */
	public static function get_navigation_data( WC_Order $order ): array {
		$list_query = self::get_list_query_from_request();

		$result = self::compute_neighbors( $order, $list_query );
		if ( null === $result ) {
			$list_query = array();
			$result     = self::compute_neighbors( $order, array() );
		}

		if ( null === $result ) {
			$result = array(
				'prev_id' => null,
				'next_id' => null,
			);
		}

		$result['list_query'] = $list_query;
		return $result;
	}

	/**
	 * Renders a single prev/next button — either an active link or a disabled span.
	 *
	 * @param string|null $url       URL for the button, or null when disabled.
	 * @param string      $text      Visible text ("Prev" / "Next").
	 * @param string      $aria      Accessible label ("Previous order" / "Next order").
	 * @param string      $svg_path  SVG path data for the chevron.
	 * @param string      $variant   "is-prev" (icon before text) or "is-next" (icon after text).
	 */
	private static function render_button( ?string $url, string $text, string $aria, string $svg_path, string $variant ): void {
		$svg     = '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="' . esc_attr( $svg_path ) . '"></path></svg>';
		$content = 'is-prev' === $variant
			? $svg . '<span class="woocommerce-order-list-nav__label">' . esc_html( $text ) . '</span>'
			: '<span class="woocommerce-order-list-nav__label">' . esc_html( $text ) . '</span>' . $svg;
		$class   = 'woocommerce-order-list-nav__button ' . esc_attr( $variant );

		if ( null === $url ) {
			?>
			<span class="<?php echo esc_attr( $class ); ?>" aria-disabled="true" aria-label="<?php echo esc_attr( $aria ); ?>"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG markup with escaped path; text node is escaped above. ?></span>
			<?php
		} else {
			?>
			<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $class ); ?>" aria-label="<?php echo esc_attr( $aria ); ?>"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG markup with escaped path; text node is escaped above. ?></a>
			<?php
		}
	}

	/**
	 * Resolves the prev/next order IDs against the given filter set.
	 *
	 * Returns null when the order is not in the filter set, signaling a
	 * fallback to the unfiltered set is needed.
	 *
	 * @param WC_Order             $order      Current order.
	 * @param array<string, mixed> $list_query Filter args (already mapped to wc_get_orders keys).
	 * @return array{prev_id: int|null, next_id: int|null}|null
	 */
	private static function compute_neighbors( WC_Order $order, array $list_query ): ?array {
		$date = $order->get_date_created();
		if ( null === $date ) {
			return null;
		}
		$timestamp = $date->getTimestamp();

		$base_args = array_merge( $list_query, array( 'type' => 'shop_order' ) );

		if ( ! empty( $list_query ) && ! self::order_matches_filters( $order, $base_args ) ) {
			return null;
		}

		$prev_ids = wc_get_orders(
			array_merge(
				$base_args,
				array(
					'date_created' => '<' . $timestamp,
					'orderby'      => 'date',
					'order'        => 'DESC',
					'limit'        => 1,
					'return'       => 'ids',
				)
			)
		);
		$next_ids = wc_get_orders(
			array_merge(
				$base_args,
				array(
					'date_created' => '>' . $timestamp,
					'orderby'      => 'date',
					'order'        => 'ASC',
					'limit'        => 1,
					'return'       => 'ids',
				)
			)
		);

		return array(
			'prev_id' => self::first_id( $prev_ids ),
			'next_id' => self::first_id( $next_ids ),
		);
	}

	/**
	 * Returns the first ID from a `wc_get_orders( …, 'return' => 'ids' )` result, or null if empty.
	 *
	 * @param mixed $result wc_get_orders return value when called with `return=ids`.
	 * @return int|null
	 */
	private static function first_id( $result ): ?int {
		if ( ! is_array( $result ) || empty( $result ) ) {
			return null;
		}
		$first = reset( $result );
		return is_numeric( $first ) ? (int) $first : null;
	}

	/**
	 * Returns true when the given order matches the supplied filter args.
	 *
	 * @param WC_Order             $order Order to check.
	 * @param array<string, mixed> $args  Filter args (must include `type`).
	 * @return bool
	 */
	private static function order_matches_filters( WC_Order $order, array $args ): bool {
		$matches = wc_get_orders(
			array_merge(
				$args,
				array(
					'id'     => $order->get_id(),
					'limit'  => 1,
					'return' => 'ids',
				)
			)
		);

		return is_array( $matches ) && ! empty( $matches );
	}

	/**
	 * Reads filter params from the current request, falling back to the
	 * referer when the current URL has none. Returns args mapped to
	 * wc_get_orders parameter names.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_list_query_from_request(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current = self::extract_whitelisted_params( $_GET );
		if ( ! empty( $current ) ) {
			return self::map_to_query_args( $current );
		}

		$referer = wp_get_referer();
		if ( ! $referer || ! self::is_orders_list_url( $referer ) ) {
			return array();
		}

		$query = wp_parse_url( $referer, PHP_URL_QUERY );
		if ( ! is_string( $query ) || '' === $query ) {
			return array();
		}

		$parsed = array();
		wp_parse_str( $query, $parsed );

		return self::map_to_query_args( self::extract_whitelisted_params( $parsed ) );
	}

	/**
	 * Pulls and sanitizes whitelisted params from a query array.
	 *
	 * @param array<int|string, mixed> $params Raw params (e.g. `$_GET` or `wp_parse_str` output).
	 * @return array<string, mixed>
	 */
	private static function extract_whitelisted_params( array $params ): array {
		$result = array();

		foreach ( self::FILTER_WHITELIST as $key ) {
			if ( ! isset( $params[ $key ] ) ) {
				continue;
			}
			$value = $params[ $key ];
			if ( is_string( $value ) ) {
				$value = wp_unslash( $value );
			}

			$sanitized = self::sanitize_param( $key, $value );
			if ( null !== $sanitized ) {
				$result[ $key ] = $sanitized;
			}
		}

		return $result;
	}

	/**
	 * Sanitizes a single param value. Returns null when the value should
	 * be ignored (empty, "all", trash, etc.).
	 *
	 * @param string $key   Param name.
	 * @param mixed  $value Raw value.
	 * @return mixed|null
	 */
	private static function sanitize_param( string $key, $value ) {
		switch ( $key ) {
			case 'status':
				if ( ! is_string( $value ) ) {
					return null;
				}
				$value = sanitize_text_field( $value );
				if ( '' === $value || 'all' === $value || 'trash' === $value ) {
					return null;
				}
				return $value;

			case 's':
				$value = is_string( $value ) ? sanitize_text_field( $value ) : '';
				return '' === $value ? null : $value;

			case '_customer_user':
				$value = absint( $value );
				return $value > 0 ? $value : null;

			case '_created_via':
				if ( is_array( $value ) ) {
					$value = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $value ) );
					$value = array_values( array_filter( $value ) );
					return ! empty( $value ) ? $value : null;
				}
				$value = is_string( $value ) ? sanitize_text_field( $value ) : '';
				return '' === $value ? null : array( $value );
		}

		return null;
	}

	/**
	 * Maps URL param names to wc_get_orders arg names.
	 *
	 * @param array<string, mixed> $params Whitelisted, sanitized URL params.
	 * @return array<string, mixed>
	 */
	private static function map_to_query_args( array $params ): array {
		$args = array();

		if ( isset( $params['status'] ) ) {
			$args['status'] = $params['status'];
		}
		if ( isset( $params['s'] ) ) {
			$args['s'] = $params['s'];
		}
		if ( isset( $params['_customer_user'] ) ) {
			$args['customer'] = $params['_customer_user'];
		}
		if ( isset( $params['_created_via'] ) ) {
			$args['created_via'] = $params['_created_via'];
		}

		return $args;
	}

	/**
	 * Returns true when the URL points at the orders list page (not the order edit/new screen).
	 *
	 * @param string $url URL to test.
	 * @return bool
	 */
	private static function is_orders_list_url( string $url ): bool {
		$query = wp_parse_url( $url, PHP_URL_QUERY );
		if ( ! is_string( $query ) || '' === $query ) {
			return false;
		}

		$parsed = array();
		wp_parse_str( $query, $parsed );

		if ( 'wc-orders' !== ( $parsed['page'] ?? null ) ) {
			return false;
		}

		$action = $parsed['action'] ?? '';
		return ! in_array( $action, array( 'edit', 'new' ), true );
	}

	/**
	 * Builds the order edit URL, carrying forward the list filter params.
	 *
	 * @param int|null             $order_id   Target order ID, or null to disable the chevron.
	 * @param array<string, mixed> $list_query Filter args (in wc_get_orders form).
	 * @return string|null
	 */
	private static function build_order_url( ?int $order_id, array $list_query ): ?string {
		if ( null === $order_id ) {
			return null;
		}

		$args = array_merge(
			array(
				'page'   => 'wc-orders',
				'action' => 'edit',
				'id'     => $order_id,
			),
			self::query_args_to_url_params( $list_query )
		);

		return admin_url( add_query_arg( $args, 'admin.php' ) );
	}

	/**
	 * Maps wc_get_orders args back to URL param names for the link href.
	 *
	 * @param array<string, mixed> $args wc_get_orders args.
	 * @return array<string, mixed>
	 */
	private static function query_args_to_url_params( array $args ): array {
		$params = array();
		if ( isset( $args['status'] ) ) {
			$params['status'] = $args['status'];
		}
		if ( isset( $args['s'] ) ) {
			$params['s'] = $args['s'];
		}
		if ( isset( $args['customer'] ) ) {
			$params['_customer_user'] = $args['customer'];
		}
		if ( isset( $args['created_via'] ) ) {
			$created_via            = (array) $args['created_via'];
			$params['_created_via'] = 1 === count( $created_via ) ? $created_via[0] : $created_via;
		}
		return $params;
	}
}
