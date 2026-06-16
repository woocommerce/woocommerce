<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments\MultiCurrency;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\MultiCurrency\WooPaymentsLegacyAccountAdapter;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsLegacyAccountAdapter class.
 */
class WooPaymentsLegacyAccountAdapterTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->reset_legacy_proxy_mocks();

		parent::tearDown();
	}

	/**
	 * @testdox Should fail closed when the WooPayments runtime is absent.
	 */
	public function test_fails_closed_when_woopayments_runtime_is_absent(): void {
		$this->mock_woopayments_runtime( false, null );
		$sut = wc_get_container()->get( WooPaymentsLegacyAccountAdapter::class );

		$this->assertFalse( $sut->is_provider_connected() );
		$this->assertFalse( $sut->is_account_rejected() );
		$this->assertFalse( $sut->get_cached_account_data() );
		$this->assertSame( array(), $sut->get_account_customer_supported_currencies() );
		$this->assertSame( array(), $sut->get_supported_countries() );
		$this->assertSame( '', $sut->get_provider_onboarding_page_url() );
	}

	/**
	 * @testdox Should delegate to the legacy account service when available.
	 */
	public function test_delegates_to_legacy_account_service_when_available(): void {
		$account = $this->create_recording_account();
		$this->mock_woopayments_runtime( true, $account );
		$sut = wc_get_container()->get( WooPaymentsLegacyAccountAdapter::class );

		$this->assertTrue( $sut->is_provider_connected() );
		$this->assertTrue( $sut->is_account_rejected() );
		$this->assertSame( array( 'account_id' => 'acct_123' ), $sut->get_cached_account_data( true ) );
		$this->assertSame( array( 'GBP', 'EUR' ), $sut->get_account_customer_supported_currencies() );
		$this->assertSame( array( 'US', 'GB' ), $sut->get_supported_countries() );
		$this->assertSame( 'https://example.test/onboarding', $sut->get_provider_onboarding_page_url() );
		$this->assertTrue( $account->forced_refresh );
	}

	/**
	 * @testdox Should fail closed when the legacy account service throws.
	 */
	public function test_fails_closed_when_legacy_account_service_throws(): void {
		$account = $this->create_throwing_account();
		$this->mock_woopayments_runtime( true, $account );
		$sut = wc_get_container()->get( WooPaymentsLegacyAccountAdapter::class );

		$this->assertTrue( $sut->is_provider_connected( true ) );
		$this->assertFalse( $sut->is_provider_connected() );
		$this->assertFalse( $sut->is_account_rejected() );
		$this->assertFalse( $sut->get_cached_account_data() );
		$this->assertSame( array(), $sut->get_account_customer_supported_currencies() );
	}

	/**
	 * Mock WooPayments account-service access.
	 *
	 * @param bool        $class_loaded Whether WC_Payments is available.
	 * @param object|null $account      Account service.
	 */
	private function mock_woopayments_runtime( bool $class_loaded, ?object $account ): void {
		$this->register_legacy_proxy_function_mocks(
			array(
				'class_exists' => function ( $class_name, $autoload = true ) use ( $class_loaded ) {
					if ( 'WC_Payments' === ltrim( (string) $class_name, '\\' ) ) {
						return $class_loaded;
					}
					return class_exists( $class_name, $autoload );
				},
			)
		);

		if ( $class_loaded ) {
			$this->register_legacy_proxy_static_mocks(
				array(
					'WC_Payments' => array(
						'get_account_service' => static fn() => $account,
					),
				)
			);
		}
	}

	/**
	 * Create a recording legacy account test double.
	 *
	 * @return object
	 */
	private function create_recording_account(): object {
		return new class() {
			/**
			 * Whether force refresh was requested.
			 *
			 * @var bool
			 */
			public bool $forced_refresh = false;

			/**
			 * Tell whether the provider account is connected.
			 *
			 * @param bool $on_error Error fallback.
			 * @return bool
			 */
			public function is_provider_connected( bool $on_error = false ): bool {
				unset( $on_error );

				return true;
			}

			/**
			 * Tell whether the account is rejected.
			 *
			 * @return bool
			 */
			public function is_account_rejected(): bool {
				return true;
			}

			/**
			 * Get cached account data.
			 *
			 * @param bool $force_refresh Whether to refresh.
			 * @return array<string,string>
			 */
			public function get_cached_account_data( bool $force_refresh = false ): array {
				$this->forced_refresh = $force_refresh;

				return array( 'account_id' => 'acct_123' );
			}

			/**
			 * Get account-supported customer currencies.
			 *
			 * @return string[]
			 */
			public function get_account_customer_supported_currencies(): array {
				return array( 'GBP', 'EUR' );
			}

			/**
			 * Get supported countries.
			 *
			 * @return string[]
			 */
			public function get_supported_countries(): array {
				return array( 'US', 'GB' );
			}

			/**
			 * Get the onboarding URL.
			 *
			 * @return string
			 */
			public function get_provider_onboarding_page_url(): string {
				return 'https://example.test/onboarding';
			}
		};
	}

	/**
	 * Create a throwing legacy account test double.
	 *
	 * @return object
	 */
	private function create_throwing_account(): object {
		return new class() {
			/**
			 * Throw when checking provider connection.
			 *
			 * @throws \RuntimeException Always thrown.
			 */
			public function is_provider_connected(): bool {
				throw new \RuntimeException( 'Account failed' );
			}

			/**
			 * Throw when checking rejected state.
			 *
			 * @throws \RuntimeException Always thrown.
			 */
			public function is_account_rejected(): bool {
				throw new \RuntimeException( 'Account failed' );
			}

			/**
			 * Throw when fetching account data.
			 *
			 * @throws \RuntimeException Always thrown.
			 */
			public function get_cached_account_data(): array {
				throw new \RuntimeException( 'Account failed' );
			}

			/**
			 * Throw when fetching supported currencies.
			 *
			 * @throws \RuntimeException Always thrown.
			 */
			public function get_account_customer_supported_currencies(): array {
				throw new \RuntimeException( 'Account failed' );
			}
		};
	}
}
