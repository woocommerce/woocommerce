<?php
/**
 * Product review reply ability.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductReviewAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Replies to an approved product review as the current user. Unlike wp-admin's "Approve and reply", a held review is refused, so approving stays a separate step.
 */
class ProductReviewReply extends AbstractDomainAbility implements AbilityDefinition {

	use ProductReviewAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @since 11.4.0
	 * @return string
	 */
	public static function get_name(): string {
		return 'woocommerce/product-review-reply';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Reply to product review', 'woocommerce' ),
			'description'         => __( 'Publish a reply to an approved product review, as the current user and under their display name. Approve a held review with woocommerce/product-review-update first. Check the review\'s store_reply_count first, so the store does not answer it twice. A reply can be held or trashed with woocommerce/product-review-update. Replies to replies are not supported.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_reply_output_schema(),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_reply_to_review' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => false,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Check reply access: the REST API's create and edit checks, and the admin reply's edit_post check on the product.
	 *
	 * @since 11.4.0
	 * @param mixed $input Ability input.
	 * @return bool
	 */
	public static function can_reply_to_review( $input = array() ): bool {
		$review_id = self::get_id_from_input( $input );

		if ( $review_id <= 0 || ! self::check_review_permission( 'create', 0 ) || ! self::check_review_permission( 'edit', $review_id ) ) {
			return false;
		}

		$review = self::get_review( $review_id );

		return null === $review || current_user_can( 'edit_post', (int) $review->comment_post_ID );
	}

	/**
	 * Add the reply.
	 *
	 * @since 11.4.0
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public static function execute( array $input ) {
		$review = self::get_review_from_input( $input );
		if ( is_wp_error( $review ) ) {
			return $review;
		}

		if ( 'approved' !== self::get_review_status( $review ) ) {
			return new \WP_Error(
				'woocommerce_ability_product_review_not_approved',
				__( 'Only approved reviews can get a reply. Approve the review with the woocommerce/product-review-update ability first.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		$content = trim( wp_kses_post( $input['content'] ) );
		if ( '' === $content ) {
			return new \WP_Error(
				'woocommerce_ability_product_review_reply_empty',
				__( 'The reply has no text left after removing unsafe HTML.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$user     = wp_get_current_user();
		$reply_id = wp_new_comment(
			wp_slash(
				array(
					'comment_post_ID'      => (int) $review->comment_post_ID,
					'comment_parent'       => (int) $review->comment_ID,
					'comment_type'         => 'comment',
					'comment_content'      => $content,
					'comment_author'       => $user->display_name,
					'comment_author_email' => $user->user_email,
					'comment_author_url'   => $user->user_url,
					'user_id'              => $user->ID,
				)
			),
			true
		);

		if ( is_wp_error( $reply_id ) || ! $reply_id ) {
			$data = is_wp_error( $reply_id ) ? $reply_id->get_error_data() : null;

			return new \WP_Error(
				'woocommerce_ability_product_review_reply_failed',
				// WordPress escapes these messages for HTML; the agent reads plain text.
				is_wp_error( $reply_id ) ? html_entity_decode( $reply_id->get_error_message(), ENT_QUOTES, 'UTF-8' ) : __( 'Failed to add the reply.', 'woocommerce' ),
				array( 'status' => is_int( $data ) ? $data : 500 )
			);
		}

		$reply = get_comment( (int) $reply_id );
		// wp_new_comment() turns the reply into a top-level comment when the review left approved or held meanwhile.
		if ( $reply instanceof \WP_Comment && (int) $reply->comment_parent !== (int) $review->comment_ID ) {
			if ( ! wp_delete_comment( (int) $reply_id, true ) ) {
				return new \WP_Error(
					'woocommerce_ability_product_review_reply_failed',
					/* translators: %d: comment ID. */
					sprintf( __( 'The review was moderated while the reply was being added, and the reply was saved as a separate comment %d that could not be removed.', 'woocommerce' ), (int) $reply_id ),
					array(
						'status' => 500,
						'saved'  => true,
						'id'     => (int) $reply_id,
					)
				);
			}

			return new \WP_Error(
				'woocommerce_ability_product_review_not_approved',
				__( 'The review was moderated while the reply was being added, so no reply was published.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		if ( ! $reply instanceof \WP_Comment ) {
			return new \WP_Error(
				'woocommerce_ability_product_review_reply_failed',
				/* translators: %d: reply ID. */
				sprintf( __( 'Reply %d was added, but it could not be read back.', 'woocommerce' ), (int) $reply_id ),
				array(
					'status' => 500,
					'saved'  => true,
					'id'     => (int) $reply_id,
				)
			);
		}

		return self::format_reply( $reply );
	}

	/**
	 * Get the input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'      => array(
					'type'        => 'integer',
					'description' => __( 'ID of the review to reply to, from the woocommerce/product-reviews-query ability.', 'woocommerce' ),
					'minimum'     => 1,
				),
				'content' => array(
					'type'        => 'string',
					'description' => __( 'Reply text, shown publicly under the review. Plain text or safe HTML; Markdown is not rendered.', 'woocommerce' ),
					'minLength'   => 1,
					'pattern'     => '\S',
				),
			),
			'required'             => array( 'id', 'content' ),
			'additionalProperties' => false,
		);
	}
}
