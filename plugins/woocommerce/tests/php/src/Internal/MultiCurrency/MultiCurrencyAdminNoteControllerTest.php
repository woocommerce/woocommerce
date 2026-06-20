<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyAccountInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyAdminNoteController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\Providers\MultiCurrencyProviderAccountResolver;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyAdminNoteController class.
 */
class MultiCurrencyAdminNoteControllerTest extends WC_Unit_Test_Case {

	private const ADMIN_INIT = 'admin_init';

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down(): void {
		remove_all_filters( self::ADMIN_INIT );

		parent::tear_down();
	}

	/**
	 * @testdox Should not register admin note hook when plugin owns runtime.
	 */
	public function test_does_not_register_admin_note_hook_when_plugin_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN, true );

		$sut->register();

		$this->assertFalse( has_action( self::ADMIN_INIT, array( $sut, 'handle_admin_init' ) ) );
	}

	/**
	 * @testdox Should not register admin note hook outside admin requests.
	 */
	public function test_does_not_register_admin_note_hook_outside_admin_requests(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false );

		$sut->register();

		$this->assertFalse( has_action( self::ADMIN_INIT, array( $sut, 'handle_admin_init' ) ) );
	}

	/**
	 * @testdox Should register admin note hook once when core owns runtime in admin.
	 */
	public function test_registers_admin_note_hook_once_when_core_owns_runtime_in_admin(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, true );

		$sut->register();
		$sut->register();

		$this->assertSame( 10, has_action( self::ADMIN_INIT, array( $sut, 'handle_admin_init' ) ) );
	}

	/**
	 * @testdox Should not save admin note when add-note blockers are present.
	 *
	 * @dataProvider add_note_blocker_provider
	 *
	 * @param bool   $is_ajax            Whether the request is Ajax.
	 * @param string $wc_version         WooCommerce version.
	 * @param bool   $provider_connected Whether the provider is connected.
	 * @param bool   $can_be_added       Whether the note can be added.
	 */
	public function test_does_not_save_admin_note_when_blockers_are_present(
		bool $is_ajax,
		string $wc_version,
		bool $provider_connected,
		bool $can_be_added
	): void {
		$saved_notes = array();
		$sut         = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, true );
		$sut->set_ajax_request_resolver( static fn(): bool => $is_ajax );
		$sut->set_wc_version_resolver( static fn(): string => $wc_version );
		$sut->set_provider_connected_resolver( static fn(): bool => $provider_connected );
		$sut->set_note_can_be_added_resolver( static fn(): bool => $can_be_added );
		$sut->set_note_saver(
			static function ( array $note ) use ( &$saved_notes ): void {
				$saved_notes[] = $note;
			}
		);

		$sut->handle_admin_init();

		$this->assertSame( array(), $saved_notes );
	}

	/**
	 * @testdox Should save the projected multi-currency availability note when eligible.
	 */
	public function test_saves_projected_multi_currency_availability_note_when_eligible(): void {
		$saved_notes = array();
		$sut         = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, true );
		$sut->set_ajax_request_resolver( static fn(): bool => false );
		$sut->set_wc_version_resolver( static fn(): string => '11.0.0' );
		$sut->set_note_can_be_added_resolver( static fn(): bool => true );
		$sut->set_note_saver(
			static function ( array $note ) use ( &$saved_notes ): void {
				$saved_notes[] = $note;
			}
		);

		$sut->handle_admin_init();

		$this->assertCount( 1, $saved_notes );
		$this->assertSame( 'wc-payments-notes-multi-currency-available', $saved_notes[0]['name'] );
		$this->assertSame( 'Sell worldwide in multiple currencies', $saved_notes[0]['title'] );
		$this->assertSame( 'woocommerce-payments', $saved_notes[0]['source'] );
		$this->assertSame( 'Set up now', $saved_notes[0]['actions'][0]['label'] );
		$this->assertSame( admin_url( 'admin.php?page=wc-settings&tab=checkout&path=/woopayments/settings#advanced' ), $saved_notes[0]['actions'][0]['query'] );
		$this->assertSame( 'unactioned', $saved_notes[0]['actions'][0]['status'] );
		$this->assertTrue( $saved_notes[0]['actions'][0]['primary'] );
	}

	/**
	 * @testdox Should use provider-neutral account resolver when no test override is set.
	 */
	public function test_uses_provider_account_resolver_when_no_override_is_set(): void {
		$saved_notes = array();
		$sut         = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, true, false );
		$sut->set_ajax_request_resolver( static fn(): bool => false );
		$sut->set_wc_version_resolver( static fn(): string => '11.0.0' );
		$sut->set_note_can_be_added_resolver( static fn(): bool => true );
		$sut->set_note_saver(
			static function ( array $note ) use ( &$saved_notes ): void {
				$saved_notes[] = $note;
			}
		);

		$sut->handle_admin_init();

		$this->assertSame( array(), $saved_notes );
	}

	/**
	 * Data provider for add-note blockers.
	 *
	 * @return array<string,array{0: bool, 1: string, 2: bool, 3: bool}>
	 */
	public function add_note_blocker_provider(): array {
		return array(
			'ajax request'           => array( true, '11.0.0', true, true ),
			'unsupported WC version' => array( false, '4.3.9', true, true ),
			'provider disconnected'  => array( false, '11.0.0', false, true ),
			'note cannot be added'   => array( false, '11.0.0', true, false ),
		);
	}

	/**
	 * Create an admin note controller.
	 *
	 * @param string $owner              Runtime owner.
	 * @param bool   $is_admin           Whether the request is admin.
	 * @param bool   $provider_connected Whether the provider account is connected.
	 * @return MultiCurrencyAdminNoteController
	 */
	private function create_controller( string $owner, bool $is_admin, bool $provider_connected = true ): MultiCurrencyAdminNoteController {
		$account_resolver = new MultiCurrencyProviderAccountResolver();
		$account_resolver->set_account( $this->create_account_boundary( $provider_connected ) );

		$controller = new MultiCurrencyAdminNoteController();
		$controller->init( $this->create_arbiter( $owner ), $account_resolver );
		$controller->set_admin_request_resolver( static fn(): bool => $is_admin );

		return $controller;
	}

	/**
	 * Create a provider account boundary test double.
	 *
	 * @param bool $provider_connected Whether the provider account is connected.
	 * @return MultiCurrencyAccountInterface
	 */
	private function create_account_boundary( bool $provider_connected ): MultiCurrencyAccountInterface {
		return new class( $provider_connected ) implements MultiCurrencyAccountInterface {
			/**
			 * Whether the provider account is connected.
			 *
			 * @var bool
			 */
			private bool $provider_connected;

			/**
			 * Constructor.
			 *
			 * @param bool $provider_connected Whether the provider account is connected.
			 */
			public function __construct( bool $provider_connected ) {
				$this->provider_connected = $provider_connected;
			}

			/**
			 * Tell whether the rate provider account is connected.
			 *
			 * @param bool $on_error Value to return on provider errors.
			 * @return bool
			 */
			public function is_provider_connected( bool $on_error = false ): bool {
				unset( $on_error );

				return $this->provider_connected;
			}

			/**
			 * Tell whether the connected account is rejected.
			 *
			 * @return bool
			 */
			public function is_account_rejected(): bool {
				return false;
			}

			/**
			 * Get cached provider account data.
			 *
			 * @param bool $force_refresh Whether to force-refresh provider data.
			 * @return array<string,mixed>
			 */
			public function get_cached_account_data( bool $force_refresh = false ) {
				unset( $force_refresh );

				return array();
			}

			/**
			 * Get account-supported customer currencies.
			 *
			 * @return string[]
			 */
			public function get_account_customer_supported_currencies(): array {
				return array();
			}

			/**
			 * Get provider-supported countries.
			 *
			 * @return string[]
			 */
			public function get_supported_countries(): array {
				return array();
			}

			/**
			 * Get the provider onboarding URL.
			 *
			 * @return string
			 */
			public function get_provider_onboarding_page_url(): string {
				return '';
			}
		};
	}

	/**
	 * Create a static multi-currency runtime arbiter.
	 *
	 * @param string $owner Runtime owner.
	 * @return MultiCurrencyRuntimeArbiter
	 */
	private function create_arbiter( string $owner ): MultiCurrencyRuntimeArbiter {
		return new class( $owner ) extends MultiCurrencyRuntimeArbiter {
			/**
			 * Runtime owner.
			 *
			 * @var string
			 */
			private string $owner;

			/**
			 * Constructor.
			 *
			 * @param string $owner Runtime owner.
			 */
			public function __construct( string $owner ) {
				$this->owner = $owner;
			}

			/**
			 * Get the multi-currency runtime owner for the current site.
			 *
			 * @return string
			 */
			public function get_runtime_owner(): string {
				return $this->owner;
			}

			/**
			 * Tell whether core multi-currency may register hooks.
			 *
			 * @return bool
			 */
			public function should_core_register(): bool {
				return MultiCurrencyRuntimeArbiter::OWNER_CORE === $this->owner;
			}
		};
	}
}
