<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderWithdrawal;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\DataStore as NotesDataStore;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Exception;

/**
 * Adds an inbox notification about the order withdrawal feature for eligible stores.
 *
 * @internal Just for internal use.
 */
final class OrderWithdrawalFeatureHighlightNotification implements RegisterHooksInterface {

	public const NOTE_NAME              = 'wc-admin-order-withdrawal-feature';
	public const CREATED_OPTION         = 'woocommerce_order_withdrawal_inbox_notification_created';
	public const ENABLED_NOTE_NAME      = 'wc-admin-order-withdrawal-enabled';
	public const ENABLED_CREATED_OPTION = 'woocommerce_order_withdrawal_enabled_inbox_notification_created';

	private const COMING_SOON_OPTION    = 'woocommerce_coming_soon';
	private const ENDPOINT_OPTION       = 'woocommerce_myaccount_order_withdrawal_endpoint';
	private const ENDPOINT_SLUG         = 'withdraw-order';
	private const FEATURES_SETTINGS_URL = 'admin.php?page=wc-settings&tab=advanced&section=features';
	private const DOCUMENTATION_URL     = 'https://woocommerce.com/document/customer-order-withdrawal/';

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
	 * This is called when the `woocommerce_coming_soon` option is updated. It checks if the store has gone live and if so, it calls the `possibly_add_note` method to add the note.
	 *
	 * @internal
	 * @since 11.1.0
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
		try {
			if ( $this->has_note_been_created( self::CREATED_OPTION, self::NOTE_NAME ) ) {
				return;
			}

			if ( ! $this->is_applicable() ) {
				return;
			}

			if ( ! add_option( self::CREATED_OPTION, 'yes', '', false ) ) {
				return;
			}

			$this->get_feature_highlight_note()->save();
		} catch ( Exception $exception ) {
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
	 * Add a note when the order withdrawal feature is enabled.
	 *
	 * @param mixed $feature_id Feature being toggled.
	 * @param mixed $enabled    Whether the feature was enabled.
	 *
	 * @since 11.2.0
	 */
	public function possibly_add_enabled_note( $feature_id, $enabled ): void {
		if ( 'order_withdrawal' !== $feature_id || true !== $enabled ) {
			return;
		}

		try {
			if ( $this->has_note_been_created( self::ENABLED_CREATED_OPTION, self::ENABLED_NOTE_NAME ) ) {
				return;
			}

			if ( ! add_option( self::ENABLED_CREATED_OPTION, 'yes', '', false ) ) {
				return;
			}

			$this->get_enabled_note()->save();
		} catch ( Exception $exception ) {
			delete_option( self::ENABLED_CREATED_OPTION );
			wc_get_logger()->error(
				'Unable to create the order withdrawal enabled inbox notification.',
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
	private function is_applicable(): bool {
		return 'no' === get_option( self::COMING_SOON_OPTION, 'yes' )
			&& $this->store_sells_to_eu_or_all_countries();
	}

	/**
	 * Get the inbox note.
	 */
	private function get_feature_highlight_note(): Note {
		$note = new Note();

		$note->set_title(
			__( 'Enable order withdrawal for EU regulatory requirements', 'woocommerce' )
		);
		$note->set_content(
			__(
				'Stores selling to EU countries may need to offer customers a way to withdraw from qualifying orders. Review how to enable the order withdrawal feature in the Advanced settings.',
				'woocommerce'
			)
		);
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'review-feature-settings',
			__( 'Get started', 'woocommerce' ),
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
	 * Get the inbox note shown after the feature is enabled.
	 */
	private function get_enabled_note(): Note {
		$note = new Note();

		$note->set_title( __( 'The order withdrawal feature is enabled', 'woocommerce' ) );
		$note->set_content(
			__( 'This gives customers a simple, self-serve way to request a withdrawal during the applicable period, generally 14 days from delivery for goods or from when a service contract is agreed.', 'woocommerce' )
		);
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::ENABLED_NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'view-page',
			__( 'View page', 'woocommerce' ),
			$this->get_order_withdrawal_page_url(),
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
	 * Whether a note has already been created, including soft-deleted notes.
	 *
	 * @param string $created_option Option tracking whether the note was created.
	 * @param string $note_name      Note name stored in the data store.
	 */
	private function has_note_been_created( string $created_option, string $note_name ): bool {
		if ( 'yes' === get_option( $created_option, 'no' ) ) {
			return true;
		}

		/**
		 * Data store instance.
		 *
		 * @var NotesDataStore $data_store
		 */
		$data_store = Notes::load_data_store();
		$note_ids   = $data_store->get_notes_with_name( $note_name );

		if ( empty( $note_ids ) ) {
			return false;
		}

		update_option( $created_option, 'yes', false );

		return true;
	}

	/**
	 * Get the configured public order withdrawal page URL.
	 */
	private function get_order_withdrawal_page_url(): string {
		$endpoint    = (string) get_option( self::ENDPOINT_OPTION, self::ENDPOINT_SLUG );
		$account_url = wc_get_page_permalink( 'myaccount' );

		return wc_get_endpoint_url( $endpoint, '', $account_url ? $account_url : home_url( '/' ) );
	}

	/**
	 * Whether the store sells to at least one EU country.
	 */
	private function store_sells_to_eu_or_all_countries(): bool {
		$woocommerce = function_exists( 'WC' ) ? WC() : null;

		if ( ! $woocommerce || ! $woocommerce->countries instanceof \WC_Countries ) {
			return false;
		}

		return ! empty(
			array_intersect(
				$woocommerce->countries->get_european_union_countries(),
				array_keys( $woocommerce->countries->get_allowed_countries() )
			)
		);
	}
}
