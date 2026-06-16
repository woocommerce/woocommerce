<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySelectedCurrencyPersistenceService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencySelectedCurrencyPersistenceService class.
 */
class MultiCurrencySelectedCurrencyPersistenceServiceTest extends WC_Unit_Test_Case {

	/**
	 * Original WooCommerce session.
	 *
	 * @var mixed
	 */
	private $original_session;

	/**
	 * Original WooCommerce cart.
	 *
	 * @var mixed
	 */
	private $original_cart;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_session = WC()->session;
		$this->original_cart    = WC()->cart;
		wp_set_current_user( 0 );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		WC()->session = $this->original_session;
		WC()->cart    = $this->original_cart;
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * @testdox Should update logged-in user meta for an enabled currency.
	 */
	public function test_updates_logged_in_user_meta_for_enabled_currency(): void {
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );
		$sut = $this->create_service( 'USD' );

		$updated = $sut->update_selected_currency( ' gbp ' );

		$this->assertTrue( $updated, 'Enabled currencies should be persisted.' );
		$this->assertSame( 'GBP', get_user_meta( $user_id, 'wcpay_currency', true ) );
	}

	/**
	 * @testdox Should reject disabled currency without writing user meta.
	 */
	public function test_rejects_disabled_currency_without_writing_user_meta(): void {
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );
		$sut = $this->create_service( 'USD' );

		$updated = $sut->update_selected_currency( 'EUR' );

		$this->assertFalse( $updated, 'Disabled currencies should not be persisted.' );
		$this->assertSame( '', get_user_meta( $user_id, 'wcpay_currency', true ) );
	}

	/**
	 * @testdox Should update guest session for an enabled currency.
	 */
	public function test_updates_guest_session_for_enabled_currency(): void {
		$session      = $this->create_session();
		WC()->session = $session;
		$sut          = $this->create_service( 'USD' );

		$updated = $sut->update_selected_currency( 'GBP' );

		$this->assertTrue( $updated, 'Guest currency changes should be persisted to session.' );
		$this->assertSame( 'GBP', WC()->session->get( 'wcpay_currency' ) );
		$this->assertSame( 1, $session->cookie_writes, 'Guest currency changes should request a session cookie.' );
	}

	/**
	 * @testdox Should detect stored logged-in user currency.
	 */
	public function test_detects_stored_logged_in_user_currency(): void {
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );
		update_user_meta( $user_id, 'wcpay_currency', 'GBP' );
		$sut = $this->create_service( 'USD' );

		$this->assertTrue( $sut->has_stored_currency_code() );
	}

	/**
	 * @testdox Should detect stored guest session currency.
	 */
	public function test_detects_stored_guest_session_currency(): void {
		WC()->session = $this->create_session( 'GBP' );
		$sut          = $this->create_service( 'USD' );

		$this->assertTrue( $sut->has_stored_currency_code() );
	}

	/**
	 * @testdox Should report no stored currency when user and session are empty.
	 */
	public function test_reports_no_stored_currency_when_user_and_session_are_empty(): void {
		WC()->session = $this->create_session();
		$sut          = $this->create_service( 'USD' );

		$this->assertFalse( $sut->has_stored_currency_code() );
	}

	/**
	 * @testdox Should copy guest session currency to new customer meta.
	 */
	public function test_copies_guest_session_currency_to_new_customer_meta(): void {
		$session      = $this->create_session( 'GBP' );
		WC()->session = $session;
		$user_id      = self::factory()->user->create();
		$sut          = $this->create_service( 'USD' );

		$updated = $sut->set_new_customer_currency_meta( $user_id );

		$this->assertTrue( $updated, 'Session currency should be copied to new customer meta.' );
		$this->assertSame( 'GBP', get_user_meta( $user_id, 'wcpay_currency', true ) );
	}

	/**
	 * @testdox Should recalculate cart after currency changes.
	 */
	public function test_recalculates_cart_after_currency_change(): void {
		$cart      = $this->create_cart();
		WC()->cart = $cart;
		$sut       = $this->create_service( 'USD' );
		wp_set_current_user( self::factory()->user->create() );

		$sut->update_selected_currency( 'GBP' );

		$this->assertSame( 1, $cart->calculate_totals_calls, 'Changing currency should recalculate cart totals.' );
	}

	/**
	 * Create the persistence service.
	 *
	 * @param string $selected_code Selected currency code.
	 * @return MultiCurrencySelectedCurrencyPersistenceService
	 */
	private function create_service( string $selected_code ): MultiCurrencySelectedCurrencyPersistenceService {
		return new MultiCurrencySelectedCurrencyPersistenceService( $this->create_state_builder( $selected_code ) );
	}

	/**
	 * Create a state builder test double.
	 *
	 * @param string $selected_code Selected currency code.
	 * @return MultiCurrencyStateBuilder
	 */
	private function create_state_builder( string $selected_code ): MultiCurrencyStateBuilder {
		return new class( $this->create_state( $selected_code ) ) extends MultiCurrencyStateBuilder {
			/**
			 * Multi-currency state.
			 *
			 * @var MultiCurrencyState
			 */
			private MultiCurrencyState $state;

			/**
			 * Constructor.
			 *
			 * @param MultiCurrencyState $state Multi-currency state.
			 */
			public function __construct( MultiCurrencyState $state ) {
				$this->state = $state;
			}

			/**
			 * Build a multi-currency state snapshot.
			 *
			 * @return MultiCurrencyState
			 */
			public function build(): MultiCurrencyState {
				return $this->state;
			}
		};
	}

	/**
	 * Create multi-currency state.
	 *
	 * @param string $selected_code Selected currency code.
	 * @return MultiCurrencyState
	 */
	private function create_state( string $selected_code ): MultiCurrencyState {
		$usd = $this->create_currency( 'USD', true );
		$gbp = $this->create_currency( 'GBP', false );

		$enabled = array(
			'USD' => $usd,
			'GBP' => $gbp,
		);

		return new MultiCurrencyState( $enabled, $enabled, $usd, $enabled[ $selected_code ] );
	}

	/**
	 * Create a currency.
	 *
	 * @param string $code       Currency code.
	 * @param bool   $is_default Whether this is the default currency.
	 * @return MultiCurrencyCurrency
	 */
	private function create_currency( string $code, bool $is_default ): MultiCurrencyCurrency {
		return new MultiCurrencyCurrency( $this->create_localization(), $code, 1.0, $is_default );
	}

	/**
	 * Create a localization test double.
	 *
	 * @return MultiCurrencyLocalizationInterface
	 */
	private function create_localization(): MultiCurrencyLocalizationInterface {
		return new class() implements MultiCurrencyLocalizationInterface {
			/**
			 * Get a currency format.
			 *
			 * @param string $currency_code Currency code.
			 * @return array<string,mixed>
			 */
			public function get_currency_format( $currency_code ): array {
				unset( $currency_code );

				return array(
					'currency_pos' => 'left',
					'thousand_sep' => ',',
					'decimal_sep'  => '.',
					'num_decimals' => 2,
				);
			}

			/**
			 * Get locale data for a country.
			 *
			 * @param string $country Country code.
			 * @return array<string,mixed>
			 */
			public function get_country_locale_data( $country ): array {
				unset( $country );

				return array();
			}
		};
	}

	/**
	 * Create a session test double.
	 *
	 * @param string|null $currency_code Optional stored currency code.
	 * @return object
	 */
	private function create_session( ?string $currency_code = null ): object {
		return new class( $currency_code ) {
			/**
			 * Session data.
			 *
			 * @var array<string,mixed>
			 */
			private array $data = array();

			/**
			 * Session cookie write count.
			 *
			 * @var int
			 */
			public int $cookie_writes = 0;

			/**
			 * Constructor.
			 *
			 * @param string|null $currency_code Optional stored currency code.
			 */
			public function __construct( ?string $currency_code ) {
				if ( null !== $currency_code ) {
					$this->data['wcpay_currency'] = $currency_code;
				}
			}

			/**
			 * Get a session value.
			 *
			 * @param string $key     Session key.
			 * @param mixed  $default_value Default value.
			 * @return mixed
			 */
			public function get( string $key, $default_value = null ) {
				return $this->data[ $key ] ?? $default_value;
			}

			/**
			 * Set a session value.
			 *
			 * @param string $key   Session key.
			 * @param mixed  $value Session value.
			 */
			public function set( string $key, $value ): void {
				$this->data[ $key ] = $value;
			}

			/**
			 * Set the customer session cookie.
			 *
			 * @param bool $set Whether to set the cookie.
			 */
			public function set_customer_session_cookie( bool $set ): void {
				if ( $set ) {
					++$this->cookie_writes;
				}
			}
		};
	}

	/**
	 * Create a cart test double.
	 *
	 * @return object
	 */
	private function create_cart(): object {
		return new class() {
			/**
			 * Calculate totals call count.
			 *
			 * @var int
			 */
			public int $calculate_totals_calls = 0;

			/**
			 * Calculate totals.
			 */
			public function calculate_totals(): void {
				++$this->calculate_totals_calls;
			}
		};
	}
}
