<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Schemas;

use Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi;


/**
 * ProductCategorySchema class.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 */
class ProductCategorySchema extends TermSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'product-category';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'product-category';

	/**
	 * Image attachment schema instance.
	 *
	 * @var ImageAttachmentSchema
	 */
	protected $image_attachment_schema;

	/**
	 * Constructor.
	 *
	 * @param ExtendRestApi         $extend Rest Extending instance.
	 * @param ImageAttachmentSchema $image_attachment_schema Image attachment schema instance.
	 */
	public function __construct( ExtendRestApi $extend, ImageAttachmentSchema $image_attachment_schema ) {
		$this->image_attachment_schema = $image_attachment_schema;
		parent::__construct( $extend );
	}

	/**
	 * Term properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		$schema                 = parent::get_properties();
		$schema['image']        = [
			'description' => __( 'Category image.', 'woocommerce' ),
			'type'        => 'object',
			'context'     => [ 'view', 'edit', 'embed' ],
			'readonly'    => true,
			'properties'  => $this->image_attachment_schema->get_properties(),
		];
		$schema['review_count'] = [
			'description' => __( 'Number of reviews for products in this category.', 'woocommerce' ),
			'type'        => 'integer',
			'context'     => [ 'view', 'edit' ],
			'readonly'    => true,
		];
		$schema['permalink']    = [
			'description' => __( 'Category URL.', 'woocommerce' ),
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
		$count    = get_term_meta( $term->term_id, 'product_count_product_cat', true );

		if ( $count ) {
			$response['count'] = (int) $count;
		}

		$response['image']        = $this->image_attachment_schema->get_item_response( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
		$response['review_count'] = $this->get_category_review_count( $term );
		$response['permalink']    = get_term_link( $term->term_id, 'product_cat' );

		return $response;
	}

	/**
	 * Get total number of reviews for products in a category.
	 *
	 * @param \WP_Term $term Term object.
	 * @return int
	 */
	protected function get_category_review_count( $term ) {
		global $wpdb;

		$children = get_term_children( $term->term_id, 'product_cat' );

		if ( ! $children || is_wp_error( $children ) ) {
			$terms_to_count_str = absint( $term->term_id );
		} else {
			$terms_to_count     = array_unique( array_map( 'absint', array_merge( array( $term->term_id ), $children ) ) );
			$terms_to_count_str = implode( ',', $terms_to_count );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$products_of_category_sql = $wpdb->prepare(
			"SELECT SUM(comment_count) as review_count
			FROM {$wpdb->posts} AS posts
			INNER JOIN {$wpdb->term_relationships} AS term_relationships ON posts.ID = term_relationships.object_id
			WHERE term_relationships.term_taxonomy_id IN ({$terms_to_count_str})"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$review_count = $wpdb->get_var( $products_of_category_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return (int) $review_count;
	}
}
