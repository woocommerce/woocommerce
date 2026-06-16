<?php
/**
 * MultiCurrencyRuntimeServiceFactory class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;

/**
 * Creates native multi-currency runtime services used by controllers.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyRuntimeServiceFactory {

	/**
	 * State builder factory.
	 *
	 * @var MultiCurrencyStateBuilderFactory
	 */
	private MultiCurrencyStateBuilderFactory $state_builder_factory;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyStateBuilderFactory $state_builder_factory State builder factory.
	 */
	final public function init( MultiCurrencyStateBuilderFactory $state_builder_factory ): void {
		$this->state_builder_factory = $state_builder_factory;
	}

	/**
	 * Create a request context.
	 *
	 * @return MultiCurrencyRequestContext
	 */
	public function create_request_context(): MultiCurrencyRequestContext {
		return new MultiCurrencyRequestContext();
	}

	/**
	 * Create an order context service.
	 *
	 * @return MultiCurrencyOrderContextService
	 */
	public function create_order_context_service(): MultiCurrencyOrderContextService {
		return new MultiCurrencyOrderContextService();
	}

	/**
	 * Create a geolocation service.
	 *
	 * @param MultiCurrencyLocalizationInterface|null $localization_service Optional localization boundary.
	 * @return MultiCurrencyGeolocationService
	 */
	public function create_geolocation_service( ?MultiCurrencyLocalizationInterface $localization_service = null ): MultiCurrencyGeolocationService {
		return new MultiCurrencyGeolocationService( $localization_service ?? new MultiCurrencyLocalizationService() );
	}

	/**
	 * Create a selected-currency persistence service.
	 *
	 * @param MultiCurrencyStateBuilder|null $state_builder Optional state builder boundary.
	 * @return MultiCurrencySelectedCurrencyPersistenceService
	 */
	public function create_selected_currency_persistence_service( ?MultiCurrencyStateBuilder $state_builder = null ): MultiCurrencySelectedCurrencyPersistenceService {
		return new MultiCurrencySelectedCurrencyPersistenceService( $state_builder ?? $this->state_builder_factory->create() );
	}

	/**
	 * Create a switcher projection service.
	 *
	 * @param MultiCurrencyStateBuilder|null $state_builder Optional state builder boundary.
	 * @return MultiCurrencySwitcherProjectionService
	 */
	public function create_switcher_projection_service( ?MultiCurrencyStateBuilder $state_builder = null ): MultiCurrencySwitcherProjectionService {
		return new MultiCurrencySwitcherProjectionService( $state_builder ?? $this->state_builder_factory->create() );
	}

	/**
	 * Create an analytics projection service.
	 *
	 * @param MultiCurrencyStateBuilder|null $state_builder Optional state builder boundary.
	 * @return MultiCurrencyAnalyticsProjectionService
	 */
	public function create_analytics_projection_service( ?MultiCurrencyStateBuilder $state_builder = null ): MultiCurrencyAnalyticsProjectionService {
		return new MultiCurrencyAnalyticsProjectionService( $state_builder ?? $this->state_builder_factory->create() );
	}

	/**
	 * Create an analytics SQL projection service.
	 *
	 * @return MultiCurrencyAnalyticsSqlProjectionService
	 */
	public function create_analytics_sql_projection_service(): MultiCurrencyAnalyticsSqlProjectionService {
		return new MultiCurrencyAnalyticsSqlProjectionService();
	}

	/**
	 * Create a tracking projection service.
	 *
	 * @param MultiCurrencyStateBuilder|null $state_builder Optional state builder boundary.
	 * @return MultiCurrencyTrackingProjectionService
	 */
	public function create_tracking_projection_service( ?MultiCurrencyStateBuilder $state_builder = null ): MultiCurrencyTrackingProjectionService {
		return new MultiCurrencyTrackingProjectionService( $state_builder ?? $this->state_builder_factory->create() );
	}

	/**
	 * Create a tracking order-count projection service.
	 *
	 * @return MultiCurrencyTrackingOrderCountProjectionService
	 */
	public function create_tracking_order_count_projection_service(): MultiCurrencyTrackingOrderCountProjectionService {
		return new MultiCurrencyTrackingOrderCountProjectionService();
	}

	/**
	 * Create a store-currency lifecycle service.
	 *
	 * @return MultiCurrencyStoreCurrencyLifecycleService
	 */
	public function create_store_currency_lifecycle_service(): MultiCurrencyStoreCurrencyLifecycleService {
		$cache = new MultiCurrencyDatabaseCache();

		return new MultiCurrencyStoreCurrencyLifecycleService(
			$cache,
			$this->state_builder_factory->create( null, $cache )
		);
	}
}
