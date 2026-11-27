<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\ProductPageIntegration;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Tests\Blocks\Helpers\BlockTypeRegistryTrait;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsMocks;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\StockNotificationsFeatureTrait;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductPageIntegration class.
 */
class ProductPageIntegrationTest extends WC_Unit_Test_Case {

	use BlockTypeRegistryTrait;
	use StockNotificationsFeatureTrait;

	/**
	 * Text shared by both versions of the "already joined" notice, with and without the My Account link.
	 */
	private const ALREADY_JOINED = 'You have already joined this waitlist.';

	/**
	 * The System Under Test.
	 *
	 * @var ProductPageIntegration
	 */
	private $sut;

	/**
	 * Interactivity API state before the test, as rendering the block adds to it.
	 *
	 * @var array
	 */
	private $interactivity_state;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// The blocks are not registered on `init`, because `init` runs with a classic theme.
		$this->replace_block_types(
			AddToCartWithOptionsMocks::get_mock_classes(
				'add-to-cart-with-options',
				'add-to-cart-with-options-quantity-selector',
				'add-to-cart-with-options-variation-selector',
				'add-to-cart-with-options-variation-selector-attribute',
				'add-to-cart-with-options-variation-selector-attribute-name'
			)
		);

		update_option( 'woocommerce_customer_stock_notifications_allow_signups', 'yes' );

		// The container caches the instance and the hooks it added in an earlier test are gone
		// after that test tore down, so re-resolve the services to hook the product page again.
		$this->enable_stock_notifications_feature();
		$this->init_stock_notifications_services();

		$this->sut = wc_get_container()->get( ProductPageIntegration::class );

		$this->interactivity_state = $this->interactivity_state_data()->getValue( wp_interactivity() );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			$this->interactivity_state_data()->setValue( wp_interactivity(), $this->interactivity_state );
			$this->restore_stock_notifications_feature_option();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Get the Interactivity API's private state property.
	 *
	 * @return \ReflectionProperty
	 */
	private function interactivity_state_data(): \ReflectionProperty {
		$property = new \ReflectionProperty( \WP_Interactivity_API::class, 'state_data' );
		$property->setAccessible( true );

		return $property;
	}

	/**
	 * Create a variable product with an out of stock variation and load its single product page.
	 *
	 * @return int The product ID.
	 */
	private function create_and_visit_variable_product(): int {
		$fixtures         = new FixtureData();
		$variable_product = $fixtures->get_variable_product(
			array(),
			array( $fixtures->get_product_attribute( 'color', array( 'red', 'blue' ) ) )
		);
		$product_id       = $variable_product->get_id();

		// Keep one variation purchasable so the block fires its template hooks.
		$fixtures->get_variation_product(
			$product_id,
			array( 'pa_color' => 'red-slug' ),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		$fixtures->get_variation_product(
			$product_id,
			array( 'pa_color' => 'blue-slug' ),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::OUT_OF_STOCK,
			)
		);

		// Sync the parent stock status from its variations so the block treats it as purchasable.
		\WC_Product_Variable::sync( $product_id );

		$this->go_to( get_permalink( $product_id ) );

		// `go_to()` overwrites the global with the query var, so set it again for the hook callbacks.
		$GLOBALS['product'] = wc_get_product( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		return $product_id;
	}

	/**
	 * Create an out of stock simple product and load its single product page.
	 *
	 * @return int The product ID.
	 */
	private function create_and_visit_out_of_stock_simple_product(): int {
		$fixtures   = new FixtureData();
		$product_id = $fixtures->get_simple_product(
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::OUT_OF_STOCK,
			)
		)->get_id();

		$this->go_to( get_permalink( $product_id ) );
		$GLOBALS['product'] = wc_get_product( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		return $product_id;
	}

	/**
	 * @testdox Should render an interactive form after the Add to Cart + Options block so it keeps its Interactivity API form.
	 */
	public function test_interactive_form_is_rendered_after_add_to_cart_with_options_block(): void {
		$product_id = $this->create_and_visit_variable_product();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'data-wp-on--submit', $markup, 'The Add to Cart + Options block should keep its Interactivity API form when sign-ups are enabled.' );
		$this->assertSame( 1, substr_count( $markup, 'class="wc_bis_form"' ), 'The Back in Stock form should render once.' );
		$this->assertGreaterThan( strpos( $markup, '</form>' ), strpos( $markup, 'class="wc_bis_form"' ), 'The Back in Stock form should render outside the block form.' );

		// The attribute inputs sit inside the block form, to read its selected attributes.
		$block_form = new \WP_HTML_Tag_Processor( substr( $markup, 0, strpos( $markup, '</form>' ) ) );
		$this->assertTrue( $block_form->next_tag( array( 'class_name' => 'wc-block-add-to-cart-with-options' ) ), 'The block form should render.' );
		$attribute_input = null;
		while ( $block_form->next_tag( 'input' ) ) {
			if ( 'attribute_pa_color' === $block_form->get_attribute( 'name' ) ) {
				$attribute_input = array(
					'form'  => $block_form->get_attribute( 'form' ),
					'value' => $block_form->get_attribute( 'data-wp-bind--value' ),
				);
			}
		}
		$this->assertSame( 'state.selectedAttributeValue', $attribute_input['value'] ?? null, 'The block form should hold the chosen attributes.' );

		$html = new \WP_HTML_Tag_Processor( $markup );
		$html->next_tag( array( 'class_name' => 'wc_bis_form' ) );
		$this->assertNotNull( $html->get_attribute( 'hidden' ), 'The form should stay hidden until an out of stock variation is chosen.' );
		$this->assertSame( 'woocommerce/back-in-stock-form', $html->get_attribute( 'data-wp-interactive' ), 'The form should be driven by the Interactivity API.' );

		$html->next_tag( 'form' );
		$this->assertSame( $html->get_attribute( 'id' ), $attribute_input['form'] ?? '', 'The attribute inputs should be posted with the Back in Stock form.' );

		$bound_inputs = array();
		while ( $html->next_tag( 'input' ) ) {
			$bound_inputs[ $html->get_attribute( 'name' ) ] = $html->get_attribute( 'data-wp-bind--value' );
		}
		$this->assertSame( 'woocommerce/products::state.productInContext.id', $bound_inputs['wc_bis_product_id'] ?? null, 'The form should target the chosen variation.' );
	}

	/**
	 * @testdox Should render the form when the classic template hook fires outside the Add to Cart + Options block.
	 */
	public function test_form_is_rendered_by_classic_template_hook(): void {
		$this->create_and_visit_variable_product();

		ob_start();
		do_action( 'woocommerce_after_add_to_cart_form' );
		$markup = ob_get_clean();

		$this->assertStringContainsString( 'wc_bis_form', $markup, 'The Back in Stock form should render when the hook fires from a classic template.' );
		$this->assertStringNotContainsString( 'data-wp-', $markup, 'The classic form should not carry Interactivity API directives.' );
	}

	/**
	 * @testdox Should render a visible form for an out of stock simple product after the Add to Cart + Options block.
	 */
	public function test_form_is_rendered_for_simple_product_after_add_to_cart_with_options_block(): void {
		$product_id = $this->create_and_visit_out_of_stock_simple_product();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'data-wp-on--submit', $markup, 'The Add to Cart + Options block should keep its Interactivity API form for an out of stock simple product.' );
		$this->assertGreaterThan( strpos( $markup, '</form>' ), strpos( $markup, 'class="wc_bis_form"' ), 'The Back in Stock form should render outside the block form.' );

		$form = substr( $markup, strpos( $markup, '<div class="wc_bis_form"' ) );
		$html = new \WP_HTML_Tag_Processor( $form );
		$this->assertTrue( $html->next_tag( array( 'class_name' => 'wc_bis_form' ) ), 'The Back in Stock form should render.' );
		$this->assertNull( $html->get_attribute( 'hidden' ), 'The form should be visible.' );
		$this->assertStringNotContainsString( 'woocommerce/back-in-stock-form', $form, 'A simple product form needs no Interactivity API store.' );
	}

	/**
	 * Create a variable product whose variations are all out of stock and load its single product page.
	 *
	 * @return int The product ID.
	 */
	private function create_and_visit_out_of_stock_variable_product(): int {
		$fixtures         = new FixtureData();
		$variable_product = $fixtures->get_variable_product(
			array(),
			array( $fixtures->get_product_attribute( 'color', array( 'red', 'blue' ) ) )
		);
		$product_id       = $variable_product->get_id();
		foreach ( array( 'red-slug', 'blue-slug' ) as $color ) {
			$fixtures->get_variation_product(
				$product_id,
				array( 'pa_color' => $color ),
				array(
					'regular_price' => 10,
					'stock_status'  => ProductStockStatus::OUT_OF_STOCK,
				)
			);
		}
		\WC_Product_Variable::sync( $product_id );
		$this->go_to( get_permalink( $product_id ) );
		$GLOBALS['product'] = wc_get_product( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		return $product_id;
	}

	/**
	 * @testdox Should render a visible form for the parent product when no variation can be bought, as the block shows no variation selector.
	 */
	public function test_form_is_rendered_for_parent_when_no_variation_can_be_bought(): void {
		$product_id = $this->create_and_visit_out_of_stock_variable_product();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertSame( 1, substr_count( $markup, 'class="wc_bis_form"' ), 'The Back in Stock form should render once.' );
		$this->assertStringNotContainsString( 'form="wc-bis-form-', $markup, 'Without a variation selector there are no attributes to post.' );

		$html = new \WP_HTML_Tag_Processor( $markup );
		$html->next_tag( array( 'class_name' => 'wc_bis_form' ) );
		$this->assertNull( $html->get_attribute( 'hidden' ), 'The form should be visible.' );
		$this->assertNull( $html->get_attribute( 'data-wp-interactive' ), 'The form needs no Interactivity API store.' );

		$posted_product_id = null;
		while ( $html->next_tag( 'input' ) ) {
			if ( 'wc_bis_product_id' === $html->get_attribute( 'name' ) ) {
				$posted_product_id = $html->get_attribute( 'value' );
			}
		}
		$this->assertSame( (string) $product_id, $posted_product_id, 'The sign-up should be for the parent product.' );
	}

	/**
	 * @testdox Should not render the form for an Add to Cart + Options block of another product than the one being viewed.
	 */
	public function test_form_is_not_rendered_for_add_to_cart_with_options_block_of_another_product(): void {
		$this->create_and_visit_out_of_stock_simple_product();
		$other_product_id = ( new FixtureData() )->get_simple_product(
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::OUT_OF_STOCK,
			)
		)->get_id();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $other_product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringNotContainsString( 'wc_bis_form', $markup, 'Blocks for other products, e.g. in related products, should not get a Back in Stock form.' );
	}

	/**
	 * @testdox Should not render a second form after the Add to Cart + Options block when the classic hook already rendered one.
	 */
	public function test_form_is_not_rendered_twice_when_classic_hook_rendered_it_first(): void {
		$product_id = $this->create_and_visit_out_of_stock_simple_product();

		ob_start();
		do_action( 'woocommerce_simple_add_to_cart' );
		$classic_markup = ob_get_clean();

		$block_markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'wc_bis_form', $classic_markup, 'The classic hook should render the form.' );
		$this->assertStringNotContainsString( 'wc_bis_form', $block_markup, 'The block should not render a second form for the same product.' );
	}

	/**
	 * @testdox Should render the form when the simple product hook fires outside the Add to Cart + Options block.
	 */
	public function test_form_is_rendered_by_simple_product_hook(): void {
		$this->create_and_visit_out_of_stock_simple_product();

		ob_start();
		do_action( 'woocommerce_simple_add_to_cart' );
		$markup = ob_get_clean();

		$this->assertStringContainsString( 'wc_bis_form', $markup, 'The Back in Stock form should render when the simple product hook fires from a classic template.' );
	}

	/**
	 * @testdox Should send guests back to the product after logging in when an account is required.
	 */
	public function test_login_link_returns_guest_to_the_product_when_account_is_required(): void {
		update_option( 'woocommerce_customer_stock_notifications_require_account', 'yes' );
		wp_set_current_user( 0 );
		$product_id = $this->create_and_visit_out_of_stock_simple_product();

		try {
			ob_start();
			do_action( 'woocommerce_simple_add_to_cart' );
			$markup = ob_get_clean();
		} finally {
			delete_option( 'woocommerce_customer_stock_notifications_require_account' );
		}

		$expected_url = add_query_arg( 'redirect_to', rawurlencode( get_permalink( $product_id ) ), wc_get_page_permalink( 'myaccount' ) );

		$this->assertStringContainsString( 'Please <a href="' . esc_url( $expected_url ) . '">log in</a> to sign up for stock notifications.', $markup, 'The login link should return the shopper to the product page after logging in.' );
		$this->assertStringNotContainsString( 'wc_bis_form', $markup, 'The Back in Stock form should not render for a guest when an account is required.' );
	}

	/**
	 * @testdox Should render the form inside the legacy Add to Cart Form block.
	 */
	public function test_form_is_rendered_inside_legacy_add_to_cart_form_block(): void {
		$variable_product_id = $this->create_and_visit_variable_product();
		$markup              = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $variable_product_id . '} --><!-- wp:woocommerce/add-to-cart-form /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'wc_bis_form', $markup, 'The Back in Stock form should render inside the legacy Add to Cart Form block for a variable product.' );

		$simple_product_id = $this->create_and_visit_out_of_stock_simple_product();
		$markup            = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $simple_product_id . '} --><!-- wp:woocommerce/add-to-cart-form /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'wc_bis_form', $markup, 'The Back in Stock form should render inside the legacy Add to Cart Form block for an out of stock simple product.' );
	}

	/**
	 * Log in a customer who has a sign-up for the given product.
	 *
	 * @param int    $product_id The product ID.
	 * @param string $status     The sign-up status.
	 */
	private function log_in_customer_signed_up_for( int $product_id, string $status = NotificationStatus::ACTIVE ): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );

		$notification = new Notification();
		$notification->set_status( $status );
		$notification->set_product_id( $product_id );
		$notification->set_user_id( $user_id );
		$notification->save();
	}

	/**
	 * @testdox Should show the "already joined" notice instead of the form on load to a logged-in customer who already signed up.
	 */
	public function test_already_joined_notice_replaces_form_for_signed_up_customer(): void {
		$product_id = $this->create_and_visit_out_of_stock_simple_product();
		$this->log_in_customer_signed_up_for( $product_id );

		ob_start();
		do_action( 'woocommerce_simple_add_to_cart' );
		$markup = ob_get_clean();

		$this->assertStringContainsString( self::ALREADY_JOINED, $markup, 'A signed-up customer should see the "already joined" notice on load.' );
		$this->assertStringNotContainsString( 'wc_bis_form', $markup, 'A signed-up customer should not see the Back in Stock form on load.' );
	}

	/**
	 * @testdox Should keep the form on the sign-up request, which shows its own notice.
	 */
	public function test_form_is_rendered_on_the_signup_request(): void {
		$product_id = $this->create_and_visit_out_of_stock_simple_product();
		$this->log_in_customer_signed_up_for( $product_id );
		$_POST['wc_bis_register'] = '1';

		ob_start();
		do_action( 'woocommerce_simple_add_to_cart' );
		$markup = ob_get_clean();
		unset( $_POST['wc_bis_register'] );

		$this->assertStringContainsString( 'wc_bis_form', $markup, 'The sign-up request should not add the "already joined" notice.' );
	}

	/**
	 * @testdox Should show the "already joined" notice from the classic hook to a customer signed up for a parent product when out of stock items are hidden.
	 */
	public function test_already_joined_notice_replaces_classic_form_for_parent_when_out_of_stock_items_are_hidden(): void {
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
		$this->log_in_customer_signed_up_for( $this->create_and_visit_out_of_stock_variable_product() );

		ob_start();
		do_action( 'woocommerce_after_add_to_cart_form' );
		$markup = ob_get_clean();

		$this->assertStringContainsString( self::ALREADY_JOINED, $markup, 'The notice should render.' );
		$this->assertStringNotContainsString( 'wc_bis_form', $markup, 'The form should not render.' );
	}

	/**
	 * @testdox Should show the "already joined" notice after the Add to Cart + Options block to a customer signed up for a parent product no variation of which can be bought.
	 */
	public function test_already_joined_notice_replaces_block_form_for_parent_when_no_variation_can_be_bought(): void {
		$product_id = $this->create_and_visit_out_of_stock_variable_product();
		$this->log_in_customer_signed_up_for( $product_id );

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertSame( 1, substr_count( $markup, self::ALREADY_JOINED ), 'The notice should be printed once.' );

		$html = new \WP_HTML_Tag_Processor( $markup );
		$this->assertFalse( $html->next_tag( array( 'class_name' => 'wc_bis_form' ) ), 'The form should not render.' );
	}

	/**
	 * @testdox Should let the Interactivity API swap the form for the "already joined" notice when a joined variation is chosen in the Add to Cart + Options block.
	 */
	public function test_block_form_gives_way_to_notice_for_joined_variation(): void {
		$product_id      = $this->create_and_visit_variable_product();
		$out_of_stock_id = wc_get_product( $product_id )->get_children()[1];
		$this->log_in_customer_signed_up_for( $out_of_stock_id );

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$state = wp_interactivity_state( 'woocommerce/back-in-stock-form' );
		$this->assertSame( array( $out_of_stock_id ), $state['signedUpVariationIds'] ?? null, 'The store should know the joined variations.' );

		$html = new \WP_HTML_Tag_Processor( $markup );
		$this->assertTrue( $html->next_tag( array( 'class_name' => 'wc_bis_form' ) ), 'The form should render.' );
		$this->assertSame( '!state.isVisible', $html->get_attribute( 'data-wp-bind--hidden' ), 'The store should show and hide the form.' );

		$this->assertTrue( $html->next_tag( array( 'class_name' => 'wc_bis_already_signed_up' ) ), 'The notice should render after the form.' );
		$this->assertNotNull( $html->get_attribute( 'hidden' ), 'The notice should stay hidden until a joined variation is chosen.' );
		$this->assertSame( 'woocommerce/back-in-stock-form', $html->get_attribute( 'data-wp-interactive' ), 'The notice should be driven by the Interactivity API.' );
		$this->assertSame( '!state.isSignedUpNoticeVisible', $html->get_attribute( 'data-wp-bind--hidden' ), 'The store should show and hide the notice.' );
	}

	/**
	 * @testdox Should keep rendering a variable product's hidden form, which JS points at the selected variation.
	 */
	public function test_variable_product_form_is_rendered_for_customer_signed_up_on_the_parent(): void {
		$product_id = $this->create_and_visit_variable_product();
		$this->log_in_customer_signed_up_for( $product_id );

		ob_start();
		do_action( 'woocommerce_after_add_to_cart_form' );
		$markup = ob_get_clean();

		$this->assertStringContainsString( 'wc_bis_form', $markup, 'The hidden variation form should still render.' );
		$this->assertStringNotContainsString( 'wc_bis_already_signed_up', $markup, 'The parent sign-up should not replace the variation form.' );
	}

	/**
	 * Sign-up statuses that are not active.
	 *
	 * @return array<array{string}>
	 */
	public static function inactive_statuses(): array {
		return array( array( NotificationStatus::CANCELLED ), array( NotificationStatus::PENDING ) );
	}

	/**
	 * @testdox Should keep the form for a logged-in customer whose sign-up is $status.
	 * @dataProvider inactive_statuses
	 *
	 * @param string $status The sign-up status.
	 */
	public function test_form_is_rendered_for_customer_with_inactive_signup( string $status ): void {
		$product_id = $this->create_and_visit_out_of_stock_simple_product();
		$this->log_in_customer_signed_up_for( $product_id, $status );

		ob_start();
		do_action( 'woocommerce_simple_add_to_cart' );
		$markup = ob_get_clean();

		$this->assertStringContainsString( 'wc_bis_form', $markup, 'A sign-up that is not active should keep the form, so submitting it can activate or resend it.' );
	}

	/**
	 * @testdox Should keep the form for a logged-in customer who joined as a guest with the same email.
	 */
	public function test_form_is_rendered_for_customer_with_guest_signup(): void {
		$product_id = $this->create_and_visit_out_of_stock_simple_product();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'customer' ) ) );

		$notification = new Notification();
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->set_product_id( $product_id );
		$notification->set_user_email( wp_get_current_user()->user_email );
		$notification->save();

		ob_start();
		do_action( 'woocommerce_simple_add_to_cart' );
		$markup = ob_get_clean();

		$this->assertStringContainsString( 'wc_bis_form', $markup, 'A guest sign-up should keep the form, as My Account only lists the customer\'s own sign-ups.' );
	}

	/**
	 * Render a variable product's form and get the variations listed on it as joined.
	 *
	 * @param string|null $markup Set to the rendered markup.
	 * @return array<int>|null The variation IDs, or null without the attribute.
	 */
	private function render_signed_up_variation_ids( ?string &$markup = null ): ?array {
		ob_start();
		do_action( 'woocommerce_after_add_to_cart_form' );
		$markup = (string) ob_get_clean();

		$html = new \WP_HTML_Tag_Processor( $markup );
		$this->assertTrue( $html->next_tag( array( 'class_name' => 'wc_bis_form' ) ), 'The variation form should render.' );
		$ids = $html->get_attribute( 'data-bis-signed-up-variations' );

		return is_string( $ids ) ? json_decode( $ids, true ) : null;
	}

	/**
	 * @testdox Should list the joined out of stock variations on the form and print one hidden "already joined" notice.
	 */
	public function test_signed_up_variations_are_listed_on_the_form(): void {
		$product_id = $this->create_and_visit_variable_product();
		// The fixture creates the in stock variation first.
		list( $in_stock_id, $out_of_stock_id ) = wc_get_product( $product_id )->get_children();

		$other_out_of_stock_id = ( new FixtureData() )->get_variation_product( $product_id, array( 'pa_color' => 'red-slug' ), array( 'stock_status' => ProductStockStatus::OUT_OF_STOCK ) )->get_id();
		$GLOBALS['product']    = wc_get_product( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->log_in_customer_signed_up_for( $out_of_stock_id );
		// Joining an in stock variation must not swap its add to cart button for the notice.
		foreach ( array( $other_out_of_stock_id, $in_stock_id ) as $variation_id ) {
			$notification = new Notification();
			$notification->set_status( NotificationStatus::ACTIVE );
			$notification->set_product_id( $variation_id );
			$notification->set_user_id( get_current_user_id() );
			$notification->save();
		}

		$ids = $this->render_signed_up_variation_ids( $markup );

		$this->assertEqualsCanonicalizing( array( $out_of_stock_id, $other_out_of_stock_id ), $ids, 'Only the joined out of stock variations should be listed.' );
		$this->assertSame( 1, substr_count( $markup, self::ALREADY_JOINED ), 'The notice should be printed once.' );
		$html = new \WP_HTML_Tag_Processor( $markup );
		$html->next_tag( array( 'class_name' => 'wc_bis_already_signed_up' ) );
		$this->assertNotNull( $html->get_attribute( 'hidden' ), 'The notice should start hidden.' );
		$this->assertSame( (string) $product_id, $html->get_attribute( 'data-bis-product-id' ), 'The notice should name the product its form is for.' );
	}

	/**
	 * @testdox Should not list joined variations when personalization is turned off.
	 */
	public function test_signed_up_variations_are_not_listed_without_personalization(): void {
		$product_id = $this->create_and_visit_variable_product();
		$this->log_in_customer_signed_up_for( wc_get_product( $product_id )->get_children()[1] );
		add_filter( 'woocommerce_customer_stock_notifications_personalization_enabled', '__return_false' );

		$this->assertNull( $this->render_signed_up_variation_ids( $markup ) );
		$this->assertStringNotContainsString( 'wc_bis_already_signed_up', $markup );
	}

	/**
	 * @testdox Should not list a variation whose sign-up is $status.
	 * @dataProvider inactive_statuses
	 *
	 * @param string $status The sign-up status.
	 */
	public function test_inactive_variation_signup_is_not_listed( string $status ): void {
		$product_id = $this->create_and_visit_variable_product();
		$this->log_in_customer_signed_up_for( wc_get_product( $product_id )->get_children()[1], $status );

		$this->assertNull( $this->render_signed_up_variation_ids( $markup ), 'A sign-up that is not active should keep the form.' );
		$this->assertStringNotContainsString( 'wc_bis_already_signed_up', $markup, 'No notice should be printed.' );
	}

	/**
	 * @testdox Should not list a variation joined as a guest with the logged-in customer's email.
	 */
	public function test_guest_variation_signup_is_not_listed(): void {
		$product_id = $this->create_and_visit_variable_product();
		$user_id    = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );

		$notification = new Notification();
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->set_product_id( wc_get_product( $product_id )->get_children()[1] );
		$notification->set_user_email( wp_get_current_user()->user_email );
		$notification->save();

		$this->assertNull( $this->render_signed_up_variation_ids(), 'A guest sign-up should keep the form.' );
	}

	/**
	 * @testdox Should not list a joined variation with 'any' attributes, whose chosen values aren't known yet.
	 */
	public function test_variation_with_any_attribute_is_not_listed(): void {
		$product_id         = $this->create_and_visit_variable_product();
		$any_id             = ( new FixtureData() )->get_variation_product( $product_id, array( 'pa_color' => '' ), array( 'stock_status' => ProductStockStatus::OUT_OF_STOCK ) )->get_id();
		$GLOBALS['product'] = wc_get_product( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->log_in_customer_signed_up_for( $any_id );

		$this->assertNull( $this->render_signed_up_variation_ids() );
	}

	/**
	 * @testdox Should not list joined variations for guests.
	 */
	public function test_signed_up_variations_are_not_listed_for_guests(): void {
		$product_id = $this->create_and_visit_variable_product();
		$this->log_in_customer_signed_up_for( wc_get_product( $product_id )->get_children()[1] );
		wp_set_current_user( 0 );

		$this->assertNull( $this->render_signed_up_variation_ids( $markup ) );
		$this->assertStringNotContainsString( 'wc_bis_already_signed_up', $markup );
	}
}
