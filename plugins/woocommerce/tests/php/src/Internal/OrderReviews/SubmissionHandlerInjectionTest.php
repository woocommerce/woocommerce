<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\OrderReviews;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\OrderReviews\ItemEligibility;
use Automattic\WooCommerce\Internal\OrderReviews\SubmissionHandler;
use WC_Helper_Product;
use WC_Order;
use WC_Unit_Test_Case;
use WP_Comment;
use WP_HTML_Tag_Processor;
use WP_REST_Request;
use WPAjaxDieContinueException;

/**
 * Attack-oriented tests for the Review Order submission handler.
 *
 * Every value a guest controls (review text, rating, row ids, extra POST fields, and the
 * billing name, email, IP and user agent captured at checkout) is pushed through the
 * handler, then the stored review and each place it is rendered are checked.
 */
class SubmissionHandlerInjectionTest extends WC_Unit_Test_Case {

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_feature_customer_review_request_enabled', 'yes' );
		update_option( 'comment_moderation', '0' );
		update_option( 'comment_max_links', 0 );
		update_option( 'moderation_keys', '' );
		update_option( 'disallowed_keys', '' );
		update_option( 'use_smilies', 0 );
		wp_set_current_user( 0 );
		ItemEligibility::reset_cache();

		// The container wires this in production via init(); the test dispatches the
		// handler directly, so register the moderation-transition hook here to
		// exercise the auto-rejection tag lifecycle. WP_UnitTestCase restores hooks
		// on tearDown, so it does not leak between tests.
		add_action( 'transition_comment_status', array( new SubmissionHandler(), 'clear_auto_rejected_flag' ), 10, 3 );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			$_POST = array();
			ItemEligibility::reset_cache();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * XSS and markup payloads a guest can type into the review textarea.
	 *
	 * @return array<string, array{string}>
	 */
	public function provide_text_payloads(): array {
		return array(
			'script tag'                       => array( '<script>alert(1)</script>Nice' ),
			'script tag mixed case'            => array( '<ScRiPt>alert(1)</sCrIpT>Nice' ),
			'nested script split'              => array( '<scr<script>ipt>alert(1)</scr</script>ipt>' ),
			'img onerror'                      => array( '<img src=x onerror=alert(1)>' ),
			'img slash separators'             => array( '<img/src=x/onerror=alert(1)>' ),
			'svg onload'                       => array( '<svg onload=alert(1)><circle r=1 /></svg>' ),
			'svg nested script'                => array( '<svg><script>alert(1)</script></svg>' ),
			'math and mglyph'                  => array( '<math><mtext><table><mglyph><style><img src=x onerror=alert(1)>' ),
			'iframe srcdoc'                    => array( '<iframe srcdoc="<script>alert(1)</script>"></iframe>' ),
			'object data'                      => array( '<object data="javascript:alert(1)"></object>' ),
			'embed src'                        => array( '<embed src="javascript:alert(1)">' ),
			'form action'                      => array( '<form action="https://evil.test"><button>Go</button></form>' ),
			'input autofocus onfocus'          => array( '<input autofocus onfocus=alert(1)>' ),
			'details ontoggle'                 => array( '<details open ontoggle=alert(1)>' ),
			'video source onerror'             => array( '<video><source onerror=alert(1)></video>' ),
			'style tag'                        => array( '<style>body{display:none}</style>Nice' ),
			'link stylesheet'                  => array( '<link rel=stylesheet href=https://evil.test/x.css>' ), // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Attack payload, never output.
			'meta refresh'                     => array( '<meta http-equiv="refresh" content="0;url=https://evil.test">' ),
			'base href'                        => array( '<base href="https://evil.test/">' ),
			'anchor javascript href'           => array( '<a href="javascript:alert(1)">x</a>' ),
			'anchor javascript mixed case'     => array( '<a href="JaVaScRiPt:alert(1)">x</a>' ),
			'anchor javascript tab entity'     => array( '<a href="jav&#x09;ascript:alert(1)">x</a>' ),
			'anchor javascript newline entity' => array( '<a href="java&#10;script:alert(1)">x</a>' ),
			'anchor javascript decimal'        => array( '<a href="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;alert(1)">x</a>' ),
			'anchor javascript leading space'  => array( '<a href=" javascript:alert(1)">x</a>' ),
			'anchor unquoted javascript'       => array( '<a href=javascript:alert(1)//>x</a>' ),
			'anchor data uri'                  => array( '<a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">x</a>' ),
			'anchor vbscript'                  => array( '<a href="vbscript:msgbox(1)">x</a>' ),
			'anchor event handlers'            => array( '<a href="https://example.test" onclick="alert(1)" onmouseover=alert(1)>x</a>' ),
			'anchor target and download'       => array( '<a href="https://example.test" target="_blank" download>x</a>' ),
			'interactivity directives'         => array( '<a href="https://example.test" data-wp-bind--href="state.url" data-wp-on--click="actions.x">x</a>' ),
			'interactivity store island'       => array( '<span data-wp-interactive="woocommerce/store-notices" data-wp-context="{&quot;notice&quot;:{&quot;notice&quot;:&quot;&lt;img src=x onerror=alert(1)&gt;&quot;}}" data-wp-watch="callbacks.renderNoticeContent"></span>' ),
			'style overlay'                    => array( '<a href="https://example.test" style="position:fixed;inset:0;z-index:9999">x</a>' ),
			'class and id clobbering'          => array( '<strong id="woocommerce-review-order" class="woocommerce-error">x</strong>' ),
			'blockquote cite javascript'       => array( '<blockquote cite="javascript:alert(1)">x</blockquote>' ),
			'q cite data uri'                  => array( '<q cite="data:text/html,<script>alert(1)</script>">x</q>' ),
			'abbr title quote breakout'        => array( '<abbr title="x&quot; onmouseover=&quot;alert(1)">x</abbr>' ),
			'abbr title raw quote breakout'    => array( '<abbr title="x" onmouseover="alert(1)">x</abbr>' ),
			'unclosed attribute'               => array( '<a href="https://example.test" title="<img src=x onerror=alert(1)>' ),
			'html comment smuggling'           => array( '<!--<img src=x onerror=alert(1)>-->Nice' ),
			'comment breakout'                 => array( '<!-- --!><img src=x onerror=alert(1)> -->' ),
			'cdata smuggling'                  => array( '<![CDATA[<img src=x onerror=alert(1)>]]>' ),
			'textarea breakout'                => array( '</textarea><script>alert(1)</script>' ),
			'attribute breakout for data attr' => array( '"><img src=x onerror=alert(1)>' ),
			'null byte in tag name'            => array( "<scr\0ipt>alert(1)</scr\0ipt>" ),
			'null byte in protocol'            => array( "<a href=\"java\0script:alert(1)\">x</a>" ),
			'fullwidth angle brackets'         => array( '＜script＞alert(1)＜/script＞' ),
			'encoded entities'                 => array( '&lt;script&gt;alert(1)&lt;/script&gt; &#60;img src=x onerror=alert(1)&#62;' ),
			'double encoded entities'          => array( '&amp;lt;script&amp;gt;alert(1)&amp;lt;/script&amp;gt;' ),
			'block markup'                     => array( '<!-- wp:html --><script>alert(1)</script><!-- /wp:html -->' ),
			'shortcode'                        => array( '[woocommerce_order_tracking] [embed]https://evil.test[/embed]' ),
			'template expression'              => array( '{{constructor.constructor(\'alert(1)\')()}} ${alert(1)}' ),
			'sql fragment'                     => array( "' OR 1=1; DROP TABLE wp_comments; -- \\'" ),
			'invalid utf8'                     => array( "Nice \xC3\x28 review \xF0\x28\x8C\xBC" ),
		);
	}

	/**
	 * @testdox Review text payload "$payload" is stored and rendered without executable markup.
	 *
	 * @dataProvider provide_text_payloads
	 *
	 * @param string $payload Review text.
	 */
	public function test_text_payload_is_neutralised_on_insert( string $payload ): void {
		$built   = $this->make_order();
		$comment = $this->submit_single_review( $built, $payload );

		$this->assert_review_is_safe( $comment, $built );
	}

	/**
	 * @testdox Review text payload "$payload" is neutralised when it edits an existing review.
	 *
	 * @dataProvider provide_text_payloads
	 *
	 * @param string $payload Review text.
	 */
	public function test_text_payload_is_neutralised_on_update( string $payload ): void {
		if ( ! mb_check_encoding( $payload, 'UTF-8' ) ) {
			$this->markTestSkipped( 'Covered by test_invalid_utf8_edit_leaves_the_review_unchanged.' );
		}

		$built    = $this->make_order();
		$original = $this->submit_single_review( $built, 'A plain first review.' );

		$updated = $this->submit_single_review( $built, $payload );

		$this->assertSame( (int) $original->comment_ID, (int) $updated->comment_ID, 'The second submit must edit the first review.' );
		$this->assert_review_is_safe( $updated, $built );
	}

	/**
	 * @testdox An edit with invalid UTF-8 fails for that row and leaves the stored review unchanged.
	 */
	public function test_invalid_utf8_edit_leaves_the_review_unchanged(): void {
		$built    = $this->make_order();
		$original = $this->submit_single_review( $built, 'A plain first review.' );

		$response = $this->submit_row( $built, array( 'text' => "Nice \xC3\x28 review" ) );

		$this->assertSame( 'update_failed', $response['data']['results'][0]['error'] ?? null );
		$this->assertSame( 'A plain first review.', get_comment( (int) $original->comment_ID )->comment_content );
	}

	/**
	 * @testdox Review text payload "$payload" cannot break out of the prefilled Review Order row.
	 *
	 * @dataProvider provide_text_payloads
	 *
	 * @param string $payload Review text.
	 */
	public function test_text_payload_cannot_break_out_of_the_prefilled_row( string $payload ): void {
		$built   = $this->make_order();
		$comment = $this->submit_single_review( $built, $payload );

		$baseline = $this->render_row( $built, 'Plain' );
		$attacked = $this->render_row( $built, (string) $comment->comment_content );

		$this->assertSame(
			$this->tag_signature( $baseline ),
			$this->tag_signature( $attacked ),
			'Stored review text must not add tags or attributes to the prefilled row.'
		);
	}

	/**
	 * Markup payloads a guest can enter as billing first or last name at checkout.
	 *
	 * @return array<string, array{string}>
	 */
	public function provide_name_payloads(): array {
		return array(
			'script tag'          => array( '<script>alert(1)</script>Jane' ),
			'img onerror'         => array( '<img src=x onerror=alert(1)>Jane' ),
			'attribute breakout'  => array( 'Jane"><svg onload=alert(1)>' ),
			'single quote'        => array( "Jane' onmouseover='alert(1)" ),
			'interactivity attrs' => array( '<span data-wp-interactive="x" data-wp-watch="callbacks.y">Jane</span>' ),
			'entity smuggling'    => array( '&lt;img src=x onerror=alert(1)&gt;' ),
			'octets'              => array( 'Jane %3Cscript%3E' ),
			'newlines and tabs'   => array( "Jane\n<script>\talert(1)</script>" ),
		);
	}

	/**
	 * @testdox Billing name payload "$payload" is stored as plain text and renders safely.
	 *
	 * @dataProvider provide_name_payloads
	 *
	 * @param string $payload Billing first name.
	 */
	public function test_name_payload_is_neutralised( string $payload ): void {
		$built   = $this->make_order( array( 'first_name' => $payload ) );
		$comment = $this->submit_single_review( $built, 'Nice.' );

		$this->assertStringNotContainsString( '<', $comment->comment_author, 'Stored author must not contain a raw tag opener.' );
		$this->assertStringNotContainsString( '>', $comment->comment_author, 'Stored author must not contain a raw tag closer.' );

		$author_link = get_comment_author_link( $comment );
		$this->assert_html_allowed( $author_link, array( 'a' => array( 'href', 'rel', 'class' ) ), 'author link' );

		$store_review = $this->get_store_api_review( (int) $comment->comment_ID, $built['product_id'] );
		$this->assertNotNull( $store_review, 'Approved review must be listed by the Store API.' );
		$this->assertStringNotContainsString( '<', (string) $store_review['reviewer'], 'Store API reviewer must not contain markup.' );
	}

	/**
	 * @testdox A billing name made only of markup falls back to the anonymous label.
	 */
	public function test_markup_only_name_falls_back_to_anonymous(): void {
		$built   = $this->make_order(
			array(
				'first_name' => '<img src=x onerror=alert(1)>',
				'last_name'  => '<script>alert(1)</script>',
			)
		);
		$comment = $this->submit_single_review( $built, 'Nice.' );

		$this->assertSame( __( 'Anonymous', 'woocommerce' ), $comment->comment_author );
	}

	/**
	 * Email addresses that must never reach the order in the first place.
	 *
	 * @return array<string, array{string}>
	 */
	public function provide_invalid_email_payloads(): array {
		return array(
			'quoted markup local part' => array( '"<script>alert(1)</script>"@example.test' ),
			'angle bracket'            => array( 'a<b@example.test' ),
			'attribute breakout'       => array( 'a@example.test" onmouseover="alert(1)' ),
			'javascript scheme'        => array( 'javascript:alert(1)@example.test' ),
			'header injection'         => array( "a@example.test\r\nBcc: victim@example.test" ),
		);
	}

	/**
	 * @testdox Billing email payload "$payload" is rejected before it can be stored on the order.
	 *
	 * @dataProvider provide_invalid_email_payloads
	 *
	 * @param string $payload Billing email.
	 */
	public function test_invalid_email_payload_is_rejected_by_the_order( string $payload ): void {
		$order = new WC_Order();

		$this->expectException( \WC_Data_Exception::class );
		$order->set_billing_email( $payload );
	}

	/**
	 * @testdox A valid email with special characters is stored verbatim and a resubmit still finds the review.
	 */
	public function test_special_character_email_is_stored_raw_and_resubmit_reuses_review(): void {
		$built = $this->make_order( array( 'email' => "a&b'c+d{e}~f@example.test" ) );

		$first  = $this->submit_single_review( $built, 'First.' );
		$second = $this->submit_single_review( $built, 'Second.' );

		$this->assertSame( "a&b'c+d{e}~f@example.test", $first->comment_author_email );
		$this->assertSame( (int) $first->comment_ID, (int) $second->comment_ID, 'Resubmit must reuse the same review.' );
	}

	/**
	 * @testdox A spoofed customer IP from a proxy header is reduced to IP characters on the review.
	 */
	public function test_spoofed_ip_is_reduced_to_ip_characters(): void {
		$built   = $this->make_order( array( 'ip' => '1.2.3.4"><script>alert(1)</script>' ) );
		$comment = $this->submit_single_review( $built, 'Nice.' );

		$this->assertMatchesRegularExpression( '/^[0-9a-fA-F:., ]*$/', $comment->comment_author_IP );
	}

	/**
	 * @testdox An oversized user agent with markup is truncated to the column limit.
	 */
	public function test_user_agent_payload_is_truncated(): void {
		$agent   = '<script>alert(1)</script>' . str_repeat( 'A', 400 );
		$built   = $this->make_order( array( 'agent' => $agent ) );
		$comment = $this->submit_single_review( $built, 'Nice.' );

		$this->assertLessThanOrEqual( 254, strlen( $comment->comment_agent ) );
	}

	/**
	 * @testdox Comment fields smuggled into a row cannot change approval, owner, type, parent or meta.
	 */
	public function test_smuggled_row_fields_are_ignored(): void {
		update_option( 'comment_moderation', '1' );
		$built         = $this->make_order();
		$other_product = WC_Helper_Product::create_simple_product();
		$parent_id     = self::factory()->comment->create( array( 'comment_post_ID' => $built['product_id'] ) );

		$response = $this->submit_row(
			$built,
			array(
				'rating'           => 4,
				'text'             => 'Smuggled.',
				'comment_approved' => 1,
				'comment_post_ID'  => $other_product->get_id(),
				'comment_type'     => 'comment',
				'comment_parent'   => $parent_id,
				'user_id'          => 1,
				'comment_meta'     => array(
					'verified' => 0,
					'rating'   => 5,
				),
			)
		);
		$comment  = get_comment( $this->first_comment_id( $response ) );

		$this->assertSame( 'pending_moderation', $response['data']['results'][0]['status'] );
		$this->assertSame( '0', $comment->comment_approved );
		$this->assertSame( $built['product_id'], (int) $comment->comment_post_ID );
		$this->assertSame( 'review', $comment->comment_type );
		$this->assertSame( '0', $comment->comment_parent );
		$this->assertSame( '0', $comment->user_id );
		$this->assertSame( '1', get_comment_meta( (int) $comment->comment_ID, 'verified', true ) );
		$this->assertSame( '4', get_comment_meta( (int) $comment->comment_ID, 'rating', true ) );
	}

	/**
	 * @testdox Top-level comment form fields do not trigger the product page rating hooks.
	 */
	public function test_top_level_comment_form_fields_do_not_change_the_review(): void {
		update_option( 'woocommerce_review_rating_required', 'yes' );
		$built         = $this->make_order();
		$other_product = WC_Helper_Product::create_simple_product();

		$response = $this->submit_row(
			$built,
			array(
				'rating' => 2,
				'text'   => 'Top-level fields.',
			),
			array(
				'comment_post_ID' => $other_product->get_id(),
				'rating'          => '',
			)
		);
		$comment  = get_comment( $this->first_comment_id( $response ) );

		$this->assertSame( 'ok', $response['data']['results'][0]['status'] );
		$this->assertSame( $built['product_id'], (int) $comment->comment_post_ID );
		$this->assertSame( '2', get_comment_meta( (int) $comment->comment_ID, 'rating', true ) );
	}

	/**
	 * Rating values a client can post.
	 *
	 * @return array<string, array{mixed, string, ?string}>
	 */
	public function provide_rating_payloads(): array {
		return array(
			'markup suffix'     => array( '5<script>alert(1)</script>', 'ok', '5' ),
			'exponent'          => array( '1e1', 'error', null ),
			'negative'          => array( '-1', 'error', null ),
			'too high'          => array( '6', 'error', null ),
			'float'             => array( '4.9', 'ok', '4' ),
			'array'             => array( array( 'x' ), 'ok', '1' ),
			'sql fragment'      => array( '5 OR 1=1', 'ok', '5' ),
			'hex'               => array( '0x5', 'skipped', null ),
			'whitespace number' => array( ' 3 ', 'ok', '3' ),
		);
	}

	/**
	 * @testdox Rating payload "$rating" is cast to a whole star value or rejected.
	 *
	 * @dataProvider provide_rating_payloads
	 *
	 * @param mixed       $rating          Posted rating.
	 * @param string      $expected_status Expected row status, or "skipped" when no result is returned.
	 * @param string|null $expected_meta   Expected stored rating meta.
	 */
	public function test_rating_payload_is_cast_or_rejected( $rating, string $expected_status, ?string $expected_meta ): void {
		$built = $this->make_order();

		$response = $this->submit_row(
			$built,
			array(
				'rating' => $rating,
				'text'   => 'Rated.',
			)
		);

		if ( 'skipped' === $expected_status ) {
			$this->assertSame( array(), $response['data']['results'] );
			return;
		}

		$row = $response['data']['results'][0];
		$this->assertSame( $expected_status, $row['status'] );
		if ( null !== $expected_meta ) {
			$this->assertSame( $expected_meta, get_comment_meta( (int) $row['comment_id'], 'rating', true ) );
		}
	}

	/**
	 * @testdox Row ids pointing at another customer's order item or product are rejected.
	 */
	public function test_foreign_row_ids_are_rejected(): void {
		$victim   = $this->make_order( array( 'email' => 'victim@example.test' ) );
		$attacker = $this->make_order();

		$response = $this->submit(
			$attacker['order'],
			array(
				array(
					'product_id'    => $victim['product_id'],
					'order_item_id' => $victim['item_id'],
					'rating'        => 1,
					'text'          => 'Foreign item.',
				),
				array(
					'product_id'    => $victim['product_id'],
					'order_item_id' => $attacker['item_id'],
					'rating'        => 1,
					'text'          => 'Foreign product.',
				),
			)
		);

		$this->assertSame( 'invalid_row', $response['data']['results'][0]['error'] );
		$this->assertSame( 'product_mismatch', $response['data']['results'][1]['error'] );
		$this->assertSame( 0, $this->count_comments( $victim['product_id'] ) );
	}

	/**
	 * @testdox Non-numeric row keys and non-array rows cannot inject results or break the response.
	 */
	public function test_malformed_row_structure_is_handled(): void {
		$built = $this->make_order();

		$response = $this->submit(
			$built['order'],
			array(
				'<script>'  => array(
					'product_id'    => $built['product_id'],
					'order_item_id' => $built['item_id'],
					'rating'        => 5,
					'text'          => array( '<script>alert(1)</script>' ),
				),
				'not-a-row' => '<img src=x onerror=alert(1)>',
			)
		);

		$this->assertTrue( $response['success'] );
		$encoded = (string) wp_json_encode( $response );
		$this->assertStringNotContainsString( 'script', $encoded, 'The response must not echo submitted input.' );
		$this->assertStringNotContainsString( 'onerror', $encoded, 'The response must not echo submitted input.' );
	}

	/**
	 * @testdox A logged-in user cannot submit reviews for someone else's order with its key.
	 */
	public function test_logged_in_non_owner_is_rejected(): void {
		$owner_id    = self::factory()->user->create( array( 'role' => 'customer' ) );
		$attacker_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		$built       = $this->make_order( array( 'customer_id' => $owner_id ) );
		wp_set_current_user( $attacker_id );

		$response = $this->submit_row(
			$built,
			array(
				'rating' => 1,
				'text'   => 'Not mine.',
			)
		);

		$this->assertFalse( $response['success'] );
		$this->assertSame( 0, $this->count_comments( $built['product_id'] ) );
	}

	/**
	 * @testdox A review matching the disallowed keys is stored as spam or trash and is not published.
	 */
	public function test_disallowed_keys_send_review_to_spam(): void {
		update_option( 'disallowed_keys', 'casino-bonus' );
		$built = $this->make_order();

		$response   = $this->submit_row( $built, array( 'text' => 'Visit casino-bonus now.' ) );
		$comment_id = $this->first_comment_id( $response );

		$this->assertSame( 'pending_moderation', $response['data']['results'][0]['status'], 'The response must not reveal the spam verdict.' );
		$this->assertContains( wp_get_comment_status( $comment_id ), array( 'spam', 'trash' ) );
		$this->assertNull( $this->get_store_api_review( $comment_id, $built['product_id'] ), 'Spam must not be public.' );
	}

	/**
	 * @testdox Spam plugins see the review through preprocess_comment and can mark it as spam.
	 */
	public function test_spam_plugin_hooks_run_and_can_reject_the_review(): void {
		$seen = array();
		add_filter(
			'preprocess_comment',
			function ( $data ) use ( &$seen ) {
				$seen[] = $data['comment_content'];
				return $data;
			}
		);
		add_filter(
			'pre_comment_approved',
			function () {
				return 'spam';
			}
		);
		$built = $this->make_order();

		$response   = $this->submit_row( $built, array( 'text' => 'Buy followers.' ) );
		$comment_id = $this->first_comment_id( $response );

		$this->assertSame( array( 'Buy followers.' ), $seen );
		$this->assertSame( 'spam', wp_get_comment_status( $comment_id ) );
		$this->assertSame( 'pending_moderation', $response['data']['results'][0]['status'] );
	}

	/**
	 * @testdox A held review fires comment_post and emails the moderator.
	 */
	public function test_held_review_fires_comment_post_and_notifies_moderator(): void {
		update_option( 'comment_moderation', '1' );
		update_option( 'moderation_notify', '1' );
		$recipients = array();
		add_filter(
			'wp_mail',
			function ( $atts ) use ( &$recipients ) {
				$recipients = array_merge( $recipients, (array) $atts['to'] );
				return $atts;
			}
		);
		$comment_post_count = did_action( 'comment_post' );
		$built              = $this->make_order();

		$this->submit_single_review( $built, 'Please moderate.' );

		$this->assertSame( $comment_post_count + 1, did_action( 'comment_post' ) );
		$this->assertContains( get_option( 'admin_email' ), $recipients, 'The moderator should receive a notification email.' );
	}

	/**
	 * @testdox Several products reviewed in one submission are all stored despite the flood check.
	 */
	public function test_multi_row_submission_is_not_treated_as_a_flood(): void {
		$built = $this->make_order( array(), 3 );

		$rows = array();
		foreach ( $built['item_ids'] as $index => $item_id ) {
			$rows[] = array(
				'product_id'    => $built['product_ids'][ $index ],
				'order_item_id' => $item_id,
				'rating'        => 5,
				'text'          => 'Same text for every product.',
			);
		}

		$response = $this->submit( $built['order'], $rows );

		$this->assertSame( array( 'ok', 'ok', 'ok' ), array_column( $response['data']['results'], 'status' ) );
	}

	/**
	 * @testdox An order without a customer IP can be reviewed right after a new order note.
	 */
	public function test_order_without_ip_is_not_blocked_by_a_recent_order_note(): void {
		$built = $this->make_order( array( 'ip' => '' ) );
		$built['order']->add_order_note( 'A fresh note with an empty author IP.' );

		$comment = $this->submit_single_review( $built, 'Nice.' );

		$this->assertSame( 'review', $comment->comment_type );
	}

	/**
	 * @testdox A second submission for another item right after the first one is stored.
	 */
	public function test_second_submission_right_after_the_first_is_stored(): void {
		$built = $this->make_order( array(), 2 );

		$first  = $this->submit(
			$built['order'],
			array(
				array(
					'product_id'    => $built['product_ids'][0],
					'order_item_id' => $built['item_ids'][0],
					'rating'        => 5,
					'text'          => 'First.',
				),
			)
		);
		$second = $this->submit(
			$built['order'],
			array(
				array(
					'product_id'    => $built['product_ids'][1],
					'order_item_id' => $built['item_ids'][1],
					'rating'        => 5,
					'text'          => 'Second.',
				),
			)
		);

		$this->assertSame( 'ok', $first['data']['results'][0]['status'] );
		$this->assertSame( 'ok', $second['data']['results'][0]['status'] );
	}

	/**
	 * @testdox Repeated rows for the same item in one submission yield one review; the extra rows alias the first, not stacked.
	 */
	public function test_duplicate_slot_rows_in_one_submission_are_not_stacked(): void {
		$built = $this->make_order();

		$response = $this->submit(
			$built['order'],
			array(
				array(
					'product_id'    => $built['product_id'],
					'order_item_id' => $built['item_id'],
					'rating'        => 5,
					'text'          => 'First take.',
				),
				array(
					'product_id'    => $built['product_id'],
					'order_item_id' => $built['item_id'],
					'rating'        => 1,
					'text'          => 'Second take.',
				),
			)
		);

		$this->assertSame( 1, $this->count_comments( $built['product_id'] ), 'One order item must yield one review, not stacked duplicates.' );

		$results = $response['data']['results'];
		$this->assertSame( 'ok', $results[0]['status'] );
		// The extra same-slot row reports the first row's outcome instead of an error.
		$this->assertSame( 'ok', $results[1]['status'] );
		$this->assertSame( $results[0]['comment_id'], $results[1]['comment_id'], 'The extra row must alias the first, not create a second review.' );

		$comment = get_comment( $results[0]['comment_id'] );
		$this->assertSame( 'First take.', $comment->comment_content, 'The first row is kept; extras cannot overwrite it.' );
		$this->assertSame( '5', get_comment_meta( (int) $comment->comment_ID, 'rating', true ) );
	}

	/**
	 * The stock page collapses a product's line items to one row
	 * (ItemEligibility::unique_slot_items), but a theme override copied from an
	 * older version, or a tampered POST, can still send two rows for the same
	 * slot. The extra row must alias the first (report success without storing a
	 * second review), so the whole form doesn't fail on those sites.
	 *
	 * @testdox A second row for the same product slot aliases the first instead of stacking or failing.
	 */
	public function test_extra_same_slot_row_aliases_the_first(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = $this->make_order_for_products( array( $product, $product ) );

		$item_ids = array();
		foreach ( array_values( $order->get_items() ) as $item ) {
			$item_ids[] = $item->get_id();
		}

		$response = $this->submit(
			$order,
			array(
				array(
					'product_id'    => $product->get_id(),
					'order_item_id' => $item_ids[0],
					'rating'        => 5,
					'text'          => 'Nice.',
				),
				array(
					'product_id'    => $product->get_id(),
					'order_item_id' => $item_ids[1],
					'rating'        => 1,
					'text'          => 'Sneaky second review.',
				),
			)
		);

		$results = $response['data']['results'];
		$this->assertSame( 'ok', $results[0]['status'] );
		$this->assertSame( 'ok', $results[1]['status'], 'The extra row must not fail the form.' );
		$this->assertSame( $results[0]['comment_id'], $results[1]['comment_id'], 'The extra row must alias the first review.' );
		$this->assertSame( 1, $this->count_comments( $product->get_id() ) );
	}

	/**
	 * @testdox Editing a review to add a disallowed word holds it instead of leaving it published.
	 */
	public function test_editing_a_review_to_add_a_disallowed_word_holds_it(): void {
		update_option( 'disallowed_keys', 'casino-bonus' );
		$built    = $this->make_order();
		$original = $this->submit_single_review( $built, 'Totally clean.' );

		$response = $this->submit(
			$built['order'],
			array(
				array(
					'product_id'    => $built['product_id'],
					'order_item_id' => $built['item_id'],
					'rating'        => 5,
					'text'          => 'Visit casino-bonus now.',
				),
			)
		);

		$this->assertSame( 'pending_moderation', $response['data']['results'][0]['status'] );
		$this->assertNotSame( 'approved', wp_get_comment_status( (int) $original->comment_ID ), 'An edit adding a disallowed word must not stay published.' );
	}

	/**
	 * @testdox Editing only the star rating keeps an already published review published.
	 */
	public function test_editing_only_the_rating_keeps_the_published_review(): void {
		$built    = $this->make_order();
		$original = $this->submit_single_review( $built, 'Great product.' );
		$this->assertSame( 'approved', wp_get_comment_status( (int) $original->comment_ID ) );

		// A word in the already published review is later disallowed; a rating-only
		// edit keeps the unchanged text and must not re-moderate and trash it.
		update_option( 'disallowed_keys', 'product' );

		$response = $this->submit(
			$built['order'],
			array(
				array(
					'product_id'    => $built['product_id'],
					'order_item_id' => $built['item_id'],
					'rating'        => 3,
					'text'          => 'Great product.',
				),
			)
		);

		$this->assertSame( 'ok', $response['data']['results'][0]['status'] );
		$this->assertSame( 'approved', wp_get_comment_status( (int) $original->comment_ID ) );
		$this->assertSame( 3, (int) get_comment_meta( (int) $original->comment_ID, 'rating', true ) );
	}

	/**
	 * @testdox A rating-only edit keeps a published review that contains a link approved.
	 */
	public function test_editing_only_the_rating_keeps_a_published_review_with_a_link(): void {
		$built    = $this->make_order();
		$original = $this->submit_single_review( $built, 'Great shop, see <a href="https://example.com">here</a>.' );

		$this->assertSame( 'approved', wp_get_comment_status( (int) $original->comment_ID ) );
		$this->assertStringContainsString( 'rel=', (string) $original->comment_content, 'Core should add rel to the stored link, which the raw text lacks.' );

		// Turn on hold-everything moderation now, so if the edit wrongly saw the
		// stored rel as a content change it would re-moderate and get held. The
		// rel-aware comparison must treat the resubmit as unchanged and stay approved.
		update_option( 'comment_moderation', '1' );

		// Resubmit the prefilled content verbatim (what the form shows, rel and all)
		// with only a new rating. It must not read as a content change and re-moderate.
		$response = $this->submit(
			$built['order'],
			array(
				array(
					'product_id'    => $built['product_id'],
					'order_item_id' => $built['item_id'],
					'rating'        => 2,
					'text'          => (string) $original->comment_content,
				),
			)
		);

		$this->assertSame( 'ok', $response['data']['results'][0]['status'] );
		$this->assertSame( 'approved', wp_get_comment_status( (int) $original->comment_ID ) );
		$this->assertSame( 2, (int) get_comment_meta( (int) $original->comment_ID, 'rating', true ) );
	}

	/**
	 * @testdox Editing a review whose author account was deleted updates it without fataling.
	 */
	public function test_editing_after_the_author_account_is_deleted_does_not_fatal(): void {
		require_once ABSPATH . 'wp-admin/includes/user.php';

		$built   = $this->make_order();
		$email   = $built['order']->get_billing_email();
		$user_id = self::factory()->user->create(
			array(
				'role'       => 'customer',
				'user_email' => $email,
			)
		);
		$built['order']->set_customer_id( $user_id );
		$built['order']->save();

		wp_set_current_user( $user_id );
		$original = $this->submit_single_review( $built, 'Solid purchase.' );
		$this->assertSame( $user_id, (int) $original->user_id, 'Review should be attributed to the customer account.' );

		// The account is deleted, then the customer edits via the order key as a guest.
		wp_delete_user( $user_id );
		wp_set_current_user( 0 );

		$response = $this->submit(
			$built['order'],
			array(
				array(
					'product_id'    => $built['product_id'],
					'order_item_id' => $built['item_id'],
					'rating'        => 4,
					'text'          => 'Even better on second use.',
				),
			)
		);

		$this->assertContains( $response['data']['results'][0]['status'] ?? null, array( 'ok', 'pending_moderation' ) );
		$this->assertSame( 4, (int) get_comment_meta( (int) $original->comment_ID, 'rating', true ) );
	}

	/**
	 * @testdox A customer is not locked out after their review is auto-rejected by the disallowed-keys list.
	 */
	public function test_customer_can_resubmit_after_an_auto_rejected_review(): void {
		update_option( 'disallowed_keys', 'casino-bonus' );
		$built = $this->make_order();

		$this->submit(
			$built['order'],
			array(
				array(
					'product_id'    => $built['product_id'],
					'order_item_id' => $built['item_id'],
					'rating'        => 5,
					'text'          => 'Visit casino-bonus now.',
				),
			)
		);

		$response = $this->submit(
			$built['order'],
			array(
				array(
					'product_id'    => $built['product_id'],
					'order_item_id' => $built['item_id'],
					'rating'        => 5,
					'text'          => 'A perfectly clean review.',
				),
			)
		);

		$this->assertSame( 'ok', $response['data']['results'][0]['status'] );
		// The auto-rejected row is reused, not stacked.
		$this->assertSame( 1, $this->count_comments( $built['product_id'] ) );
	}

	/**
	 * @testdox Repeated disallowed resubmissions reuse the same rejected row instead of stacking new ones.
	 */
	public function test_repeated_disallowed_resubmissions_do_not_stack_rows(): void {
		update_option( 'disallowed_keys', 'casino-bonus' );
		$built = $this->make_order();

		for ( $i = 0; $i < 3; $i++ ) {
			$response = $this->submit(
				$built['order'],
				array(
					array(
						'product_id'    => $built['product_id'],
						'order_item_id' => $built['item_id'],
						'rating'        => 5,
						'text'          => "Visit casino-bonus attempt {$i}.",
					),
				)
			);
			$this->assertSame( 'pending_moderation', $response['data']['results'][0]['status'] );
		}

		// Rows stay in trash, which the default comment count excludes, so count
		// across every status to prove the table didn't grow.
		$total = (int) get_comments(
			array(
				'post_id' => $built['product_id'],
				'status'  => array( 'approve', 'hold', 'spam', 'trash' ),
				'count'   => true,
			)
		);
		$this->assertSame( 1, $total, 'Every disallowed retry must reuse one row, not grow the table.' );
	}

	/**
	 * @testdox A moderator confirming an auto-rejected review as spam blocks further resubmission.
	 */
	public function test_moderator_confirming_an_auto_rejected_review_blocks_resubmission(): void {
		update_option( 'disallowed_keys', 'casino-bonus' );
		$built = $this->make_order();

		$response   = $this->submit(
			$built['order'],
			array(
				array(
					'product_id'    => $built['product_id'],
					'order_item_id' => $built['item_id'],
					'rating'        => 5,
					'text'          => 'Visit casino-bonus now.',
				),
			)
		);
		$comment_id = (int) $response['data']['results'][0]['comment_id'];

		// A moderator confirms the automatic rejection as spam; that transition
		// clears the automatic tag, so the verdict becomes final.
		wp_spam_comment( $comment_id );
		update_option( 'disallowed_keys', '' );

		$retry = $this->submit(
			$built['order'],
			array(
				array(
					'product_id'    => $built['product_id'],
					'order_item_id' => $built['item_id'],
					'rating'        => 5,
					'text'          => 'A perfectly clean review.',
				),
			)
		);

		$this->assertSame( 'update_failed', $retry['data']['results'][0]['error'] ?? null );
	}

	/**
	 * @testdox A moderator's trash verdict still blocks the customer from resubmitting.
	 */
	public function test_moderator_rejection_still_blocks_resubmission(): void {
		$built  = $this->make_order();
		$review = $this->submit_single_review( $built, 'A genuine review.' );

		// A moderator trashes it; this is not an automatic pipeline verdict.
		wp_trash_comment( (int) $review->comment_ID );

		$response = $this->submit(
			$built['order'],
			array(
				array(
					'product_id'    => $built['product_id'],
					'order_item_id' => $built['item_id'],
					'rating'        => 5,
					'text'          => 'Trying again.',
				),
			)
		);

		$this->assertSame( 'update_failed', $response['data']['results'][0]['error'] ?? null );
	}

	/**
	 * @testdox Two variations of one product reviewed with identical text are both stored.
	 */
	public function test_identical_text_on_two_variations_is_not_a_duplicate(): void {
		$variable   = WC_Helper_Product::create_variation_product();
		$variations = array_slice( $variable->get_children(), 0, 2 );
		$order      = $this->make_order_for_products( array_map( 'wc_get_product', $variations ) );

		$rows = array();
		foreach ( array_values( $order->get_items() ) as $item ) {
			$rows[] = array(
				'product_id'    => $item->get_variation_id(),
				'order_item_id' => $item->get_id(),
				'rating'        => 5,
				'text'          => 'Great.',
			);
		}

		$response = $this->submit( $order, $rows );

		$this->assertSame( array( 'ok', 'ok' ), array_column( $response['data']['results'], 'status' ) );
	}

	/**
	 * @testdox A review whose text exceeds the column size does not break the handler.
	 */
	public function test_oversized_text_does_not_break_the_handler(): void {
		$built = $this->make_order();

		$response = $this->submit_row( $built, array( 'text' => str_repeat( '<b>x</b>', 12000 ) ) );

		$this->assertTrue( $response['success'] );
		$this->assertArrayHasKey( 0, $response['data']['results'] );
	}

	/**
	 * Submit one review for the order's first item and return the stored comment.
	 *
	 * @param array  $built Order fixture from make_order().
	 * @param string $text  Review text.
	 * @return WP_Comment
	 */
	private function submit_single_review( array $built, string $text ): WP_Comment {
		$response = $this->submit_row( $built, array( 'text' => $text ) );

		$comment = get_comment( $this->first_comment_id( $response ) );
		$this->assertInstanceOf( WP_Comment::class, $comment, 'Submission should store a review: ' . wp_json_encode( $response ) );

		return $comment;
	}

	/**
	 * Submit a single review row for the order's first item and return the response.
	 *
	 * The row defaults to a valid 5-star review; $row overrides or adds fields (including
	 * smuggled comment fields), and $extra_post adds top-level POST fields.
	 *
	 * @param array $built      Order fixture from make_order().
	 * @param array $row        Row fields merged over the defaults.
	 * @param array $extra_post Extra top-level POST fields.
	 * @return array{success:bool, data:mixed}
	 */
	private function submit_row( array $built, array $row = array(), array $extra_post = array() ): array {
		$row = array_merge(
			array(
				'product_id'    => $built['product_id'],
				'order_item_id' => $built['item_id'],
				'rating'        => 5,
				'text'          => 'Nice.',
			),
			$row
		);

		return $this->submit( $built['order'], array( $row ), $extra_post );
	}

	/**
	 * Assert the stored review and its public renderings only contain allowed markup.
	 *
	 * @param WP_Comment $comment Stored review.
	 * @param array      $built   Order fixture from make_order().
	 */
	private function assert_review_is_safe( WP_Comment $comment, array $built ): void {
		$comment_tags = wp_kses_allowed_html( 'pre_comment_content' );

		$stored_allowed = $this->attribute_allowlist( $comment_tags, array( 'a' => array( 'rel' ) ) );
		$this->assert_html_allowed( (string) $comment->comment_content, $stored_allowed, 'stored content' );

		$rendered_allowed = $this->attribute_allowlist(
			$comment_tags,
			array(
				'a'  => array( 'rel' ),
				'p'  => array(),
				'br' => array(),
			)
		);
		$rendered         = (string) apply_filters( 'comment_text', get_comment_text( $comment ), $comment, array() );
		$this->assert_html_allowed( $rendered, $rendered_allowed, 'comment_text output' );

		$store_review = $this->get_store_api_review( (int) $comment->comment_ID, $built['product_id'] );
		$this->assertNotNull( $store_review, 'Approved review must be listed by the Store API.' );
		$this->assert_html_allowed( (string) $store_review['review'], $rendered_allowed, 'Store API review' );
	}

	/**
	 * Assert every tag and attribute in the HTML is allowed and URLs use allowed protocols.
	 *
	 * @param string                  $html    HTML to inspect.
	 * @param array<string, string[]> $allowed Tag name => allowed attribute names.
	 * @param string                  $context Label for failure messages.
	 */
	private function assert_html_allowed( string $html, array $allowed, string $context ): void {
		$processor = new WP_HTML_Tag_Processor( $html );
		while ( $processor->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
			$tag = strtolower( (string) $processor->get_tag() );
			$this->assertArrayHasKey( $tag, $allowed, "Unexpected <{$tag}> in {$context}: {$html}" );
			if ( $processor->is_tag_closer() ) {
				continue;
			}

			foreach ( (array) $processor->get_attribute_names_with_prefix( '' ) as $name ) {
				$this->assertContains( $name, $allowed[ $tag ], "Unexpected {$name} attribute on <{$tag}> in {$context}: {$html}" );

				$value = $processor->get_attribute( $name );
				if ( in_array( $name, array( 'href', 'cite', 'src' ), true ) && is_string( $value ) ) {
					$compact = (string) preg_replace( '/[\x00-\x20]+/', '', $value );
					if ( preg_match( '/^([a-z][a-z0-9+.\-]*):/i', $compact, $matches ) ) {
						$this->assertContains( strtolower( $matches[1] ), wp_allowed_protocols(), "Disallowed protocol in {$name} in {$context}: {$html}" );
					}
				}
			}
		}
	}

	/**
	 * Build a tag => attribute names map from a kses allow-list plus extras.
	 *
	 * @param array                   $kses_tags kses allow-list.
	 * @param array<string, string[]> $extra     Extra tags or attributes.
	 * @return array<string, string[]>
	 */
	private function attribute_allowlist( array $kses_tags, array $extra ): array {
		$allowed = array();
		foreach ( $kses_tags as $tag => $attributes ) {
			$allowed[ $tag ] = array_map( 'strtolower', array_keys( (array) $attributes ) );
		}
		foreach ( $extra as $tag => $attributes ) {
			$allowed[ $tag ] = array_merge( $allowed[ $tag ] ?? array(), $attributes );
		}

		return $allowed;
	}

	/**
	 * Tag names and attribute names in document order, ignoring text and attribute values.
	 *
	 * @param string $html HTML to summarise.
	 * @return string[]
	 */
	private function tag_signature( string $html ): array {
		$signature = array();
		$processor = new WP_HTML_Tag_Processor( $html );
		while ( $processor->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
			$names = (array) $processor->get_attribute_names_with_prefix( '' );
			sort( $names );
			$signature[] = ( $processor->is_tag_closer() ? '/' : '' ) . $processor->get_tag() . '[' . implode( ',', $names ) . ']';
		}

		return $signature;
	}

	/**
	 * Render the Review Order row template with the given prefilled text.
	 *
	 * @param array  $built Order fixture from make_order().
	 * @param string $text  Prefilled review text.
	 * @return string
	 */
	private function render_row( array $built, string $text ): string {
		$item = $built['order']->get_item( $built['item_id'] );

		return wc_get_template_html(
			'order/customer-review-order-row.php',
			array(
				'item'            => $item,
				'product'         => wc_get_product( $built['product_id'] ),
				'order'           => $built['order'],
				'row_index'       => 0,
				'existing_rating' => 5,
				'existing_text'   => $text,
			)
		);
	}

	/**
	 * Find a review in the public Store API reviews listing.
	 *
	 * @param int $comment_id Review ID.
	 * @param int $product_id Product ID.
	 * @return array|null
	 */
	private function get_store_api_review( int $comment_id, int $product_id ): ?array {
		$request = new WP_REST_Request( 'GET', '/wc/store/v1/products/reviews' );
		$request->set_param( 'product_id', (string) $product_id );
		$request->set_param( 'per_page', 100 );
		$response = rest_get_server()->dispatch( $request );

		foreach ( (array) $response->get_data() as $review ) {
			if ( (int) ( $review['id'] ?? 0 ) === $comment_id ) {
				return $review;
			}
		}

		return null;
	}

	/**
	 * Count all comments on a product.
	 *
	 * @param int $product_id Product ID.
	 * @return int
	 */
	private function count_comments( int $product_id ): int {
		return (int) get_comments(
			array(
				'post_id' => $product_id,
				'count'   => true,
			)
		);
	}

	/**
	 * Build a completed order whose billing fields and request metadata come from the attacker.
	 *
	 * @param array $overrides     Optional first_name, last_name, email, ip, agent and customer_id.
	 * @param int   $product_count How many simple products to add.
	 * @return array{order:WC_Order, product_id:int, item_id:int, product_ids:int[], item_ids:int[]}
	 */
	private function make_order( array $overrides = array(), int $product_count = 1 ): array {
		$products = array();
		for ( $i = 0; $i < $product_count; $i++ ) {
			$products[] = WC_Helper_Product::create_simple_product();
		}

		$order = $this->make_order_for_products( $products, $overrides );

		$product_ids = array();
		$item_ids    = array();
		foreach ( $order->get_items() as $item ) {
			$product_ids[] = (int) $item->get_product_id();
			$item_ids[]    = (int) $item->get_id();
		}

		return array(
			'order'       => $order,
			'product_id'  => $product_ids[0],
			'item_id'     => $item_ids[0],
			'product_ids' => $product_ids,
			'item_ids'    => $item_ids,
		);
	}

	/**
	 * Build a completed order for the given products.
	 *
	 * @param \WC_Product[] $products  Products to add.
	 * @param array         $overrides Optional first_name, last_name, email, ip, agent and customer_id.
	 * @return WC_Order
	 */
	private function make_order_for_products( array $products, array $overrides = array() ): WC_Order {
		$order = new WC_Order();
		$order->set_customer_id( (int) ( $overrides['customer_id'] ?? 0 ) );
		$order->set_billing_first_name( $overrides['first_name'] ?? 'Jane' );
		$order->set_billing_last_name( $overrides['last_name'] ?? 'Doe' );
		$order->set_billing_email( $overrides['email'] ?? 'jane@example.test' );
		$order->set_customer_ip_address( $overrides['ip'] ?? '203.0.113.7' );
		$order->set_customer_user_agent( $overrides['agent'] ?? 'Mozilla/5.0' );
		$order->set_status( OrderStatus::COMPLETED );
		foreach ( $products as $product ) {
			$order->add_product( $product, 1 );
		}
		$order->save();

		return $order;
	}

	/**
	 * Post rows to the handler for the order and capture the JSON response.
	 *
	 * @param WC_Order $order      Order being reviewed.
	 * @param array    $rows       Raw `reviews` payload.
	 * @param array    $extra_post Extra top-level POST fields.
	 * @return array{success:bool, data:mixed}
	 */
	private function submit( WC_Order $order, array $rows, array $extra_post = array() ): array {
		$_POST = wp_slash(
			array_merge(
				$extra_post,
				array(
					'order_id' => $order->get_id(),
					'key'      => $order->get_order_key(),
					'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
					'reviews'  => $rows,
				)
			)
		);

		// wp_send_json_*() ends in wp_die(); throw like WP_Ajax_UnitTestCase so execution stops there.
		$die_handler = function () {
			return function ( $message ) {
				throw new WPAjaxDieContinueException( esc_html( (string) $message ) );
			};
		};
		add_filter( 'wp_die_ajax_handler', $die_handler );
		add_filter( 'wp_doing_ajax', '__return_true' );

		ob_start();
		try {
			( new SubmissionHandler() )->handle();
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		} finally {
			$body = (string) ob_get_clean();
			remove_filter( 'wp_die_ajax_handler', $die_handler );
			remove_filter( 'wp_doing_ajax', '__return_true' );
		}

		$decoded = json_decode( $body, true );

		return array(
			'success' => is_array( $decoded ) && ! empty( $decoded['success'] ),
			'data'    => is_array( $decoded ) ? ( $decoded['data'] ?? null ) : null,
		);
	}

	/**
	 * Comment ID of the first row in a handler response.
	 *
	 * @param array $response Handler response.
	 * @return int
	 */
	private function first_comment_id( array $response ): int {
		$results = (array) ( $response['data']['results'] ?? array() );
		$first   = reset( $results );

		return (int) ( is_array( $first ) ? ( $first['comment_id'] ?? 0 ) : 0 );
	}
}
