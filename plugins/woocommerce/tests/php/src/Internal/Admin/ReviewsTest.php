<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\Reviews;
use Automattic\WooCommerce\Internal\Admin\ReviewsListTable;
use Generator;
use ReflectionClass;
use ReflectionException;
use WC_Unit_Test_Case;
use WP_Comment;

/**
 * Tests for the admin reviews handler.
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews
 */
class ReviewsTest extends WC_Unit_Test_Case {

	/**
	 * Sets the global vars before each test.
	 */
	public function setUp() : void {
		global $current_screen;

		$this->old_current_screen = $current_screen;

		parent::setUp();
	}

	/**
	 * Restores the global vars after each test.
	 */
	public function tearDown() : void {
		global $current_screen;

		$current_screen = $this->old_current_screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		parent::tearDown();
	}

	/**
	 * Tests that can get the class instance.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews::get_instance()
	 *
	 * @return void
	 */
	public function test_get_instance() {
		$this->assertInstanceOf( Reviews::class, Reviews::get_instance() );
	}

	/**
	 * Tests that can get the capability to view the reviews page.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews::get_capability()
	 *
	 * @return void
	 */
	public function test_get_view_page_capability() {

		$this->assertEquals( 'moderate_comments', Reviews::get_capability() );

		$callback = function() {
			return 'manage_woocommerce';
		};

		add_filter( 'woocommerce_product_reviews_page_capability', $callback );

		$this->assertEquals( 'manage_woocommerce', Reviews::get_capability() );

		remove_filter( 'woocommerce_product_reviews_page_capability', $callback );
	}

	/**
	 * Tests that `load_reviews_screen()` creates an instance of {@see ReviewsListTable}.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews::load_reviews_screen()
	 *
	 * @return void
	 * @throws ReflectionException If the method or the property is not found.
	 */
	public function test_load_reviews_screen() {
		$reviews = new Reviews();

		// This has to be manually set, otherwise instantiating ReviewsListTable will throw an undefined index error.
		$hook_property = ( new ReflectionClass( $reviews ) )->getProperty( 'reviews_page_hook' );
		$hook_property->setAccessible( true );
		$hook_property->setValue( $reviews, 'product_page_product-reviews' );

		$list_table_property = ( new ReflectionClass( $reviews ) )->getProperty( 'reviews_list_table' );
		$list_table_property->setAccessible( true );

		$this->assertNull( $list_table_property->getValue( $reviews ) );

		$reviews->load_reviews_screen();

		$this->assertInstanceOf( ReviewsListTable::class, $list_table_property->getValue( $reviews ) );
	}

	/**
	 * Tests that can get the pending comment count bubble.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews::get_pending_count_bubble()
	 * @dataProvider data_provider_get_pending_count_bubble
	 *
	 * @param int    $number_pending Number of pending product reviews.
	 * @param string $expected_html  Expected return value.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_pending_count_bubble( int $number_pending, string $expected_html ) {
		// Add a normal post with some pending comments -- these should not appear in our counts.
		$post_id = $this->factory()->post->create(
			[
				'post_type' => 'post',
			]
		);
		$this->factory()->comment->create_many(
			3,
			[
				'comment_post_ID'  => $post_id,
				'comment_approved' => '0',
			]
		);

		if ( $number_pending > 0 ) {
			// Now add a product with a bunch of reviews.
			$product_id = $this->factory()->post->create(
				[
					'post_type' => 'product',
				]
			);

			// Create moderated comments -- these _should_ appear in our counts.
			$this->factory()->comment->create_many(
				$number_pending,
				[
					'comment_type' => 'review',
					'comment_post_ID' => $product_id,
					'comment_approved' => '0',
				]
			);

			// Create some approved comments -- these _should not_ appear in our counts.
			$this->factory()->comment->create_many(
				2,
				[
					'comment_type' => 'review',
					'comment_post_ID' => $product_id,
					'comment_approved' => '1',
				]
			);
		}

		$reviews = new Reviews();
		$method  = ( new ReflectionClass( $reviews ) )->getMethod( 'get_pending_count_bubble' );
		$method->setAccessible( true );

		$this->assertSame(
			$expected_html,
			$method->invoke( $reviews )
		);
	}

	/** @see test_get_pending_count_bubble */
	public function data_provider_get_pending_count_bubble() : Generator {
		yield 'no pending' => [ 0, '' ];
		yield 'has pending' => [
			2,
			' <span class="awaiting-mod count-2"><span class="pending-count">2</span></span>',
		];
	}

	/**
	 * Tests that it will override the parent file and the submenu file globals when editing a review.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews::edit_review_parent_file()
	 *
	 * @return void
	 */
	public function test_edit_review_parent_file() {
		global $submenu_file, $current_screen;

		$product = $this->factory()->post->create( [ 'post_type' => 'product' ] );
		$review = $this->factory()->comment->create( [ 'comment_post_ID' => $product ] );
		$current_screen = (object) [ 'id' => 'comment' ]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$_GET['c'] = $review;
		$reviews = new Reviews();

		$this->assertSame( 'edit.php?post_type=product', $reviews->edit_review_parent_file( 'test' ) );
		$this->assertSame( 'product-reviews', $submenu_file );
	}

	/**
	 * Tests that it will override the headline text when editing or moderating a review or reply.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews::edit_comments_screen_text()
	 * @dataProvider data_provider_edit_comments_screen_text
	 *
	 * @param string $translated_text Translated text.
	 * @param string $original_text   Original text (raw).
	 * @param bool   $is_review       Whether we should test a review comment.
	 * @param bool   $is_reply        Whether we should test a reply to a review comment.
	 * @param string $expected_text   Expected text output.
	 * @return void
	 */
	public function test_edit_comments_screen_text( string $translated_text, string $original_text, bool $is_review, bool $is_reply, string $expected_text ) {
		global $comment;

		$product = $this->factory()->post->create( [ 'post_type' => 'product' ] );
		$review  = $this->factory()->comment->create_and_get( [ 'comment_post_ID' => $product ] );
		$reply   = $this->factory()->comment->create_and_get(
			[
				'comment_post_ID' => $product,
				'comment_parent'  => $review->comment_ID,
			]
		);

		if ( ! $is_review ) {
			$post    = $this->factory()->post->create();
			$comment = $this->factory()->comment->create_and_get( [ 'comment_post_ID' => $post ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} else {
			$comment = $is_reply ? $reply : $review; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		$this->assertSame( $expected_text, ( new Reviews() )->edit_comments_screen_text( $translated_text, $original_text ) );
	}

	/** @see test_edit_comments_screen_text */
	public function data_provider_edit_comments_screen_text() : Generator {
		yield 'Regular comment' => [ 'Edit Comment', 'Edit Comment', false, false, 'Edit Comment' ];
		yield 'Not the expected text'  => [ 'Foo', 'Bar', true, false, 'Foo' ];
		yield 'Edit Review' => [ 'Edit Comment Translated', 'Edit Comment', true, false, 'Edit Review' ];
		yield 'Edit Review Reply' => [ 'Edit Comment Translated', 'Edit Comment', true, true, 'Edit Review Reply' ];
		yield 'Moderate Review' => [ 'Moderate Comment Translated', 'Moderate Comment', true, false, 'Moderate Review' ];
		yield 'Moderate Review Reply' => [ 'Moderate Comment Translated', 'Moderate Comment', true, true, 'Moderate Review Reply' ];
	}

	/**
	 * Tests that can output the reviews list table and filter it.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews::render_reviews_list_table()
	 *
	 * @return void
	 * @throws ReflectionException If the property doesn't exist.
	 */
	public function test_render_reviews_list_table() {
		$GLOBALS['hook_suffix'] = 'product_page_product-reviews'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$reviews = Reviews::get_instance();
		$list_table = new ReviewsListTable( [ 'screen' => 'product_page_product-reviews' ] );

		$property = ( new ReflectionClass( $reviews ) )->getProperty( 'reviews_list_table' );
		$property->setAccessible( true );
		$property->setValue( $reviews, $list_table );

		add_filter(
			'woocommerce_product_reviews_list_table',
			static function ( $content ) {
				return $content . 'custom additional content';
			}
		);

		ob_start();

		$reviews->render_reviews_list_table();

		$output = ob_get_clean();

		$this->assertStringContainsString( '<form id="reviews-filter" method="get">', $output );
		$this->assertStringContainsString( '<input type="hidden" name="page" value="' . Reviews::MENU_SLUG . '" />', $output );
		$this->assertStringContainsString( '<input type="hidden" name="post_type" value="product" />', $output );
		$this->assertStringEndsWith( 'custom additional content', $output );

		remove_all_filters( 'woocommerce_product_reviews_list_table' );
	}

	/**
	 * Tests that the reviews page is properly identified.
	 *
	 * @covers       \Automattic\WooCommerce\Internal\Admin\Reviews::is_reviews_page()
	 * @dataProvider provider_is_reviews_page
	 *
	 * @param string|null $new_current_screen The value of the global $pageview var.
	 * @param bool        $expected_result    The expected bool result.
	 * @return void
	 */
	public function test_is_reviews_page( $new_current_screen, $expected_result ) {
		global $current_screen;

		$current_screen = $new_current_screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$reviews = new Reviews();

		$this->assertSame( $expected_result, $reviews->is_reviews_page() );
	}

	/** @see test_is_reviews_page */
	public function provider_is_reviews_page() : Generator {

		yield 'Global current_screen is null' => [
			'new_current_screen' => null,
			'expected_result'    => false,
		];

		yield 'Global current_screen has no base' => [
			'new_current_screen' => (object) [],
			'expected_result'    => false,
		];

		$any_screen = (object) [ 'base' => 'any-page' ];

		yield 'current_screen->base is anything other than the reviews page' => [
			'new_current_screen' => $any_screen,
			'expected_result'    => false,
		];

		$reviews_screen = (object) [ 'base' => 'product_page_product-reviews' ];

		yield 'Page is product-reviews' => [
			'new_current_screen' => $reviews_screen,
			'expected_result'    => true,
		];
	}

	/**
	 * Tests that the admin notice messages are properly returned.
	 *
	 * @covers       \Automattic\WooCommerce\Internal\Admin\Reviews::get_bulk_action_notice_messages()
	 * @dataProvider provider_get_bulk_action_notice_messages
	 *
	 * @param string[] $statuses        The wp comment statuses after a bulk operation.
	 * @param int      $count           The number of affected comments.
	 * @param array    $expected_result The action notice messages.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_get_bulk_action_notice_messages( $statuses, $count, $expected_result ) {

		$reviews = new Reviews();

		$method = ( new ReflectionClass( $reviews ) )->getMethod( 'get_bulk_action_notice_messages' );
		$method->setAccessible( true );

		$_REQUEST = [];

		foreach ( $statuses as $status ) {
			$_REQUEST[ $status ] = $count;
		}
		$_REQUEST['ids'] = '1,2,3';

		$result = $method->invoke( $reviews );

		foreach ( $expected_result as $i => $expected_message ) {
			$this->assertContains( $expected_message, $result[ $i ] );
		}
	}

	/** @see test_get_bulk_action_notice_messages */
	public function provider_get_bulk_action_notice_messages() : Generator {

		yield 'An approved review status' => [
			'status'         => [ 'approved' ],
			'count'          => 1,
			'expected_array' => [ '1 review approved' ],
		];

		yield 'Two approved review statuses' => [
			'status'         => [ 'approved' ],
			'count'          => 2,
			'expected_array' => [ '2 reviews approved' ],
		];

		yield 'An unapproved review status' => [
			'status'         => [ 'unapproved' ],
			'count'          => 1,
			'expected_array' => [ '1 review unapproved' ],
		];

		yield 'Two unapproved review statuses' => [
			'status'         => [ 'unapproved' ],
			'count'          => 2,
			'expected_array' => [ '2 reviews unapproved' ],
		];

		yield 'A deleted review status' => [
			'status'         => [ 'deleted' ],
			'count'          => 1,
			'expected_array' => [ '1 review permanently deleted' ],
		];

		yield 'Two deleted review statuses' => [
			'status'         => [ 'deleted' ],
			'count'          => 2,
			'expected_array' => [ '2 reviews permanently deleted' ],
		];

		yield 'A trashed review status' => [
			'status'         => [ 'trashed' ],
			'count'          => 1,
			'expected_array' => [ '1 review moved to the Trash.' ],
		];

		yield 'Two trashed review statuses' => [
			'status'         => [ 'trashed' ],
			'count'          => 2,
			'expected_array' => [ '2 reviews moved to the Trash.' ],
		];

		yield 'An untrashed review status' => [
			'status'         => [ 'untrashed' ],
			'count'          => 1,
			'expected_array' => [ '1 review restored from the Trash' ],
		];

		yield 'Two untrashed review statuses' => [
			'status'         => [ 'untrashed' ],
			'count'          => 2,
			'expected_array' => [ '2 reviews restored from the Trash' ],
		];

		yield 'A spammed review status' => [
			'status'         => [ 'spammed' ],
			'count'          => 1,
			'expected_array' => [ '1 review marked as spam.' ],
		];

		yield 'Two spammed review statuses' => [
			'status'         => [ 'spammed' ],
			'count'          => 2,
			'expected_array' => [ '2 reviews marked as spam.' ],
		];

		yield 'An unspammed review status' => [
			'status'         => [ 'unspammed' ],
			'count'          => 1,
			'expected_array' => [ '1 review restored from the spam' ],
		];

		yield 'Two unspammed review statuses' => [
			'status'         => [ 'unspammed' ],
			'count'          => 2,
			'expected_array' => [ '2 reviews restored from the spam' ],
		];

		yield 'Two different statuses' => [
			'status'         => [ 'approved', 'unapproved' ],
			'count'          => 1,
			'expected_array' => [ '1 review approved', '1 review unapproved' ],
		];
	}

	/**
	 * Tests that a notice message will result in a valid HTML return.
	 *
	 * @covers       \Automattic\WooCommerce\Internal\Admin\Reviews::maybe_display_reviews_bulk_action_notice()
	 * @dataProvider provider_maybe_display_reviews_bulk_action_notice
	 *
	 * @param array  $messages        The action notice messages.
	 * @param string $expected_result The expected result.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_maybe_display_reviews_bulk_action_notice( $messages, $expected_result ) {

		$mock = $this->getMockBuilder( Reviews::class )
			->setMethods( [ 'get_bulk_action_notice_messages' ] )
			->getMock();

		$mock->expects( $this->once() )
			->method( 'get_bulk_action_notice_messages' )
			->willReturn( $messages );

		$method = ( new ReflectionClass( $mock ) )->getMethod( 'maybe_display_reviews_bulk_action_notice' );
		$method->setAccessible( true );

		ob_start();

		$method->invoke( $mock );

		$this->assertSame( $expected_result, ob_get_clean() );
	}

	/** @see test_maybe_display_reviews_bulk_action_notice */
	public function provider_maybe_display_reviews_bulk_action_notice() : Generator {

		yield 'No messages are returned' => [
			'messages'        => [],
			'expected_result' => '',
		];

		yield 'A message is returned' => [
			'messages'        => [ 'test' ],
			'expected_result' => '<div id="moderated" class="updated"><p>test</p></div>',
		];

		yield 'Two messages are returned' => [
			'messages'        => [ 'test1', 'test2' ],
			'expected_result' => '<div id="moderated" class="updated"><p>test1<br/>
test2</p></div>',
		];
	}

	/**
	 * Tests that the display method is called only for the reviews page.
	 *
	 * @covers       \Automattic\WooCommerce\Internal\Admin\Reviews::display_notices()
	 * @dataProvider provider_display_notices
	 *
	 * @param bool $is_reviews_page                Whether the current page is the reviews page or not.
	 * @param bool $should_call_the_display_method Indicates if the display method should be called.
	 */
	public function test_display_notices( $is_reviews_page, $should_call_the_display_method ) {

		$mock = $this->getMockBuilder( Reviews::class )
			->setMethods( [ 'is_reviews_page', 'maybe_display_reviews_bulk_action_notice' ] )
			->getMock();

		$mock->expects( $this->once() )
			->method( 'is_reviews_page' )
			->willReturn( $is_reviews_page );

		$mock->expects( $this->exactly( (int) $should_call_the_display_method ) )
			->method( 'maybe_display_reviews_bulk_action_notice' );

		$mock->display_notices();
	}

	/** @see test_display_notices */
	public function provider_display_notices() : Generator {

		yield 'Is the reviews page' => [
			'is_reviews_page'                          => true,
			'maybe_display_reviews_bulk_action_notice' => true,
		];

		yield 'Is not the reviews page' => [
			'is_reviews_page'                          => false,
			'maybe_display_reviews_bulk_action_notice' => false,
		];
	}

	/**
	 * Tests scenarios that should return false.
	 *
	 * @covers       \Automattic\WooCommerce\Internal\Admin\Reviews::is_review_or_reply()
	 * @dataProvider provider_is_review_or_reply
	 *
	 * @param WP_Comment|array|null $object   Object to pass in to the method.
	 * @param bool                  $expected Expected result.
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_is_not_review_or_reply( $object, bool $expected ) : void {
		$reviews = new Reviews();
		$method  = ( new ReflectionClass( $reviews ) )->getMethod( 'is_review_or_reply' );
		$method->setAccessible( true );

		$this->assertSame( $expected, $method->invoke( $reviews, $object ) );
	}

	/** @see test_is_not_review_or_reply */
	public function provider_is_review_or_reply(): Generator {
		yield 'null object' => [ null, false ];
		yield 'invalid array' => [ [ 'data' ], false ];
	}

	/**
	 * Tests different cases that require factories.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\Reviews::is_review_or_reply()
	 *
	 * @return void
	 * @throws ReflectionException If the method doesn't exist.
	 */
	public function test_is_review_or_reply_with_comment_object() : void {
		$reviews = new Reviews();
		$method  = ( new ReflectionClass( $reviews ) )->getMethod( 'is_review_or_reply' );
		$method->setAccessible( true );

		$regular_comment = $this->factory()->comment->create_and_get(
			[
				'comment_post_ID'  => $this->factory()->post->create(),
			]
		);
		$this->assertFalse( $method->invoke( $reviews, $regular_comment ) );

		$review = $this->factory()->comment->create_and_get(
			[
				'comment_type'     => 'review',
				'comment_post_ID'  => $this->factory()->post->create(
					[
						'post_type'  => 'product',
					]
				),
			]
		);
		$this->assertTrue( $method->invoke( $reviews, $review ) );

		$review_reply = $this->factory()->comment->create_and_get(
			[
				'comment_type'     => 'comment',
				'comment_post_ID'  => $this->factory()->post->create(
					[
						'post_type'  => 'product',
					]
				),
			]
		);
		$this->assertTrue( $method->invoke( $reviews, $review_reply ) );
	}

}
