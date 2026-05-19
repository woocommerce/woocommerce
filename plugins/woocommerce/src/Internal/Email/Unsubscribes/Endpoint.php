<?php
/**
 * Email unsubscribes Endpoint class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Email\Unsubscribes;

/**
 * Public-facing endpoint that handles the unsubscribe links embedded in
 * customer emails.
 *
 * URL shape: `?wc-email-unsubscribe=<order_id>&kind=<email_kind>&email=<urlencoded>&sig=<hmac>`
 *
 * Signature: HMAC-SHA-256 of `"{order_id}|{normalized_email}|{kind}"` using
 * `wp_salt('nonce')` as the key. The kind is part of the payload so a link
 * issued for one email type can't be replayed to opt out of another.
 *
 * No expiry on the link — CAN-SPAM expects unsubscribes to remain valid.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class Endpoint {

	/**
	 * Query var carrying the order id. The presence of this var is what
	 * triggers the endpoint; the value is informational only (lookup is by
	 * email + kind, not order).
	 */
	public const QUERY_VAR = 'wc-email-unsubscribe';

	/**
	 * Storage layer.
	 *
	 * @var Storage
	 */
	private Storage $storage;

	/**
	 * Container-injected dependencies.
	 *
	 * @internal
	 *
	 * @param Storage $storage Storage layer.
	 */
	final public function init( Storage $storage ): void {
		$this->storage = $storage;
		add_action( 'template_redirect', array( $this, 'maybe_handle' ) );
	}

	/**
	 * Build the URL the email's unsubscribe link should point to.
	 *
	 * @param int    $order_id Order id (informational; lookup is by email + kind).
	 * @param string $email    Billing email.
	 * @param string $kind     Email-kind identifier (the email class's `$this->id`).
	 * @return string
	 */
	public static function url_for( int $order_id, string $email, string $kind ): string {
		$normalized = strtolower( trim( $email ) );
		$sig        = self::sign( $order_id, $normalized, $kind );

		return add_query_arg(
			array(
				self::QUERY_VAR => $order_id,
				'kind'          => $kind,
				'email'         => rawurlencode( $normalized ),
				'sig'           => $sig,
			),
			home_url( '/' )
		);
	}

	/**
	 * Fired on `template_redirect`. Quick-bail when the query var is absent so
	 * normal requests are unaffected.
	 *
	 * @internal
	 */
	public function maybe_handle(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- signature replaces nonce here; verified below.
		if ( ! isset( $_GET[ self::QUERY_VAR ] ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- signature verified below.
		$order_id = absint( $_GET[ self::QUERY_VAR ] );
		$kind     = isset( $_GET['kind'] ) ? sanitize_key( wp_unslash( $_GET['kind'] ) ) : '';
		$email    = isset( $_GET['email'] ) ? sanitize_email( rawurldecode( wp_unslash( $_GET['email'] ) ) ) : '';
		$sig      = isset( $_GET['sig'] ) ? sanitize_text_field( wp_unslash( $_GET['sig'] ) ) : '';
		// phpcs:enable

		if ( '' === $email || '' === $kind || '' === $sig || ! self::verify( $order_id, $email, $kind, $sig ) ) {
			$this->render_invalid();
			return;
		}

		$this->storage->mark_unsubscribed( $email, $kind );
		$this->render_unsubscribed();
	}

	/**
	 * Compute the HMAC signature for a (order, email, kind) triple.
	 *
	 * @param int    $order_id Order id.
	 * @param string $email    Already-normalized (lowercased, trimmed) email.
	 * @param string $kind     Email-kind identifier.
	 * @return string Hex digest.
	 */
	private static function sign( int $order_id, string $email, string $kind ): string {
		return hash_hmac( 'sha256', $order_id . '|' . $email . '|' . $kind, wp_salt( 'nonce' ) );
	}

	/**
	 * Constant-time signature verification.
	 *
	 * @param int    $order_id  Order id from the URL.
	 * @param string $email     Email from the URL (sanitized).
	 * @param string $kind      Kind from the URL (sanitized).
	 * @param string $signature Signature from the URL.
	 * @return bool
	 */
	private static function verify( int $order_id, string $email, string $kind, string $signature ): bool {
		$expected = self::sign( $order_id, strtolower( trim( $email ) ), $kind );
		return hash_equals( $expected, $signature );
	}

	/**
	 * Render the "you've been unsubscribed" page.
	 */
	private function render_unsubscribed(): void {
		wp_die(
			wp_kses_post( '<p>' . esc_html__( 'You won\'t receive any more of these emails from us.', 'woocommerce' ) . '</p>' ),
			esc_html__( 'Unsubscribed', 'woocommerce' ),
			array( 'response' => 200 )
		);
	}

	/**
	 * Render the "we couldn't verify this link" page. Same status code as
	 * success so the response shape doesn't leak whether the email exists.
	 */
	private function render_invalid(): void {
		wp_die(
			wp_kses_post( '<p>' . esc_html__( 'This unsubscribe link could not be verified. It may have been altered or copied incompletely.', 'woocommerce' ) . '</p>' ),
			esc_html__( 'Link not valid', 'woocommerce' ),
			array( 'response' => 200 )
		);
	}
}
