<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Order_Badge;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Order_Badge
 */
class OrderBadgeTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Order_Badge
	 */
	private $sut;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new Order_Badge();
	}

	public function tearDown(): void {
		// Order_Badge registers an admin_menu hook on construction; remove only
		// ours so it doesn't leak into other suites.
		remove_action( 'admin_menu', array( $this->sut, 'apply' ), PHP_INT_MAX );
		parent::tearDown();
	}

	/**
	 * Invoke a private Order_Badge method.
	 *
	 * The public entry point apply() depends on wc_processing_order_count() and
	 * live order data; the badge formatters and append helpers are pure and
	 * worth covering directly.
	 *
	 * @param string $method Method name.
	 * @param array  $args   Arguments.
	 * @return mixed
	 */
	private function invoke( string $method, array $args ) {
		$ref = new \ReflectionMethod( Order_Badge::class, $method );
		$ref->setAccessible( true );
		return $ref->invoke( $this->sut, ...$args );
	}

	/**
	 * Counts at or below the cap render as the formatted number, and the
	 * count-N class always carries the true value.
	 */
	public function test_badge_html_shows_raw_count_at_or_below_cap() {
		$html = $this->invoke( 'build_badge_html', array( 99 ) );

		$this->assertStringContainsString( 'menu-counter', $html );
		$this->assertStringContainsString( 'count-99', $html );
		$this->assertStringContainsString( '>99<', $html );
	}

	/**
	 * Counts above the cap display as "99+" to keep the bubble narrow, while
	 * the count-N class still records the true value.
	 */
	public function test_badge_html_caps_display_at_99_plus() {
		$html = $this->invoke( 'build_badge_html', array( 150 ) );

		$this->assertStringContainsString( '99+', $html );
		$this->assertStringContainsString( 'count-150', $html );
	}

	/**
	 * The top-level variant is a numberless attention dot (a numeric counter
	 * there would collide with WP's flyout arrow).
	 */
	public function test_dot_html_is_a_numberless_attention_dot() {
		$html = $this->invoke( 'build_dot_html', array() );

		$this->assertStringContainsString( 'wc-attention-dot', $html );
		$this->assertStringContainsString( 'aria-hidden="true"', $html );
		$this->assertStringNotContainsString( 'processing-count', $html );
	}

	/**
	 * Appending a badge to a $menu entry is idempotent: a second call must not
	 * stack a duplicate bubble when the title already carries one.
	 */
	public function test_append_to_menu_entry_is_idempotent() {
		global $menu;
		$menu_backup = $menu;
		$menu        = array(
			array( 'Orders', 'edit_others_shop_orders', 'wc-orders', 'Orders', '' ),
		);

		$badge = $this->invoke( 'build_badge_html', array( 3 ) );
		$this->invoke( 'append_to_menu_entry', array( 'wc-orders', $badge ) );
		$this->invoke( 'append_to_menu_entry', array( 'wc-orders', $badge ) );

		$this->assertSame(
			1,
			substr_count( (string) $menu[0][0], 'wc-order-attention' ),
			'Badge must be appended exactly once.'
		);

		$menu = $menu_backup;
	}
}
