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
	 * Add the product settings cards to the form.
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
							'id'       => 'shop-pages',
							'label'    => __( 'Shop pages', 'woocommerce' ),
							'layout'   => array(
								'type'          => 'card',
								'isCollapsible' => false,
							),
							'children' => array(
								'woocommerce_shop_page_id',
								'woocommerce_cart_redirect_after_add',
								'woocommerce_enable_ajax_add_to_cart',
								'woocommerce_placeholder_image',
							),
						),
						array(
							'id'       => 'measurements',
							'label'    => __( 'Measurements', 'woocommerce' ),
							'layout'   => array(
								'type'          => 'card',
								'isCollapsible' => false,
							),
							'children' => array(
								'woocommerce_weight_unit',
								'woocommerce_dimension_unit',
							),
						),
						array(
							'id'       => 'reviews',
							'label'    => __( 'Reviews', 'woocommerce' ),
							'layout'   => array(
								'type'          => 'card',
								'isCollapsible' => false,
							),
							'children' => array(
								'woocommerce_enable_reviews',
								'woocommerce_review_rating_verification_label',
								'woocommerce_review_rating_verification_required',
								'woocommerce_enable_review_rating',
								'woocommerce_review_rating_required',
							),
						),
						array(
							'id'       => 'stock-management',
							'label'    => __( 'Stock management', 'woocommerce' ),
							'layout'   => array(
								'type'          => 'card',
								'isCollapsible' => false,
							),
							'children' => array(
								'woocommerce_manage_stock',
								'woocommerce_hold_stock_minutes',
							),
						),
						array(
							'id'       => 'stock-notifications',
							'label'    => __( 'Stock notifications', 'woocommerce' ),
							'layout'   => array(
								'type'          => 'card',
								'isCollapsible' => false,
							),
							'children' => array(
								'woocommerce_notify_low_stock',
								'woocommerce_notify_no_stock',
								'woocommerce_notify_backorder',
								'woocommerce_stock_email_recipient',
								'woocommerce_notify_low_stock_amount',
								'woocommerce_notify_no_stock_amount',
							),
						),
						array(
							'id'       => 'stock-display',
							'label'    => __( 'Stock display', 'woocommerce' ),
							'layout'   => array(
								'type'          => 'card',
								'isCollapsible' => false,
							),
							'children' => array(
								'woocommerce_hide_out_of_stock_items',
								'woocommerce_stock_format',
							),
						),
					),
				),
			),
			1
		);
	}
}
