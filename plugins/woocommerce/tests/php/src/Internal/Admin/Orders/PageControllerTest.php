<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\Orders {

	use Automattic\WooCommerce\Internal\Admin\Orders\PageController;
	use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
	use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
	use Automattic\WooCommerce\Utilities\OrderUtil;

	/**
	 * Tests related to the HPOS orders admin pages controller.
	 */
	class PageControllerTest extends \WC_Unit_Test_Case {
		use HPOSToggleTrait;

		/**
		 * Previous HPOS state.
		 *
		 * @var bool
		 */
		private static bool $hpos_prev_state;

		/**
		 * @var int ID of test admin user.
		 */
		private $user_admin;

		/**
		 * Set up class fixtures.
		 */
		public static function setUpBeforeClass(): void {
			parent::setUpBeforeClass();

			self::$hpos_prev_state = OrderUtil::custom_orders_table_usage_is_enabled();
			add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
			OrderHelper::create_order_custom_table_if_not_exist();

			if ( self::$hpos_prev_state ) {
				OrderHelper::toggle_cot_feature_and_usage( false );
			}
		}

		/**
		 * Tear down class fixtures.
		 */
		public static function tearDownAfterClass(): void {
			if ( OrderUtil::custom_orders_table_usage_is_enabled() !== self::$hpos_prev_state ) {
				OrderHelper::toggle_cot_feature_and_usage( self::$hpos_prev_state );
			}

			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );

			parent::tearDownAfterClass();
		}

		/**
		 * Set up before each test.
		 *
		 * @return void
		 */
		public function setUp(): void {
			parent::setUp();
			$this->toggle_cot_feature_and_usage( false );

			$this->user_admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
			wp_set_current_user( $this->user_admin );

			global $mock_filter_input;
			$mock_filter_input = false;
		}

		/**
		 * @testDox Basic order screen detection works.
		 */
		public function test_is_order_screen_any() {
			set_current_screen();

			$controller = new PageController();
			$screen     = get_current_screen();

			$screen->post_type = 'shop_order';
			$this->assertTrue( $controller->is_order_screen() );

			$screen->post_type = 'post';
			$this->assertFalse( $controller->is_order_screen() );

			$this->toggle_cot_feature_and_usage( true );
			global $pagenow, $plugin_page;

			$controller  = new PageController();
			$pagenow     = 'admin.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$plugin_page = 'wc-orders'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$controller->setup();
			$this->assertTrue( $controller->is_order_screen() );

			$controller = new PageController();
			$pagenow    = 'edit.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$controller->setup();
			$this->assertFalse( $controller->is_order_screen() );
		}

		/**
		 * @testDox Order list table screen detection works.
		 */
		public function test_is_order_screen_list() {
			set_current_screen();

			$controller = new PageController();
			$screen     = get_current_screen();

			$screen->post_type = 'shop_order';
			$screen->base      = 'edit';
			$this->assertTrue( $controller->is_order_screen( 'shop_order', 'list' ) );

			$screen->base = 'post';
			$this->assertFalse( $controller->is_order_screen( 'shop_order', 'list' ) );

			$this->toggle_cot_feature_and_usage( true );
			global $pagenow, $plugin_page;

			$controller     = new PageController();
			$pagenow        = 'admin.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$plugin_page    = 'wc-orders'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$_GET['action'] = '';
			$controller->setup();
			$this->assertTrue( $controller->is_order_screen( 'shop_order', 'list' ) );

			$controller     = new PageController();
			$_GET['action'] = 'edit';
			$controller->setup();
			$this->assertFalse( $controller->is_order_screen( 'shop_order', 'list' ) );
		}

		/**
		 * @testDox Edit Order screen detection works.
		 */
		public function test_is_order_screen_edit() {
			global $mock_filter_input, $mock_return;
			$mock_filter_input = true;
			set_current_screen();

			$controller = new PageController();
			$screen     = get_current_screen();

			$screen->post_type = 'shop_order';
			$screen->base      = 'post';
			$mock_return       = 123;
			$this->assertTrue( $controller->is_order_screen( 'shop_order', 'edit' ) );

			$mock_filter_input = false;
			$this->assertFalse( $controller->is_order_screen( 'shop_order', 'edit' ) );

			$this->toggle_cot_feature_and_usage( true );
			global $pagenow, $plugin_page;

			$controller     = new PageController();
			$pagenow        = 'admin.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$plugin_page    = 'wc-orders'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$_GET['action'] = 'edit';
			$controller->setup();
			$this->assertTrue( $controller->is_order_screen( 'shop_order', 'edit' ) );

			$controller     = new PageController();
			$_GET['action'] = 'new';
			$controller->setup();
			$this->assertFalse( $controller->is_order_screen( 'shop_order', 'edit' ) );
		}

		/**
		 * @testDox Add New Order screen detection works.
		 */
		public function test_is_order_screen_new() {
			set_current_screen();

			$controller = new PageController();
			$screen     = get_current_screen();

			$screen->post_type = 'shop_order';
			$screen->base      = 'post';
			$screen->action    = 'add';
			$this->assertTrue( $controller->is_order_screen( 'shop_order', 'new' ) );

			$screen->action = '';
			$this->assertFalse( $controller->is_order_screen( 'shop_order', 'new' ) );

			$this->toggle_cot_feature_and_usage( true );
			global $pagenow, $plugin_page;

			$controller     = new PageController();
			$pagenow        = 'admin.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$plugin_page    = 'wc-orders'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$_GET['action'] = 'new';
			$controller->setup();
			$this->assertTrue( $controller->is_order_screen( 'shop_order', 'new' ) );

			$controller     = new PageController();
			$_GET['action'] = 'edit';
			$controller->setup();
			$this->assertFalse( $controller->is_order_screen( 'shop_order', 'new' ) );
		}
	}
}

/**
 * Mocks for global functions used in PageController
 */
namespace Automattic\WooCommerce\Internal\Admin\Orders {
	/**
	 * The filter_input function will return NULL if we change the $_GET or $_POST variables at runtime, so we
	 * need to override it in PageController's namespace when we want it to return a specific value for testing.
	 *
	 * @return mixed
	 */
	function filter_input() {
		global $mock_filter_input, $mock_return;

		if ( true === $mock_filter_input ) {
			return $mock_return;
		} else {
			return call_user_func_array( '\filter_input', func_get_args() );
		}
	}
}
