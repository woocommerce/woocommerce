<?php
/**
 * Product review update tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\Domain\ProductReviewUpdate;

/**
 * Tests review moderation through the Abilities API.
 */
class ProductReviewUpdateTest extends \WC_Unit_Test_Case {

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
	 * A shop manager that every test acts as.
	 *
	 * @var int
	 */
	private static $user_id;

	/**
	 * The product every test reviews; each test's changes to it roll back.
	 *
	 * @var int
	 */
	private static $shared_product_id;

	/**
	 * Create the fixtures that no test changes beyond its own transaction.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$user_id           = $factory->user->create( array( 'role' => 'shop_manager' ) );
		self::$shared_product_id = \WC_Helper_Product::create_simple_product()->get_id();
	}

	/**
	 * Delete the shared product, whose lookup rows the WordPress class cleanup leaves behind.
	 */
	public static function wpTearDownAfterClass(): void {
		wc_get_product( self::$shared_product_id )->delete( true );
	}

	/**
	 * Set up a shop manager and a product.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::$user_id );
		$this->sut        = new \WP_Ability( ProductReviewUpdate::get_name(), ProductReviewUpdate::get_registration_args() );
		$this->product_id = self::$shared_product_id;
	}

	/**
	 * Create a review in a given status, reaching spam and trash the way WordPress does.
	 *
	 * @param string $status Moderation status.
	 * @param array  $args   Comment overrides.
	 * @return int
	 */
	private function review( string $status, array $args = array() ): int {
		$id = self::factory()->comment->create(
			array_merge(
				array(
					'comment_post_ID'      => $this->product_id,
					'comment_type'         => 'review',
					'comment_approved'     => 'hold' === $status ? '0' : '1',
					'comment_content'      => 'Lovely item',
					'comment_author'       => 'Private Reviewer',
					'comment_author_email' => 'private@example.test',
				),
				$args
			)
		);
		update_comment_meta( $id, 'rating', 4 );
		if ( 'spam' === $status ) {
			wp_spam_comment( $id );
		} elseif ( 'trash' === $status ) {
			wp_trash_comment( $id );
		}
		return $id;
	}

	/**
	 * @testdox Should move a review from $from to $to and report the previous status.
	 *
	 * @testWith ["hold", "approved", "1"]
	 *           ["approved", "hold", "0"]
	 *           ["approved", "spam", "spam"]
	 *           ["spam", "approved", "1"]
	 *           ["approved", "trash", "trash"]
	 *           ["trash", "hold", "0"]
	 *           ["spam", "trash", "trash"]
	 *           ["trash", "spam", "spam"]
	 *
	 * @param string $from   Starting status.
	 * @param string $to     Requested status.
	 * @param string $stored Expected comment_approved value.
	 */
	public function test_moves_review_between_statuses( string $from, string $to, string $stored ): void {
		$id = $this->review( $from );

		$result = $this->sut->execute(
			array(
				'id'     => $id,
				'status' => $to,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['changed'] );
		$this->assertSame( $from, $result['previous_status'] );
		$this->assertSame( $to, $result['review']['status'] );
		$this->assertSame( $this->product_id, $result['review']['product_id'] );
		$this->assertSame( $stored, get_comment( $id )->comment_approved, 'The stored status should match the request.' );
	}

	/**
	 * @testdox Should leave trash and spam through untrash and unspam, so the trash metadata is cleared and spam filters learn.
	 */
	public function test_restores_through_untrash_and_unspam(): void {
		$trashed = $this->review( 'trash' );
		$spam    = $this->review( 'spam' );
		$unspams = did_action( 'unspammed_comment' );

		$this->sut->execute(
			array(
				'id'     => $trashed,
				'status' => 'approved',
			)
		);
		$this->sut->execute(
			array(
				'id'     => $spam,
				'status' => 'hold',
			)
		);

		$this->assertSame( '', get_comment_meta( $trashed, '_wp_trash_meta_status', true ), 'Untrash should clear the trash metadata.' );
		$this->assertSame( '0', get_comment( $spam )->comment_approved );
		$this->assertSame( $unspams + 1, did_action( 'unspammed_comment' ), 'Leaving spam should go through wp_unspam_comment().' );
	}

	/**
	 * @testdox Should keep a trashed spam review as spam on restore.
	 */
	public function test_trashing_spam_keeps_it_spam_on_restore(): void {
		$id = $this->review( 'spam' );

		$this->sut->execute(
			array(
				'id'     => $id,
				'status' => 'trash',
			)
		);

		$this->assertSame( 'spam', get_comment_meta( $id, '_wp_trash_meta_status', true ) );
		wp_untrash_comment( $id );
		$this->assertSame( 'spam', get_comment( $id )->comment_approved );
	}

	/**
	 * @testdox Should change nothing when the review already has the status $status.
	 *
	 * @testWith ["approved"]
	 *           ["hold"]
	 *           ["spam"]
	 *           ["trash"]
	 *
	 * @param string $status Status the review has and is asked for.
	 */
	public function test_same_status_is_a_no_op( string $status ): void {
		$id         = $this->review( $status );
		$trash_meta = get_comment_meta( $id, '_wp_trash_meta_status', true );
		$changes    = did_action( 'transition_comment_status' );
		$spam_calls = did_action( 'spam_comment' );

		$result = $this->sut->execute(
			array(
				'id'     => $id,
				'status' => $status,
			)
		);

		$this->assertIsArray( $result );
		$this->assertFalse( $result['changed'] );
		$this->assertSame( $status, $result['previous_status'] );
		$this->assertSame( $changes, did_action( 'transition_comment_status' ), 'No status transition should run.' );
		$this->assertSame( $spam_calls, did_action( 'spam_comment' ), 'A spam review should not be reported again.' );
		$this->assertSame( $trash_meta, get_comment_meta( $id, '_wp_trash_meta_status', true ), 'The restore status should be kept.' );
	}

	/**
	 * @testdox Should moderate legacy rated comments and refuse comments that are not reviews.
	 */
	public function test_review_scope(): void {
		$legacy = $this->review( 'hold', array( 'comment_type' => 'comment' ) );
		$plain  = self::factory()->comment->create(
			array(
				'comment_post_ID' => $this->product_id,
				'comment_type'    => 'comment',
			)
		);
		$reply  = self::factory()->comment->create(
			array(
				'comment_post_ID' => $this->product_id,
				'comment_parent'  => $this->review( 'approved' ),
			)
		);
		$post   = self::factory()->comment->create( array( 'comment_post_ID' => self::factory()->post->create() ) );

		$result = $this->sut->execute(
			array(
				'id'     => $legacy,
				'status' => 'approved',
			)
		);
		$this->assertTrue( $result['changed'], 'A legacy comment with a rating is a review.' );

		$elsewhere = self::factory()->comment->create(
			array(
				'comment_post_ID' => self::factory()->post->create(),
				'comment_parent'  => $this->review( 'approved' ),
			)
		);

		foreach ( array( $plain, $post, $elsewhere, 99999999 ) as $id ) {
			$result = $this->sut->execute(
				array(
					'id'     => $id,
					'status' => 'approved',
				)
			);
			$this->assertWPError( $result );
			$this->assertSame( 'woocommerce_ability_product_review_not_found', $result->get_error_code(), "Comment {$id} is not a review." );
		}
	}

	/**
	 * @testdox Should require the REST API's review edit permission and honour its filter.
	 */
	public function test_permissions(): void {
		$id    = $this->review( 'hold' );
		$input = array(
			'id'     => $id,
			'status' => 'approved',
		);

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertSame( 'ability_invalid_permissions', $this->sut->execute( $input )->get_error_code(), 'moderate_comments alone is not enough.' );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$seen = array();
		add_filter(
			'woocommerce_rest_check_permissions',
			function ( $permission, $context, $object_id, $object_type ) use ( &$seen ) {
				unset( $permission );
				$seen[] = array( $context, $object_id, $object_type );
				return false;
			},
			10,
			4
		);

		$this->assertSame( 'ability_invalid_permissions', $this->sut->execute( $input )->get_error_code() );
		$this->assertContains( array( 'edit', $id, 'product_review' ), $seen );
		$this->assertSame( '0', get_comment( $id )->comment_approved );
	}

	/**
	 * @testdox Should refuse to trash when the site deletes trashed reviews, and to moderate reviews of a trashed product.
	 */
	public function test_refused_changes(): void {
		$id = $this->review( 'approved' );
		add_filter( 'woocommerce_rest_product_review_trashable', '__return_false' );

		$result = $this->sut->execute(
			array(
				'id'     => $id,
				'status' => 'trash',
			)
		);

		$this->assertSame( 'woocommerce_ability_product_review_trash_not_supported', $result->get_error_code() );
		$this->assertNotNull( get_comment( $id ), 'The review should not be deleted.' );
		$this->assertSame( '1', get_comment( $id )->comment_approved );

		wp_trash_post( $this->product_id );
		$result = $this->sut->execute(
			array(
				'id'     => $id,
				'status' => 'hold',
			)
		);

		$this->assertSame( 'woocommerce_ability_product_review_status_locked', $result->get_error_code() );
		$this->assertStringContainsString( 'Restore the product', $result->get_error_message() );
		$this->assertSame( 'post-trashed', get_comment( $id )->comment_approved );

		$custom = $this->review( 'approved', array( 'comment_post_ID' => \WC_Helper_Product::create_simple_product()->get_id() ) );
		wp_update_comment(
			array(
				'comment_ID'       => $custom,
				'comment_approved' => 'custom-status',
			)
		);
		$result = $this->sut->execute(
			array(
				'id'     => $custom,
				'status' => 'hold',
			)
		);

		$this->assertSame( 'woocommerce_ability_product_review_status_locked', $result->get_error_code() );
		$this->assertStringNotContainsString( 'Restore the product', $result->get_error_message(), 'Only a trashed product needs restoring.' );
	}

	/**
	 * @testdox Should trash a review when the trashable filter returns a truthy non-boolean, as REST does.
	 */
	public function test_truthy_trashable_filter_allows_trash(): void {
		$id = $this->review( 'approved' );
		add_filter( 'woocommerce_rest_product_review_trashable', fn() => 1 );

		$result = $this->sut->execute(
			array(
				'id'     => $id,
				'status' => 'trash',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'trash', get_comment( $id )->comment_approved );
	}

	/**
	 * @testdox Should moderate a direct reply to a review, but not a reply to a reply.
	 */
	public function test_moderates_replies(): void {
		$review = $this->review( 'approved' );
		$reply  = self::factory()->comment->create(
			array(
				'comment_post_ID'  => $this->product_id,
				'comment_parent'   => $review,
				'comment_approved' => '1',
				'comment_content'  => 'Thanks!',
				'user_id'          => get_current_user_id(),
			)
		);
		$nested = self::factory()->comment->create(
			array(
				'comment_post_ID' => $this->product_id,
				'comment_parent'  => $reply,
			)
		);

		$result = $this->sut->execute(
			array(
				'id'     => $reply,
				'status' => 'trash',
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['changed'] );
		$this->assertSame( 'approved', $result['previous_status'] );
		$this->assertSame( $review, $result['reply']['review_id'] );
		$this->assertSame( 'trash', $result['reply']['status'] );
		$this->assertArrayNotHasKey( 'review', $result );
		$this->assertSame( 'trash', get_comment( $reply )->comment_approved );

		$result = $this->sut->execute(
			array(
				'id'     => $nested,
				'status' => 'trash',
			)
		);
		$this->assertSame( 'woocommerce_ability_product_review_not_found', $result->get_error_code() );
	}

	/**
	 * @testdox Should require review edit access for replies too.
	 */
	public function test_reply_permissions(): void {
		$reply = self::factory()->comment->create(
			array(
				'comment_post_ID' => $this->product_id,
				'comment_parent'  => $this->review( 'approved' ),
			)
		);
		$input = array(
			'id'     => $reply,
			'status' => 'hold',
		);

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertSame( 'ability_invalid_permissions', $this->sut->execute( $input )->get_error_code(), 'moderate_comments alone is not enough.' );
		$this->assertSame( '1', get_comment( $reply )->comment_approved );
	}

	/**
	 * @testdox Should not return reviewer identity.
	 */
	public function test_withholds_reviewer_identity(): void {
		$result = $this->sut->execute(
			array(
				'id'     => $this->review( 'hold' ),
				'status' => 'approved',
			)
		);

		$this->assertSame( 'Lovely item', $result['review']['review'] );
		$this->assertStringNotContainsString( 'Private Reviewer', wp_json_encode( $result ) );
		$this->assertStringNotContainsString( 'private@example.test', wp_json_encode( $result ) );
	}
}
