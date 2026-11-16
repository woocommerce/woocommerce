<?php
/**
 * Product review update ability.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductReviewAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Moderates a product review or a direct reply to one. Asking for the status it already has changes nothing.
 */
class ProductReviewUpdate extends AbstractDomainAbility implements AbilityDefinition {

	use ProductReviewAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @since 11.4.0
	 * @return string
	 */
	public static function get_name(): string {
		return 'woocommerce/product-review-update';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Update product review', 'woocommerce' ),
			'description'         => __( 'Moderate a product review, or a reply to one: approve it, hold it for moderation (shown as Pending in wp-admin), mark it as spam or move it to the trash. Moderate for spam, abuse or off-topic content, never to hide negative reviews. Requesting the status a review already has changes nothing and returns changed false. Reviewer identity is withheld.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_output_schema(),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_edit_review' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				// Not idempotent: the Abilities API run endpoint demands DELETE for destructive idempotent abilities.
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => false,
					'destructive' => true,
				),
			),
		);
	}

	/**
	 * Check review edit access. A reply needs edit access to its review and the right to edit the reply itself.
	 * An ID that is neither passes for users who could edit any review, so they get the not-found error.
	 *
	 * @since 11.4.0
	 * @param mixed $input Ability input.
	 * @return bool
	 */
	public static function can_edit_review( $input = array() ): bool {
		$id = self::get_id_from_input( $input );
		if ( $id <= 0 ) {
			return false;
		}

		$reply = self::get_review_reply( $id );
		if ( null === $reply ) {
			return self::check_review_permission( 'edit', $id );
		}

		return self::check_review_permission( 'edit', (int) $reply->comment_parent ) && current_user_can( 'edit_comment', $id );
	}

	/**
	 * Change the moderation status of a review.
	 *
	 * @since 11.4.0
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public static function execute( array $input ) {
		$reply  = self::get_review_reply( $input['id'] );
		$review = $reply ?? self::get_review_from_input( $input );
		if ( is_wp_error( $review ) ) {
			return $review;
		}

		$previous = self::get_review_status( $review );
		$status   = $input['status'];

		if ( ! array_key_exists( $previous, self::get_review_statuses() ) ) {
			return new \WP_Error(
				'woocommerce_ability_product_review_status_locked',
				'post-trashed' === $previous
					? __( 'The product of this review is in the trash. Restore the product to moderate its reviews.', 'woocommerce' )
					/* translators: %s: stored comment status. */
					: sprintf( __( 'The review has the status "%s", which this ability cannot change.', 'woocommerce' ), $previous ),
				array( 'status' => 409 )
			);
		}

		$changed = $previous !== $status;
		if ( $changed ) {
			$error = self::change_status( $review, $status );
			if ( null !== $error ) {
				return $error;
			}
		}

		$saved = get_comment( (int) $review->comment_ID );
		if ( ! $saved instanceof \WP_Comment ) {
			return new \WP_Error(
				'woocommerce_ability_product_review_status_failed',
				$changed
					? __( 'The review status was changed, but the review could not be read back.', 'woocommerce' )
					: __( 'The review could not be read back.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		if ( null !== $reply ) {
			return array(
				'changed'         => $changed,
				'previous_status' => $previous,
				'reply'           => self::format_reply( $saved ),
			);
		}

		$formatted = self::format_review( $saved, true, true );
		if ( is_wp_error( $formatted ) ) {
			return $changed ? ExtensibleAbilities::saved_write_error( $formatted, 'product_review', (int) $saved->comment_ID ) : $formatted;
		}

		return array(
			'changed'         => $changed,
			'previous_status' => $previous,
			'review'          => $formatted,
		);
	}

	/**
	 * Move a review to a new status. A review leaves the trash or spam through WordPress's untrash and unspam
	 * first, so the trash metadata is cleared and spam filters such as Akismet learn from the correction.
	 *
	 * @param \WP_Comment $review Review.
	 * @param string      $status Target status.
	 * @return \WP_Error|null
	 */
	private static function change_status( \WP_Comment $review, string $status ): ?\WP_Error {
		$review_id = (int) $review->comment_ID;

		if ( 'trash' === $status ) {
			/**
			 * Filters whether a review can go to the trash. When it cannot, the REST API deletes it permanently instead, which this ability never does.
			 *
			 * @since 3.5.0
			 *
			 * @param bool        $supports_trash Whether reviews can be trashed.
			 * @param \WP_Comment $review         Review, or reply to a review, to trash.
			 */
			$trashable = (bool) apply_filters( 'woocommerce_rest_product_review_trashable', ( EMPTY_TRASH_DAYS > 0 ), $review );

			// wp_trash_comment() deletes permanently when trash is off, whatever the filter says.
			if ( ! $trashable || EMPTY_TRASH_DAYS <= 0 ) {
				return new \WP_Error(
					'woocommerce_ability_product_review_trash_not_supported',
					__( 'This site deletes trashed comments immediately, so the review cannot be moved to the trash.', 'woocommerce' ),
					array( 'status' => 501 )
				);
			}
		}

		$ok       = true;
		$previous = self::get_review_status( $review );
		$current  = $previous;
		if ( 'trash' === $current ) {
			$ok      = wp_untrash_comment( $review_id );
			$current = self::get_current_status( $review_id, $current );
		}
		// Trashing a spam review keeps it spam on untrash; only a move to another status unspams it.
		if ( $ok && 'spam' === $current && ! in_array( $status, array( 'spam', 'trash' ), true ) ) {
			$ok      = wp_unspam_comment( $review_id );
			$current = self::get_current_status( $review_id, $current );
		}

		if ( $ok && $current !== $status ) {
			switch ( $status ) {
				case 'spam':
					$ok = wp_spam_comment( $review_id );
					break;
				case 'trash':
					$ok = wp_trash_comment( $review_id );
					break;
				default:
					$ok = wp_set_comment_status( $review_id, 'approved' === $status ? 'approve' : 'hold', true );
			}
		}

		if ( true === $ok ) {
			return null;
		}

		$data = array( 'status' => 500 );
		// Leaving trash or spam can succeed before the final move fails; retrying the same request finishes the move.
		if ( $current !== $previous ) {
			$data['current_status'] = $current;
		}

		return new \WP_Error(
			'woocommerce_ability_product_review_status_failed',
			is_wp_error( $ok ) ? $ok->get_error_message() : __( 'Failed to update the review status.', 'woocommerce' ),
			$data
		);
	}

	/**
	 * Re-read the stored status after WordPress changed it.
	 *
	 * @param int    $review_id Review ID.
	 * @param string $fallback  Status to report when the review cannot be read.
	 * @return string
	 */
	private static function get_current_status( int $review_id, string $fallback ): string {
		$review = get_comment( $review_id );

		return $review instanceof \WP_Comment ? self::get_review_status( $review ) : $fallback;
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
				'id'     => array(
					'type'        => 'integer',
					'description' => __( 'Review ID from the woocommerce/product-reviews-query ability, or the ID of a reply to a review.', 'woocommerce' ),
					'minimum'     => 1,
				),
				'status' => array(
					'type'        => 'string',
					'description' => __( 'New moderation status. "hold" puts the review back in the moderation queue.', 'woocommerce' ),
					'enum'        => array_keys( self::get_review_statuses() ),
				),
			),
			'required'             => array( 'id', 'status' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the output schema.
	 *
	 * @return array
	 */
	private static function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'changed'         => array( 'type' => 'boolean' ),
				'previous_status' => array( 'type' => 'string' ),
				'review'          => ExtensibleAbilities::add_fields_schema( self::get_review_output_schema(), 'product_review' ),
				'reply'           => self::get_reply_output_schema(),
			),
		);
	}
}
