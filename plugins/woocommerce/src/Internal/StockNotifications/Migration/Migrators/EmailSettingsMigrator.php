<?php
/**
 * EmailSettingsMigrator class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\WriterInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Migrates the legacy Back In Stock Notifications email settings to their Core
 * `WC_Email` equivalents.
 *
 * Legacy `confirm` (the post-signup confirmation email) maps to Core `verified`, NOT
 * `verify` — reversing the two swaps their copy, since `verify` is the double opt-in
 * email and `verified` is the confirmation sent after it. Pinned by a unit test.
 *
 * Each settings option is a `WC_Email::$settings` array. Migration merges per sub-key
 * (`subject`, `heading`, ...) rather than replacing the array, so migrating one field
 * never clobbers a hand-edited sibling.
 */
class EmailSettingsMigrator implements MigratorInterface {

	/**
	 * Section slug, used in state keys and CLI `--section` values.
	 */
	private const SLUG = 'emails';

	/**
	 * Legacy email settings option name to its Core equivalent.
	 *
	 * @var array<string, string>
	 */
	private const OPTION_MAP = array(
		'woocommerce_bis_notification_received_settings' => 'woocommerce_customer_stock_notification_settings',
		'woocommerce_bis_notification_verify_settings'   => 'woocommerce_customer_stock_notification_verify_settings',
		'woocommerce_bis_notification_confirm_settings'  => 'woocommerce_customer_stock_notification_verified_settings',
	);

	/**
	 * Sub-keys migrated within each settings array. Shared by legacy and Core: both are
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
	 * Delimiter joining a core option name and sub-key into one batch identifier.
	 */
	private const ID_DELIMITER = '::';

	/**
	 * Outcome code for a value containing a placeholder token outside the known set.
	 *
	 * @var string
	 */
	private const OUTCOME_UNKNOWN_PLACEHOLDER = 'unknown_placeholder';

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
	 * Whether to overwrite a sub-key the merchant has already edited.
	 *
	 * @var bool
	 */
	private bool $force;

	/**
	 * Identifiers already visited by this instance, so they leave the outstanding list
	 * even when nothing was persisted - a dry run records no state at all, and without
	 * this its section would never drain.
	 *
	 * @var array<string,bool>
	 */
	private array $handled = array();

	/**
	 * Constructor.
	 *
	 * @param MigrationState $state    Migration run state.
	 * @param Reporter       $reporter Outcome reporter.
	 * @param bool           $force    Whether to overwrite merchant-edited sub-keys. CLI only.
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
	 * Count the sub-keys that still need a write. Display only.
	 *
	 * @return int
	 */
	public function count_remaining(): int {
		$count = 0;

		foreach ( $this->all_ids() as $id ) {
			if ( MigrationState::OPTION_ACTION_WRITE === $this->decide( $id ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Fetch the outstanding `core_option::sub_key` identifiers, capped at $size.
	 *
	 * Includes sub-keys that need a write and sub-keys the merchant has edited, since the
	 * latter still need to be visited so migrate_batch() can report them. This section is
	 * small and finite, so $cursor is not used as a keyset: the same outstanding list is
	 * returned every call.
	 *
	 * @param int $cursor Unused; this section never needs more than one pass.
	 * @param int $size   Maximum number of identifiers to return.
	 * @return array List of `core_option::sub_key` identifiers.
	 */
	public function get_batch( int $cursor, int $size ): array {
		$outstanding = array();

		foreach ( $this->all_ids() as $id ) {
			if ( isset( $this->handled[ $id ] ) ) {
				continue;
			}

			if ( MigrationState::OPTION_ACTION_SKIP_UNCHANGED !== $this->decide( $id ) ) {
				$outstanding[] = $id;
			}
		}

		return array_slice( $outstanding, 0, max( 0, $size ) );
	}

	/**
	 * Migrate the given `core_option::sub_key` identifiers.
	 *
	 * Sub-key writes are grouped by option so each option is written once per batch, with
	 * every other sub-key it already holds left untouched.
	 *
	 * @param array           $ids    Identifiers returned by get_batch().
	 * @param WriterInterface $writer Writer to route all persistence through.
	 * @return array Outcome counts keyed by outcome code.
	 */
	public function migrate_batch( array $ids, WriterInterface $writer ): array {
		$counts = array();
		$row_id = 0;

		$writes_by_option = array();

		foreach ( $ids as $id ) {
			$parsed = $this->parse_id( $id );
			if ( null === $parsed ) {
				continue;
			}

			++$row_id;
			list( $legacy_key, $core_key, $sub_key ) = $parsed;

			$action = $this->decide( $id );

			$this->handled[ $id ] = true;

			if ( MigrationState::OPTION_ACTION_SKIP_USER_MODIFIED === $action ) {
				$this->reporter->record( self::SLUG, Reporter::OUTCOME_SKIPPED_USER_MODIFIED, $row_id );
				$counts[ Reporter::OUTCOME_SKIPPED_USER_MODIFIED ] = ( $counts[ Reporter::OUTCOME_SKIPPED_USER_MODIFIED ] ?? 0 ) + 1;

				if ( ! $writer->is_dry_run() ) {
					// Record what the merchant's value is now, so it reports once rather than
					// staying outstanding on every later run.
					$core_settings       = (array) get_option( $core_key, array() );
					$current_target_hash = $this->state->fingerprint_value( $core_settings[ $sub_key ] ?? '' );
					$source_settings     = (array) get_option( $legacy_key, array() );
					$source_hash         = $this->state->fingerprint_value( $source_settings[ $sub_key ] ?? '' );

					$this->state->record_option_fingerprint(
						$this->fingerprint_key( $core_key, $sub_key ),
						$source_hash,
						$current_target_hash,
						true
					);
				}

				continue;
			}

			if ( MigrationState::OPTION_ACTION_WRITE !== $action ) {
				continue;
			}

			$legacy_settings = (array) get_option( $legacy_key, array() );
			$value           = $legacy_settings[ $sub_key ] ?? '';

			if ( in_array( $sub_key, self::TEXT_SUB_KEYS, true ) && is_string( $value ) ) {
				foreach ( $this->find_unknown_placeholders( $value ) as $placeholder ) {
					$this->reporter->record( self::SLUG, self::OUTCOME_UNKNOWN_PLACEHOLDER, $row_id );
					$counts[ self::OUTCOME_UNKNOWN_PLACEHOLDER ] = ( $counts[ self::OUTCOME_UNKNOWN_PLACEHOLDER ] ?? 0 ) + 1;
					break;
				}
			}

			$writes_by_option[ $core_key ][ $sub_key ] = array(
				'value'       => $value,
				'source_hash' => $this->state->fingerprint_value( $value ),
			);

			$counts[ Reporter::OUTCOME_MIGRATED ] = ( $counts[ Reporter::OUTCOME_MIGRATED ] ?? 0 ) + 1;
		}

		foreach ( $writes_by_option as $core_key => $sub_key_writes ) {
			$core_settings = (array) get_option( $core_key, array() );

			foreach ( $sub_key_writes as $sub_key => $write ) {
				$core_settings[ $sub_key ] = $write['value'];
			}

			$writer->write_option( $core_key, $core_settings );

			if ( $writer->is_dry_run() ) {
				continue;
			}

			foreach ( $sub_key_writes as $sub_key => $write ) {
				$this->state->record_option_fingerprint(
					$this->fingerprint_key( $core_key, $sub_key ),
					$write['source_hash'],
					$write['source_hash']
				);
			}
		}

		return $counts;
	}

	/**
	 * Every `core_option::sub_key` identifier this migrator is responsible for.
	 *
	 * @return string[]
	 */
	private function all_ids(): array {
		$ids = array();

		foreach ( self::OPTION_MAP as $legacy_key => $core_key ) {
			foreach ( self::SUB_KEYS as $sub_key ) {
				$ids[] = $legacy_key . self::ID_DELIMITER . $core_key . self::ID_DELIMITER . $sub_key;
			}
		}

		return $ids;
	}

	/**
	 * Split a batch identifier into its legacy option, Core option and sub-key.
	 *
	 * @param string $id Identifier produced by all_ids().
	 * @return array{0: string, 1: string, 2: string}|null Null when the identifier does not parse.
	 */
	private function parse_id( string $id ): ?array {
		$parts = explode( self::ID_DELIMITER, $id );

		if ( 3 !== count( $parts ) || ! isset( self::OPTION_MAP[ $parts[0] ] ) ) {
			return null;
		}

		return array( $parts[0], $parts[1], $parts[2] );
	}

	/**
	 * The fingerprint key used to track one sub-key of one Core option independently.
	 *
	 * @param string $core_key Core option name.
	 * @param string $sub_key  Settings sub-key.
	 * @return string
	 */
	private function fingerprint_key( string $core_key, string $sub_key ): string {
		return $core_key . self::ID_DELIMITER . $sub_key;
	}

	/**
	 * Decide what to do with one `core_option::sub_key` identifier.
	 *
	 * @param string $id Identifier produced by all_ids().
	 * @return string One of the MigrationState::OPTION_ACTION_* constants.
	 */
	private function decide( string $id ): string {
		$parsed = $this->parse_id( $id );

		if ( null === $parsed ) {
			return MigrationState::OPTION_ACTION_SKIP_UNCHANGED;
		}

		list( $legacy_key, $core_key, $sub_key ) = $parsed;

		$legacy_settings = (array) get_option( $legacy_key, array() );
		$source_value    = $legacy_settings[ $sub_key ] ?? '';
		$source_hash     = $this->state->fingerprint_value( $source_value );

		$core_settings        = (array) get_option( $core_key, array() );
		$current_target_value = $core_settings[ $sub_key ] ?? '';
		$current_target_hash  = $this->state->fingerprint_value( $current_target_value );

		return $this->state->decide_option_action(
			$this->fingerprint_key( $core_key, $sub_key ),
			$source_hash,
			$current_target_hash,
			$this->force
		);
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
