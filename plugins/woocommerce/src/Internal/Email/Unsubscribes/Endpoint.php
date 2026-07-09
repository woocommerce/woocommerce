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
 * URL shape: `?wc-email-unsubscribe=<order_id>&kind=<email_kind>&email_hash=<sha256>&sig=<hmac>`
 *
 * Signature: HMAC-SHA-256 of `"{order_id}|{email_hash}|{kind}"` using
 * `wp_salt('nonce')` as the key. The kind is part of the payload so a link
 * issued for one email type can't be replayed to opt out of another.
 *
 * No expiry on the link — CAN-SPAM expects unsubscribes to remain valid.
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class Endpoint {

	/**
	 * Query var carrying the order id. The presence of this var is what
	 * triggers the endpoint; the value is informational only (lookup is by
	 * email hash + kind, not order).
	 */
	public const QUERY_VAR = 'wc-email-unsubscribe';

	/**
	 * Query var carrying the SHA-256 hash of the recipient's normalized email.
	 */
	public const QUERY_VAR_HASH = 'email_hash';

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
	 * The raw email is hashed before it ever lands in the URL — see the class
	 * docblock for why. Callers pass the raw address (so the API stays
	 * ergonomic and signing stays in one place), but the rendered link only
	 * contains the hash.
	 *
	 * @param int    $order_id Order id (informational; lookup is by email hash + kind).
	 * @param string $email    Billing email.
	 * @param string $kind     Email-kind identifier (the email class's `$this->id`).
	 * @return string
	 */
	public static function url_for( int $order_id, string $email, string $kind ): string {
		$hash = Storage::hash_email( $email );
		if ( '' === $hash ) {
			return '';
		}
		$sig = self::sign( $order_id, $hash, $kind );

		return add_query_arg(
			array(
				self::QUERY_VAR      => $order_id,
				'kind'               => $kind,
				self::QUERY_VAR_HASH => $hash,
				'sig'                => $sig,
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
		$hash     = isset( $_GET[ self::QUERY_VAR_HASH ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::QUERY_VAR_HASH ] ) ) : '';
		$sig      = isset( $_GET['sig'] ) ? sanitize_text_field( wp_unslash( $_GET['sig'] ) ) : '';
		// phpcs:enable

		// Reject anything that doesn't match `Storage::HASH_PATTERN` before we
		// even hash-compare — a malformed hash can't possibly verify, and the
		// shared constant means the endpoint and storage agree on what valid
		// means.
		if ( '' === $hash || '' === $kind || '' === $sig || 1 !== preg_match( Storage::HASH_PATTERN, $hash ) || ! self::verify( $order_id, $hash, $kind, $sig ) ) {
			$this->render_invalid();
			return;
		}

		$this->storage->mark_unsubscribed_by_hash( $hash, $kind );
		$this->render_unsubscribed();
	}

	/**
	 * Compute the HMAC signature for a (order, hash, kind) triple.
	 *
	 * @param int    $order_id Order id.
	 * @param string $hash     SHA-256 hash of the normalized email.
	 * @param string $kind     Email-kind identifier.
	 * @return string Hex digest.
	 */
	private static function sign( int $order_id, string $hash, string $kind ): string {
		return hash_hmac( 'sha256', $order_id . '|' . $hash . '|' . $kind, wp_salt( 'nonce' ) );
	}

	/**
	 * Constant-time signature verification.
	 *
	 * @param int    $order_id  Order id from the URL.
	 * @param string $hash      Email hash from the URL (already shape-validated).
	 * @param string $kind      Kind from the URL (sanitized).
	 * @param string $signature Signature from the URL.
	 * @return bool
	 */
	private static function verify( int $order_id, string $hash, string $kind, string $signature ): bool {
		$expected = self::sign( $order_id, $hash, $kind );
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
