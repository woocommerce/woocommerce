<?php
/**
 * OptionsMigrator class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;

defined( 'ABSPATH' ) || exit;

/**
 * Migrates the legacy Back In Stock Notifications settings — general and email alike — to
 * their Core equivalents.
 *
 * Not a `MigratorInterface`: this is a fixed set of twenty values, so there is nothing to
 * scan, no cursor to keep and no failure marker to write. `MigrationBatchProcessor` calls
 * `migrate()` at the top of every batch instead, which keeps options inside the retry and
 * requirement checks a run already has.
 *
 * Idempotency is read-back-and-compare rather than a stored marker: a value whose Core home
 * already holds it is left alone, and a value that is written is confirmed by reading it back
 * before it counts as migrated. A key this instance has settled is not looked at again for the
 * rest of the run, so a merchant editing a setting mid-run cannot have it overwritten by a
 * later batch.
 *
 * Two mappings that are easy to get backwards:
 *
 * - `wc_bis_allow_signups` is the option that actually turns signups on for a migrated store.
 *   `WC_Install::enable_customer_stock_notifications_signups()` writes
 *   `woocommerce_back_in_stock_allow_signups`, a key `Config::allows_signups()` never reads.
 * - Legacy `confirm` (the post-signup confirmation email) maps to Core `verified`, NOT
 *   `verify` — reversing the two swaps their copy, since `verify` is the double opt-in email
 *   and `verified` is the confirmation sent after it. Pinned by a unit test.
 *
 * Each email settings option is a `WC_Email::$settings` array, merged per sub-key rather than
 * replaced, so migrating one field never clobbers a hand-edited sibling.
 */
class OptionsMigrator {

	/**
	 * Section slug, used in report tables and log entries.
	 */
	private const SLUG = 'options';

	/**
	 * Legacy general option name to `array{ core: string, default: mixed }`.
	 *
	 * Defaults mirror the legacy admin settings screen so a store that never wrote the
	 * option row still migrates the value the merchant actually saw.
	 *
	 * @var array<string, array{core: string, default: mixed}>
	 */
	private const GENERAL_MAP = array(
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
	 * Legacy email settings option name to its Core equivalent.
	 *
	 * @var array<string, string>
	 */
	private const EMAIL_MAP = array(
		'woocommerce_bis_notification_received_settings' => 'woocommerce_customer_stock_notification_settings',
		'woocommerce_bis_notification_verify_settings'   => 'woocommerce_customer_stock_notification_verify_settings',
		'woocommerce_bis_notification_confirm_settings'  => 'woocommerce_customer_stock_notification_verified_settings',
	);

	/**
	 * Sub-keys migrated within each email settings array. Shared by legacy and Core: both are
	 * `WC_Email` subclasses with the same base form fields plus an injected `intro_content`.
	 *
	 * @var string[]
	 */
	private const SUB_KEYS = array( 'enabled', 'subject', 'heading', 'intro_content', 'additional_content' );

	/**
	 * Sub-keys that carry free text and are checked for placeholder tokens outside the
	 * known set. `enabled` is a toggle, not text.
	 *
	 * @var string[]
	 */
	private const TEXT_SUB_KEYS = array( 'subject', 'heading', 'intro_content', 'additional_content' );

	/**
	 * Placeholders every Core stock notification email declares. Both sides ship this
	 * same default set; the legacy `woocommerce_bis_*_email_placeholders` filters are the
	 * only way a stored value could contain anything outside it.
	 *
	 * @var string[]
	 */
	private const KNOWN_PLACEHOLDERS = array( '{site_title}', '{product_name}' );

	/**
	 * Outcome code for a value containing a placeholder token outside the known set.
	 *
	 * @var string
	 */
	private const OUTCOME_UNKNOWN_PLACEHOLDER = 'unknown_placeholder';

	/**
	 * Outcome reporter.
	 *
	 * @var Reporter
	 */
	private Reporter $reporter;

	/**
	 * Values this instance has settled, so a run that calls `migrate()` once per batch only
	 * looks at each of them once. A value that failed to land is left out, so the next batch
	 * tries it again.
	 *
	 * @var array<string, true>
	 */
	private array $settled = array();

	/**
	 * Constructor.
	 *
	 * @param Reporter $reporter Outcome reporter.
	 */
	public function __construct( Reporter $reporter ) {
		$this->reporter = $reporter;
	}

	/**
	 * Section slug, used in report tables and log entries.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return self::SLUG;
	}

	/**
	 * Whether every value already sits in its Core home.
	 *
	 * Reads the stored options rather than this instance's own progress, so the Tools screen
	 * can ask it without a run in hand.
	 *
	 * @return bool
	 */
	public function is_done(): bool {
		foreach ( self::GENERAL_MAP as $legacy_key => $mapping ) {
			if ( ! $this->values_match( get_option( $mapping['core'] ), get_option( $legacy_key, $mapping['default'] ) ) ) {
				return false;
			}
		}

		foreach ( self::EMAIL_MAP as $legacy_key => $core_key ) {
			$legacy_settings = (array) get_option( $legacy_key, array() );
			$core_settings   = (array) get_option( $core_key, array() );

			foreach ( self::SUB_KEYS as $sub_key ) {
				if ( ! $this->values_match( $core_settings[ $sub_key ] ?? null, $legacy_settings[ $sub_key ] ?? '' ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Migrate every legacy setting that is not already in its Core home.
	 *
	 * @param Writer $writer Writer to route all persistence through.
	 * @return array Outcome counts keyed by outcome code.
	 */
	public function migrate( Writer $writer ): array {
		$counts = array();
		$row_id = 0;

		foreach ( self::GENERAL_MAP as $legacy_key => $mapping ) {
			++$row_id;

			$core_key = $mapping['core'];

			if ( isset( $this->settled[ $core_key ] ) ) {
				continue;
			}

			$value  = get_option( $legacy_key, $mapping['default'] );
			$before = get_option( $core_key );

			if ( $this->values_match( $before, $value ) ) {
				$this->settled[ $core_key ] = true;

				continue;
			}

			$writer->write_option( $core_key, $value );

			// Confirm the value as it reads back, not as it was handed over: a write that did
			// not land must stay outstanding so the next batch tries it again.
			// `write_option()`'s own return is no use here — `update_option()` returns false
			// for a value that was already what it is being set to.
			$after  = $writer->is_dry_run() ? $value : get_option( $core_key );
			$landed = $this->values_match( $after, $value );

			if ( $landed ) {
				$this->settled[ $core_key ] = true;
			}

			$this->record( $counts, $landed ? Reporter::OUTCOME_MIGRATED : Reporter::OUTCOME_FAILED, $row_id );
		}

		foreach ( self::EMAIL_MAP as $legacy_key => $core_key ) {
			$row_id = $this->migrate_email_settings( $legacy_key, $core_key, $row_id, $writer, $counts );
		}

		return $counts;
	}

	/**
	 * Migrate one email settings option, sub-key by sub-key, in a single write.
	 *
	 * @param string $legacy_key Legacy settings option name.
	 * @param string $core_key   Core settings option name.
	 * @param int    $row_id     Identifier of the last value reported, to number these from.
	 * @param Writer $writer     Writer to route all persistence through.
	 * @param array  $counts     Outcome counts, added to in place.
	 * @return int The identifier of the last value reported.
	 */
	private function migrate_email_settings( string $legacy_key, string $core_key, int $row_id, Writer $writer, array &$counts ): int {
		$legacy_settings = (array) get_option( $legacy_key, array() );
		$core_settings   = (array) get_option( $core_key, array() );
		$pending         = array();

		foreach ( self::SUB_KEYS as $sub_key ) {
			++$row_id;

			$marker = $core_key . '::' . $sub_key;

			if ( isset( $this->settled[ $marker ] ) ) {
				continue;
			}

			$value = $legacy_settings[ $sub_key ] ?? '';

			if ( $this->values_match( $core_settings[ $sub_key ] ?? null, $value ) ) {
				$this->settled[ $marker ] = true;

				continue;
			}

			if ( in_array( $sub_key, self::TEXT_SUB_KEYS, true ) && is_string( $value ) && ! empty( $this->find_unknown_placeholders( $value ) ) ) {
				$this->record( $counts, self::OUTCOME_UNKNOWN_PLACEHOLDER, $row_id );
			}

			$core_settings[ $sub_key ] = $value;
			$pending[ $sub_key ]       = $row_id;
		}

		if ( empty( $pending ) ) {
			return $row_id;
		}

		$writer->write_option( $core_key, $core_settings );

		$stored = $writer->is_dry_run() ? $core_settings : (array) get_option( $core_key, array() );

		foreach ( $pending as $sub_key => $sub_row_id ) {
			$landed = $this->values_match( $stored[ $sub_key ] ?? null, $core_settings[ $sub_key ] );

			if ( $landed ) {
				$this->settled[ $core_key . '::' . $sub_key ] = true;
			}

			$this->record( $counts, $landed ? Reporter::OUTCOME_MIGRATED : Reporter::OUTCOME_FAILED, $sub_row_id );
		}

		return $row_id;
	}

	/**
	 * Report one outcome and add it to this call's counts.
	 *
	 * @param array  $counts  Outcome counts, added to in place.
	 * @param string $outcome One of the outcome codes.
	 * @param int    $row_id  Identifier of the value the outcome belongs to.
	 * @return void
	 */
	private function record( array &$counts, string $outcome, int $row_id ): void {
		$this->reporter->record( self::SLUG, $outcome, $row_id );

		$counts[ $outcome ] = ( $counts[ $outcome ] ?? 0 ) + 1;
	}

	/**
	 * Whether a stored value is the value that was meant to be there.
	 *
	 * Scalars are compared as strings: an option round-trips through the database as text, so
	 * an integer default written as `0` reads back as `'0'` and is the same value.
	 *
	 * @param mixed $stored   The value read back from the store.
	 * @param mixed $expected The value that should be there.
	 * @return bool
	 */
	private function values_match( $stored, $expected ): bool {
		if ( is_scalar( $stored ) && is_scalar( $expected ) ) {
			return (string) $stored === (string) $expected;
		}

		return $stored === $expected;
	}

	/**
	 * Find `{...}` placeholder tokens in a value that fall outside the known set.
	 *
	 * A token outside the known set indicates a custom placeholder added through the
	 * legacy `woocommerce_bis_*_email_placeholders` filters, which Core does not fill in.
	 *
	 * @param string $value Text to scan.
	 * @return string[] Unknown placeholder tokens found, e.g. `{custom_field}`.
	 */
	private function find_unknown_placeholders( string $value ): array {
		if ( ! preg_match_all( '/\{[a-zA-Z0-9_]+\}/', $value, $matches ) ) {
			return array();
		}

		return array_values( array_diff( $matches[0], self::KNOWN_PLACEHOLDERS ) );
	}
}
