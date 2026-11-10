<?php
/**
 * Product review ability helpers.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Enums\ProductReviewStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Loads, checks and formats product reviews for the review abilities. Reviewer identity never reaches the output.
 */
trait ProductReviewAbilityTrait {

	/**
	 * Map each moderation status to the `comment_approved` value WordPress stores for it.
	 *
	 * @return array<string, string>
	 */
	protected static function get_review_statuses(): array {
		return array(
			ProductReviewStatus::APPROVED => '1',
			ProductReviewStatus::HOLD     => '0',
			ProductReviewStatus::SPAM     => 'spam',
			ProductReviewStatus::TRASH    => 'trash',
		);
	}

	/**
	 * Get the moderation status of a review. Values outside the vocabulary, such as `post-trashed`, are returned as stored.
	 *
	 * @param \WP_Comment $review Review.
	 * @return string
	 */
	protected static function get_review_status( \WP_Comment $review ): string {
		$status = array_search( (string) $review->comment_approved, self::get_review_statuses(), true );

		return false === $status ? (string) $review->comment_approved : $status;
	}

	/**
	 * Load a review. Legacy plain comments on a product count only when they carry a rating; replies never count.
	 *
	 * @param int $review_id Comment ID.
	 * @return \WP_Comment|null
	 */
	protected static function get_review( int $review_id ): ?\WP_Comment {
		$comment = get_comment( $review_id );

		if ( ! $comment instanceof \WP_Comment || 0 !== (int) $comment->comment_parent || 'product' !== get_post_type( (int) $comment->comment_post_ID ) ) {
			return null;
		}

		$type = get_comment_type( $comment );
		if ( 'review' === $type || ( 'comment' === $type && (int) get_comment_meta( (int) $comment->comment_ID, 'rating', true ) > 0 ) ) {
			return $comment;
		}

		return null;
	}

	/**
	 * Load the review that the input `id` names.
	 *
	 * @param array $input Ability input.
	 * @return \WP_Comment|\WP_Error
	 */
	protected static function get_review_from_input( array $input ) {
		$review = self::get_review( $input['id'] );

		if ( null === $review ) {
			return new \WP_Error(
				'woocommerce_ability_product_review_not_found',
				/* translators: %d: review ID. */
				sprintf( __( 'Product review %d does not exist. Use the woocommerce/product-reviews-query ability to find the review ID.', 'woocommerce' ), $input['id'] ),
				array( 'status' => 404 )
			);
		}

		return $review;
	}

	/**
	 * Check review access as the REST API does, including its permission filter. The review type is checked on load, so legacy reviews pass.
	 *
	 * @param string $context   Permission context, such as `edit` or `create`.
	 * @param int    $review_id Review ID, or zero when no review is involved yet.
	 * @return bool
	 */
	protected static function check_review_permission( string $context, int $review_id ): bool {
		/**
		 * Filters whether the current user can act on a product review. Fired with the same arguments as the REST API's review checks.
		 *
		 * @since 2.6.0
		 *
		 * @param bool   $permission  Whether the user has permission.
		 * @param string $context     Operation: 'edit' or 'create'.
		 * @param int    $object_id   Review ID.
		 * @param string $object_type Always 'product_review'.
		 */
		return true === apply_filters( 'woocommerce_rest_check_permissions', current_user_can( 'edit_products' ), $context, $review_id, 'product_review' );
	}

	/**
	 * Schema of a review as the review abilities output it.
	 *
	 * @return array
	 */
	protected static function get_review_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'           => array( 'type' => 'integer' ),
				'product_id'   => array( 'type' => 'integer' ),
				'product_name' => array( 'type' => 'string' ),
				'date_created' => array(
					'type'   => 'string',
					'format' => 'date-time',
				),
				'rating'       => array( 'type' => 'integer' ),
				'verified'     => array( 'type' => 'boolean' ),
				'status'       => array( 'type' => 'string' ),
				'review'       => array( 'type' => 'string' ),
			),
		);
	}

	/**
	 * Format a review without exposing reviewer identity.
	 *
	 * @param \WP_Comment $comment      Review.
	 * @param bool        $with_product Whether to include the product ID and name.
	 * @param bool        $with_status  Whether to include the moderation status.
	 * @return array|\WP_Error
	 */
	protected static function format_review( \WP_Comment $comment, bool $with_product, bool $with_status ) {
		$review       = array(
			'id'       => (int) $comment->comment_ID,
			'rating'   => (int) get_comment_meta( (int) $comment->comment_ID, 'rating', true ),
			'verified' => (bool) get_comment_meta( (int) $comment->comment_ID, 'verified', true ),
			'review'   => wp_strip_all_tags( (string) $comment->comment_content ),
		);
		$date_created = self::format_comment_date( $comment->comment_date_gmt );
		if ( null !== $date_created ) {
			$review['date_created'] = $date_created;
		}
		if ( $with_product ) {
			$review['product_id']   = (int) $comment->comment_post_ID;
			$review['product_name'] = (string) get_post_field( 'post_title', (int) $comment->comment_post_ID, 'raw' );
		}
		if ( $with_status ) {
			$review['status'] = self::get_review_status( $comment );
		}
		return ExtensibleAbilities::add_fields_to_object( $review, 'product_review', $comment );
	}

	/**
	 * Render a GMT comment date as UTC, or null when there is no usable date.
	 *
	 * Imported and legacy comments can carry '0000-00-00 00:00:00', which
	 * strtotime() rolls into year -1 rather than rejecting.
	 *
	 * @param string|null $date_gmt comment_date_gmt as stored.
	 * @return string|null
	 */
	protected static function format_comment_date( ?string $date_gmt ): ?string {
		$date_gmt  = trim( (string) $date_gmt );
		$timestamp = '' === $date_gmt ? false : strtotime( $date_gmt . ' UTC' );

		return $timestamp > 0 ? gmdate( 'Y-m-d\TH:i:s\Z', $timestamp ) : null;
	}
}
