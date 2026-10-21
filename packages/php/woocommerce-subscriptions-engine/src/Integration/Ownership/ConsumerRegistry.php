<?php
/**
 * ConsumerRegistry - the set of extensions consuming engine functionality.
 *
 * A consumer extension registers its slug here on load, identifying itself as an
 * owner of the primitives it drives, with an optional configuration. The due
 * dispatcher only notifies owners that registered a `contract_due_handler`, so the
 * engine stays inert when bundled but unused.
 *
 * Static (not instance state) because registration is a load-time, by-class-name
 * call - every consumer reaches the registry by class name.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership;

use Automattic\WooCommerce\SubscriptionsEngine\Integration\Dispatch\DueDispatcher;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Support\ArgumentFilter;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of the extensions consuming engine functionality.
 */
final class ConsumerRegistry {

	/**
	 * Configuration keys a consumer may register.
	 */
	private const CONFIG_KEYS = array(
		'contract_due_handler' => true,
	);

	/**
	 * Registered consumers keyed by slug, each with its validated configuration.
	 *
	 * @var array<string, array{contract_due_handler?: callable}>
	 */
	private static $consumers = array();

	/**
	 * Register a consumer extension by its (trimmed, non-empty) slug. A repeat registration, an
	 * unknown key or a non-callable handler gets `_doing_it_wrong()`: the first registration is
	 * kept and an invalid key is dropped. Register by `init`; a handler registered after
	 * `wp_loaded` schedules the recurring due scan right away.
	 *
	 * @param string               $slug   The consumer extension's registered slug.
	 * @param array<string, mixed> $config Consumer configuration. `contract_due_handler`:
	 *                                     `callable( ContractView $contract, array $context ): void`,
	 *                                     called when a contract is due, with `$context['due_at']`
	 *                                     (UTC) and `$context['lock']` (the dispatcher's
	 *                                     `ContractLock`, operation `engine_due_dispatch`, which
	 *                                     other callers see while the handler runs). The
	 *                                     dispatcher releases the lock when the
	 *                                     handler returns, unless the handler already did, and keeps
	 *                                     it until it expires when the handler throws. A handler
	 *                                     that leaves the contract due (same due moment) is notified
	 *                                     again by a later or overlapping scan; moving or clearing
	 *                                     the due moment stops that.
	 */
	public static function register( string $slug, array $config = array() ): void {
		$slug = trim( $slug );
		if ( '' === $slug ) {
			return;
		}

		if ( isset( self::$consumers[ $slug ] ) ) {
			self::reject( sprintf( 'Consumer "%s" is already registered; the first registration is kept.', $slug ) );
			return;
		}

		$filtered_config = ArgumentFilter::filter_known_keys( __METHOD__, $config, self::CONFIG_KEYS );
		$registration    = array();

		if ( array_key_exists( 'contract_due_handler', $filtered_config ) ) {
			$handler = $filtered_config['contract_due_handler'];
			if ( is_callable( $handler ) ) {
				$registration['contract_due_handler'] = $handler;
			} else {
				self::reject( sprintf( 'The "contract_due_handler" of consumer "%s" must be callable; it is ignored.', $slug ) );
			}
		}

		self::$consumers[ $slug ] = $registration;

		if ( isset( $registration['contract_due_handler'] ) && did_action( 'wp_loaded' ) ) {
			DueDispatcher::ensure_scheduled();
		}
	}

	/**
	 * Remove a consumer registration. A deactivating consumer deregisters itself while its code
	 * is still loaded; when it had a due handler, the due dispatcher re-checks its recurring scan
	 * right away and removes it once no owner has a handler. An unknown slug is a no-op.
	 *
	 * @param string $slug The consumer extension's registered slug.
	 */
	public static function unregister( string $slug ): void {
		$slug        = trim( $slug );
		$had_handler = isset( self::$consumers[ $slug ]['contract_due_handler'] );
		unset( self::$consumers[ $slug ] );

		if ( $had_handler ) {
			DueDispatcher::ensure_scheduled();
		}
	}

	/**
	 * The contract due handler a consumer registered, or null when it has none or is not registered.
	 *
	 * @param string $slug The consumer extension's registered slug.
	 */
	public static function get_contract_due_handler( string $slug ): ?callable {
		return self::$consumers[ trim( $slug ) ]['contract_due_handler'] ?? null;
	}

	/**
	 * The slugs of the consumers that registered a contract due handler (order not significant).
	 *
	 * @return array<int, string>
	 */
	public static function get_slugs_with_contract_due_handler(): array {
		$slugs = array();
		foreach ( self::$consumers as $slug => $registration ) {
			if ( isset( $registration['contract_due_handler'] ) ) {
				$slugs[] = (string) $slug;
			}
		}

		return $slugs;
	}

	/**
	 * Clear every registration.
	 *
	 * @internal Public only so test setUp can isolate per-test state. Not part
	 *           of the consumer API.
	 */
	public static function reset(): void {
		self::$consumers = array();
	}

	/**
	 * Raise the `_doing_it_wrong()` notice for an invalid registration.
	 *
	 * @param string $message What is wrong.
	 */
	private static function reject( string $message ): void {
		_doing_it_wrong( __CLASS__ . '::register', esc_html( $message ), '0.0.1' );
	}
}
