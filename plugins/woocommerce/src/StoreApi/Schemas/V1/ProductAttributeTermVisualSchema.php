<?php
/**
 * Product attribute term visual schema helpers.
 *
 * @package WooCommerce\StoreApi
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

use Automattic\WooCommerce\Internal\ProductAttributes\VisualAttributeTermMeta;

/**
 * Product attribute term visual response helpers.
 */
final class ProductAttributeTermVisualSchema {

	/**
	 * Response property name for experimental visual data.
	 */
	public const PROPERTY_NAME = '__experimentalVisual';

	/**
	 * Get the visual data property schema.
	 *
	 * @param array $context Schema contexts.
	 * @return array
	 *
	 * @since 10.9.0
	 */
	public static function get_property_schema( array $context = array( 'view', 'edit' ) ): array {
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
	 * Add visual data to a term response.
	 *
	 * @param array    $response Term response.
	 * @param \WP_Term $term Term object.
	 * @param array    $visuals_by_term_id Preloaded visual data keyed by term ID.
	 * @return array
	 *
	 * @since 10.9.0
	 */
	public static function add_visual_data( array $response, \WP_Term $term, array $visuals_by_term_id = array() ): array {
		if ( VisualAttributeTermMeta::is_visual_attribute_taxonomy( $term->taxonomy ) ) {
			$term_id                         = (int) $term->term_id;
			$response[ self::PROPERTY_NAME ] = $visuals_by_term_id[ $term_id ] ?? VisualAttributeTermMeta::get_term_visual( $term_id );
		}

		return $response;
	}
}
