<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderWithdrawal;

use Automattic\WooCommerce\Admin\Notes\DataStore;
use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Throwable;

/**
 * Adds an inbox notification about the order withdrawal feature for eligible stores.
 *
 * @internal Just for internal use.
 */
final class OrderWithdrawalFeatureHighlightNotification implements RegisterHooksInterface {

	public const NOTE_NAME      = 'wc-admin-order-withdrawal-feature';
	public const CREATED_OPTION = 'woocommerce_order_withdrawal_inbox_notification_created';

	private const FEATURE_ID                        = 'order_withdrawal';
	private const ALLOWED_COUNTRIES_OPTION          = 'woocommerce_allowed_countries';
	private const ALL_EXCEPT_COUNTRIES_OPTION       = 'woocommerce_all_except_countries';
	private const SPECIFIC_ALLOWED_COUNTRIES_OPTION = 'woocommerce_specific_allowed_countries';
	private const COMING_SOON_OPTION                = 'woocommerce_coming_soon';
	private const FEATURES_SETTINGS_URL             = 'admin.php?page=wc-settings&tab=advanced&section=features';
	private const DOCUMENTATION_URL                 = 'https://woocommerce.com/';

	/**
	 * Register hooks.
	 *
	 * @since 11.1.0
	 */
	public function register(): void {
		add_action(
			'update_option_' . self::COMING_SOON_OPTION,
			array( $this, 'maybe_add_note_when_store_goes_live' ),
			10,
			2
		);
		add_action( 'wc_admin_daily', array( $this, 'possibly_add_note' ) );
	}

	/**
	 * Add the note when the store is changed from coming soon to live.
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $value     New option value.
	 */
	public function maybe_add_note_when_store_goes_live( $old_value, $value ): void {
		if ( 'yes' !== $old_value || 'no' !== $value ) {
			return;
		}

		$this->possibly_add_note();
	}

	/**
	 * Add the note if the store is eligible and it has never been created before.
	 */
	public function possibly_add_note(): void {
		if ( ! $this->is_applicable() ) {
			return;
		}

		try {
			if (
				$this->has_note_been_created() ||
				! add_option( self::CREATED_OPTION, 'yes', '', false )
			) {
				return;
			}

			$this->get_note()->save();
		} catch ( Throwable $exception ) {
			delete_option( self::CREATED_OPTION );
			wc_get_logger()->error(
				'Unable to create the order withdrawal inbox notification.',
				array(
					'source'    => 'order-withdrawal',
					'exception' => $exception,
				)
			);
		}
	}

	/**
	 * Whether the notification is relevant for the current store settings.
	 */
	public function is_applicable(): bool {
		return 'no' === get_option( self::COMING_SOON_OPTION, 'yes' )
			&& ! FeaturesUtil::feature_is_enabled( self::FEATURE_ID )
			&& $this->store_sells_to_eu_or_all_countries();
	}

	/**
	 * Get the inbox note.
	 */
	public function get_note(): Note {
		$note = new Note();

		$note->set_title(
			__( 'Enable order withdrawal for EU regulatory requirements', 'woocommerce' )
		);
		$note->set_content(
			__(
				'Stores selling to EU countries may need to offer customers a way to withdraw from qualifying orders. WooCommerce includes an order withdrawal feature you can enable.',
				'woocommerce'
			)
		);
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_WARNING );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'review-feature-settings',
			__( 'Review feature settings', 'woocommerce' ),
			admin_url( self::FEATURES_SETTINGS_URL ),
			Note::E_WC_ADMIN_NOTE_ACTIONED,
			true
		);
		$note->add_action(
			'learn-more',
			__( 'Learn more', 'woocommerce' ),
			self::DOCUMENTATION_URL,
			Note::E_WC_ADMIN_NOTE_UNACTIONED
		);

		return $note;
	}

	/**
	 * Whether this note has already been created, including soft-deleted notes.
	 */
	private function has_note_been_created(): bool {
		if ( 'yes' === get_option( self::CREATED_OPTION, 'no' ) ) {
			return true;
		}

		$data_store = Notes::load_data_store();
		$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );

		if ( empty( $note_ids ) ) {
			return false;
		}

		update_option( self::CREATED_OPTION, 'yes', false );

		return true;
	}

	/**
	 * Whether the store sells to at least one EU country, or sells to all countries.
	 */
	private function store_sells_to_eu_or_all_countries(): bool {
		$allowed_countries = get_option( self::ALLOWED_COUNTRIES_OPTION, 'all' );

		if (
			! is_string( $allowed_countries ) ||
			'all' === $allowed_countries ||
			'' === $allowed_countries
		) {
			return true;
		}

		$woocommerce = function_exists( 'WC' ) ? WC() : null;

		if ( ! $woocommerce || ! $woocommerce->countries instanceof \WC_Countries ) {
			return false;
		}

		$eu_countries = $woocommerce->countries->get_european_union_countries();

		if ( 'specific' === $allowed_countries ) {
			$specific_countries = $this->get_country_codes_option( self::SPECIFIC_ALLOWED_COUNTRIES_OPTION );

			return ! empty( array_intersect( $eu_countries, $specific_countries ) );
		}

		if ( 'all_except' === $allowed_countries ) {
			$excluded_countries = $this->get_country_codes_option( self::ALL_EXCEPT_COUNTRIES_OPTION );

			return ! empty( array_diff( $eu_countries, $excluded_countries ) );
		}

		return true;
	}

	/**
	 * Get country codes stored in a country-list option.
	 *
	 * @param string $option Option name.
	 * @return string[]
	 */
	private function get_country_codes_option( string $option ): array {
		$countries = get_option( $option, array() );

		if ( ! is_array( $countries ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'strval', $countries ) ) );
	}
}
