<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WP_UnitTestCase;

/**
 * Tests for the Customer Account block.
 */
class CustomerAccountTest extends WP_UnitTestCase {

	/**
	 * User ID for tests.
	 *
	 * @var int
	 */
	private int $user_id;

	/**
	 * Original show_avatars option value.
	 *
	 * @var mixed
	 */
	private $original_show_avatars;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user_id               = $this->factory->user->create();
		$this->original_show_avatars = get_option( 'show_avatars' );
		update_option( 'show_avatars', 1 );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'pre_get_avatar_data' );
		wp_set_current_user( 0 );
		wp_delete_user( $this->user_id );
		update_option( 'show_avatars', $this->original_show_avatars );
		parent::tearDown();
	}

	/**
	 * Render the Customer Account block via do_blocks().
	 *
	 * @param string $attrs JSON object string for block attributes.
	 * @return string Rendered markup.
	 */
	private function render_customer_account( string $attrs = '' ): string {
		return do_blocks( "<!-- wp:woocommerce/customer-account {$attrs} /-->" );
	}

	/**
	 * @testdox Should render the default account icon when the user is not logged in.
	 */
	public function test_renders_icon_when_user_is_not_logged_in(): void {
		wp_set_current_user( 0 );

		$markup = $this->render_customer_account(
			'{"iconClass":"wc-block-customer-account__account-icon"}'
		);

		$this->assertStringContainsString( '<svg', $markup );
		$this->assertStringNotContainsString( 'wc-block-customer-account__avatar', $markup );
	}

	/**
	 * @testdox Should render the user avatar when a custom avatar is available.
	 */
	public function test_renders_avatar_when_user_has_custom_avatar(): void {
		wp_set_current_user( $this->user_id );

		add_filter(
			'pre_get_avatar_data',
			function ( $args ) {
				$args['url'] = 'https://example.com/custom-avatar.jpg';
				return $args;
			}
		);

		$markup = $this->render_customer_account(
			'{"iconClass":"wc-block-customer-account__account-icon"}'
		);

		$this->assertStringContainsString( 'wc-block-customer-account__avatar', $markup );
		$this->assertStringContainsString( 'custom-avatar.jpg', $markup );
	}

	/**
	 * @testdox Should not render avatar markup when show_avatars is disabled.
	 */
	public function test_does_not_render_avatar_when_show_avatars_is_disabled(): void {
		wp_set_current_user( $this->user_id );
		update_option( 'show_avatars', 0 );

		add_filter(
			'pre_get_avatar_data',
			function ( $args ) {
				$args['url'] = 'https://example.com/custom-avatar.jpg';
				return $args;
			}
		);

		$markup = $this->render_customer_account(
			'{"iconClass":"wc-block-customer-account__account-icon"}'
		);

		$this->assertStringNotContainsString( 'wc-block-customer-account__avatar', $markup );
		$this->assertStringNotContainsString( 'custom-avatar.jpg', $markup );
	}

	/**
	 * Data provider for displayStyle attribute tests.
	 *
	 * @return array<string, array{string, bool, bool, bool}>
	 */
	public function provider_display_style(): array {
		return array(
			'icon and text' => array( 'icon_and_text', true, true, false ),
			'text only'     => array( 'text_only', false, true, false ),
			'icon only'     => array( 'icon_only', true, false, true ),
		);
	}

	/**
	 * @testdox Should render icon, label, and aria-label according to displayStyle.
	 *
	 * @dataProvider provider_display_style
	 *
	 * @param string $display_style Display style attribute.
	 * @param bool   $has_icon      Whether an SVG icon is expected.
	 * @param bool   $has_label     Whether a text label is expected.
	 * @param bool   $has_aria      Whether an aria-label is expected.
	 */
	public function test_display_style( string $display_style, bool $has_icon, bool $has_label, bool $has_aria ): void {
		$markup = $this->render_customer_account(
			'{"displayStyle":"' . $display_style . '","iconClass":"wc-block-customer-account__account-icon"}'
		);

		$this->assertSame( $has_icon, str_contains( $markup, '<svg' ) );
		$this->assertSame( $has_label, str_contains( $markup, 'class="label"' ) );
		$this->assertSame( $has_aria, str_contains( $markup, 'aria-label=' ) );
	}
}
