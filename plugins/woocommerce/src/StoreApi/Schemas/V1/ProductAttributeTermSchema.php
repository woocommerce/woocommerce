<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

/**
 * ProductAttributeTermSchema class.
 */
class ProductAttributeTermSchema extends TermSchema {
	/**
	 * Preloaded visual data keyed by term ID.
	 *
	 * @var array<int, array{type: string, value: string}>
	 */
	private $preloaded_visual_data = array();

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
		return ProductAttributeTermVisualSchema::add_visual_data( parent::get_item_response( $term ), $term, $this->preloaded_visual_data );
	}

	/**
	 * Use preloaded visual data for subsequent term responses.
	 *
	 * @internal
	 *
	 * @param array $visual_data Visual data keyed by term ID.
	 */
	public function set_preloaded_visual_data( array $visual_data ): void {
		$this->preloaded_visual_data = $visual_data;
	}

	/**
	 * Clear preloaded visual data.
	 *
	 * @internal
	 */
	public function clear_preloaded_visual_data(): void {
		$this->preloaded_visual_data = array();
	}
}
