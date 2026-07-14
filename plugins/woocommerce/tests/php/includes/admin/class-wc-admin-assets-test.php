<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Admin_Assets.
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Admin_Assets_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Admin_Assets
	 */
	private $sut;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WC_Admin_Assets();
		$this->sut->register_scripts();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		unset( $_GET['page'] );
		wp_dequeue_script( 'woocommerce_admin' );
		wp_dequeue_script( 'heartbeat' );
		parent::tearDown();
	}

	/**
	 * @testdox Should set up the lost connection notice and heartbeat correctly per screen, and never re-enqueue autosave.
	 * @testWith ["woocommerce_page_wc-orders", "woocommerce_page_wc-orders", "", false, true]
	 *           ["shop_order", "post", "shop_order", false, true]
	 *           ["product", "post", "product", false, false]
	 *           ["toplevel_page_woocommerce", "toplevel_page_woocommerce", "", true, false]
	 *
	 * @param string $screen_id   Screen id.
	 * @param string $base        Screen base.
	 * @param string $post_type   Screen post type, if any.
	 * @param bool   $is_wc_admin Whether to simulate a wc-admin React page.
	 * @param bool   $expected    Expected show_lost_connection_notice value.
	 */
	public function test_admin_scripts_lost_connection_notice_setup( string $screen_id, string $base, string $post_type, bool $is_wc_admin, bool $expected ): void {
		set_current_screen();
		$screen            = get_current_screen();
		$screen->id        = $screen_id;
		$screen->base      = $base;
		$screen->post_type = $post_type;
		$_GET['page']      = $is_wc_admin ? 'wc-admin' : '';

		$this->sut->admin_scripts();

		$localized = wp_scripts()->get_data( 'woocommerce_admin', 'data' );
		$this->assertIsString( $localized, 'woocommerce_admin should be localized on this screen' );
		$this->assertStringContainsString(
			'"show_lost_connection_notice":"' . ( $expected ? '1' : '' ) . '"',
			$localized,
			'show_lost_connection_notice should be ' . ( $expected ? 'true' : 'false' ) . " for screen '{$screen_id}'"
		);

		$this->assertSame(
			$expected,
			wp_scripts()->query( 'heartbeat', 'enqueued' ),
			'heartbeat should be enqueued exactly on the screens that use the notice'
		);

		$this->assertFalse(
			wp_scripts()->query( 'autosave', 'enqueued' ),
			'autosave must never be enqueued, since it turns on unrelated post.js handlers'
		);
	}

	/**
	 * @testdox Should render the lost connection notice markup only where expected.
	 * @testWith ["woocommerce_page_wc-orders", "woocommerce_page_wc-orders", "", true]
	 *           ["shop_order", "post", "shop_order", true]
	 *           ["product", "post", "product", false]
	 *
	 * @param string $screen_id     Screen id.
	 * @param string $base          Screen base.
	 * @param string $post_type     Screen post type, if any.
	 * @param bool   $should_render Whether the markup should render at all.
	 */
	public function test_render_lost_connection_notice_markup( string $screen_id, string $base, string $post_type, bool $should_render ): void {
		set_current_screen();
		$screen            = get_current_screen();
		$screen->id        = $screen_id;
		$screen->base      = $base;
		$screen->post_type = $post_type;

		ob_start();
		$this->sut->render_lost_connection_notice();
		$output = ob_get_clean();

		if ( ! $should_render ) {
			$this->assertSame( '', $output, "No notice should render for screen '{$screen_id}'" );
			return;
		}

		$this->assertStringContainsString( 'id="wc-lost-connection-notice"', $output );
	}
}
