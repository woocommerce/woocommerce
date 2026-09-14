<?php
/**
 * View configuration for the Settings DataForm page.
 *
 * @package WooCommerce
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\SettingsDataForm;

use WP_View_Config_Data;

// phpcs:disable SlevomatCodingStandard.Files.TypeNameMatchesFileName.NoMatchBetweenTypeNameAndFileName -- wp-build copies this package class; Core loads it explicitly.

/**
 * Provides the form layout for the woo_settings/product entity.
 *
 * @internal
 */
final class ViewConfig {

	/**
	 * Register the entity's view configuration filter.
	 *
	 * @since 11.2.0
	 */
	public function __construct() {
		add_filter( 'get_entity_view_config_woo_settings_product', array( $this, 'handle_get_entity_view_config_woo_settings_product' ) );
	}

	/**
	 * Add the store address and currency sections to the form.
	 *
	 * @internal
	 *
	 * @param mixed $data The view configuration container.
	 * @return mixed The updated container.
	 */
	public function handle_get_entity_view_config_woo_settings_product( $data ) {
		// @phpstan-ignore class.notFound (WordPress 7.1's View Config API is not in the bundled WordPress stubs yet.)
		if ( ! $data instanceof WP_View_Config_Data ) {
			return $data;
		}

		// @phpstan-ignore class.notFound (The guarded WordPress 7.1 container provides merge().)
		return $data->merge(
			array(
				'form' => array(
					'layout' => array(
						'type'          => 'regular',
						'labelPosition' => 'top',
					),
					'fields' => array(
						array(
							'id'          => 'store-address',
							'label'       => __( 'Store address', 'woocommerce' ),
							'description' => __( 'The location used to calculate tax and shipping rates.', 'woocommerce' ),
							'layout'      => array(
								'type'     => 'card',
								'isOpened' => true,
							),
							'children'    => array(
								'woocommerce_store_address',
								'woocommerce_store_address_2',
								'woocommerce_store_city',
								'woocommerce_default_country',
								'woocommerce_store_postcode',
							),
						),
						array(
							'id'          => 'currency-options',
							'label'       => __( 'Currency options', 'woocommerce' ),
							'description' => __( 'Choose your currency and how prices appear in your store.', 'woocommerce' ),
							'layout'      => array(
								'type'     => 'card',
								'isOpened' => true,
							),
							'children'    => array(
								'woocommerce_currency',
								'woocommerce_currency_pos',
								'woocommerce_price_thousand_sep',
								'woocommerce_price_decimal_sep',
								'woocommerce_price_num_decimals',
							),
						),
					),
				),
			),
			1
		);
	}
}
