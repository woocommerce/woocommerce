<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

/**
 * ProductAttributeTermSchema class.
 */
class ProductAttributeTermSchema extends TermSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'product-attribute-term';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'product-attribute-term';

	/**
	 * Term properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		$schema = parent::get_properties();

		$schema[ ProductAttributeTermVisualSchema::PROPERTY_NAME ] = ProductAttributeTermVisualSchema::get_property_schema();

		return $schema;
	}

	/**
	 * Convert a product attribute term object into an object suitable for the response.
	 *
	 * @param \WP_Term $term Term object.
	 * @return array
	 */
	public function get_item_response( $term ) {
		return ProductAttributeTermVisualSchema::add_visual_data( parent::get_item_response( $term ), $term );
	}
}
