<?php
/**
 * MultiCurrencyProjectionServiceFactory class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;

/**
 * Creates native multi-currency projection service graphs.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyProjectionServiceFactory {

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
	 * Create a price projection service.
	 *
	 * @param MultiCurrencyLocalizationInterface|null $localization_service Optional localization boundary.
	 * @param MultiCurrencyStateBuilder|null          $state_builder        Optional state builder boundary.
	 * @return MultiCurrencyPriceProjectionService
	 */
	public function create_price_projection_service(
		?MultiCurrencyLocalizationInterface $localization_service = null,
		?MultiCurrencyStateBuilder $state_builder = null
	): MultiCurrencyPriceProjectionService {
		$localization_service = $localization_service ?? new MultiCurrencyLocalizationService();
		$state_builder        = $state_builder ?? $this->state_builder_factory->create( $localization_service );

		return new MultiCurrencyPriceProjectionService(
			$state_builder,
			new MultiCurrencyPriceCalculator( $localization_service )
		);
	}

	/**
	 * Create a frontend projection service.
	 *
	 * @param MultiCurrencyLocalizationInterface|null $localization_service Optional localization boundary.
	 * @param MultiCurrencyStateBuilder|null          $state_builder        Optional state builder boundary.
	 * @return MultiCurrencyFrontendProjectionService
	 */
	public function create_frontend_projection_service(
		?MultiCurrencyLocalizationInterface $localization_service = null,
		?MultiCurrencyStateBuilder $state_builder = null
	): MultiCurrencyFrontendProjectionService {
		$localization_service = $localization_service ?? new MultiCurrencyLocalizationService();
		$state_builder        = $state_builder ?? $this->state_builder_factory->create( $localization_service );

		return new MultiCurrencyFrontendProjectionService(
			$state_builder,
			$localization_service,
			new MultiCurrencyGeolocationService( $localization_service )
		);
	}
}
