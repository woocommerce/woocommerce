<?php
/**
 * MultiCurrencyAdminNoteProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\Admin\Settings\Utils;

/**
 * Projects multi-currency WC Admin notes without saving or deleting notes.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyAdminNoteProjectionService {

	private const NOTE_NAME           = 'wc-payments-notes-multi-currency-available';
	private const NOTE_SETUP_PATH     = '/woopayments/settings';
	private const NOTE_SETUP_FRAGMENT = 'advanced';
	private const NOTE_SOURCE         = 'woocommerce-payments';
	private const MIN_WC_VERSION      = '4.4.0';
	private const NOTE_TYPE_INFO      = 'info';
	private const ACTION_STATUS       = 'unactioned';
	private const ADMIN_INIT_HOOK     = 'admin_init';

	/**
	 * Project admin note hook metadata.
	 *
	 * @param bool $is_admin Whether the request is an admin request.
	 * @return array{actions: array<int,array<string,mixed>>}
	 *
	 * @since 11.0.0
	 */
	public static function get_hook_manifest( bool $is_admin ): array {
		if ( ! $is_admin ) {
			return array( 'actions' => array() );
		}

		return array(
			'actions' => array(
				array(
					'hook'     => self::ADMIN_INIT_HOOK,
					'callback' => 'add_woo_admin_notes',
					'priority' => 10,
				),
			),
		);
	}

	/**
	 * Project the multi-currency availability note.
	 *
	 * @return array<string,mixed>
	 *
	 * @since 11.0.0
	 */
	public static function get_note_manifest(): array {
		return array(
			'name'         => self::NOTE_NAME,
			'title'        => __( 'Sell worldwide in multiple currencies', 'woocommerce' ),
			'content'      => __( 'Boost your international sales by allowing your customers to shop and pay in their local currency.', 'woocommerce' ),
			'content_data' => array(),
			'type'         => self::NOTE_TYPE_INFO,
			'source'       => self::NOTE_SOURCE,
			'actions'      => array(
				array(
					'name'    => self::NOTE_NAME,
					'label'   => __( 'Set up now', 'woocommerce' ),
					'query'   => Utils::wc_payments_settings_url(
						self::NOTE_SETUP_PATH,
						array(),
						self::NOTE_SETUP_FRAGMENT
					),
					'status'  => self::ACTION_STATUS,
					'primary' => true,
				),
			),
		);
	}

	/**
	 * Project whether WC Admin notes are supported.
	 *
	 * @param string $wc_version WooCommerce version.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function supports_wc_admin_notes( string $wc_version ): bool {
		return '' !== $wc_version && version_compare( $wc_version, self::MIN_WC_VERSION, '>=' );
	}

	/**
	 * Project add-note eligibility and note metadata.
	 *
	 * @param bool   $is_ajax            Whether the current request is Ajax.
	 * @param string $wc_version         WooCommerce version.
	 * @param bool   $provider_connected Whether the provider account is connected.
	 * @param bool   $can_be_added       Whether note traits say the note can be added.
	 * @return array{should_add: bool, note: array<string,mixed>|null, blockers: array<int,string>}
	 *
	 * @since 11.0.0
	 */
	public static function get_add_note_manifest(
		bool $is_ajax,
		string $wc_version,
		bool $provider_connected,
		bool $can_be_added
	): array {
		$blockers = array();

		if ( $is_ajax ) {
			$blockers[] = 'ajax_request';
		}

		if ( ! self::supports_wc_admin_notes( $wc_version ) ) {
			$blockers[] = 'unsupported_wc_version';
		}

		if ( ! $provider_connected ) {
			$blockers[] = 'provider_not_connected';
		}

		if ( ! $can_be_added ) {
			$blockers[] = 'note_cannot_be_added';
		}

		if ( ! empty( $blockers ) ) {
			return array(
				'should_add' => false,
				'note'       => null,
				'blockers'   => $blockers,
			);
		}

		return array(
			'should_add' => true,
			'note'       => self::get_note_manifest(),
			'blockers'   => array(),
		);
	}

	/**
	 * Project delete-note metadata.
	 *
	 * @param string $wc_version WooCommerce version.
	 * @return array{should_delete: bool, note_name: string|null, blockers: array<int,string>}
	 *
	 * @since 11.0.0
	 */
	public static function get_delete_note_manifest( string $wc_version ): array {
		if ( ! self::supports_wc_admin_notes( $wc_version ) ) {
			return array(
				'should_delete' => false,
				'note_name'     => null,
				'blockers'      => array( 'unsupported_wc_version' ),
			);
		}

		return array(
			'should_delete' => true,
			'note_name'     => self::NOTE_NAME,
			'blockers'      => array(),
		);
	}
}
