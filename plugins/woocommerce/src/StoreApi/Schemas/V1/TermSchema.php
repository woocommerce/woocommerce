<?php
namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

use Automattic\WooCommerce\Internal\ProductAttributes\VisualAttributeTermMeta;

/**
 * TermSchema class.
 */
class TermSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'term';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'term';

	/**
	 * Term properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'id'                   => array(
				'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'name'                 => array(
				'description' => __( 'Term name.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'slug'                 => array(
				'description' => __( 'String based identifier for the term.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'description'          => array(
				'description' => __( 'Term description.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'parent'               => array(
				'description' => __( 'Parent term ID, if applicable.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'count'                => array(
				'description' => __( 'Number of objects (posts of any type) assigned to the term.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'__experimentalVisual' => array(
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
			),
		];
	}

	/**
	 * Convert a term object into an object suitable for the response.
	 *
	 * @param \WP_Term $term Term object.
	 * @return array
	 */
	public function get_item_response( $term ) {
		$response = [
			'id'          => (int) $term->term_id,
			'name'        => $this->prepare_html_response( $term->name ),
			'slug'        => $term->slug,
			'description' => $this->prepare_html_response( $term->description ),
			'parent'      => (int) $term->parent,
			'count'       => (int) $term->count,
		];

		if ( VisualAttributeTermMeta::is_visual_attribute_taxonomy( $term->taxonomy ) ) {
			$response['__experimentalVisual'] = VisualAttributeTermMeta::get_term_visual( (int) $term->term_id );
		}

		return $response;
	}
}
