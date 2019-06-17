<?php
/**
 * Convert a review to the schema format.
 *
 * @package WooCommerce/RestApi
 */

namespace WooCommerce\RestApi\Controllers\Version4\Responses;

defined( 'ABSPATH' ) || exit;

/**
 * ProductReviewResponse class.
 */
class ProductReviewResponse extends AbstractObjectResponse {

	/**
	 * Convert object to match data in the schema.
	 *
	 * @param \WP_Comment $object Comment data.
	 * @param string      $context Request context. Options: 'view' and 'edit'.
	 * @return array
	 */
	public function prepare_response( $object, $context ) {
		$data = array(
			'id'                   => (int) $object->comment_ID,
			'date_created'         => wc_rest_prepare_date_response( $object->comment_date ),
			'date_created_gmt'     => wc_rest_prepare_date_response( $object->comment_date_gmt ),
			'product_id'           => (int) $object->comment_post_ID,
			'status'               => $this->prepare_status_response( (string) $object->comment_approved ),
			'reviewer'             => $object->comment_author,
			'reviewer_email'       => $object->comment_author_email,
			'review'               => $object->comment_content,
			'rating'               => (int) get_comment_meta( $object->comment_ID, 'rating', true ),
			'verified'             => wc_review_is_from_verified_owner( $object->comment_ID ),
			'reviewer_avatar_urls' => rest_get_avatar_urls( $object->comment_author_email ),
		);

		if ( 'view' === $context ) {
			$data['review'] = wpautop( $data['review'] );
		}

		return $data;
	}

	/**
	 * Checks comment_approved to set comment status for single comment output.
	 *
	 * @param string|int $comment_approved comment status.
	 * @return string Comment status.
	 */
	protected function prepare_status_response( $comment_approved ) {
		switch ( $comment_approved ) {
			case 'hold':
			case '0':
				$status = 'hold';
				break;
			case 'approve':
			case '1':
				$status = 'approved';
				break;
			case 'spam':
			case 'trash':
			default:
				$status = $comment_approved;
				break;
		}

		return $status;
	}
}
