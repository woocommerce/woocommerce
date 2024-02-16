<?php
/**
 * @package WooCommerce\Tests\WC_Shortcodes
 */

/**
 * Class WC_Shortcodes_Test.
 */
class WC_Shortcodes_Test extends WC_Unit_Test_Case {

	/**
	 * An administrator user.
	 *
	 * @var WP_User
	 */
	protected static $user_administrator;

	/**
	 * A contributor user.
	 *
	 * @var WP_User
	 */
	protected static $user_contributor;

	/**
	 * Setup once before running tests.
	 *
	 * @param object $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$user_administrator = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		self::$user_contributor   = $factory->user->create(
			array(
				'role' => 'contributor',
			)
		);
	}

	/**
	 * Setup before each test method.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::$user_administrator );
	}

	/**
	 * Disable the "Unexpected deprecation notice for Theme without comments.php." message that makes the test fail but isn't relevant in this context.
	 */
	private function disable_deprecation_notice() {
		remove_action( 'deprecated_file_included', array( $this, 'deprecated_function_run' ), 10, 4 );
	}

	/**
	 * Enable the deprecation notice again.
	 */
	private function enable_deprecation_notice() {
		add_action( 'deprecated_file_included', array( $this, 'deprecated_function_run' ), 10, 4 );
	}

	/**
	 * Ensure the `product_page` shortcode renders a published product correctly.
	 */
	public function test_product_page_shortcode_published_product() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->save();
		$product_id = $product->get_id();
		$this->disable_deprecation_notice();

		$product_page = WC_Shortcodes::product_page(
			array(
				'id' => $product_id,
			)
		);

		$this->enable_deprecation_notice();

		$this->assertNotEmpty( $product->get_name() );
		$this->assertStringContainsString( $product->get_name(), $product_page );
	}

	/**
	 * Ensure the `product_page` shortcode renders a published product correctly even with status set to `any`.
	 */
	public function test_product_page_shortcode_published_product_status_any() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->save();
		$product_id = $product->get_id();
		wp_set_current_user( 0 );
		$this->disable_deprecation_notice();

		$product_page = WC_Shortcodes::product_page(
			array(
				'id'     => $product_id,
				'status' => 'any',
			)
		);

		$this->enable_deprecation_notice();

		$this->assertNotEmpty( $product->get_name() );
		$this->assertStringContainsString( $product->get_name(), $product_page );
	}

	/**
	 * Ensure the `product_page` shortcode renders a published product correctly when given an SKU.
	 */
	public function test_product_page_shortcode_published_product_by_sku() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->set_sku( 'test-sku' );
		$product->save();
		$this->disable_deprecation_notice();

		$product_page = WC_Shortcodes::product_page(
			array(
				'sku' => 'test-sku',
			)
		);

		$this->enable_deprecation_notice();

		$this->assertNotEmpty( $product->get_name() );
		$this->assertStringContainsString( $product->get_name(), $product_page );
	}

	/**
	 * Ensure the `product_page` shortcode renders a public, but hidden product belonging to another user.
	 */
	public function test_product_page_shortcode_hidden_product() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->set_catalog_visibility( 'hidden' );
		$product->save();
		$product_id = $product->get_id();
		wp_set_current_user( self::$user_contributor );
		$this->disable_deprecation_notice();

		$product_page = WC_Shortcodes::product_page(
			array(
				'id' => $product_id,
			)
		);

		$this->enable_deprecation_notice();

		$this->assertNotEmpty( $product->get_name() );
		$this->assertStringContainsString( $product->get_name(), $product_page );
	}

	/**
	 * Ensure the `product_page` shortcode does not render a trashed product.
	 */
	public function test_product_page_shortcode_trashed_product() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->save();
		$product_id = $product->get_id();
		wp_trash_post( $product_id );

		$product_page = WC_Shortcodes::product_page(
			array(
				'id'     => $product_id,
				'status' => 'trash',
			)
		);

		$this->assertEmpty( $product_page );
	}

	/**
	 * Ensure the `product_page` shortcode does not render a trashed product given by SKU.
	 */
	public function test_product_page_shortcode_trashed_product_sku() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->set_sku( 'test-sku' );
		$product->save();
		$product_id = $product->get_id();
		wp_trash_post( $product_id );

		$product_page = WC_Shortcodes::product_page(
			array(
				'sku'    => 'test-sku',
				'status' => 'trash',
			)
		);

		$this->assertEmpty( $product_page );
	}

	/**
	 * Ensure we can override the list of invalid statuses for the `product_page` shortcode so that a trashed product is rendered.
	 */
	public function test_product_page_shortcode_trashed_product_override() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->save();
		$product_id = $product->get_id();
		wp_trash_post( $product_id );
		add_filter( 'woocommerce_shortcode_product_page_invalid_statuses', '__return_empty_array' );
		$this->disable_deprecation_notice();

		$product_page = WC_Shortcodes::product_page(
			array(
				'id'     => $product_id,
				'status' => 'trash',
			)
		);

		$this->enable_deprecation_notice();

		$this->assertNotEmpty( $product_page );

		remove_filter( 'woocommerce_shortcode_product_page_invalid_statuses', '__return_empty_array' );
	}

	/**
	 * Ensure the `product_page` shortcode does not render a draft product belonging to another user.
	 */
	public function test_product_page_shortcode_draft_product() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->set_status( 'draft' );
		$product->save();
		$product_id = $product->get_id();
		$this->disable_deprecation_notice();

		$product_page = WC_Shortcodes::product_page(
			array(
				'id'     => $product_id,
				'status' => 'draft',
			)
		);

		$this->enable_deprecation_notice();

		$this->assertNotEmpty( $product_page );

		wp_set_current_user( self::$user_contributor );

		$product_page = WC_Shortcodes::product_page(
			array(
				'id'     => $product_id,
				'status' => 'draft',
			)
		);

		$this->assertEmpty( $product_page );
	}

	/**
	 * Ensure the `product_page` shortcode does not render a private product belonging to another user.
	 */
	public function test_product_page_shortcode_private_product() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->set_status( 'private' );
		$product->save();
		$product_id = $product->get_id();
		$this->disable_deprecation_notice();

		$product_page = WC_Shortcodes::product_page(
			array(
				'id'     => $product_id,
				'status' => 'private',
			)
		);

		$this->enable_deprecation_notice();

		$this->assertNotEmpty( $product_page );

		wp_set_current_user( self::$user_contributor );

		$product_page = WC_Shortcodes::product_page(
			array(
				'id'     => $product_id,
				'status' => 'private',
			)
		);

		$this->assertEmpty( $product_page );
	}

	/**
	 * Ensure the `product_page` shortcode does not render a private product that is given by SKU and belongs to another user.
	 */
	public function test_product_page_shortcode_private_product_by_sku() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->set_status( 'private' );
		$product->set_sku( 'test-sku' );
		$product->save();
		$this->disable_deprecation_notice();

		$product_page = WC_Shortcodes::product_page(
			array(
				'sku'    => 'test-sku',
				'status' => 'private',
			)
		);

		$this->enable_deprecation_notice();

		$this->assertNotEmpty( $product_page );

		wp_set_current_user( self::$user_contributor );

		$product_page = WC_Shortcodes::product_page(
			array(
				'sku'    => 'test-sku',
				'status' => 'private',
			)
		);

		$this->assertEmpty( $product_page );
	}

	/**
	 * Ensure we can override the `product_page` shortcode's read permission check to allow rendering a private product belonging to another user.
	 */
	public function test_product_page_shortcode_private_product_override() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->set_status( 'private' );
		$product->save();
		$product_id = $product->get_id();
		$this->disable_deprecation_notice();

		$product_page = WC_Shortcodes::product_page(
			array(
				'id'     => $product_id,
				'status' => 'private',
			)
		);

		$this->enable_deprecation_notice();

		$this->assertNotEmpty( $product_page );

		wp_set_current_user( self::$user_contributor );
		add_filter( 'woocommerce_shortcode_product_page_force_rendering', '__return_true' );
		$this->disable_deprecation_notice();

		$product_page = WC_Shortcodes::product_page(
			array(
				'id'     => $product_id,
				'status' => 'private',
			)
		);

		$this->enable_deprecation_notice();

		$this->assertNotEmpty( $product_page );

		remove_filter( 'woocommerce_shortcode_product_page_force_rendering', '__return_true' );
		add_filter( 'woocommerce_shortcode_product_page_force_rendering', '__return_null' );

		$product_page = WC_Shortcodes::product_page(
			array(
				'id'     => $product_id,
				'status' => 'private',
			)
		);

		$this->assertEmpty( $product_page );

		remove_filter( 'woocommerce_shortcode_product_page_force_rendering', '__return_null' );
	}

	/**
	 * Ensure we can override the `product_page` shortcode's read permission check to block rendering a public product.
	 */
	public function test_product_page_shortcode_public_product_override() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->save();
		$product_id = $product->get_id();
		$this->disable_deprecation_notice();

		$product_page = WC_Shortcodes::product_page(
			array(
				'id' => $product_id,
			)
		);

		$this->enable_deprecation_notice();

		$this->assertNotEmpty( $product_page );

		add_filter( 'woocommerce_shortcode_product_page_force_rendering', '__return_false' );

		$product_page = WC_Shortcodes::product_page(
			array(
				'id'     => $product_id,
				'status' => 'private',
			)
		);

		$this->assertEmpty( $product_page );

		remove_filter( 'woocommerce_shortcode_product_page_force_rendering', '__return_false' );
	}

	/**
	 * Ensure the `product_page` shortcode does not render a pending product belonging to another user.
	 */
	public function test_product_page_shortcode_pending_product() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->set_status( 'pending' );
		$product->save();
		$product_id = $product->get_id();
		$this->disable_deprecation_notice();

		$product_page = WC_Shortcodes::product_page(
			array(
				'id'     => $product_id,
				'status' => 'pending',
			)
		);

		$this->enable_deprecation_notice();

		$this->assertNotEmpty( $product_page );

		wp_set_current_user( self::$user_contributor );

		$product_page = WC_Shortcodes::product_page(
			array(
				'id'     => $product_id,
				'status' => 'pending',
			)
		);

		$this->assertEmpty( $product_page );
	}

	/**
	 * Ensure the `product_page` shortcode renders the password prompt for a protected product belonging to any user.
	 */
	public function test_product_page_shortcode_protected_product() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->set_post_password( 'test password' );
		$product->save();
		$product_id = $product->get_id();

		$product_page = WC_Shortcodes::product_page(
			array(
				'id'         => $product_id,
				'visibility' => 'protected',
			)
		);

		$this->assertStringContainsString( 'This content is password protected', $product_page );

		wp_set_current_user( self::$user_contributor );

		$product_page = WC_Shortcodes::product_page(
			array(
				'id'         => $product_id,
				'visibility' => 'protected',
			)
		);

		$this->assertStringContainsString( 'This content is password protected', $product_page );
	}
}
