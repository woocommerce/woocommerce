<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyAnalyticsProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyAnalyticsSqlProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyGeolocationService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyOrderContextService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRequestContext;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRuntimeServiceFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySelectedCurrencyPersistenceService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStoreCurrencyLifecycleService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySwitcherProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyTrackingOrderCountProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyTrackingProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyRuntimeServiceFactory class.
 */
class MultiCurrencyRuntimeServiceFactoryTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var MultiCurrencyRuntimeServiceFactory
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = wc_get_container()->get( MultiCurrencyRuntimeServiceFactory::class );
	}

	/**
	 * @testdox Should create request and order context services.
	 */
	public function test_creates_context_services(): void {
		$this->assertInstanceOf( MultiCurrencyRequestContext::class, $this->sut->create_request_context(), 'Factory should create request context services.' );
		$this->assertInstanceOf( MultiCurrencyOrderContextService::class, $this->sut->create_order_context_service(), 'Factory should create order context services.' );
	}

	/**
	 * @testdox Should create selected-currency persistence and geolocation services.
	 */
	public function test_creates_selected_currency_services(): void {
		$this->assertInstanceOf( MultiCurrencySelectedCurrencyPersistenceService::class, $this->sut->create_selected_currency_persistence_service(), 'Factory should create selected-currency persistence services.' );
		$this->assertInstanceOf( MultiCurrencyGeolocationService::class, $this->sut->create_geolocation_service(), 'Factory should create geolocation services.' );
	}

	/**
	 * @testdox Should create switcher projection services.
	 */
	public function test_creates_switcher_projection_services(): void {
		$this->assertInstanceOf( MultiCurrencySwitcherProjectionService::class, $this->sut->create_switcher_projection_service(), 'Factory should create switcher projection services.' );
	}

	/**
	 * @testdox Should create analytics projection services.
	 */
	public function test_creates_analytics_projection_services(): void {
		$this->assertInstanceOf( MultiCurrencyAnalyticsProjectionService::class, $this->sut->create_analytics_projection_service(), 'Factory should create analytics projection services.' );
		$this->assertInstanceOf( MultiCurrencyAnalyticsSqlProjectionService::class, $this->sut->create_analytics_sql_projection_service(), 'Factory should create analytics SQL projection services.' );
	}

	/**
	 * @testdox Should create tracking projection services.
	 */
	public function test_creates_tracking_projection_services(): void {
		$this->assertInstanceOf( MultiCurrencyTrackingProjectionService::class, $this->sut->create_tracking_projection_service(), 'Factory should create tracking projection services.' );
		$this->assertInstanceOf( MultiCurrencyTrackingOrderCountProjectionService::class, $this->sut->create_tracking_order_count_projection_service(), 'Factory should create tracking order-count projection services.' );
	}

	/**
	 * @testdox Should create store-currency lifecycle services.
	 */
	public function test_creates_store_currency_lifecycle_services(): void {
		$this->assertInstanceOf( MultiCurrencyStoreCurrencyLifecycleService::class, $this->sut->create_store_currency_lifecycle_service(), 'Factory should create store-currency lifecycle services.' );
	}
}
