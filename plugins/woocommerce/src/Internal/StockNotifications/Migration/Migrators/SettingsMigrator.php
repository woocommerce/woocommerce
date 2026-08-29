<?php
/**
 * SettingsMigrator class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;

defined( 'ABSPATH' ) || exit;

/**
 * Migrates the legacy Back In Stock Notifications general settings to their Core
 * `Config` equivalents.
 *
 * `wc_bis_allow_signups` migrating to `woocommerce_customer_stock_notifications_allow_signups`
 * is the option that actually turns signups on for a migrated store:
 * `WC_Install::enable_customer_stock_notifications_signups()` writes
 * `woocommerce_back_in_stock_allow_signups`, a key `Config::allows_signups()` never reads,
 * so without this migrator new installs never really got signups enabled by default.
 */
class SettingsMigrator implements MigratorInterface {

	/**
	 * Section slug, used in state keys and CLI `--section` values.
	 */
	private const SLUG = 'settings';

	/**
	 * Legacy option name to `array{ core: string, default: mixed }`.
	 *
	 * Defaults mirror the legacy admin settings screen so a store that never wrote the
	 * option row still migrates the value the merchant actually saw.
	 *
	 * @var array<string, array{core: string, default: mixed}>
	 */
	private const OPTION_MAP = array(
		'wc_bis_allow_signups'                      => array(
			'core'    => 'woocommerce_customer_stock_notifications_allow_signups',
			'default' => 'yes',
		),
		'wc_bis_double_opt_in_required'             => array(
			'core'    => 'woocommerce_customer_stock_notifications_require_double_opt_in',
			'default' => 'no',
		),
		'wc_bis_delete_unverified_days_threshold'   => array(
			'core'    => 'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold',
			'default' => 0,
		),
		'wc_bis_account_required'                   => array(
			'core'    => 'woocommerce_customer_stock_notifications_require_account',
			'default' => 'no',
		),
		'wc_bis_create_new_account_on_registration' => array(
			'core'    => 'woocommerce_customer_stock_notifications_create_account_on_signup',
			'default' => 'no',
		),
	);

	/**
	 * Migration run state, used to track which options have already been migrated.
	 *
	 * @var MigrationState
	 */
	private MigrationState $state;

	/**
	 * Legacy option keys already visited by this instance, so they leave the outstanding
	 * list even when nothing was persisted - a dry run records no state at all, and
	 * without this its section would never drain.
	 *
	 * @var array<string,bool>
	 */
	private array $handled = array();

	/**
	 * Constructor.
	 *
	 * @param MigrationState $state Migration run state.
	 */
	public function __construct( MigrationState $state ) {
		$this->state = $state;
	}

	/**
	 * Section slug, used in state keys and CLI `--section` values.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return self::SLUG;
	}

	/**
	 * Count the options that still need a write. Display only.
	 *
	 * @param int $cursor Ignored: this section identifies its items by option key, not by a
	 *                    sequential id, so it never reads a cursor.
	 * @return int
	 */
	public function count_remaining( int $cursor = 0 ): int { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- part of MigratorInterface; see above.
		$count = 0;

		foreach ( array_keys( self::OPTION_MAP ) as $legacy_key ) {
			$core_key = self::OPTION_MAP[ $legacy_key ]['core'];

			if ( ! $this->state->is_option_migrated( $core_key ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Fetch the outstanding legacy option keys, capped at $size.
	 *
	 * A key is outstanding until it has been migrated once. This section is small and
	 * finite, so $cursor is not used as a keyset: the same outstanding list is returned
	 * every call.
	 *
	 * @param int $cursor Unused; this section never needs more than one pass.
	 * @param int $size   Maximum number of option keys to return.
	 * @return array List of legacy option keys.
	 */
	public function get_batch( int $cursor, int $size ): array {
		$outstanding = array();

		foreach ( array_keys( self::OPTION_MAP ) as $legacy_key ) {
			if ( isset( $this->handled[ $legacy_key ] ) ) {
				continue;
			}

			$core_key = self::OPTION_MAP[ $legacy_key ]['core'];

			if ( ! $this->state->is_option_migrated( $core_key ) ) {
				$outstanding[] = $legacy_key;
			}
		}

		return array_slice( $outstanding, 0, max( 0, $size ) );
	}

	/**
	 * Migrate the given legacy option keys.
	 *
	 * @param array  $ids    Legacy option keys returned by get_batch().
	 * @param Writer $writer Writer to route all persistence through.
	 * @return array Outcome counts keyed by outcome code.
	 */
	public function migrate_batch( array $ids, Writer $writer ): array {
		$counts = array();

		foreach ( $ids as $legacy_key ) {
			if ( ! isset( self::OPTION_MAP[ $legacy_key ] ) ) {
				continue;
			}

			$mapping  = self::OPTION_MAP[ $legacy_key ];
			$core_key = $mapping['core'];
			$value    = get_option( $legacy_key, $mapping['default'] );

			$this->handled[ $legacy_key ] = true;

			// Guard the write as well as the selection, so an id that reaches this method
			// from anywhere but `get_batch()` still only ever writes once.
			if ( $this->state->is_option_migrated( $core_key ) ) {
				continue;
			}

			$writer->write_option( $core_key, $value );

			if ( ! $writer->is_dry_run() ) {
				$this->state->mark_option_migrated( $core_key );
			}

			$counts[ Reporter::OUTCOME_MIGRATED ] = ( $counts[ Reporter::OUTCOME_MIGRATED ] ?? 0 ) + 1;
		}

		return $counts;
	}
}
