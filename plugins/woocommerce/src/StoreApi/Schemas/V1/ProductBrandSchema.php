<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;


/**
 * ProductBrandSchema class.
 */
class ProductBrandSchema extends TermSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'product-brand';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'product-brand';

	/**
	 * Image attachment schema instance.
	 *
	 * @var ImageAttachmentSchema
	 */
	protected $image_attachment_schema;

	/**
	 * Constructor.
	 *
	 * @param ExtendSchema     $extend Rest Extending instance.
	 * @param SchemaController $controller Schema Controller instance.
	 */
	public function __construct( ExtendSchema $extend, SchemaController $controller ) {
		parent::__construct( $extend, $controller );
		$this->image_attachment_schema = $this->controller->get( ImageAttachmentSchema::IDENTIFIER );
	}

	/**
	 * Term properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		$schema                 = parent::get_properties();
		$schema['image']        = [
			'description' => __( 'Brand image.', 'woocommerce' ),
			'type'        => 'object',
			'context'     => [ 'view', 'edit', 'embed' ],
			'readonly'    => true,
			'properties'  => $this->image_attachment_schema->get_properties(),
		];
		$schema['review_count'] = [
			'description' => __( 'Number of reviews for products of this brand.', 'woocommerce' ),
			'type'        => 'integer',
			'context'     => [ 'view', 'edit' ],
			'readonly'    => true,
		];
		$schema['permalink']    = [
			'description' => __( 'Brand URL.', 'woocommerce' ),
			'type'        => 'string',
			'format'      => 'uri',
			'context'     => [ 'view', 'edit', 'embed' ],
			'readonly'    => true,
		];
		return $schema;
	}

	/**
	 * Convert a term object into an object suitable for the response.
	 *
	 * @param \WP_Term $term Term object.
	 * @return array
	 */
	public function get_item_response( $term ) {
		$response = parent::get_item_response( $term );
		$count    = get_term_meta( $term->term_id, 'product_count_product_brand', true );

		if ( $count ) {
			$response['count'] = (int) $count;
		}

		$response['image']        = $this->image_attachment_schema->get_item_response( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
		$response['review_count'] = $this->get_brand_review_count( $term );
		$response['permalink']    = get_term_link( $term->term_id, 'product_brand' );

		return $response;
	}

	/**
	 * Get total number of reviews for products of a brand.
	 *
	 * @param \WP_Term $term Term object.
	 * @return int
	 */
	protected function get_brand_review_count( $term ) {
		global $wpdb;

		$children = get_term_children( $term->term_id, 'product_brand' );

		if ( ! $children || is_wp_error( $children ) ) {
			$terms_to_count_str = absint( $term->term_id );
		} else {
			$terms_to_count     = array_unique( array_map( 'absint', array_merge( array( $term->term_id ), $children ) ) );
			$terms_to_count_str = implode( ',', $terms_to_count );
		}

		$products_of_brand_sql = "
			SELECT SUM(comment_count) as review_count
			FROM {$wpdb->posts} AS posts
			INNER JOIN {$wpdb->term_relationships} AS term_relationships ON posts.ID = term_relationships.object_id
			WHERE term_relationships.term_taxonomy_id IN (" . esc_sql( $terms_to_count_str ) . ')
		';

		$review_count = $wpdb->get_var( $products_of_brand_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return (int) $review_count;
	}
}
