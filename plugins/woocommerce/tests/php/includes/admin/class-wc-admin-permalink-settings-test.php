<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Admin_Permalink_Settings.
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Admin_Permalink_Settings_Test extends WC_Unit_Test_Case {

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
	 */
	private function activate_french_product_slug_translation(): void {
		$translate_product_slug = static function ( string $translation, string $text, string $context, string $domain ): string {
			if ( 'woocommerce' === $domain && 'slug' === $context && 'product' === $text && 'fr_FR' === determine_locale() ) {
				return 'produit';
			}

			return $translation;
		};

		add_filter( 'gettext_with_context', $translate_product_slug, 10, 4 );
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
	 * Both posted values are typed loosely on purpose: the fields are free-form request input, and
	 * some tests post arrays to exercise the non-string fallbacks.
	 *
	 * @param mixed $product_permalink           Posted `product_permalink` radio value.
	 * @param mixed $product_permalink_structure Posted `product_permalink_structure` value, or null to leave the field out.
	 * @return string Rendered settings HTML.
	 */
	private function save_and_render( $product_permalink, $product_permalink_structure = null ): string {
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

		$_POST = array();

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
	 * @param string   $html        Rendered settings HTML.
	 * @param string   $expected_id One of the $labels entries.
	 * @param string[] $labels      Rows expected to render, in document order. Defaults to all four.
	 */
	private function assert_only_radio_checked( string $html, string $expected_id, array $labels = array( 'default', 'shop_base', 'shop_base_category', 'custom' ) ): void {
		$xpath  = $this->get_xpath( $html );
		$radios = $xpath->query( '//input[@name="product_permalink"]' );

		$this->assertSame( count( $labels ), $radios->length, 'Unexpected number of product_permalink radios in the rendered markup.' );

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
	 * A multilingual plugin can filter `locale` per request, which moves get_locale() and the
	 * wc_switch_to_site_locale() window away from the configured site locale. The Default base is
	 * initialized and compared in the configured site locale, so the screen must keep reporting
	 * Default for the base wc_get_permalink_structure() persisted.
	 *
	 * @testdox Should keep "Default" checked when a locale filter changes the request locale.
	 */
	public function test_default_structure_stays_checked_when_a_locale_filter_changes_the_request_locale(): void {
		$this->ensure_shop_page();
		$this->activate_french_product_slug_translation();
		add_filter( 'locale', static fn() => 'fr_FR' );
		$this->assertSame( 'fr_FR', determine_locale(), 'The request locale should follow the locale filter.' );

		delete_option( 'woocommerce_permalinks' );
		wc_get_permalink_structure();
		$html = $this->render_settings();

		$this->assertSame( 'product', get_option( 'woocommerce_permalinks' )['product_base'], 'The Default base should be initialized in the site locale.' );
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
	 * The Custom base field is the next tab stop after the radio group, and focusing it selects
	 * Custom base — so a Tab keystroke is enough to post the field's prefilled Default structure
	 * through the custom branch. That branch prepends a slash, and the slash-prefixed form is not
	 * interchangeable with the bare slug the Default radio stores: under index.php (PATHINFO)
	 * permalinks it produces an `index.php//product/%product%` permastruct whose URLs do not
	 * resolve. The save path therefore converges the two, storing Default's bare form.
	 *
	 * @testdox Should store the bare Default base and keep "Default" checked when its own structure is saved through the Custom base field.
	 */
	public function test_default_structure_saved_as_a_custom_base_is_normalized(): void {
		$this->ensure_shop_page();

		$xpath        = $this->get_xpath( $this->render_settings() );
		$custom_input = $xpath->query( '//input[@id="woocommerce_permalink_structure"]' )->item( 0 );
		$this->assertInstanceOf( DOMElement::class, $custom_input );

		$html = $this->save_and_render( 'custom', $custom_input->getAttribute( 'value' ) );

		$this->assertSame( 'product', get_option( 'woocommerce_permalinks' )['product_base'], 'The Default-equivalent custom base should be normalized to the bare slug.' );
		$this->assert_only_radio_checked( $html, 'default' );
	}

	/**
	 * A pre-existing slash-prefixed `/product` — persisted by versions that stored the Tab-saved
	 * Default structure verbatim — is reported honestly as a Custom base rather than as Default:
	 * under PATHINFO permalinks the stored form genuinely behaves differently, and nothing is
	 * rewritten on render. Saving any predefined choice from there converges the stored value.
	 *
	 * @testdox Should report a legacy slash-prefixed Default base as a Custom base until a save converges it.
	 */
	public function test_legacy_slash_prefixed_default_base_shows_as_custom(): void {
		$this->ensure_shop_page();

		$permalinks                 = (array) get_option( 'woocommerce_permalinks', array() );
		$permalinks['product_base'] = '/product';
		update_option( 'woocommerce_permalinks', $permalinks );

		$html         = $this->render_settings();
		$custom_input = $this->get_xpath( $html )->query( '//input[@id="woocommerce_permalink_structure"]' )->item( 0 );

		$this->assertSame( '/product', get_option( 'woocommerce_permalinks' )['product_base'], 'The render must not rewrite the stored value.' );
		$this->assertInstanceOf( DOMElement::class, $custom_input );
		$this->assertSame( '/product/', $custom_input->getAttribute( 'value' ) );
		$this->assert_only_radio_checked( $html, 'custom' );

		// A save posting that same value through the custom branch converges it to the bare form.
		$this->assert_only_radio_checked( $this->save_and_render( 'custom', '/product/' ), 'default' );
		$this->assertSame( 'product', get_option( 'woocommerce_permalinks' )['product_base'] );
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
	 * A Shop page slug equal to the default product slug makes "Default" and "Shop base"
	 * indistinguishable once stored: both persist the bare default base, so the checked-state
	 * search — which maps a stored base back to whichever predefined choice would persist it —
	 * has two valid answers and reports the first.
	 *
	 * Reporting "Shop base" instead would only move the wrong label to the merchant who picked
	 * Default. The two forms cannot be told apart at storage either: the slashed `/product` that
	 * would distinguish them is the shape that breaks PATHINFO permalinks, which is why the base
	 * is normalized to the bare form in the first place.
	 *
	 * Nothing downstream depends on which label renders — the stored base is byte-identical, so
	 * the product URLs are too.
	 *
	 * @testdox Should report "Default" when the Shop slug makes both choices store the same base.
	 */
	public function test_shop_base_equal_to_the_default_base_reports_as_default(): void {
		$shop_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Product',
				'post_name'   => 'product',
				'post_status' => 'publish',
			)
		);
		update_option( 'woocommerce_shop_page_id', $shop_id );

		$base_slug = urldecode( get_page_uri( $shop_id ) );
		$this->assertSame( 'product', $base_slug, 'The fixture Shop page should share the default product slug.' );

		$html = $this->save_and_render( '/' . trailingslashit( $base_slug ) );

		$this->assertSame( 'product', get_option( 'woocommerce_permalinks' )['product_base'], 'Both choices store the bare default base.' );
		$this->assert_only_radio_checked( $html, 'default' );
	}


	/**
	 * A custom base is normalized before it is stored: every `#` is removed so the base cannot
	 * open a URL fragment, and each run of slashes collapses into one.
	 *
	 * @testdox Should collapse repeated slashes and remove hashes from a custom base.
	 */
	public function test_custom_base_repeated_slashes_and_hashes_are_normalized(): void {
		$this->ensure_shop_page();

		$html = $this->save_and_render( 'custom', '//widgets///gad#gets' );

		$this->assertSame( '/widgets/gadgets', get_option( 'woocommerce_permalinks' )['product_base'] );
		$this->assert_only_radio_checked( $html, 'custom' );
	}

	/**
	 * A base of nothing but the category token gives products the same URL shape as the category
	 * archives they sit under, so the two collide. The save path prefixes the default base rather
	 * than storing the token alone. Guard carried unchanged from #13374.
	 *
	 * @testdox Should prefix the default base when a custom base is nothing but the category token.
	 *
	 * @testWith ["%product_cat%"]
	 *           ["/%product_cat%/"]
	 *           ["//%product_cat%//"]
	 *
	 * @param string $posted_structure Custom base as posted by the form.
	 */
	public function test_custom_base_of_only_the_category_token_is_prefixed( string $posted_structure ): void {
		$this->ensure_shop_page();

		$html = $this->save_and_render( 'custom', $posted_structure );

		$this->assertSame( '/product/%product_cat%', get_option( 'woocommerce_permalinks' )['product_base'] );
		$this->assert_only_radio_checked( $html, 'custom' );
	}

	/**
	 * The Shop rows are gated on wc_get_page_id( 'shop' ), which returns 0 when the
	 * woocommerce_get_shop_page_id filter yields a truthy non-numeric value. A stored base
	 * matching a hidden Shop row must fall back to Custom base — otherwise no rendered radio is
	 * checked at all.
	 *
	 * @testdox Should check "Custom base" when the stored structure's Shop row is not rendered.
	 */
	public function test_shop_structure_falls_back_to_custom_when_shop_rows_are_hidden(): void {
		$permalinks                 = (array) get_option( 'woocommerce_permalinks', array() );
		$permalinks['product_base'] = '/shop';
		update_option( 'woocommerce_permalinks', $permalinks );

		add_filter( 'woocommerce_get_shop_page_id', static fn() => 'abc' );

		// Only the Default and Custom base rows render without a Shop page.
		$this->assert_only_radio_checked( $this->render_settings(), 'custom', array( 'default', 'custom' ) );
	}

	/**
	 * A custom base carrying no usable characters sanitizes down to the empty string, and an empty
	 * product_base never survives: wc_get_permalink_structure() drops it and refills the option
	 * from the request locale, outside any locale window, then writes it back. An administrator
	 * browsing in their own language therefore persisted that language's slug. Resolving to the
	 * site-locale default at save time keeps the stored value deterministic.
	 *
	 * The locales have to diverge for this to be observable: when they agree, the refill produces
	 * the same slug the fix stores and nothing looks wrong.
	 *
	 * @testdox Should store the site-locale Default base when the posted custom structure sanitizes to nothing.
	 *
	 * @testWith [""]
	 *           ["   "]
	 *           ["###"]
	 *           ["/"]
	 *           ["///"]
	 *
	 * @param string $posted_structure Posted `product_permalink_structure` value.
	 */
	public function test_custom_base_that_sanitizes_to_nothing_falls_back_to_the_default_base( string $posted_structure ): void {
		$this->ensure_shop_page();
		$this->set_up_french_admin_user();
		$this->activate_french_product_slug_translation();

		$html = $this->save_and_render( 'custom', $posted_structure );

		$this->assertSame( 'product', get_option( 'woocommerce_permalinks' )['product_base'], 'An empty base must never reach the option, where the request locale would refill it.' );
		$this->assert_only_radio_checked( $html, 'default' );
	}

	/**
	 * Both permalink fields are free-form request input; an array reaching trim() or
	 * wc_sanitize_permalink() is a fatal, since both are declared to take a string. The custom
	 * branch has its own path: a scalar 'custom' radio with an array structure field used to store
	 * '/', which wc_sanitize_permalink() collapses to '', leaving wc_get_permalink_structure() to
	 * refill the option in the next request's locale. Both now resolve to the default base
	 * directly, in the site locale.
	 *
	 * @testdox Should fall back to the Default base when a posted permalink field is not a string.
	 *
	 * @testWith [["custom"], ["widgets"]]
	 *           ["custom", ["widgets"]]
	 *
	 * @param mixed $product_permalink           Posted `product_permalink` value.
	 * @param mixed $product_permalink_structure Posted `product_permalink_structure` value.
	 */
	public function test_non_string_posted_fields_fall_back_to_the_default_base( $product_permalink, $product_permalink_structure ): void {
		$this->ensure_shop_page();

		$html = $this->save_and_render( $product_permalink, $product_permalink_structure );

		$this->assertSame( 'product', get_option( 'woocommerce_permalinks' )['product_base'] );
		$this->assert_only_radio_checked( $html, 'default' );
	}
}
