<?php
/**
 * Term Schema.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Schemas;

defined( 'ABSPATH' ) || exit;

/**
 * TermSchema class.
 *
 * @since 2.5.0
 */
class TermSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'term';

	/**
	 * Term properties.
	 *
	 * @return array
	 */
	protected function get_properties() {
		return [
			'id'          => array(
				'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'name'        => array(
				'description' => __( 'Term name.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'slug'        => array(
				'description' => __( 'String based identifier for the term.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'description' => array(
				'description' => __( 'Term description.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'count'       => array(
				'description' => __( 'Number of objects (posts of any type) assigned to the term.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
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
		return [
			'id'          => (int) $term->term_id,
			'name'        => $this->prepare_html_response( $term->name ),
			'description' => $this->prepare_html_response( $term->description ),
			'slug'        => $term->slug,
			'count'       => (int) $term->count,
		];
	}
}
