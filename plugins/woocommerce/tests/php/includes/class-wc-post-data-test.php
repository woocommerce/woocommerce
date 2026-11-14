<?php
/**
 * Post data tests
 *
 * @package WooCommerce\Tests\Post_Data.
 */

use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class WC_Post_Data_Test
 */
class WC_Post_Data_Test extends \WC_Unit_Test_Case {

	use HPOSToggleTrait;

	/**
	 * Ensure the HPOS tables exist before per-test transactions start.
	 */
	public static function wpSetUpBeforeClass(): void {
		$previous_hpos_state = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );

		try {
			self::setup_cot_tables();
			if ( OrderUtil::custom_orders_table_usage_is_enabled() !== $previous_hpos_state ) {
				OrderHelper::toggle_cot_feature_and_usage( $previous_hpos_state );
			}
		} finally {
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

	/**
	 * Data provider of coupon codes, as entered and as stored.
	 *
	 * @return array[]
	 */
	public function data_provider_coupon_codes(): array {
		return array(
			'ampersand'                             => array( 'B&Q', 'B&amp;Q' ),
			'apostrophe'                            => array( "TEST'S", "TEST'S" ),
			'ampersand and apostrophe'              => array( "M&M'S", "M&amp;M'S" ),
			'double quotes'                         => array( 'TEST"2024"', 'TEST"2024"' ),
			'percent followed by digits'            => array( '%20TEST', '%20TEST' ),
			'non-ASCII'                             => array( 'CAFÉ', 'CAFÉ' ),
			'greater-than sign'                     => array( 'a>b', 'a&gt;b' ),
			'less-than sign'                        => array( '10<20', '10&lt;20' ),
			'ampersand before a legacy entity name' => array( '50&cent', '50&amp;cent' ),
			'backslash'                             => array( 'a\\b', 'a\\b' ),
			'tag-like text'                         => array( 'x<5>y', 'x&lt;5&gt;y' ),
			'HTML tags'                             => array( '<b>bold</b>', '&lt;b&gt;bold&lt;/b&gt;' ),
		);
	}

	/**
	 * Data provider for test_coupon_code_is_stored_consistently_and_found_by_customers.
	 *
	 * @return array[]
	 */
	public function data_provider_coupon_codes_by_role(): array {
		$data = array();
		// Shop managers lack unfiltered_html, like guests, so WordPress runs their titles through kses. Administrators don't.
		foreach ( array( 'shop_manager', 'administrator' ) as $role ) {
			foreach ( $this->data_provider_coupon_codes() as $label => $code ) {
				$data[ "{$label} saved by {$role}" ] = array( $role, $code[0], $code[1] );
			}
		}

		return $data;
	}

	/**
	 * Create a coupon the same way as the coupon edit screen, which passes slashed $_POST data to wp_insert_post().
	 *
	 * @param string $code Coupon code as entered.
	 * @return int Coupon ID.
	 */
	private function create_coupon_from_edit_screen( string $code ): int {
		return wp_insert_post(
			wp_slash(
				array(
					'post_title'  => $code,
					'post_type'   => 'shop_coupon',
					'post_status' => 'publish',
				)
			)
		);
	}

	/**
	 * @testdox Should not let post title filters change coupon codes on save or lookup.
	 *
	 * @testWith ["title_save_pre"]
	 *           ["pre_post_title"]
	 *
	 * @param string $hook_name Post title filter.
	 */
	public function test_post_title_filters_do_not_change_coupon_codes( string $hook_name ): void {
		add_filter(
			$hook_name,
			static function ( $title ) {
				return $title . '-filtered';
			}
		);
		$this->login_as_role( 'shop_manager' );

		$coupon_id = $this->create_coupon_from_edit_screen( 'B&Q' );
		$post_id   = self::factory()->post->create( array( 'post_title' => 'Post' ) );

		$this->assertSame( 'Post-filtered', get_post( $post_id )->post_title, 'Other post types should still be filtered' );
		$this->assertSame( 'B&amp;Q', get_post( $coupon_id )->post_title, 'Unexpected stored coupon code' );

		wp_set_current_user( 0 );
		$this->assertSame( $coupon_id, wc_get_coupon_id_by_code( wc_format_coupon_code( 'B&Q' ) ), 'The coupon should be found by the code as entered' );
	}

	/**
	 * @testdox Should keep changes that other wp_insert_post_data filters make to coupon codes.
	 */
	public function test_wp_insert_post_data_filters_can_change_coupon_codes(): void {
		add_filter(
			'wp_insert_post_data',
			static function ( $data ) {
				$data['post_title'] = 'changed';
				return $data;
			}
		);

		$coupon_id = $this->create_coupon_from_edit_screen( 'B&Q' );

		$this->assertSame( 'changed', get_post( $coupon_id )->post_title );
	}

	/**
	 * @testdox Should display an ampersand in the coupon list without double encoding it.
	 */
	public function test_coupon_list_displays_ampersand(): void {
		global $post;

		require_once WC_ABSPATH . 'includes/admin/list-tables/class-wc-admin-list-table-coupons.php';
		$this->login_as_administrator();
		$coupon_id = $this->create_coupon_from_edit_screen( 'Coupon&Test' );
		$post      = get_post( $coupon_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The list table reads the current row from the global post.
		$sut       = new WC_Admin_List_Table_Coupons();

		$this->expectOutputRegex( '/>coupon&amp;test<\/a>/' );
		$sut->render_columns( 'coupon_code', $coupon_id );
	}

	/**
	 * @testdox Should store coupon codes in the same format for every role, and find them by the code customers enter.
	 *
	 * @dataProvider data_provider_coupon_codes_by_role
	 *
	 * @param string $role           Role saving the coupon.
	 * @param string $code           Code as entered by the merchant and by the customer.
	 * @param string $expected_title Expected post_title in the database.
	 */
	public function test_coupon_code_is_stored_consistently_and_found_by_customers( string $role, string $code, string $expected_title ): void {
		$this->login_as_role( $role );

		$coupon_id = $this->create_coupon_from_edit_screen( $code );

		$this->assertSame( $expected_title, get_post( $coupon_id )->post_title, 'Unexpected stored coupon code' );

		wp_set_current_user( 0 );
		$this->assertSame( $coupon_id, wc_get_coupon_id_by_code( wc_format_coupon_code( $code ) ), 'The coupon should be found by the code as entered' );
	}

	/**
	 * @testdox Should keep the stored coupon code when another user updates the coupon.
	 *
	 * @dataProvider data_provider_coupon_codes
	 *
	 * @param string $code           Code as entered by the merchant.
	 * @param string $expected_title Expected post_title in the database.
	 */
	public function test_coupon_code_is_unchanged_when_the_coupon_is_updated( string $code, string $expected_title ): void {
		$this->login_as_administrator();
		$coupon_id = $this->create_coupon_from_edit_screen( $code );

		// wp_update_post() slashes the stored post before saving it again, and shop managers' titles go through kses.
		$this->login_as_role( 'shop_manager' );
		wp_update_post(
			array(
				'ID'          => $coupon_id,
				'post_status' => 'draft',
			)
		);

		$this->assertSame( $expected_title, get_post( $coupon_id )->post_title );
	}

	/**
	 * @testdox Should show coupon codes as entered on the edit screen, and keep the stored code when saved again.
	 *
	 * @dataProvider data_provider_coupon_codes
	 *
	 * @param string $code           Code as entered by the merchant.
	 * @param string $expected_title Expected post_title in the database.
	 */
	public function test_coupon_code_is_shown_as_entered_on_the_edit_screen( string $code, string $expected_title ): void {
		$this->login_as_administrator();
		$coupon_id = $this->create_coupon_from_edit_screen( $code );

		// The edit screen prints this value in the title field, and the browser decodes it once.
		$shown_code = html_entity_decode( get_post( $coupon_id, OBJECT, 'edit' )->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		$this->assertSame( $code, $shown_code, 'The edit screen should show the code as entered' );

		wp_update_post(
			wp_slash(
				array(
					'ID'         => $coupon_id,
					'post_title' => $shown_code,
				)
			)
		);

		$this->assertSame( $expected_title, get_post( $coupon_id )->post_title, 'Saving the shown code should not change the stored code' );
	}

	/**
	 * @testdox Should leave the edit screen title of other post types unchanged.
	 */
	public function test_edit_post_title_is_unchanged_for_other_post_types(): void {
		$post_id = self::factory()->post->create();

		$this->assertSame( 'a&amp;b', WC_Post_Data::decode_coupon_code_for_editing( 'a&amp;b', $post_id ) );
	}

	/**
	 * @testdox Should store coupon codes saved through the coupon object, as the REST API does.
	 *
	 * @testWith ["B&Q", "b&amp;q"]
	 *           ["TEST'S", "test's"]
	 *           ["50&cent", "50&amp;cent"]
	 *           ["x<5>y", "x&lt;5&gt;y"]
	 *
	 * @param string $code           Code as entered.
	 * @param string $expected_title Expected post_title in the database.
	 */
	public function test_coupon_code_saved_with_crud_is_stored_consistently( string $code, string $expected_title ): void {
		$this->login_as_administrator();

		$coupon = new WC_Coupon();
		$coupon->set_code( $code );
		$coupon->save();

		$this->assertSame( $expected_title, get_post( $coupon->get_id() )->post_title, 'Unexpected stored coupon code' );

		wp_set_current_user( 0 );
		$this->assertSame( $coupon->get_id(), wc_get_coupon_id_by_code( wc_format_coupon_code( $code ) ), 'The coupon should be found by the code as entered' );
	}

	/**
	 * @testdox Should encode emoji in coupon codes when the posts table can't store them, like WordPress does.
	 */
	public function test_coupon_code_emoji_is_encoded_for_utf8_tables(): void {
		global $wpdb;

		add_filter(
			'pre_get_col_charset',
			function ( $charset, $table, $column ) use ( $wpdb ) {
				return $wpdb->posts === $table && 'post_title' === $column ? 'utf8' : $charset;
			},
			10,
			3
		);

		$coupon_id = $this->create_coupon_from_edit_screen( 'SALE🎉' );

		$this->assertSame( 'SALE&#x1f389;', get_post( $coupon_id )->post_title );
	}

	/**
	 * @testdox A coupon saved from the edit screen without a code is saved as a draft.
	 * @testWith ["publish", "", "draft"]
	 *           ["future", "", "draft"]
	 *           ["private", "", "draft"]
	 *           ["publish", "   ", "draft"]
	 *           ["pending", "", "pending"]
	 *           ["draft", "", "draft"]
	 *           ["publish", "has-code", "publish"]
	 *
	 * @param string $requested_status Status the edit screen asks for.
	 * @param string $coupon_code      Coupon code entered on the edit screen.
	 * @param string $expected_status  Status that should be saved.
	 */
	public function test_coupon_edit_screen_saves_coupon_without_code_as_draft( string $requested_status, string $coupon_code, string $expected_status ): void {
		$post_id = $this->create_auto_draft_coupon();
		$postarr = array(
			'ID'          => $post_id,
			'post_title'  => $coupon_code,
			'post_status' => $requested_status,
		);
		if ( 'future' === $requested_status ) {
			$postarr['post_date'] = gmdate( 'Y-m-d H:i:s', strtotime( '+1 week' ) );
		}

		$this->submit_coupon_edit_screen( $post_id, $postarr );

		$this->assertSame( $expected_status, get_post_status( $post_id ) );
	}

	/**
	 * @testdox Clearing the code of a published coupon on the edit screen unpublishes it through the normal status transition.
	 */
	public function test_coupon_edit_screen_unpublishes_coupon_when_code_is_cleared(): void {
		$coupon      = WC_Helper_Coupon::create_coupon( 'clear-me' );
		$transitions = array();
		$listener    = function ( $new_status, $old_status, $post ) use ( &$transitions, $coupon ) {
			if ( $post->ID === $coupon->get_id() ) {
				$transitions[] = $old_status . '>' . $new_status;
			}
		};

		add_action( 'transition_post_status', $listener, 10, 3 );

		try {
			$this->submit_coupon_edit_screen(
				$coupon->get_id(),
				array(
					'ID'          => $coupon->get_id(),
					'post_title'  => '',
					'post_status' => 'publish',
				)
			);
		} finally {
			remove_action( 'transition_post_status', $listener, 10 );
		}

		$this->assertSame( 'draft', get_post_status( $coupon->get_id() ) );
		$this->assertSame( '', get_post_field( 'post_title', $coupon->get_id() ) );
		$this->assertSame( array( 'publish>draft' ), $transitions );
	}

	/**
	 * @testdox Other coupons without a code saved during a coupon edit screen request keep their status.
	 */
	public function test_coupon_edit_screen_does_not_change_other_coupons_without_code(): void {
		$edited_post_id = $this->create_auto_draft_coupon();
		$other_post_id  = $this->create_auto_draft_coupon();

		$this->submit_coupon_edit_screen(
			$edited_post_id,
			array(
				'ID'          => $other_post_id,
				'post_title'  => '',
				'post_status' => 'publish',
			)
		);

		$this->assertSame( 'publish', get_post_status( $other_post_id ) );
	}

	/**
	 * @testdox A coupon without a code saved outside the edit screen keeps its status.
	 */
	public function test_coupon_without_code_saved_outside_edit_screen_keeps_status(): void {
		$post_id = wp_insert_post(
			array(
				'post_title'  => '',
				'post_type'   => 'shop_coupon',
				'post_status' => 'publish',
			)
		);

		$this->assertSame( 'publish', get_post_status( $post_id ) );
	}

	/**
	 * Create an auto-draft coupon, like the one WordPress creates when the new coupon screen opens.
	 *
	 * @return int
	 */
	private function create_auto_draft_coupon(): int {
		return wp_insert_post(
			array(
				'post_title'  => 'Auto Draft',
				'post_type'   => 'shop_coupon',
				'post_status' => 'auto-draft',
			)
		);
	}

	/**
	 * Save a post while simulating a coupon edit screen submission for a coupon.
	 *
	 * @param int   $edited_post_id ID of the coupon open on the edit screen.
	 * @param array $postarr        Post data to save.
	 */
	private function submit_coupon_edit_screen( int $edited_post_id, array $postarr ): void {
		$previous_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Restored after the save.
		$this->login_as_administrator();
		$_POST['post_ID']                = (string) $edited_post_id;
		$_POST['woocommerce_meta_nonce'] = wp_create_nonce( 'woocommerce_save_data' );

		try {
			wp_update_post( $postarr );
		} finally {
			$_POST = $previous_post;
		}
	}

	/**
	 * @testdox Should remove order items when permanently deleting an order.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $hpos_enabled Whether HPOS is enabled.
	 */
	public function test_deleting_order_removes_items( bool $hpos_enabled ): void {
		$previous_hpos_state = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		try {
			$this->toggle_cot_authoritative( $hpos_enabled );

			$order    = OrderHelper::create_order();
			$item_ids = array_keys( $order->get_items() );
			$this->assertNotEmpty( $item_ids, 'The order should contain an item' );

			$order->delete( true );

			$this->assertFalse( WC_Order_Factory::get_order_item( reset( $item_ids ) ), 'The deleted order item should no longer be available' );
		} finally {
			if ( OrderUtil::custom_orders_table_usage_is_enabled() !== $previous_hpos_state ) {
				$this->toggle_cot_authoritative( $previous_hpos_state );
			}
			add_filter( 'query', array( $this, '_create_temporary_tables' ) );
			add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

	/**
	 * @testdox Should fire woocommerce_product_published when product transitions to publish status.
	 */
	public function test_transition_post_status_fires_product_published_action(): void {
		$product = \WC_Helper_Product::create_simple_product( false );
		$product->set_status( 'draft' );
		$product->save();

		$published_ids = array();
		$callback      = function ( $product_id ) use ( &$published_ids ) {
			$published_ids[] = $product_id;
		};
		add_action( 'woocommerce_product_published', $callback );

		$post = get_post( $product->get_id() );
		WC_Post_Data::transition_post_status( 'publish', 'draft', $post );

		$this->assertContains( $product->get_id(), $published_ids, 'woocommerce_product_published should fire when product transitions to publish' );

		remove_action( 'woocommerce_product_published', $callback );
		$product->delete( true );
	}

	/**
	 * @testdox Should not fire woocommerce_product_published when product is already published and updated.
	 */
	public function test_transition_post_status_does_not_fire_product_published_on_update(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$published_ids = array();
		$callback      = function ( $product_id ) use ( &$published_ids ) {
			$published_ids[] = $product_id;
		};
		add_action( 'woocommerce_product_published', $callback );

		$post = get_post( $product->get_id() );
		WC_Post_Data::transition_post_status( 'publish', 'publish', $post );

		$this->assertEmpty( $published_ids, 'woocommerce_product_published should not fire when product is already published' );

		remove_action( 'woocommerce_product_published', $callback );
		$product->delete( true );
	}

	/**
	 * @testdox Should not fire woocommerce_product_published for non-product post types.
	 */
	public function test_transition_post_status_does_not_fire_product_published_for_non_products(): void {
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Test Post',
				'post_type'   => 'post',
				'post_status' => 'draft',
			)
		);

		$published_ids = array();
		$callback      = function ( $product_id ) use ( &$published_ids ) {
			$published_ids[] = $product_id;
		};
		add_action( 'woocommerce_product_published', $callback );

		$post = get_post( $post_id );
		WC_Post_Data::transition_post_status( 'publish', 'draft', $post );

		$this->assertEmpty( $published_ids, 'woocommerce_product_published should not fire for non-product post types' );

		remove_action( 'woocommerce_product_published', $callback );
		wp_delete_post( $post_id, true );
	}

	/**
	 * @testdox Should fire woocommerce_product_published when a product variation transitions to publish status.
	 */
	public function test_transition_post_status_fires_product_published_action_for_variation(): void {
		$variation = new WC_Product_Variation();
		$variation->set_status( 'draft' );
		$variation->save();

		$published_ids = array();
		$callback      = function ( $product_id ) use ( &$published_ids ) {
			$published_ids[] = $product_id;
		};
		add_action( 'woocommerce_product_published', $callback );

		$post = get_post( $variation->get_id() );
		WC_Post_Data::transition_post_status( 'publish', 'draft', $post );

		$this->assertContains( $variation->get_id(), $published_ids, 'woocommerce_product_published should fire when a variation transitions to publish' );

		remove_action( 'woocommerce_product_published', $callback );
		$variation->delete( true );
	}

	/**
	 * @testdox Should fire woocommerce_product_published when a scheduled product transitions from future to publish.
	 */
	public function test_transition_post_status_fires_product_published_action_on_scheduled_publish(): void {
		$product = \WC_Helper_Product::create_simple_product( false );
		$product->set_status( 'future' );
		$product->save();

		$published_ids = array();
		$callback      = function ( $product_id ) use ( &$published_ids ) {
			$published_ids[] = $product_id;
		};
		add_action( 'woocommerce_product_published', $callback );

		$post = get_post( $product->get_id() );
		WC_Post_Data::transition_post_status( 'publish', 'future', $post );

		$this->assertContains( $product->get_id(), $published_ids, 'woocommerce_product_published should fire when a scheduled product transitions from future to publish' );

		remove_action( 'woocommerce_product_published', $callback );
		$product->delete( true );
	}

	/**
	 * @testdox do_deferred_product_sync should sync each queued product once (even if queued multiple times) and empty the queue.
	 */
	public function test_do_deferred_product_sync_syncs_queued_products(): void {
		global $wc_deferred_product_sync;

		$wc_deferred_product_sync = array();
		$product_1                = WC_Helper_Product::create_grouped_product();
		$product_2                = WC_Helper_Product::create_grouped_product();

		$synced_ids = array();
		$callback   = function ( $product_id ) use ( &$synced_ids ) {
			$synced_ids[] = $product_id;
		};
		add_action( 'woocommerce_update_product', $callback );

		wc_deferred_product_sync( $product_1->get_id() );
		wc_deferred_product_sync( $product_2->get_id() );
		wc_deferred_product_sync( $product_1->get_id() );

		WC_Post_Data::do_deferred_product_sync();

		remove_action( 'woocommerce_update_product', $callback );

		$this->assertSame( array( $product_1->get_id(), $product_2->get_id() ), $synced_ids, 'Each queued product should be synced exactly once' );
		$this->assertEmpty( $wc_deferred_product_sync, 'The queue should be empty after the sync' );
	}

	/**
	 * @testdox do_deferred_product_sync should also sync products that get deferred while another product is being synced.
	 */
	public function test_do_deferred_product_sync_processes_products_deferred_during_sync(): void {
		global $wc_deferred_product_sync;

		$wc_deferred_product_sync = array();
		$product_1                = WC_Helper_Product::create_grouped_product();
		$product_2                = WC_Helper_Product::create_grouped_product();

		$synced_ids = array();
		$callback   = function ( $product_id ) use ( &$synced_ids, $product_1, $product_2 ) {
			$synced_ids[] = $product_id;
			if ( $product_1->get_id() === $product_id ) {
				wc_deferred_product_sync( $product_2->get_id() );
			}
		};
		add_action( 'woocommerce_update_product', $callback );

		wc_deferred_product_sync( $product_1->get_id() );

		WC_Post_Data::do_deferred_product_sync();

		remove_action( 'woocommerce_update_product', $callback );

		$this->assertSame( array( $product_1->get_id(), $product_2->get_id() ), $synced_ids, 'Products deferred while syncing another product should be synced too' );
		$this->assertEmpty( $wc_deferred_product_sync, 'The queue should be empty after the sync' );
	}

	/**
	 * @testdox do_deferred_product_sync should terminate, syncing each product at most once, when synced products keep re-deferring each other.
	 */
	public function test_do_deferred_product_sync_terminates_on_mutual_re_deferral(): void {
		global $wc_deferred_product_sync;

		$wc_deferred_product_sync = array();
		$product_1                = WC_Helper_Product::create_grouped_product();
		$product_2                = WC_Helper_Product::create_grouped_product();

		// Each product defers the other one when synced, as e.g. translation plugins do.
		// With the old array_walk-based implementation this caused an infinite loop,
		// hence the cap on the number of syncs: it makes the test fail instead of hanging.
		$synced_ids = array();
		$callback   = function ( $product_id ) use ( &$synced_ids, $product_1, $product_2 ) {
			$synced_ids[] = $product_id;
			if ( count( $synced_ids ) > 100 ) {
				$this->fail( 'do_deferred_product_sync does not terminate when synced products keep re-deferring each other' );
			}
			wc_deferred_product_sync( $product_1->get_id() === $product_id ? $product_2->get_id() : $product_1->get_id() );
		};
		add_action( 'woocommerce_update_product', $callback );

		wc_deferred_product_sync( $product_1->get_id() );

		WC_Post_Data::do_deferred_product_sync();

		remove_action( 'woocommerce_update_product', $callback );

		$this->assertSame( array( $product_1->get_id(), $product_2->get_id() ), $synced_ids, 'Each product should be synced at most once per request' );
		$this->assertEmpty( $wc_deferred_product_sync, 'The queue should be empty after the sync' );
	}

	/**
	 * @testdox Renaming an attribute term should rename the defaults of several products together and keep a default saved while the rename runs.
	 */
	public function test_renaming_attribute_term_keeps_defaults_saved_during_the_rename(): void {
		global $wpdb;
		$attribute = WC_Helper_Product::create_attribute( 'color', array( 'blue' ) );
		$taxonomy  = $attribute['attribute_taxonomy'];
		$products  = array();
		foreach ( array( 'renamed', 'saved' ) as $label ) {
			$products[ $label ] = new WC_Product_Variable();
			$products[ $label ]->set_default_attributes(
				array(
					$taxonomy => 'blue',
					'note'    => $label,
				)
			);
			$products[ $label ]->save();
		}
		$saved_meanwhile = array(
			$taxonomy => 'blue',
			'note'    => 'saved meanwhile',
		);
		// Another request saves the second product between the rename reading the rows and writing them.
		$save = function ( $query ) use ( $wpdb, $products, $saved_meanwhile, &$save ) {
			if ( 0 === strpos( $query, "UPDATE {$wpdb->postmeta} SET meta_value = CASE" ) ) {
				remove_filter( 'query', $save );
				$wpdb->update(
					$wpdb->postmeta,
					array( 'meta_value' => serialize( $saved_meanwhile ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value, WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
					array(
						'post_id'  => $products['saved']->get_id(),
						'meta_key' => '_default_attributes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					)
				);
			}
			return $query;
		};
		add_filter( 'query', $save );

		wp_update_term( $attribute['term_ids'][0], $taxonomy, array( 'slug' => 'navy-blue' ) );
		remove_filter( 'query', $save );

		$this->assertSame(
			array(
				$taxonomy => 'navy-blue',
				'note'    => 'renamed',
			),
			get_post_meta( $products['renamed']->get_id(), '_default_attributes', true ),
			'The default for the renamed term should change'
		);
		$this->assertSame( $saved_meanwhile, get_post_meta( $products['saved']->get_id(), '_default_attributes', true ), 'A default saved during the rename should be kept' );
	}

	/**
	 * @testdox Renaming an attribute term should update the default attributes that use it and leave other default values unchanged.
	 */
	public function test_renaming_attribute_term_updates_only_matching_default_attributes(): void {
		$attribute = WC_Helper_Product::create_attribute( 'color', array( 'blue' ) );
		$taxonomy  = $attribute['attribute_taxonomy'];
		// A custom attribute value can hold the same text as the stored taxonomy/slug pair.
		$custom_value = serialize( $taxonomy ) . serialize( 'blue' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$product      = new WC_Product_Variable();
		$product->set_default_attributes(
			array(
				$taxonomy => 'blue',
				'note'    => $custom_value,
			)
		);
		$product->save();

		wp_update_term( $attribute['term_ids'][0], $taxonomy, array( 'slug' => 'navy-blue' ) );

		$this->assertSame(
			array(
				$taxonomy => 'navy-blue',
				'note'    => $custom_value,
			),
			get_post_meta( $product->get_id(), '_default_attributes', true ),
			'Only the default for the renamed term should change'
		);
	}

	/**
	 * @testdox Renaming an attribute term should leave default attributes that hold objects unchanged, without creating the objects.
	 */
	public function test_renaming_attribute_term_leaves_default_attributes_with_objects_alone(): void {
		global $wpdb;

		ObjectInstantiationProbe::$was_unserialized = false;
		$attribute                                  = WC_Helper_Product::create_attribute( 'color', array( 'blue' ) );
		$taxonomy                                   = $attribute['attribute_taxonomy'];
		$product                                    = new WC_Product_Variable();
		$product->set_default_attributes( array( $taxonomy => 'blue' ) );
		$product->save();
		// Store the row directly, as only a corrupted row can hold an object.
		$stored = serialize( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			array(
				$taxonomy => 'blue',
				'probe'   => new ObjectInstantiationProbe(),
			)
		);
		$wpdb->update(
			$wpdb->postmeta,
			array( 'meta_value' => $stored ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			array(
				'post_id'  => $product->get_id(),
				'meta_key' => '_default_attributes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);

		wp_update_term( $attribute['term_ids'][0], $taxonomy, array( 'slug' => 'navy-blue' ) );

		$this->assertFalse( ObjectInstantiationProbe::$was_unserialized, 'The rename must not create objects stored in default attributes' );
		$this->assertSame(
			$stored,
			$wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_default_attributes'", $product->get_id() ) ),
			'A row holding an object should not be rewritten'
		);
	}
}
