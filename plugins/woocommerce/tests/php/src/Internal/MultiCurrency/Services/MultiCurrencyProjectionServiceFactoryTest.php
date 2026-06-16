<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyFrontendProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyProjectionServiceFactory;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyProjectionServiceFactory class.
 */
class MultiCurrencyProjectionServiceFactoryTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should create price projection services from one shared localization graph.
	 */
	public function test_creates_price_projection_service(): void {
		$sut = wc_get_container()->get( MultiCurrencyProjectionServiceFactory::class );

		$service = $sut->create_price_projection_service();

		$this->assertInstanceOf( MultiCurrencyPriceProjectionService::class, $service );
	}

	/**
	 * @testdox Should create frontend projection services from one shared localization graph.
	 */
	public function test_creates_frontend_projection_service(): void {
		$sut = wc_get_container()->get( MultiCurrencyProjectionServiceFactory::class );

		$service = $sut->create_frontend_projection_service();

		$this->assertInstanceOf( MultiCurrencyFrontendProjectionService::class, $service );
	}
}
