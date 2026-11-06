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

	/**
	 * Order withdrawal endpoint.
	 *
	 * @var OrderWithdrawalEndpoint
	 */
	private OrderWithdrawalEndpoint $endpoint;

	public const NOTE_NAME         = 'wc-admin-order-withdrawal-feature';
	public const ENABLED_NOTE_NAME = 'wc-admin-order-withdrawal-enabled';

	private const COMING_SOON_OPTION    = 'woocommerce_coming_soon';
	private const FEATURES_SETTINGS_URL = 'admin.php?page=wc-settings&tab=advanced&section=features';
	private const DOCUMENTATION_URL     = 'https://woocommerce.com/document/customer-order-withdrawal/';

	/**
	 * Initialize dependencies.
	 *
	 * @param OrderWithdrawalEndpoint $endpoint Order withdrawal endpoint.
	 * @internal
	 */
	final public function init( OrderWithdrawalEndpoint $endpoint ): void { // phpcs:ignore Generic.CodeAnalysis.UnnecessaryFinalModifier.Found -- Required by WooCommerce injection method rules.
		$this->endpoint = $endpoint;
	}

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
			if ( $this->note_exists( self::NOTE_NAME ) ) {
				return;
			}

			if ( ! $this->is_applicable() ) {
				return;
			}

			$this->get_feature_highlight_note()->save();
		} catch ( Exception $exception ) {
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
	 * @since 11.3.0
	 */
	public function possibly_add_enabled_note( $feature_id, $enabled ): void {
		if ( 'order_withdrawal' !== $feature_id || true !== $enabled ) {
			return;
		}

		try {
			if ( $this->note_exists( self::ENABLED_NOTE_NAME ) ) {
				$this->dismiss_note( self::NOTE_NAME );
				return;
			}

			$this->get_enabled_note()->save();
			$this->dismiss_note( self::NOTE_NAME );
		} catch ( Exception $exception ) {
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
	 * Dismiss notes with the given name.
	 *
	 * @param string $note_name Note name stored in the data store.
	 */
	private function dismiss_note( string $note_name ): void {
		/**
		 * Data store instance.
		 *
		 * @var NotesDataStore $data_store
		 */
		$data_store = Notes::load_data_store();
		$note_ids   = $data_store->get_notes_with_name( $note_name );

		foreach ( $note_ids as $note_id ) {
			$note = Notes::get_note( $note_id );

			if ( ! $note instanceof Note || Note::E_WC_ADMIN_NOTE_ACTIONED === $note->get_status() ) {
				continue;
			}

			$note->set_status( Note::E_WC_ADMIN_NOTE_ACTIONED );
			$note->save();
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
			__( 'Your order withdrawal page is ready. Share its URL wherever customers need access to the withdrawal form, such as your store\'s terms and conditions.', 'woocommerce' )
		);
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::ENABLED_NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'view-page',
			__( 'View page', 'woocommerce' ),
			$this->endpoint->get_url(),
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
	 * Whether a note exists, including soft-deleted notes.
	 *
	 * @param string $note_name Note name stored in the data store.
	 */
	private function note_exists( string $note_name ): bool {
		/**
		 * Data store instance.
		 *
		 * @var NotesDataStore $data_store
		 */
		$data_store = Notes::load_data_store();
		$note_ids   = $data_store->get_notes_with_name( $note_name );

		return ! empty( $note_ids );
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
