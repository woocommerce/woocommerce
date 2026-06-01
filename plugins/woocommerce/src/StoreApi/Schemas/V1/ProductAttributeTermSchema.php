<?php
declare( strict_types = 1 );

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
	 * Visual data property name.
	 *
	 * @var string
	 */
	const VISUAL_PROPERTY_NAME = '__experimentalVisual';

	/**
	 * Term properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		$schema = parent::get_properties();

		$schema[ self::VISUAL_PROPERTY_NAME ] = $this->get_visual_property_schema();

		return $schema;
	}

	/**
	 * Get the visual data property schema.
	 *
	 * @return array
	 */
	private function get_visual_property_schema(): array {
		return array(
			'description' => __( 'Experimental visual swatch data for wc-visual attribute terms.', 'woocommerce' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
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
}
