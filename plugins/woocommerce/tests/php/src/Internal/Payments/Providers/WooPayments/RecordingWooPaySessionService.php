<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsWooPaySessionService;
use WP_REST_Request;

/**
 * Recording WooPay session service for controller tests.
 */
class RecordingWooPaySessionService extends WooPaymentsWooPaySessionService {

	/**
	 * Whether WooPay is enabled.
	 *
	 * @var bool
	 */
	public bool $woopay_enabled = true;

	/**
	 * Whether the WooPay button should be shown.
	 *
	 * @var bool
	 */
	public bool $should_show_woopay_button = true;

	/**
	 * Whether WooPay save-user assets should be loaded.
	 *
	 * @var bool
	 */
	public bool $should_load_woopay_save_user_assets = true;

	/**
	 * Last session email.
	 *
	 * @var string
	 */
	public string $last_session_email = '';

	/**
	 * Last phone request.
	 *
	 * @var array<string,mixed>
	 */
	public array $last_phone_request = array();

	/**
	 * Last appearance.
	 *
	 * @var array<string,mixed>
	 */
	public array $last_appearance = array();

	/**
	 * Whether appearance should be stored.
	 *
	 * @var bool
	 */
	public bool $appearance_stored = true;

	/**
	 * Tell whether WooPay is enabled.
	 *
	 * @return bool
	 */
	public function is_woopay_enabled(): bool {
		return $this->woopay_enabled;
	}

	/**
	 * Tell whether the WooPay button should be shown.
	 *
	 * @param string $context Express checkout context.
	 * @return bool
	 */
	public function should_show_woopay_button( string $context = 'checkout' ): bool {
		unset( $context );

		return $this->should_show_woopay_button;
	}

	/**
	 * Tell whether WooPay save-user assets should load.
	 *
	 * @param string $context Express checkout context.
	 * @return bool
	 */
	public function should_load_woopay_save_user_assets( string $context = 'checkout' ): bool {
		unset( $context );

		return $this->should_load_woopay_save_user_assets;
	}

	/**
	 * Get WooPay frontend config.
	 *
	 * @param string $context Express checkout context.
	 * @return array<string,mixed>
	 */
	public function get_woopay_frontend_config( string $context = 'checkout' ): array {
		return array(
			'isWooPayEnabled'        => $this->woopay_enabled,
			'shouldShowWooPayButton' => $this->should_show_woopay_button,
			'forceNetworkSavedCards' => true,
			'woopayButton'           => array(
				'type'    => 'default',
				'theme'   => 'dark',
				'height'  => '48',
				'radius'  => '',
				'size'    => 'medium',
				'context' => $context,
			),
		);
	}

	/**
	 * Get WooPay save-user checkout data.
	 *
	 * @return array<string,bool>
	 */
	public function get_save_user_checkout_data(): array {
		return array( 'PRE_CHECK_SAVE_MY_INFO' => true );
	}

	/**
	 * Get session data.
	 *
	 * @param string|null          $email          Shopper email.
	 * @param WP_REST_Request|null $woopay_request WooPay REST request.
	 * @return array<string,string>
	 */
	public function get_session_data( ?string $email = null, ?WP_REST_Request $woopay_request = null ): array {
		unset( $woopay_request );
		$this->last_session_email = (string) $email;

		return array( 'session' => 'native' );
	}

	/**
	 * Init WooPay.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @return array<string,string>
	 */
	public function init_woopay_session( array $request ): array {
		return array( 'result' => 'success' );
	}

	/**
	 * Get encrypted session data.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @return array<string,string>
	 */
	public function get_encrypted_session_data( array $request ): array {
		return array( 'encrypted' => 'session' );
	}

	/**
	 * Store WooPay phone data.
	 *
	 * @param array<string,mixed> $request Request data.
	 */
	public function set_woopay_phone_session_data( array $request ): void {
		$this->last_phone_request = $request;
	}

	/**
	 * Get the WooPay request signature.
	 *
	 * @return string
	 */
	public function get_woopay_request_signature(): string {
		return 'signed';
	}

	/**
	 * Get encrypted minimum session data.
	 *
	 * @return array<string,string>
	 */
	public function get_encrypted_minimum_session_data(): array {
		return array( 'encrypted' => 'minimum' );
	}

	/**
	 * Save WooPay appearance data.
	 *
	 * @param array<string,mixed>             $appearance Appearance data.
	 * @param array<int,array<string,string>> $font_rules Font rules.
	 */
	public function save_woopay_appearance( array $appearance, array $font_rules = array() ): void {
		unset( $font_rules );
		$this->last_appearance = $appearance;
	}

	/**
	 * Maybe save WooPay appearance data.
	 *
	 * @param array<string,mixed>             $appearance Appearance data.
	 * @param array<int,array<string,string>> $font_rules Font rules.
	 * @return bool
	 */
	public function maybe_save_woopay_appearance( array $appearance, array $font_rules = array() ): bool {
		unset( $font_rules );
		$this->last_appearance = $appearance;

		return $this->appearance_stored;
	}

	/**
	 * Validate WooPay appearance data.
	 *
	 * @param array<string,mixed> $appearance Appearance data.
	 * @return bool
	 */
	public function validate_appearance_schema( array $appearance ): bool {
		return ! isset( $appearance['buttonTheme'] );
	}
}
