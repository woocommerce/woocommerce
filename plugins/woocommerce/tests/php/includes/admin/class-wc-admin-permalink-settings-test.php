<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Admin_Permalink_Settings.
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Admin_Permalink_Settings_Test extends WC_Unit_Test_Case {

	/**
	 * The `woocommerce_shop_page_id` option value before the test, restored in tearDown().
	 *
	 * @var string|false
	 */
	private $original_shop_page_id;

	/**
	 * Set up the admin context and load the class under test.
	 */
	public function setUp(): void {
		parent::setUp();
		set_current_screen( 'options-permalink' );
		require_once WC_ABSPATH . 'includes/admin/class-wc-admin-permalink-settings.php';
		$this->original_shop_page_id = get_option( 'woocommerce_shop_page_id' );
	}

	/**
	 * Restore the original option and superglobals after each test.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_permalinks' );
		if ( false !== $this->original_shop_page_id ) {
			update_option( 'woocommerce_shop_page_id', $this->original_shop_page_id );
		}
		unset(
			$_POST['permalink_structure'],
			$_POST['wc-permalinks-nonce'],
			$_POST['woocommerce_product_category_slug'],
			$_POST['woocommerce_product_tag_slug'],
			$_POST['woocommerce_product_attribute_slug'],
			$_POST['product_permalink'],
			$_POST['product_permalink_structure']
		);
		parent::tearDown();
	}

	/**
	 * Ensure `wc_get_page_id( 'shop' )` resolves to a real, existing post.
	 *
	 * The install-time `woocommerce_shop_page_id` option can point at a page whose row was
	 * rolled back by an earlier test's DB transaction, leaving a stale ID with no matching post.
	 *
	 * @return int Shop page ID.
	 */
	private function ensure_shop_page(): int {
		$shop_page_id = wc_get_page_id( 'shop' );

		if ( $shop_page_id > 0 && get_post( $shop_page_id ) ) {
			return $shop_page_id;
		}

		$shop_page_id = (int) wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Shop',
				'post_name'   => 'shop',
			)
		);
		update_option( 'woocommerce_shop_page_id', $shop_page_id );

		return $shop_page_id;
	}

	/**
	 * Save a product permalink choice through the real save path and render the settings HTML.
	 *
	 * WordPress's own Permalinks page redirects after processing the POST (see
	 * `wp-admin/options-permalink.php`), so a save and its resulting render never happen on the
	 * same `WC_Admin_Permalink_Settings` instance in production. Mirror that with two separate
	 * instantiations rather than reusing one.
	 *
	 * @param string      $product_permalink           Posted `product_permalink` radio value.
	 * @param string|null $product_permalink_structure Posted `product_permalink_structure` text value, if any.
	 * @return string Rendered settings HTML.
	 */
	private function save_and_render( string $product_permalink, ?string $product_permalink_structure = null ): string {
		$_POST['permalink_structure']                = '';
		$_POST['wc-permalinks-nonce']                = wp_create_nonce( 'wc-permalinks' );
		$_POST['woocommerce_product_category_slug']  = 'product-category';
		$_POST['woocommerce_product_tag_slug']       = 'product-tag';
		$_POST['woocommerce_product_attribute_slug'] = '';
		$_POST['product_permalink']                  = $product_permalink;

		if ( null !== $product_permalink_structure ) {
			$_POST['product_permalink_structure'] = $product_permalink_structure;
		} else {
			unset( $_POST['product_permalink_structure'] );
		}

		// First request: the save-time instance persists the new structure and is discarded.
		new WC_Admin_Permalink_Settings();

		unset(
			$_POST['permalink_structure'],
			$_POST['wc-permalinks-nonce'],
			$_POST['woocommerce_product_category_slug'],
			$_POST['woocommerce_product_tag_slug'],
			$_POST['woocommerce_product_attribute_slug'],
			$_POST['product_permalink'],
			$_POST['product_permalink_structure']
		);

		// Second request (post-redirect): a fresh instance reads back the now-persisted value.
		$sut = new WC_Admin_Permalink_Settings();

		ob_start();
		try {
			$sut->settings();
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		return $output;
	}

	/**
	 * Assert that exactly one of the four product permalink radios is checked, and that it's the expected one.
	 *
	 * @param string $html        Rendered settings HTML.
	 * @param string $expected_id Either 'default', 'shop_base', 'shop_base_category', or 'custom'.
	 */
	private function assert_only_radio_checked( string $html, string $expected_id ): void {
		$document       = new DOMDocument();
		$previous_state = libxml_use_internal_errors( true );
		$loaded         = $document->loadHTML( $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		$this->assertTrue( $loaded, 'The permalink settings output should be valid enough for DOM parsing.' );

		$xpath  = new DOMXPath( $document );
		$radios = $xpath->query( '//input[@name="product_permalink"]' );

		$this->assertSame( 4, $radios->length, 'Expected exactly 4 product_permalink radios in the rendered markup.' );

		$labels         = array( 'default', 'shop_base', 'shop_base_category', 'custom' );
		$checked_labels = array();

		foreach ( $radios as $index => $radio ) {
			if ( $radio->hasAttribute( 'checked' ) ) {
				$checked_labels[] = $labels[ $index ];
			}
		}

		$this->assertSame(
			array( $expected_id ),
			$checked_labels,
			"Expected only the '{$expected_id}' radio to be checked."
		);
	}

	/**
	 * @testdox Should keep "Default" checked after saving the default structure.
	 */
	public function test_default_structure_stays_checked_after_save(): void {
		$html = $this->save_and_render( '' );

		$this->assert_only_radio_checked( $html, 'default' );
	}

	/**
	 * @testdox Should keep "Shop base" checked after saving the shop-base structure.
	 */
	public function test_shop_base_structure_stays_checked_after_save(): void {
		$shop_page_id = $this->ensure_shop_page();
		$base_slug    = urldecode( get_page_uri( $shop_page_id ) );
		$html         = $this->save_and_render( '/' . trailingslashit( $base_slug ) );

		$this->assert_only_radio_checked( $html, 'shop_base' );
	}

	/**
	 * @testdox Should keep "Shop base with category" checked after saving that structure.
	 */
	public function test_shop_base_with_category_structure_stays_checked_after_save(): void {
		$shop_page_id = $this->ensure_shop_page();
		$base_slug    = urldecode( get_page_uri( $shop_page_id ) );
		$html         = $this->save_and_render( '/' . trailingslashit( $base_slug ) . trailingslashit( '%product_cat%' ) );

		$this->assert_only_radio_checked( $html, 'shop_base_category' );
	}

	/**
	 * @testdox Should keep "Custom base" checked after saving a genuinely custom structure.
	 */
	public function test_custom_structure_stays_checked_after_save(): void {
		$html = $this->save_and_render( 'custom', 'widgets' );

		$this->assert_only_radio_checked( $html, 'custom' );
		$this->assertSame( '/widgets', get_option( 'woocommerce_permalinks' )['product_base'], 'Custom base should be stored exactly as sanitized.' );
	}

	/**
	 * @testdox Should not change the stored product_base for any predefined structure.
	 */
	public function test_predefined_structures_store_the_same_sanitized_value_as_before(): void {
		$this->save_and_render( '' );
		$this->assertSame( 'product', get_option( 'woocommerce_permalinks' )['product_base'] );

		$shop_page_id = $this->ensure_shop_page();
		$base_slug    = urldecode( get_page_uri( $shop_page_id ) );

		$this->save_and_render( '/' . trailingslashit( $base_slug ) );
		$this->assertSame( '/' . $base_slug, get_option( 'woocommerce_permalinks' )['product_base'] );

		$this->save_and_render( '/' . trailingslashit( $base_slug ) . trailingslashit( '%product_cat%' ) );
		$this->assertSame( '/' . $base_slug . '/%product_cat%', get_option( 'woocommerce_permalinks' )['product_base'] );
	}
}
