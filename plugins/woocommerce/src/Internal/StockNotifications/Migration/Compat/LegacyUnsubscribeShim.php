<?php
/**
 * LegacyUnsubscribeShim class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Compat;

use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailActionController;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\LegacyHash;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps the legacy Back In Stock Notifications extension's `bis_unsub` email links working
 * against migrated Core notifications.
 *
 * Registered on `template_redirect` only while `wc_bis_migration_has_legacy_links` is set.
 * `MigrationController` owns that registration decision; this class only handles the request
 * once it is hooked up.
 *
 * Removal is a release decision, not a runtime one: a later release drops this class, along
 * with the `_wc_bis_legacy_unsub_hash` meta key and the `wc_bis_migration_has_legacy_links`
 * flag, and replaces it with a minimal permanent responder that keeps giving old links the
 * same generic notice without doing any resolution or sweeping.
 */
class LegacyUnsubscribeShim {

	/**
	 * Meta key holding the legacy notification id a Core row was migrated from.
	 */
	private const META_KEY_LEGACY_ID = '_wc_bis_legacy_id';

	/**
	 * Meta key holding the precomputed legacy unsubscribe token digest.
	 */
	private const META_KEY_LEGACY_UNSUB_HASH = '_wc_bis_legacy_unsub_hash';

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
		add_action( 'template_redirect', array( $this, 'maybe_process_legacy_unsubscribe' ) );
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
	 * Handle an incoming legacy unsubscribe request, if there is one.
	 *
	 * Every terminal outcome sets the session cookie, adds a notice and redirects, so a
	 * customer clicking a stale link never lands on an ordinary page wondering whether it
	 * worked.
	 */
	public function maybe_process_legacy_unsubscribe(): void {
		// No nonce here: these are links delivered by email, not a form submitted by a
		// logged-in visitor.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['bis_unsub'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token_raw = sanitize_text_field( wp_unslash( $_GET['bis_unsub'] ) );
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
			// feature toggle is off. The shim's own flag can outlive that toggle.
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
	 * Resolve the Core notification a legacy unsubscribe link points at, and verify its token.
	 *
	 * @param int    $legacy_id Legacy notification id from `bis_unsub_id`.
	 * @param string $token     Decoded token from `bis_unsub`.
	 * @return Notification|null Null when nothing resolved or the token did not verify.
	 */
	private function resolve_notification( int $legacy_id, string $token ): ?Notification {
		global $wpdb;

		$meta_table = $wpdb->prefix . 'wc_stock_notificationmeta';

		$notification_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name built from $wpdb->prefix, not from user input.
				"SELECT notification_id FROM {$meta_table} WHERE meta_key = %s AND CAST( meta_value AS UNSIGNED ) = %d LIMIT 1",
				self::META_KEY_LEGACY_ID,
				$legacy_id
			)
		);

		if ( ! $notification_id ) {
			wc_get_logger()->warning(
				sprintf( 'Legacy BIS unsubscribe: no notification found for legacy id %d.', $legacy_id ),
				array( 'source' => 'stock-notifications' )
			);
			return null;
		}

		$hash_values = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name built from $wpdb->prefix, not from user input.
				"SELECT meta_value FROM {$meta_table} WHERE notification_id = %d AND meta_key = %s",
				$notification_id,
				self::META_KEY_LEGACY_UNSUB_HASH
			)
		);

		$matched_meta_value = null;
		foreach ( $hash_values as $hash_value ) {
			$parsed = LegacyHash::parse( (string) $hash_value );
			if ( $parsed && $legacy_id === $parsed[0] ) {
				$matched_meta_value = (string) $hash_value;
				break;
			}
		}

		if ( null === $matched_meta_value || ! LegacyHash::verify( $matched_meta_value, $token ) ) {
			wc_get_logger()->warning(
				sprintf( 'Legacy BIS unsubscribe: token did not verify for legacy id %d.', $legacy_id ),
				array( 'source' => 'stock-notifications' )
			);
			return null;
		}

		$notification = Factory::get_notification( $notification_id );

		return $notification instanceof Notification ? $notification : null;
	}

	/**
	 * Cancel the rows a verified token authorises, and respond with a notice and redirect.
	 *
	 * Reproduces legacy scope branching: `confirmation` cancels every row for the same
	 * product and email, a registered recipient of any other link cancels only this one
	 * row (legacy sent them to a My Account endpoint Core does not register), and a guest
	 * cancels every row for that email.
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
	 * Scoping to `_wc_bis_legacy_id` keeps an old link's authority bounded to the data it
	 * was issued against: notifications signed up natively in Core after the migration
	 * carry their own working unsubscribe link and must not be swept in here. Delegates
	 * the actual cancellation to `EmailActionController::unsubscribe()`, which is a no-op
	 * for a row that is not `pending` or `active`.
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

			if ( '' === (string) $notification->get_meta( self::META_KEY_LEGACY_ID ) ) {
				continue;
			}

			if ( $this->email_action_controller->unsubscribe( $notification ) ) {
				++$cancelled;
			}
		}

		return $cancelled;
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
	 * The specific cause is logged separately in resolve_notification().
	 */
	private function respond_generic_stale_link(): void {
		$this->start_session();
		// Notice, not the default success type: an expired link is not something that worked.
		wc_add_notice( esc_html__( 'This unsubscribe link is invalid or has expired.', 'woocommerce' ), 'notice' );
		$this->redirect();
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
