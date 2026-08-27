<?php
/**
 * SettingsMigrator class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\WriterInterface;

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
	 * Legacy options with no Core `Config` equivalent, counted and reported but never
	 * written. See the plan's "Known losses" section.
	 *
	 * @var string[]
	 */
	private const KNOWN_LOSS_OPTIONS = array(
		'wc_bis_stock_threshold',
		'wc_bis_opt_in_required',
		'wc_bis_show_product_registrations_count',
		'wc_bis_loop_signup_prompt_status',
		'wc_bis_create_new_account_optin_text',
		'wc_bis_product_registrations_text',
		'wc_bis_product_registrations_plural_text',
		'wc_bis_form_header_text',
		'wc_bis_form_header_signed_up_text',
		'wc_bis_form_header_signed_up_link_text',
		'wc_bis_form_button_text',
		'wc_bis_loop_signup_prompt_text',
		'wc_bis_loop_signup_prompt_link_text',
		'wc_bis_loop_signup_prompt_signed_up_text',
		'wc_bis_loop_signup_prompt_signed_up_link_text',
	);

	/**
	 * Outcome code for an option with no Core home, counted but never written.
	 *
	 * @var string
	 */
	private const OUTCOME_NO_CORE_HOME = 'no_core_home';

	/**
	 * Migration run state, used for fingerprint bookkeeping.
	 *
	 * @var MigrationState
	 */
	private MigrationState $state;

	/**
	 * Outcome reporter.
	 *
	 * @var Reporter
	 */
	private Reporter $reporter;

	/**
	 * Whether to overwrite an option the merchant has already edited.
	 *
	 * @var bool
	 */
	private bool $force;

	/**
	 * Whether the known-losses count has already been reported this run.
	 *
	 * @var bool
	 */
	private bool $known_losses_reported = false;

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
	 * @param MigrationState $state    Migration run state.
	 * @param Reporter       $reporter Outcome reporter.
	 * @param bool           $force    Whether to overwrite merchant-edited options. CLI only.
	 */
	public function __construct( MigrationState $state, Reporter $reporter, bool $force = false ) {
		$this->state    = $state;
		$this->reporter = $reporter;
		$this->force    = $force;
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
	 * @return int
	 */
	public function count_remaining(): int {
		$count = 0;

		foreach ( array_keys( self::OPTION_MAP ) as $legacy_key ) {
			if ( MigrationState::OPTION_ACTION_WRITE === $this->decide( $legacy_key ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Fetch the outstanding legacy option keys, capped at $size.
	 *
	 * Includes options that need a write and options the merchant has edited, since the
	 * latter still need to be visited so migrate_batch() can report them. Excludes
	 * options already migrated and unchanged since. This section is small and finite, so
	 * $cursor is not used as a keyset: the same outstanding list is returned every call.
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

			$action = $this->decide( $legacy_key );

			if ( MigrationState::OPTION_ACTION_SKIP_UNCHANGED !== $action ) {
				$outstanding[] = $legacy_key;
			}
		}

		return array_slice( $outstanding, 0, max( 0, $size ) );
	}

	/**
	 * Migrate the given legacy option keys.
	 *
	 * @param array           $ids    Legacy option keys returned by get_batch().
	 * @param WriterInterface $writer Writer to route all persistence through.
	 * @return array Outcome counts keyed by outcome code.
	 */
	public function migrate_batch( array $ids, WriterInterface $writer ): array {
		$counts = array();
		$row_id = 0;

		foreach ( $ids as $legacy_key ) {
			if ( ! isset( self::OPTION_MAP[ $legacy_key ] ) ) {
				continue;
			}

			++$row_id;

			$mapping     = self::OPTION_MAP[ $legacy_key ];
			$core_key    = $mapping['core'];
			$value       = get_option( $legacy_key, $mapping['default'] );
			$source_hash = $this->state->fingerprint_value( $value );
			$action      = $this->decide( $legacy_key );

			$this->handled[ $legacy_key ] = true;

			if ( MigrationState::OPTION_ACTION_SKIP_USER_MODIFIED === $action ) {
				$this->reporter->record( self::SLUG, Reporter::OUTCOME_SKIPPED_USER_MODIFIED, $row_id );
				$counts[ Reporter::OUTCOME_SKIPPED_USER_MODIFIED ] = ( $counts[ Reporter::OUTCOME_SKIPPED_USER_MODIFIED ] ?? 0 ) + 1;

				if ( ! $writer->is_dry_run() ) {
					// Record what the merchant's value is now, so it reports once rather than
					// staying outstanding on every later run.
					$current_target_hash = $this->state->fingerprint_value( get_option( $core_key, $mapping['default'] ) );
					$this->state->record_option_fingerprint( $core_key, $source_hash, $current_target_hash, true );
				}

				continue;
			}

			if ( MigrationState::OPTION_ACTION_WRITE !== $action ) {
				continue;
			}

			$writer->write_option( $core_key, $value );

			if ( ! $writer->is_dry_run() ) {
				// Fingerprint the value as it reads back, not as it was handed over: options
				// round-trip through the database as strings, so an int written here would
				// never match its own stored form and would report as merchant-edited.
				$stored_hash = $this->state->fingerprint_value( get_option( $core_key, $mapping['default'] ) );
				$this->state->record_option_fingerprint( $core_key, $source_hash, $stored_hash );
			}

			$counts[ Reporter::OUTCOME_MIGRATED ] = ( $counts[ Reporter::OUTCOME_MIGRATED ] ?? 0 ) + 1;
		}

		$this->report_known_losses();

		return $counts;
	}

	/**
	 * Decide what to do with one mapped legacy option.
	 *
	 * @param string $legacy_key Legacy option name, a key of OPTION_MAP.
	 * @return string One of the MigrationState::OPTION_ACTION_* constants.
	 */
	private function decide( string $legacy_key ): string {
		$mapping     = self::OPTION_MAP[ $legacy_key ];
		$core_key    = $mapping['core'];
		$value       = get_option( $legacy_key, $mapping['default'] );
		$source_hash = $this->state->fingerprint_value( $value );

		$current_target_value = get_option( $core_key, $mapping['default'] );
		$current_target_hash  = $this->state->fingerprint_value( $current_target_value );

		return $this->state->decide_option_action( $core_key, $source_hash, $current_target_hash, $this->force );
	}

	/**
	 * Count and report the legacy options that have no Core home, once per run.
	 *
	 * These options are never written; they are reported so the merchant sees them as a
	 * stated decision rather than an unexplained gap.
	 *
	 * @return void
	 */
	private function report_known_losses(): void {
		if ( $this->known_losses_reported ) {
			return;
		}

		$this->known_losses_reported = true;
		$row_id                      = 0;

		foreach ( self::KNOWN_LOSS_OPTIONS as $legacy_key ) {
			if ( null === get_option( $legacy_key, null ) ) {
				continue;
			}

			++$row_id;
			$this->reporter->record( self::SLUG, self::OUTCOME_NO_CORE_HOME, $row_id );
		}
	}
}
