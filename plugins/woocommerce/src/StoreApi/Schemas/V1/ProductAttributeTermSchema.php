<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

use Automattic\WooCommerce\Internal\ProductAttributes\VisualAttributeTermMeta;

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

		$schema['__experimentalVisual'] = $this->get_visual_property_schema();

		return $schema;
	}

	/**
	 * Get the visual data property schema.
	 *
	 * @param array $context Schema contexts.
	 * @return array
	 *
	 * @since 10.9.0
	 */
	public function get_visual_property_schema( array $context = array( 'view', 'edit' ) ): array {
		return array(
			'description' => __( 'Experimental visual swatch data for wc-visual attribute terms.', 'woocommerce' ),
			'type'        => 'object',
			'context'     => $context,
			'readonly'    => true,
			'properties'  => array(
				'type'  => array(
					'type' => 'string',
					'enum' => array( VisualAttributeTermMeta::TYPE_COLOR, VisualAttributeTermMeta::TYPE_IMAGE, VisualAttributeTermMeta::TYPE_NONE ),
				),
				'value' => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Convert a product attribute term object into an object suitable for the response.
	 *
	 * @param \WP_Term $term Term object.
	 * @return array
	 */
	public function get_item_response( $term ) {
		return $this->add_visual_data( parent::get_item_response( $term ), $term );
	}

	/**
	 * Add visual data to a term response.
	 *
	 * @param array    $response Term response.
	 * @param \WP_Term $term Term object.
	 * @return array
	 *
	 * @since 10.9.0
	 */
	public function add_visual_data( array $response, \WP_Term $term ): array {
		if ( VisualAttributeTermMeta::is_visual_attribute_taxonomy( $term->taxonomy ) ) {
			$response['__experimentalVisual'] = VisualAttributeTermMeta::get_term_visual( (int) $term->term_id );
		}

		return $response;
	}
}
