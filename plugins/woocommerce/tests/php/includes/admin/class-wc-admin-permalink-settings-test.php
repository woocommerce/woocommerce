<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Admin_Permalink_Settings.
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Admin_Permalink_Settings_Test extends WC_Unit_Test_Case {

	/**
	 * Cleanup callbacks registered by test helpers, run in tearDown() even when assertions fail.
	 *
	 * @var Closure[]
	 */
	private $registered_cleanups = array();

	/**
	 * Set up the admin context and load the class under test.
	 */
	public function setUp(): void {
		parent::setUp();
		set_current_screen( 'options-permalink' );
		require_once WC_ABSPATH . 'includes/admin/wc-admin-functions.php';
		require_once WC_ABSPATH . 'includes/admin/class-wc-admin-permalink-settings.php';
	}

	/**
	 * Reset superglobals after each test.
	 *
	 * Options changed during a test need no manual restoration: WC_Unit_Test_Case wraps each test
	 * in a DB transaction and flushes the object cache, so option state reverts on its own.
	 */
	public function tearDown(): void {
		foreach ( array_reverse( $this->registered_cleanups ) as $cleanup ) {
			$cleanup();
		}
		$this->registered_cleanups = array();

		wp_set_current_user( 0 );
		$this->reset_permalink_post_data();
		parent::tearDown();
	}

	/**
	 * Remove every permalink field this test class writes to $_POST.
	 */
	private function reset_permalink_post_data(): void {
		unset(
			$_POST['permalink_structure'],
			$_POST['wc-permalinks-nonce'],
			$_POST['woocommerce_product_category_slug'],
			$_POST['woocommerce_product_tag_slug'],
			$_POST['woocommerce_product_attribute_slug'],
			$_POST['product_permalink'],
			$_POST['product_permalink_structure']
		);
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
		return (int) wc_create_page( 'shop', 'woocommerce_shop_page_id', 'Shop' );
	}

	/**
	 * Create a Shop page nested under a parent, so its URI is multi-segment (`stores/shop`).
	 *
	 * The checked-state comparison runs both the rendered structure and the stored value through
	 * wc_sanitize_permalink(), which only holds up if a multi-segment URI survives it unchanged.
	 *
	 * @return int Shop page ID.
	 */
	private function ensure_nested_shop_page(): int {
		$parent_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Stores',
				'post_name'   => 'stores',
				'post_status' => 'publish',
			)
		);
		$shop_id   = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Shop',
				'post_name'   => 'shop',
				'post_status' => 'publish',
				'post_parent' => $parent_id,
			)
		);

		update_option( 'woocommerce_shop_page_id', $shop_id );

		return (int) $shop_id;
	}

	/**
	 * Translate the product slug to French, but only for requests running in fr_FR.
	 *
	 * Lets a test tell the two locales apart: the site-locale value stays `product`, so any
	 * `produit` that reaches the stored option or the comparison came from the request locale.
	 * Removal runs from the cleanup registry, so call sites need no try/finally unwinding.
	 */
	private function activate_french_product_slug_translation(): void {
		$translate_product_slug = static function ( string $translation, string $text, string $context, string $domain ): string {
			if ( 'woocommerce' === $domain && 'slug' === $context && 'product' === $text && 'fr_FR' === determine_locale() ) {
				return 'produit';
			}

			return $translation;
		};

		add_filter( 'gettext_with_context', $translate_product_slug, 10, 4 );
		$this->registered_cleanups[] = static function () use ( $translate_product_slug ): void {
			remove_filter( 'gettext_with_context', $translate_product_slug, 10 );
		};
	}

	/**
	 * Simulate an admin request whose user locale (fr_FR) diverges from the en_US site locale.
	 *
	 * This is the divergence the fix closes: WordPress resolves admin translations in the current
	 * user's language, while both the save path and the checked-state comparison must resolve the
	 * persisted slug in the site's language.
	 */
	private function set_up_french_admin_user(): void {
		$user_id = self::factory()->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'fr_FR',
			)
		);
		wp_set_current_user( $user_id );

		$this->assertSame( 'en_US', get_locale(), 'The site locale should remain English.' );
		$this->assertSame( 'fr_FR', determine_locale(), 'The admin request should use the current user locale.' );
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

		$this->reset_permalink_post_data();

		// Second request (post-redirect): a fresh instance reads back the now-persisted value.
		return $this->render_settings();
	}

	/**
	 * Render the permalink settings section HTML from a fresh instance.
	 *
	 * @return string Rendered settings HTML.
	 */
	private function render_settings(): string {
		$sut = new WC_Admin_Permalink_Settings();

		return (string) $this->capture_output_from( array( $sut, 'settings' ) );
	}

	/**
	 * Parse rendered settings HTML into an XPath query object.
	 *
	 * @param string $html Rendered settings HTML.
	 * @return DOMXPath Query object for the parsed document.
	 */
	private function get_xpath( string $html ): DOMXPath {
		$document       = new DOMDocument();
		$previous_state = libxml_use_internal_errors( true );
		$loaded         = $document->loadHTML( $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		$this->assertTrue( $loaded, 'The permalink settings output should be valid enough for DOM parsing.' );

		return new DOMXPath( $document );
	}

	/**
	 * Assert that exactly one of the four product permalink radios is checked, and that it's the expected one.
	 *
	 * @param string $html        Rendered settings HTML.
	 * @param string $expected_id Either 'default', 'shop_base', 'shop_base_category', or 'custom'.
	 */
	private function assert_only_radio_checked( string $html, string $expected_id ): void {
		$xpath  = $this->get_xpath( $html );
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
	 * Issue #29050: every predefined structure reverted to "Custom base" on the next render,
	 * because the comparison used the raw radio values while the save path stored
	 * wc_sanitize_permalink() output — and mapped the Default radio's empty value to a slug.
	 *
	 * @testdox Should keep the saved structure checked, and store the same value as before, for every choice.
	 *
	 * @testWith ["default"]
	 *           ["shop_base"]
	 *           ["shop_base_category"]
	 *           ["custom"]
	 *
	 * @param string $choice Which product permalink option to save.
	 */
	public function test_saved_structure_stays_checked( string $choice ): void {
		$base_slug = urldecode( get_page_uri( $this->ensure_shop_page() ) );

		$cases = array(
			'default'            => array( '', null, 'product' ),
			'shop_base'          => array( '/' . trailingslashit( $base_slug ), null, '/' . $base_slug ),
			'shop_base_category' => array( '/' . trailingslashit( $base_slug ) . trailingslashit( '%product_cat%' ), null, '/' . $base_slug . '/%product_cat%' ),
			'custom'             => array( 'custom', 'widgets', '/widgets' ),
		);

		list( $posted_base, $posted_structure, $expected_stored ) = $cases[ $choice ];

		$html = $this->save_and_render( $posted_base, $posted_structure );

		$this->assertSame( $expected_stored, get_option( 'woocommerce_permalinks' )['product_base'], 'The stored product base must not change.' );
		$this->assert_only_radio_checked( $html, $choice );
	}

	/**
	 * settings_save() resolves the Default slug in the site locale; settings() has to compare
	 * against the same locale, or an administrator browsing the admin in their own language saves
	 * one translation and is shown the comparison against another.
	 *
	 * @testdox Should keep "Default" checked when the user and site locales differ.
	 */
	public function test_default_structure_stays_checked_when_user_and_site_locales_differ(): void {
		$this->ensure_shop_page();
		$this->set_up_french_admin_user();
		$this->activate_french_product_slug_translation();

		$html = $this->save_and_render( '' );

		$this->assertSame( 'product', get_option( 'woocommerce_permalinks' )['product_base'], 'The Default base should be stored in the site locale.' );
		$this->assert_only_radio_checked( $html, 'default' );
	}

	/**
	 * The Default radio keeps posting an empty value, so the payload stays byte-identical to what
	 * every earlier version submitted; the resolved structure moves to a data attribute that only
	 * the Custom-base field reads.
	 *
	 * @testdox Should expose the Default structure without changing the value it submits.
	 */
	public function test_default_radio_exposes_its_structure_without_changing_its_value(): void {
		$this->ensure_shop_page();

		$xpath         = $this->get_xpath( $this->save_and_render( '' ) );
		$default_radio = $xpath->query( '(//input[@name="product_permalink"])[1]' )->item( 0 );
		$custom_input  = $xpath->query( '//input[@id="woocommerce_permalink_structure"]' )->item( 0 );

		$this->assertInstanceOf( DOMElement::class, $default_radio );
		$this->assertInstanceOf( DOMElement::class, $custom_input );
		$this->assertSame( '', $default_radio->getAttribute( 'value' ), 'The Default radio value must remain empty.' );
		$this->assertSame( '/product/', $default_radio->getAttribute( 'data-permalink-structure' ) );
		$this->assertSame( '/product/', $custom_input->getAttribute( 'value' ) );
	}

	/**
	 * @testdox Should keep "Shop base" checked when the Shop page is nested under a parent.
	 */
	public function test_shop_base_stays_checked_for_a_nested_shop_page(): void {
		$base_slug = urldecode( get_page_uri( $this->ensure_nested_shop_page() ) );
		$this->assertSame( 'stores/shop', $base_slug, 'The fixture should produce a multi-segment page URI.' );

		$html = $this->save_and_render( '/' . trailingslashit( $base_slug ) );

		$this->assertSame( '/stores/shop', get_option( 'woocommerce_permalinks' )['product_base'] );
		$this->assert_only_radio_checked( $html, 'shop_base' );
	}

	/**
	 * Both permalink fields are free-form request input; an array reaching trim() or
	 * wc_sanitize_permalink() is a fatal, since both are declared to take a string.
	 *
	 * @testdox Should fall back to the Default base when the posted permalink fields are not scalar.
	 */
	public function test_non_scalar_posted_fields_fall_back_to_the_default_base(): void {
		$this->ensure_shop_page();

		$_POST['permalink_structure']                = '';
		$_POST['wc-permalinks-nonce']                = wp_create_nonce( 'wc-permalinks' );
		$_POST['woocommerce_product_category_slug']  = 'product-category';
		$_POST['woocommerce_product_tag_slug']       = 'product-tag';
		$_POST['woocommerce_product_attribute_slug'] = '';
		$_POST['product_permalink']                  = array( 'custom' );
		$_POST['product_permalink_structure']        = array( 'widgets' );

		new WC_Admin_Permalink_Settings();
		$this->reset_permalink_post_data();

		$this->assertSame( 'product', get_option( 'woocommerce_permalinks' )['product_base'] );
		$this->assert_only_radio_checked( $this->render_settings(), 'default' );
	}
}
