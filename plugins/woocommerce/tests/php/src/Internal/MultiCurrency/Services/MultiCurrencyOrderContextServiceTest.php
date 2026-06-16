<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyOrderContextService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyOrderContextService class.
 */
class MultiCurrencyOrderContextServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should use order currency on WooCommerce order pages and formatting backtraces.
	 */
	public function test_uses_order_currency_on_order_page_formatting_backtrace(): void {
		$sut = $this->create_service(
			array( 'WC_Order->get_formatted_order_total' )
		);

		$this->with_query_vars(
			array(
				'pagename'  => 'checkout',
				'order-pay' => 123,
			),
			function () use ( $sut ): void {
				$this->assertTrue( $sut->should_use_order_currency() );
			}
		);
	}

	/**
	 * @testdox Should not use order currency outside supported WooCommerce pages.
	 */
	public function test_does_not_use_order_currency_outside_supported_pages(): void {
		$sut = $this->create_service(
			array( 'WC_Order->get_formatted_order_total' )
		);

		$this->with_query_vars(
			array(
				'pagename'  => 'shop',
				'order-pay' => 123,
			),
			function () use ( $sut ): void {
				$this->assertFalse( $sut->should_use_order_currency() );
			}
		);
	}

	/**
	 * @testdox Should not use order currency without supported order query vars.
	 */
	public function test_does_not_use_order_currency_without_supported_order_query_vars(): void {
		$sut = $this->create_service(
			array( 'WC_Order->get_formatted_order_total' )
		);

		$this->with_query_vars(
			array( 'pagename' => 'checkout' ),
			function () use ( $sut ): void {
				$this->assertFalse( $sut->should_use_order_currency() );
			}
		);
	}

	/**
	 * @testdox Should not use order currency without supported formatting backtrace.
	 */
	public function test_does_not_use_order_currency_without_supported_formatting_backtrace(): void {
		$sut = $this->create_service( array( 'Other_Class->render' ) );

		$this->with_query_vars(
			array(
				'pagename'       => 'my-account',
				'view-order'     => 123,
				'unrelated-data' => 'present',
			),
			function () use ( $sut ): void {
				$this->assertFalse( $sut->should_use_order_currency() );
			}
		);
	}

	/**
	 * Create an order context service with a deterministic backtrace.
	 *
	 * @param array<int, string> $backtrace_summary Backtrace summary.
	 * @return MultiCurrencyOrderContextService
	 */
	private function create_service( array $backtrace_summary ): MultiCurrencyOrderContextService {
		return new MultiCurrencyOrderContextService(
			static function () use ( $backtrace_summary ): array {
				return $backtrace_summary;
			}
		);
	}

	/**
	 * Run a callback with temporary WordPress query vars.
	 *
	 * @param array<string, mixed> $query_vars Query vars.
	 * @param callable             $callback   Callback to run.
	 */
	private function with_query_vars( array $query_vars, callable $callback ): void {
		global $wp;

		$this->assertInstanceOf( \WP::class, $wp );

		$previous_query_vars = $wp->query_vars;
		$wp->query_vars      = $query_vars;

		try {
			$callback();
		} finally {
			$wp->query_vars = $previous_query_vars;
		}
	}
}
