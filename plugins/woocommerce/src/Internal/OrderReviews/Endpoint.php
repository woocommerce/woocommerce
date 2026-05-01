<?php
/**
 * Endpoint class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderReviews;

use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Order;

/**
 * Registers the standalone `/review-order/{id}/?key={order_key}` rewrite and
 * renders the read-only Review Order landing page.
 *
 * The page is intentionally hosted outside the checkout/my-account family:
 *
 * - It is not a checkout sub-mode like order-pay or order-received; the
 *   customer is reviewing past purchases, not transacting.
 * - It is not a my-account endpoint because the order key is the auth, so
 *   guest customers must be able to reach it without logging in.
 *
 * Any failed gating check renders the theme's 404 template so a leaked or
 * stale link cannot disclose order existence.
 *
 * The container auto-calls `init()` after instantiation, which is where
 * the WordPress hooks are registered. Resolution is driven by the
 * `OrderReviews` wrapper that lists this class as an `init()` argument.
 *
 * @internal Just for internal use.
 *
 * @since 10.8.0
 */
class Endpoint {

	/**
	 * Query var that the rewrite rule sets to the order id.
	 */
	public const QUERY_VAR = 'review-order';

	/**
	 * URL-path prefix this endpoint listens on.
	 */
	public const URL_PREFIX = 'review-order';

	/**
	 * Wire the endpoint into WordPress.
	 *
	 * Auto-called by the WC dependency container after instantiation.
	 *
	 * @internal
	 */
	final public function init(): void {
		add_action( 'init', array( $this, 'add_rewrite_rule' ) );
		add_filter( 'query_vars', array( $this, 'add_query_var' ), 0 );
		add_action( 'template_redirect', array( $this, 'maybe_render' ) );
	}

	/**
	 * Register the rewrite rule for the review-order endpoint.
	 */
	public function add_rewrite_rule(): void {
		add_rewrite_rule(
			'^' . self::URL_PREFIX . '/([0-9]+)/?$',
			'index.php?' . self::QUERY_VAR . '=$matches[1]',
			'top'
		);
	}

	/**
	 * Allow the query var through `WP::parse_request()`.
	 *
	 * @param string[] $vars Query vars.
	 * @return string[]
	 */
	public function add_query_var( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Decide whether this request hits the review-order endpoint and, if so,
	 * run the gating checks and render the template.
	 */
	public function maybe_render(): void {
		global $wp;

		// Use isset() rather than empty() so the literal "0" doesn't slip
		// through to normal WP routing; render() then 404s on order_id 0.
		if ( ! isset( $wp->query_vars[ self::QUERY_VAR ] ) ) {
			return;
		}

		$this->render( absint( $wp->query_vars[ self::QUERY_VAR ] ) );
		exit;
	}

	/**
	 * Render the Review Order page or its 404 fallback.
	 *
	 * Public so tests can drive the gating logic directly without the
	 * `exit` that lives on the `template_redirect` entry point.
	 *
	 * @internal
	 *
	 * @param int $order_id Order id parsed from the URL.
	 */
	public function render( int $order_id ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only landing page; the order key is the auth.
		$raw_key   = ( isset( $_GET['key'] ) && is_string( $_GET['key'] ) ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
		$order_key = is_string( $raw_key ) ? $raw_key : '';
		$order     = $order_id ? wc_get_order( $order_id ) : false;

		if ( ! $this->is_authorised( $order, $order_key ) ) {
			$this->render_404();
			return;
		}

		get_header();
		wc_get_template( 'order/customer-review-order.php', array( 'order' => $order ) );
		get_footer();
	}

	/**
	 * Build the public, tokenised URL for an order's review-order page.
	 *
	 * @param WC_Order $order Order to build the URL for.
	 * @return string
	 */
	public static function get_url( WC_Order $order ): string {
		$url = wc_get_endpoint_url( self::QUERY_VAR, (string) $order->get_id(), home_url( '/' ) );
		$url = add_query_arg( 'key', $order->get_order_key(), $url );

		/**
		 * Filter the Review Order URL that the review-request email links to.
		 *
		 * @since 10.8.0
		 *
		 * @param string   $url   The review-order URL.
		 * @param WC_Order $order The order object.
		 */
		return (string) apply_filters( 'woocommerce_review_order_url', $url, $order );
	}

	/**
	 * Decide whether the request is allowed to render the page.
	 *
	 * @param mixed  $order     The candidate order. Anything other than a `WC_Order` fails.
	 * @param string $order_key The order key supplied via query arg.
	 * @return bool
	 */
	private function is_authorised( $order, string $order_key ): bool {
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		if ( '' === $order_key || ! hash_equals( $order->get_order_key(), $order_key ) ) {
			return false;
		}

		/**
		 * Filter the order statuses that are eligible to access the Review Order page.
		 *
		 * The scheduler unschedules pending sends on refund/cancel/trash/delete, but
		 * emails already in the customer's inbox can still be clicked. The route-level
		 * check blocks those late clicks for orders that have moved out of the
		 * eligible set.
		 *
		 * @since 10.8.0
		 *
		 * @param string[] $eligible_statuses Status slugs without the `wc-` prefix.
		 * @param WC_Order $order             The order being reviewed.
		 */
		$eligible_statuses = (array) apply_filters(
			'woocommerce_review_order_eligible_statuses',
			array( OrderStatus::COMPLETED ),
			$order
		);

		if ( ! in_array( $order->get_status(), $eligible_statuses, true ) ) {
			return false;
		}

		// Logged-in customer must own the order. Guests with the order key still pass.
		if ( $order->get_customer_id() && is_user_logged_in() && get_current_user_id() !== $order->get_customer_id() ) {
			return false;
		}

		return true;
	}

	/**
	 * Mark the current request as a 404 and load the theme's 404 template.
	 *
	 * Fails closed on every gating check so a stale or tampered link cannot
	 * disclose order existence.
	 */
	private function render_404(): void {
		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();

		$template = get_query_template( '404' );
		if ( ! empty( $template ) && file_exists( $template ) ) {
			include $template;
			return;
		}

		// Fallback when the active theme has no 404 template: emit a minimal
		// page so the response body isn't empty.
		printf(
			'<!doctype html><html><head><meta charset="utf-8"><title>%1$s</title></head><body><h1>%1$s</h1></body></html>',
			esc_html__( 'Page not found', 'woocommerce' )
		);
	}
}
