<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CostOfGoodsSold;

/**
 * Trait with Cost of Goods Sold related functionality shared by the REST products and variations controllers.
 */
trait CogsAwareRestControllerTrait {

	use CogsAwareTrait;

	/**
	 * Add Cost of Goods Sold related information for a given product to the array of data that will become the REST response.
	 *
	 * @param array      $data Array of response data.
	 * @param WC_Product $product Product to get the information from.
	 */
	private function add_cogs_info_to_returned_product_data( array &$data, $product ): void {
		if ( ! $this->cogs_is_enabled() ) {
			return;
		}

		$data['cost_of_goods_sold'] = array(
			'values'      => array(
				array(
					'defined_value'   => $product->get_cogs_value(),
					'effective_value' => $product->get_cogs_effective_value(),
				),
			),
			'total_value' => $product->get_cogs_total_value(),
		);

		if ( $product instanceof \WC_Product_Variation ) {
			$data['cost_of_goods_sold']['defined_value_is_additive'] = $product->get_cogs_value_is_additive();
		}
	}

	/**
	 * Apply Cost of Goods Sold related information received in the request body to a product object.
	 *
	 * @param WP_Rest_Request $request Request data.
	 * @param WC_Product      $product The product to apply the data to.
	 */
	private function set_cogs_info_in_product_object( $request, $product ): void {
		$values = $request['cost_of_goods_sold']['values'] ?? null;
		if ( ! is_null( $values ) ) {
			$value = 0;
			foreach ( $values as $value_info ) {
				$value += (float) ( $value_info['defined_value'] ?? 0 );
			}

			$product->set_cogs_value( $value );
		}

		if ( $product instanceof \WC_Product_Variation ) {
			$is_additive = $request['cost_of_goods_sold']['defined_value_is_additive'] ?? null;
			if ( ! is_null( $is_additive ) ) {
				$product->set_cogs_value_is_additive( $is_additive );
			}
		}
	}

	/**
	 * Add Cost of Goods Sold related schema information to a given REST endpoint schema.
	 *
	 * @param array $schema The schema data set to add the information to.
	 * @param bool  $for_variations_controller True if the information is for an endpoint in the variations controller.
	 * @return array Updated schema information.
	 */
	private function add_cogs_related_product_schema( array $schema, bool $for_variations_controller ): array {
		$schema['properties']['cost_of_goods_sold'] = array(
			'description' => __( 'Cost of Goods Sold data.', 'woocommerce' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'values'                    => array(
					'description' => __( 'Cost of Goods Sold values for the product.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'defined_value'   => array(
								'description' => __( 'Defined cost value.', 'woocommerce' ),
								'type'        => 'number',
								'context'     => array( 'view', 'edit' ),
							),
							'effective_value' => array(
								'description' => __( 'Effective monetary cost value.', 'woocommerce' ),
								'type'        => 'number',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),

				),
				'defined_value_is_additive' => array(
					'description' => __( 'Applies to variations only. If true, the effective value is the base value from the parent product plus the defined value; if false, the defined value is the final effective value.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'total_value'               => array(
					'description' => __( 'Total monetary value of the Cost of Goods Sold for the product (sum of all the effective values).', 'woocommerce' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		if ( $for_variations_controller ) {
			$schema['properties']['cost_of_goods_sold']['properties']['defined_value_is_additive']['description'] =
				__( 'If true, the effective value is the base value from the parent product plus the defined value; if false, the defined value is the final effective value.', 'woocommerce' );
		}

		return $schema;
	}
}
