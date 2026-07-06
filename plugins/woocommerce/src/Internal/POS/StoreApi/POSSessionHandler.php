<?php
/**
 * POSSessionHandler class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi;

use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use WC_Session_Handler;

/**
 * Session handler used during POS Store API requests.
 *
 * In a POS request the authenticated WP user is the *operator* processing the
 * sale — the purchaser is an in-person customer. The session (and therefore
 * the cart and the eventual order) must belong to that customer, never to the
 * operator, so this handler never keys a session to the current user and
 * never touches browser cookies. Each transaction is a guest session:
 *
 * - With a valid `Cart-Token` header, the transaction's existing guest
 *   session is resumed. The header is read from `$_SERVER`, so resumption
 *   works no matter how early WooCommerce initializes the session — including
 *   `?rest_route=`-style requests (Jetpack tunnel, plain permalinks), which
 *   WooCommerce classifies as frontend and initializes eagerly on `init`,
 *   long before route dispatch.
 * - Without one, a brand-new guest session starts. The response's Cart-Token
 *   header (from AbstractCartRoute) then identifies the transaction, and the
 *   client passes it back on each call.
 *
 * init() is overridden wholesale rather than piecemeal: the parent's init
 * path binds cookie-write hooks, restores sessions from the browser cookie
 * and the `?session=` request parameter, and migrates guest sessions onto the
 * logged-in user — every one of those channels would leak cart state between
 * the operator and customers, or between transactions. None of them run here.
 * Only the DB persistence machinery (get_session/save_data) is inherited.
 *
 * Only swapped in when {@see Context::is_pos_request()} is true (see
 * {@see PolicyHooks\SessionHandlerSwap}), so web behaviour is unaffected.
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class POSSessionHandler extends WC_Session_Handler {

	/**
	 * Init the session: resume the transaction from a Cart-Token header, or
	 * start a fresh guest session.
	 *
	 * Persists on shutdown so a later request can resume the transaction by
	 * token. Deliberately binds none of the parent's other hooks (cookie
	 * writes, logout destruction, nonce tweaks — all browser-session concerns).
	 *
	 * (`final` satisfies the DI-injection-method sniff, which keys on the
	 * method name; this is WC_Session's lifecycle init, not container
	 * injection, but nothing should override it either.)
	 *
	 * @internal
	 *
	 * @return void
	 */
	final public function init() {
		add_action( 'shutdown', array( $this, 'save_data' ), 20 );

		$customer_id = $this->get_customer_id_from_cart_token();

		$this->_customer_id = '' !== $customer_id ? $customer_id : $this->generate_customer_id();
		$this->_data        = $this->get_session_data();
	}

	/**
	 * Always generate a POS-prefixed guest customer ID.
	 *
	 * The parent returns the logged-in user's ID, which would make the
	 * operator the customer and collide concurrent transactions processed
	 * under the same account. The `pos_` prefix (instead of the web guest
	 * `t_`) makes transaction sessions distinguishable from web guest
	 * sessions, so tokens can never cross surfaces in either direction.
	 * Length: the sessions table key is char(32), so 4 + 28 fills it exactly.
	 *
	 * @return string
	 */
	public function generate_customer_id() {
		return wc_rand_hash( 'pos_', 28 );
	}

	/**
	 * A POS request always has a transaction session.
	 *
	 * The parent's answer depends on the browser cookie or the logged-in
	 * user — neither identifies a POS transaction. Keeping this true also
	 * ensures save_data() persists the session regardless of who is
	 * authenticated.
	 *
	 * @return bool
	 */
	public function has_session() {
		return true;
	}

	/**
	 * Never write the session cookie.
	 *
	 * Nothing binds the cookie hooks here, but third-party code may call this
	 * directly on WC()->session; a POS transaction must never overwrite the
	 * operator's browser session cookie with a transaction guest ID.
	 *
	 * @param bool $set Ignored.
	 * @return void
	 */
	public function set_customer_session_cookie( $set ) {
		unset( $set );
	}

	/**
	 * Resolve the transaction's customer ID from the Cart-Token header, if any.
	 *
	 * Only `pos_`-prefixed transaction sessions are resumable here. Web
	 * sessions — user-keyed (numeric) or guest (`t_`, the same prefix web
	 * guests get) — must never resume as a POS transaction: a web shopper's
	 * token replayed at the register would otherwise hand their live cart to
	 * the POS surface.
	 *
	 * @return string Customer ID, or '' when there is no valid POS token.
	 */
	private function get_customer_id_from_cart_token(): string {
		$cart_token = wc_clean( wp_unslash( $_SERVER['HTTP_CART_TOKEN'] ?? '' ) );

		if ( ! is_string( $cart_token ) || '' === $cart_token || ! CartTokenUtils::validate_cart_token( $cart_token ) ) {
			return '';
		}

		$customer_id = (string) ( CartTokenUtils::get_cart_token_payload( $cart_token )['user_id'] ?? '' );

		return 0 === strpos( $customer_id, 'pos_' ) ? $customer_id : '';
	}
}
