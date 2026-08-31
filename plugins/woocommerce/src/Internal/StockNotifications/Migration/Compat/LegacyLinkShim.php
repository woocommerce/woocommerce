<?php
/**
 * LegacyLinkShim class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Compat;

use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailActionController;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Constants;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\LegacyHash;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps the legacy Back In Stock Notifications extension's `bis_unsub` and `bis_ver` email
 * links working against migrated Core notifications.
 *
 * Registered on `template_redirect` only while `wc_bis_migration_has_legacy_links` is set.
 * `MigrationController` owns that registration decision; this class only handles the request
 * once it is hooked up.
 *
 * The two link kinds share everything but their notices: the same entry point, the same
 * resolve-by-legacy-id, the same digest match, the same session cookie and the same
 * enumeration-safe generic response. Verification links additionally carry an expiry, so
 * they serve only the shoppers who signed up shortly before the run — legacy's own default
 * lifetime is one hour.
 *
 * When the legacy extension is still active, Core wins deterministically and this shim never
 * competes on hook priority: `MigrationController::register()` runs from
 * `WooCommerce::init_hooks()` during construction, before `plugins_loaded`, while
 * `WC_BIS_Account` hooks its own handlers from a `plugins_loaded` callback. At equal
 * `template_redirect` priority registration order decides, so this runs first and exits.
 * Do not "fix" that with an explicit priority.
 *
 * Removal is a release decision, not a runtime one: a later release drops this class, along
 * with the `_wc_bis_legacy_unsub_hash_*` and `_wc_bis_legacy_verify_hash_*` meta keys and the
 * `wc_bis_migration_has_legacy_links` flag, and replaces it with a minimal permanent
 * responder that keeps giving old links the same generic notice without doing any
 * resolution or sweeping.
 */
class LegacyLinkShim {

	/**
	 * Prefix of the meta key holding the legacy notification id a Core row was migrated from.
	 */
	private const META_KEY_PREFIX_LEGACY_ID = Constants::LEGACY_ID_META_KEY_PREFIX;

	/**
	 * `bis_unsub_ref` value used by the legacy "back in stock" confirmation email.
	 */
	private const REF_CONFIRMATION = 'confirmation';

	/**
	 * Email action controller. Owns the actual cancellation once a row is resolved.
	 *
	 * @var EmailActionController
	 */
	private EmailActionController $email_action_controller;

	/**
	 * Constructor. Hooks the shim into the request lifecycle.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_process_legacy_link' ) );
	}

	/**
	 * Init the service.
	 *
	 * @internal
	 *
	 * @param EmailActionController $email_action_controller The email action controller.
	 */
	final public function init( EmailActionController $email_action_controller ): void {
		$this->email_action_controller = $email_action_controller;
	}

	/**
	 * Dispatch an incoming legacy link request, if there is one.
	 *
	 * Every terminal outcome sets the session cookie, adds a notice and redirects, so a
	 * customer clicking a stale link never lands on an ordinary page wondering whether it
	 * worked.
	 */
	public function maybe_process_legacy_link(): void {
		// No nonce on either branch: these are links delivered by email, not a form
		// submitted by a logged-in visitor.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['bis_unsub'] ) ) {
			$this->process_legacy_unsubscribe();
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['bis_ver'] ) ) {
			$this->process_legacy_verify();
		}
	}

	/**
	 * Handle a legacy `bis_unsub` request.
	 */
	private function process_legacy_unsubscribe(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token_raw = isset( $_GET['bis_unsub'] ) ? sanitize_text_field( wp_unslash( $_GET['bis_unsub'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$legacy_id = isset( $_GET['bis_unsub_id'] ) ? absint( wp_unslash( $_GET['bis_unsub_id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ref = isset( $_GET['bis_unsub_ref'] ) ? sanitize_key( wp_unslash( $_GET['bis_unsub_ref'] ) ) : '';

		// Pre-1.2.0 links have no `bis_unsub_id` and used a hash Core never reproduces
		// (see Mapping\LegacyHash and the migration plan). Out of scope: same generic
		// notice as every other unresolvable link.
		if ( ! $legacy_id ) {
			$this->respond_generic_stale_link();
			return;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decoding legacy's own token encoding, not obfuscating output.
		$token = urldecode( base64_decode( $token_raw ) );

		try {
			$notification = $this->resolve_notification( $legacy_id, $token );
		} catch ( \Throwable $exception ) {
			// The notification data store is unregistered when the stock notifications
			// feature toggle is off. The shim's own flag can outlive that toggle. Anything
			// else here looks identical to a stale link from the outside, so it is logged
			// before the generic response hides it.
			wc_get_logger()->warning(
				sprintf(
					'Legacy BIS link: could not resolve legacy id %1$d: %2$s',
					$legacy_id,
					$exception->getMessage()
				),
				array( 'source' => 'stock-notifications' )
			);

			$notification = null;
		}

		if ( ! $notification ) {
			$this->respond_generic_stale_link();
			return;
		}

		$this->start_session();
		$this->cancel_and_respond( $notification, $ref );
	}

	/**
	 * Handle a legacy `bis_ver` request.
	 *
	 * `bis_ver_code` is deliberately ignored. Legacy needed it because it recomputed the
	 * hash from the code the URL carried; here the presented token is checked against a
	 * digest stored at migration time, so the code adds nothing.
	 */
	private function process_legacy_verify(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token_raw = isset( $_GET['bis_ver'] ) ? sanitize_text_field( wp_unslash( $_GET['bis_ver'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$legacy_id = isset( $_GET['bis_ver_id'] ) ? absint( wp_unslash( $_GET['bis_ver_id'] ) ) : 0;

		if ( ! $legacy_id ) {
			$this->respond_generic_stale_verify_link();
			return;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decoding legacy's own token encoding, not obfuscating output.
		$token = urldecode( base64_decode( $token_raw ) );

		$meta_key = Constants::legacy_verify_hash_meta_key( $legacy_id );

		try {
			$matched      = $this->match_link( $meta_key, $legacy_id, $token );
			$notification = null;

			if ( null !== $matched && $this->is_unexpired( $matched[1], $legacy_id ) ) {
				$notification = $this->load_notification( $matched[0], $legacy_id );
			}
		} catch ( \Throwable $exception ) {
			// Same reasoning as the unsubscribe branch: the data store is unregistered when
			// the stock notifications feature toggle is off, and the shim's own flag can
			// outlive that toggle.
			wc_get_logger()->warning(
				sprintf(
					'Legacy BIS link: could not resolve legacy id %1$d: %2$s',
					$legacy_id,
					$exception->getMessage()
				),
				array( 'source' => 'stock-notifications' )
			);

			$notification = null;
		}

		if ( ! $notification ) {
			$this->respond_generic_stale_verify_link();
			return;
		}

		$this->start_session();

		// Single use, matching legacy's own invalidate_verification_data(). Deleted whether
		// or not the row was still pending, so a link cannot be replayed.
		$this->delete_matched_meta( $notification->get_id(), $meta_key );

		$this->verify_and_respond( $notification );
	}

	/**
	 * Whether a matched verification digest is still within the expiry baked in at
	 * migration time.
	 *
	 * @param string $meta_value Matched stored meta value.
	 * @param int    $legacy_id  Legacy notification id, for the log line only.
	 * @return bool
	 */
	private function is_unexpired( string $meta_value, int $legacy_id ): bool {
		$parsed = LegacyHash::parse( $meta_value );

		if ( null === $parsed || null === $parsed[1] || $parsed[1] <= time() ) {
			wc_get_logger()->warning(
				sprintf( 'Legacy BIS link: verification link expired for legacy id %d.', $legacy_id ),
				array( 'source' => 'stock-notifications' )
			);

			return false;
		}

		return true;
	}

	/**
	 * Delete the stored digest this request spent.
	 *
	 * A Core row adopted by several legacy rows carries one digest per legacy id, each under
	 * its own key, so deleting by key spends only the one this request used. Written by
	 * direct SQL rather than through the CRUD layer: a CRUD save would bump
	 * `date_modified_gmt` on a row the shopper only followed a link on.
	 *
	 * @param int    $notification_id Core notification id.
	 * @param string $meta_key        Meta key the digest is stored under.
	 * @return void
	 */
	private function delete_matched_meta( int $notification_id, string $meta_key ): void {
		global $wpdb;

		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- deliberate direct write; see the docblock.
			Constants::core_meta(),
			array(
				'notification_id' => $notification_id,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Column name in the notification meta table, not a query argument.
				'meta_key'        => $meta_key,
			),
			array( '%d', '%s' )
		);
	}

	/**
	 * Activate the resolved row and respond with a notice and redirect.
	 *
	 * Delegates to `EmailActionController::verify()`, which supplies the pending-only guard
	 * and the idempotency: a row cancelled or already verified since the link was issued is
	 * left alone and gets the "already verified" wording.
	 *
	 * @param Notification $notification The resolved, token-verified notification.
	 * @return void
	 */
	private function verify_and_respond( Notification $notification ): void {
		if ( ! $this->email_action_controller->verify( $notification ) ) {
			wc_add_notice( esc_html__( 'This stock notification has already been verified, or is no longer active.', 'woocommerce' ), 'notice' );
			$this->redirect_verified();
			return;
		}

		$product = wc_get_product( $notification->get_product_id() );

		if ( $product instanceof \WC_Product ) {
			$notice_text = sprintf(
				/* translators: %s is product name */
				esc_html__( 'Successfully verified stock notifications for "%s".', 'woocommerce' ),
				$product->get_name()
			);
		} else {
			$notice_text = esc_html__( 'Successfully verified your stock notification.', 'woocommerce' );
		}

		wc_add_notice( $notice_text );
		$this->redirect_verified();
	}

	/**
	 * Resolve the Core notification a legacy link points at, and verify its token.
	 *
	 * @param int    $legacy_id Legacy notification id from the request.
	 * @param string $token     Decoded token from the request.
	 * @return Notification|null Null when nothing resolved or the token did not verify.
	 */
	private function resolve_notification( int $legacy_id, string $token ): ?Notification {
		$matched = $this->match_link( Constants::legacy_unsub_hash_meta_key( $legacy_id ), $legacy_id, $token );

		if ( null === $matched ) {
			return null;
		}

		return $this->load_notification( $matched[0], $legacy_id );
	}

	/**
	 * Find the digest stored for one legacy id, and check the presented token against it.
	 *
	 * The legacy id is part of the meta key, so this is a single equality match on the
	 * indexed `meta_key` column that yields both the notification the link points at and the
	 * digest to check it against. A Core row adopted by several legacy rows carries one
	 * digest per legacy id, each under its own key, so no disambiguation is needed here.
	 *
	 * @param string $meta_key  Meta key holding this legacy id's digest for this link kind.
	 * @param int    $legacy_id Legacy notification id from the request, for the log lines only.
	 * @param string $token     Decoded token from the request.
	 * @return array{0:int,1:string}|null Core notification id and the stored meta value, or
	 *                                    null when nothing resolved or the token did not verify.
	 */
	private function match_link( string $meta_key, int $legacy_id, string $token ): ?array {
		global $wpdb;

		$meta_table = Constants::core_meta();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name built from $wpdb->prefix, not from user input.
				"SELECT notification_id, meta_value FROM {$meta_table} WHERE meta_key = %s LIMIT 1",
				$meta_key
			),
			ARRAY_A
		);

		if ( ! $row ) {
			wc_get_logger()->warning(
				sprintf( 'Legacy BIS link: no stored token found for legacy id %d.', $legacy_id ),
				array( 'source' => 'stock-notifications' )
			);

			return null;
		}

		$meta_value = (string) $row['meta_value'];

		if ( ! LegacyHash::verify( $meta_value, $token ) ) {
			wc_get_logger()->warning(
				sprintf( 'Legacy BIS link: token did not verify for legacy id %d.', $legacy_id ),
				array( 'source' => 'stock-notifications' )
			);

			return null;
		}

		return array( (int) $row['notification_id'], $meta_value );
	}

	/**
	 * Load a resolved Core notification.
	 *
	 * @param int $notification_id Core notification id.
	 * @param int $legacy_id       Legacy notification id, for the log line only.
	 * @return Notification|null Null when the row could not be loaded.
	 */
	private function load_notification( int $notification_id, int $legacy_id ): ?Notification {
		$notification = Factory::get_notification( $notification_id );

		if ( ! $notification instanceof Notification ) {
			// Factory swallows the load failure, and the customer only ever sees the generic
			// stale-link notice, so this is the one place a real fault leaves a trace.
			wc_get_logger()->warning(
				sprintf(
					'Legacy BIS link: notification %1$d could not be loaded for legacy id %2$d.',
					$notification_id,
					$legacy_id
				),
				array( 'source' => 'stock-notifications' )
			);

			return null;
		}

		return $notification;
	}

	/**
	 * Cancel the rows a verified token authorises, and respond with a notice and redirect.
	 *
	 * Reproduces legacy scope branching: `confirmation` cancels every row for the same
	 * product and email, a registered recipient of any other link cancels only this one
	 * row (legacy sent them to a My Account endpoint Core does not register), and a guest
	 * cancels every row for that email.
	 *
	 * The guest branch is the widest of the three — one click on one product's link cancels
	 * that address's whole migrated set, on an endpoint with no nonce and no logged-in
	 * identity — and its legacy-parity claim is asserted here but cannot be checked in this
	 * repository, since the extension is not in it. It wants confirming against the extension
	 * source; were legacy to have scoped narrower, this branch should scope to the link's own
	 * product the way `confirmation` does.
	 *
	 * Note also that the branch is chosen by whether the address resolves to a `WP_User`, so
	 * a guest who later registers under the same address moves to the single-row branch: the
	 * same link does different things before and after they sign up.
	 *
	 * @param Notification $notification The resolved, token-verified notification.
	 * @param string       $ref          Sanitized `bis_unsub_ref` value.
	 */
	private function cancel_and_respond( Notification $notification, string $ref ): void {
		if ( self::REF_CONFIRMATION === $ref ) {
			$cancelled = $this->cancel_legacy_rows(
				NotificationQuery::get_notifications(
					array(
						'product_id' => $notification->get_product_id(),
						'user_email' => $notification->get_user_email(),
						'return'     => 'objects',
					)
				)
			);
			$this->respond_product_scoped( $notification, $cancelled );
			return;
		}

		$user = get_user_by( 'email', $notification->get_user_email() );

		if ( $user instanceof \WP_User ) {
			$cancelled = $this->email_action_controller->unsubscribe( $notification ) ? 1 : 0;
			$this->respond_single_row( $notification, $cancelled );
			return;
		}

		$cancelled = $this->cancel_legacy_rows(
			NotificationQuery::get_notifications(
				array(
					'user_email' => $notification->get_user_email(),
					'return'     => 'objects',
				)
			)
		);
		$this->respond_email_scoped( $notification->get_user_email(), $cancelled );
	}

	/**
	 * Cancel every candidate that still carries the legacy migration marker.
	 *
	 * Scoping to the `_wc_bis_legacy_id_*` marker keeps an old link's authority bounded to
	 * the data it was issued against: notifications signed up natively in Core after the
	 * migration carry their own working unsubscribe link and must not be swept in here.
	 * Delegates the actual cancellation to `EmailActionController::unsubscribe()`, which is
	 * a no-op for a row that is not `pending` or `active`.
	 *
	 * @param Notification[] $notifications Candidate notifications.
	 * @return int Number of rows actually cancelled.
	 */
	private function cancel_legacy_rows( array $notifications ): int {
		$cancelled = 0;

		foreach ( $notifications as $notification ) {
			if ( ! $notification instanceof Notification ) {
				continue;
			}

			if ( ! $this->is_migrated_row( $notification ) ) {
				continue;
			}

			if ( $this->email_action_controller->unsubscribe( $notification ) ) {
				++$cancelled;
			}
		}

		return $cancelled;
	}

	/**
	 * Whether a notification carries a legacy id marker, whichever legacy id it came from.
	 *
	 * The marker key ends in the legacy id, which this caller does not know, so the already
	 * loaded meta is scanned by prefix rather than asked for one exact key.
	 *
	 * @param Notification $notification Candidate notification.
	 * @return bool
	 */
	private function is_migrated_row( Notification $notification ): bool {
		foreach ( $notification->get_meta_data() as $meta ) {
			if ( 0 === strpos( (string) $meta->key, self::META_KEY_PREFIX_LEGACY_ID ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Respond to a `confirmation`-scoped, or a registered-user single-row, cancellation.
	 *
	 * @param Notification $notification    The notification the link was issued for.
	 * @param int          $cancelled_count Rows actually cancelled by this request.
	 */
	private function respond_product_scoped( Notification $notification, int $cancelled_count ): void {
		if ( $cancelled_count > 0 ) {
			$product = wc_get_product( $notification->get_product_id() );

			if ( $product instanceof \WC_Product ) {
				$notice_text = sprintf(
					/* translators: %1$s user email, %2$s product name */
					esc_html__( 'Successfully unsubscribed %1$s. You will not receive a notification when "%2$s" becomes available.', 'woocommerce' ),
					$notification->get_user_email(),
					$product->get_name()
				);
			} else {
				$notice_text = sprintf(
					/* translators: %s user email */
					esc_html__( 'Successfully unsubscribed %s.', 'woocommerce' ),
					$notification->get_user_email()
				);
			}
			wc_add_notice( $notice_text );
		} else {
			wc_add_notice( esc_html__( 'You are already unsubscribed.', 'woocommerce' ) );
		}

		$this->redirect();
	}

	/**
	 * Respond to a registered-user request that is limited to the single resolved row.
	 *
	 * @param Notification $notification    The notification the link was issued for.
	 * @param int          $cancelled_count 1 if the row was cancelled, 0 otherwise.
	 */
	private function respond_single_row( Notification $notification, int $cancelled_count ): void {
		if ( $cancelled_count > 0 ) {
			$product = wc_get_product( $notification->get_product_id() );

			if ( $product instanceof \WC_Product ) {
				$notice_text = sprintf(
					/* translators: %1$s user email, %2$s product name */
					esc_html__( 'Successfully unsubscribed %1$s. You will not receive a notification when "%2$s" becomes available. Manage the rest of your stock notifications from your account.', 'woocommerce' ),
					$notification->get_user_email(),
					$product->get_name()
				);
			} else {
				$notice_text = sprintf(
					/* translators: %s user email */
					esc_html__( 'Successfully unsubscribed %s.', 'woocommerce' ),
					$notification->get_user_email()
				);
			}
			wc_add_notice( $notice_text );
		} else {
			wc_add_notice( esc_html__( 'You are already unsubscribed.', 'woocommerce' ) );
		}

		$this->redirect();
	}

	/**
	 * Respond to a guest request cancelling every legacy row for one email address.
	 *
	 * @param string $user_email      The email address the link was issued for.
	 * @param int    $cancelled_count Number of rows cancelled by this request.
	 */
	private function respond_email_scoped( string $user_email, int $cancelled_count ): void {
		if ( $cancelled_count > 0 ) {
			$notice_text = sprintf(
				/* translators: %1$d number of cancelled stock notifications, %2$s user email */
				_n(
					'Successfully unsubscribed %2$s from %1$d stock notification.',
					'Successfully unsubscribed %2$s from %1$d stock notifications.',
					$cancelled_count,
					'woocommerce'
				),
				$cancelled_count,
				$user_email
			);
			wc_add_notice( $notice_text );
		} else {
			wc_add_notice( esc_html__( 'You are already unsubscribed.', 'woocommerce' ) );
		}

		$this->redirect();
	}

	/**
	 * Respond with the uniform notice for every outcome where the token did not verify or
	 * nothing resolved.
	 *
	 * Deliberately identical wording and redirect target for every cause: `bis_unsub_id` is
	 * a sequential integer on a public, unauthenticated endpoint, so distinguishing the
	 * causes would let the endpoint be used to enumerate how many signups the store had.
	 * The specific cause is logged separately in match_link().
	 */
	private function respond_generic_stale_link(): void {
		$this->start_session();
		// Notice, not the default success type: an expired link is not something that worked.
		wc_add_notice( esc_html__( 'This unsubscribe link is invalid or has expired.', 'woocommerce' ), 'notice' );
		$this->redirect();
	}

	/**
	 * The verification branch's equivalent of respond_generic_stale_link(), with the same
	 * enumeration-safety invariant: byte-identical wording and redirect target for an
	 * unknown id, a tampered token and an expired link alike. `bis_ver_id` is a sequential
	 * integer on a public, unauthenticated endpoint.
	 */
	private function respond_generic_stale_verify_link(): void {
		$this->start_session();
		wc_add_notice( esc_html__( 'This verification link is invalid or has expired.', 'woocommerce' ), 'notice' );
		$this->redirect_verified();
	}

	/**
	 * Start a cookie-based session, if there is not one already, so notices work on the
	 * frontend page the customer is redirected to.
	 *
	 * Must run before any notice is added, or the notice has nowhere to persist to.
	 */
	private function start_session(): void {
		if ( WC()->session instanceof \WC_Session_Handler && ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}
	}

	/**
	 * Redirect to the same destination Core's own verification controller uses, and stop
	 * execution.
	 *
	 * This diverges from legacy, which redirected back to the current URL with a
	 * `bis_ver_handle` cache-buster. Matching Core keeps both shim branches consistent with
	 * the controller they delegate to.
	 */
	private function redirect_verified(): void {
		/**
		 * `woocommerce_customer_stock_notification_verified_redirect_url` filter.
		 *
		 * @since 10.2.0
		 *
		 * @param  string  $url
		 * @return string
		 */
		$url = apply_filters( 'woocommerce_customer_stock_notification_verified_redirect_url', (string) get_permalink( wc_get_page_id( 'shop' ) ) );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Redirect to the same destination Core's own unsubscribe controller uses, and stop
	 * execution.
	 */
	private function redirect(): void {
		/**
		 * `woocommerce_customer_stock_notification_unsubscribe_redirect_url` filter.
		 *
		 * @since 10.2.0
		 *
		 * @param  string  $url
		 * @return string
		 */
		$url = apply_filters( 'woocommerce_customer_stock_notification_unsubscribe_redirect_url', (string) get_permalink( wc_get_page_id( 'shop' ) ) );
		wp_safe_redirect( $url );
		exit;
	}
}
