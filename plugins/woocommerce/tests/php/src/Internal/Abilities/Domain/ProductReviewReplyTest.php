<?php
/**
 * Product review reply tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\Domain\ProductReviewReply;

/**
 * Tests review replies through the Abilities API.
 */
class ProductReviewReplyTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var \WP_Ability
	 */
	private $sut;

	/**
	 * Product the reviews belong to.
	 *
	 * @var int
	 */
	private $product_id;

	/**
	 * Set up a shop manager and a product.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->sut        = new \WP_Ability( ProductReviewReply::get_name(), ProductReviewReply::get_registration_args() );
		$this->product_id = \WC_Helper_Product::create_simple_product()->get_id();
	}

	/**
	 * Create a review.
	 *
	 * @param string $approved comment_approved value.
	 * @return int
	 */
	private function review( string $approved = '1' ): int {
		return self::factory()->comment->create(
			array(
				'comment_post_ID'      => $this->product_id,
				'comment_type'         => 'review',
				'comment_approved'     => $approved,
				'comment_content'      => 'Lovely item',
				'comment_author'       => 'Private Reviewer',
				'comment_author_email' => 'private@example.test',
			)
		);
	}

	/**
	 * @testdox Should publish the reply under the review as the current user, without unsafe HTML.
	 */
	public function test_replies_as_the_current_user(): void {
		$review = $this->review();

		$result = $this->sut->execute(
			array(
				'id'      => $review,
				'content' => 'Thanks for the kind words!<script>alert(1)</script>',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( $review, $result['review_id'] );
		$this->assertSame( 'approved', $result['status'], 'A shop manager can moderate comments, so the reply is approved.' );
		$this->assertSame( 'Thanks for the kind words!', $result['reply'] );
		$reply = get_comment( $result['id'] );
		$this->assertSame( (string) $review, $reply->comment_parent );
		$this->assertSame( 'comment', $reply->comment_type );
		$this->assertSame( get_current_user_id(), (int) $reply->user_id );
		$this->assertStringNotContainsString( '<script', $reply->comment_content );
		$this->assertStringNotContainsString( 'Private Reviewer', wp_json_encode( $result ) );
	}

	/**
	 * @testdox Should refuse replies to held reviews, to replies, and text that is only unsafe HTML.
	 */
	public function test_refused_replies(): void {
		$cases = array(
			'woocommerce_ability_product_review_not_approved' => array( $this->review( '0' ), 'Thanks' ),
			'woocommerce_ability_product_review_not_found' => array(
				self::factory()->comment->create(
					array(
						'comment_post_ID' => $this->product_id,
						'comment_parent'  => $this->review(),
					)
				),
				'Thanks',
			),
			'woocommerce_ability_product_review_reply_empty' => array( $this->review(), '<script>alert(1)</script>' ),
		);

		foreach ( $cases as $code => list( $id, $content ) ) {
			$result = $this->sut->execute(
				array(
					'id'      => $id,
					'content' => $content,
				)
			);

			$this->assertWPError( $result );
			$this->assertSame( $code, $result->get_error_code() );
		}
	}

	/**
	 * @testdox Should pass on WordPress's duplicate check with its status.
	 */
	public function test_duplicate_reply(): void {
		$input = array(
			'id'      => $this->review(),
			'content' => 'Thanks!',
		);

		$this->sut->execute( $input );
		$result = $this->sut->execute( $input );

		$this->assertSame( 'woocommerce_ability_product_review_reply_failed', $result->get_error_code() );
		$this->assertSame( 409, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Should require edit_products and the right to edit the product.
	 */
	public function test_permissions(): void {
		$input = array(
			'id'      => $this->review(),
			'content' => 'Thanks!',
		);

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertSame( 'ability_invalid_permissions', $this->sut->execute( $input )->get_error_code(), 'moderate_comments alone is not enough.' );

		$user = new \WP_User( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$user->add_cap( 'edit_products' );
		wp_set_current_user( $user->ID );
		$this->assertSame( 'ability_invalid_permissions', $this->sut->execute( $input )->get_error_code(), 'Editing a published product of another user takes more than edit_products.' );
	}
}
