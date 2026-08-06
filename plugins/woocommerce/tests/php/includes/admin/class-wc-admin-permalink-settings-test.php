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
		require_once WC_ABSPATH . 'includes/admin/class-wc-admin-permalink-settings.php';
	}

	/**
	 * Reset superglobals after each test.
	 *
	 * Option changes made during the test (e.g. `woocommerce_permalinks`,
	 * `woocommerce_shop_page_id`) don't need manual restoration here: WC_Unit_Test_Case
	 * wraps every test in a DB transaction and flushes the object cache in its own
	 * tearDown(), so option state always reverts to its pre-test baseline automatically.
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
	 * Return a translation filter that distinguishes French permalink slugs from English ones.
	 *
	 * @return Closure Translation filter callback.
	 */
	private function get_french_permalink_slug_filter(): Closure {
		return static function ( string $translation, string $text, string $context, string $domain ): string {
			if ( 'woocommerce' !== $domain || ! in_array( $context, array( 'slug', 'default-slug' ), true ) || 'fr_FR' !== determine_locale() ) {
				return $translation;
			}

			$translations = array(
				'product'          => 'produit',
				'product-category' => 'categorie-produit',
				'product-tag'      => 'etiquette-produit',
			);

			return $translations[ $text ] ?? $translation;
		};
	}

	/**
	 * Make switch_to_locale() treat the given locale as installed.
	 *
	 * The test environment ships no language packs and WP_Locale_Switcher captures the available
	 * languages at bootstrap, so a real switch to any non-en_US locale silently fails without this.
	 * Restoration is registered for tearDown(), so it runs even when an assertion fails mid-test.
	 *
	 * @param string $locale Locale to mark as switchable.
	 */
	private function add_switchable_locale( string $locale ): void {
		global $wp_locale_switcher;

		$property = new ReflectionProperty( WP_Locale_Switcher::class, 'available_languages' );
		$property->setAccessible( true );
		$original_languages = $property->getValue( $wp_locale_switcher );
		$property->setValue( $wp_locale_switcher, array_merge( $original_languages, array( $locale ) ) );

		$this->registered_cleanups[] = static function () use ( $property, $wp_locale_switcher, $original_languages ): void {
			$property->setValue( $wp_locale_switcher, $original_languages );
		};
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
	 * @testdox Should keep "Default" checked after saving the default structure.
	 */
	public function test_default_structure_stays_checked_after_save(): void {
		$this->ensure_shop_page();
		$html = $this->save_and_render( '' );

		$this->assert_only_radio_checked( $html, 'default' );
	}

	/**
	 * @testdox Should keep "Default" checked when the user and site locales differ.
	 */
	public function test_default_structure_stays_checked_when_user_and_site_locales_differ(): void {
		$this->ensure_shop_page();
		$user_id = self::factory()->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'fr_FR',
			)
		);
		wp_set_current_user( $user_id );

		$translate_permalink_slugs = $this->get_french_permalink_slug_filter();

		$this->assertSame( 'en_US', get_locale(), 'The site locale should remain English.' );
		$this->assertSame( 'fr_FR', determine_locale(), 'The admin request should use the current user locale.' );

		add_filter( 'gettext_with_context', $translate_permalink_slugs, 10, 4 );
		try {
			$html = $this->save_and_render( '' );
		} finally {
			remove_filter( 'gettext_with_context', $translate_permalink_slugs, 10 );
		}

		$this->assertSame( 'product', get_option( 'woocommerce_permalinks' )['product_base'], 'Default base should be stored in the site locale.' );
		$this->assert_only_radio_checked( $html, 'default' );
	}

	/**
	 * @testdox Should expose the site-locale Default structure without changing its submitted value.
	 */
	public function test_default_structure_exposes_site_locale_value_for_custom_input(): void {
		$this->ensure_shop_page();
		$user_id = self::factory()->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'fr_FR',
			)
		);
		wp_set_current_user( $user_id );

		$translate_permalink_slugs = $this->get_french_permalink_slug_filter();

		$this->assertSame( 'en_US', get_locale(), 'The site locale should remain English.' );
		$this->assertSame( 'fr_FR', determine_locale(), 'The admin request should use the current user locale.' );

		add_filter( 'gettext_with_context', $translate_permalink_slugs, 10, 4 );
		try {
			$html = $this->save_and_render( '' );
		} finally {
			remove_filter( 'gettext_with_context', $translate_permalink_slugs, 10 );
		}

		$this->assertSame( 'product', get_option( 'woocommerce_permalinks' )['product_base'], 'The legacy Default base should remain stored without slashes.' );

		$xpath         = $this->get_xpath( $html );
		$default_radio = $xpath->query( '(//input[@name="product_permalink"])[1]' )->item( 0 );
		$custom_input  = $xpath->query( '//input[@id="woocommerce_permalink_structure"]' )->item( 0 );
		$preview       = $xpath->query( '//code[contains(concat(" ", normalize-space(@class), " "), " non-default-example ")]' )->item( 0 );

		$this->assertInstanceOf( DOMElement::class, $default_radio );
		$this->assertInstanceOf( DOMElement::class, $custom_input );
		$this->assertInstanceOf( DOMElement::class, $preview );
		$this->assertSame( '', $default_radio->getAttribute( 'value' ), 'The legacy Default radio value must remain empty.' );
		$this->assertSame( '/product/', $default_radio->getAttribute( 'data-permalink-structure' ) );
		$this->assertSame( '/product/', $custom_input->getAttribute( 'value' ) );
		$preview_text = $preview->textContent; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		$this->assertStringContainsString( '/product/sample-product/', $preview_text );
		$this->assertStringNotContainsString( '/produit/sample-product/', $preview_text );
	}

	/**
	 * The initialization path (first call to wc_get_permalink_structure(), e.g. from a front-end
	 * request), the save path, and the render-time checked-state comparison must all resolve the
	 * same deterministic site locale on multilingual installs that filter `locale` per visitor.
	 * If any of them diverges, the Default radio falls back to Custom for stores that never
	 * explicitly saved permalinks.
	 *
	 * @testdox Should keep "Default" checked on visitor-locale-filtered installs that never saved permalinks.
	 */
	public function test_default_structure_stays_checked_on_filtered_locale_installs_without_saved_permalinks(): void {
		$this->ensure_shop_page();
		delete_option( 'woocommerce_permalinks' );

		$filter_locale             = static fn(): string => 'fr_FR';
		$translate_permalink_slugs = $this->get_french_permalink_slug_filter();

		add_filter( 'locale', $filter_locale, 5 );
		add_filter( 'gettext_with_context', $translate_permalink_slugs, 10, 4 );
		try {
			// First contact persists the defaults, before the merchant ever opens the screen.
			wc_get_permalink_structure();

			$html = $this->render_settings();
		} finally {
			remove_filter( 'gettext_with_context', $translate_permalink_slugs, 10 );
			remove_filter( 'locale', $filter_locale, 5 );
		}

		$this->assertSame( 'product', get_option( 'woocommerce_permalinks' )['product_base'], 'The initialized Default base should use the deterministic site locale, not the visitor language.' );
		$this->assert_only_radio_checked( $html, 'default' );
	}

	/**
	 * @testdox Should store the deterministic site-locale Default base when saving under a visitor locale filter.
	 */
	public function test_default_structure_save_is_deterministic_under_a_visitor_locale_filter(): void {
		$this->ensure_shop_page();
		$this->add_switchable_locale( 'fr_FR' );

		$filter_locale             = static fn(): string => 'fr_FR';
		$translate_permalink_slugs = $this->get_french_permalink_slug_filter();

		add_filter( 'locale', $filter_locale, 5 );
		add_filter( 'gettext_with_context', $translate_permalink_slugs, 10, 4 );
		try {
			$html = $this->save_and_render( '' );
		} finally {
			remove_filter( 'gettext_with_context', $translate_permalink_slugs, 10 );
			remove_filter( 'locale', $filter_locale, 5 );
		}

		$this->assertSame( 'product', get_option( 'woocommerce_permalinks' )['product_base'], 'Saving Default must store the site-locale base regardless of the request language.' );
		$this->assert_only_radio_checked( $html, 'default' );
	}

	/**
	 * @testdox Should initialize missing permalink defaults in the site locale.
	 */
	public function test_missing_permalink_defaults_are_initialized_in_site_locale(): void {
		$user_id = self::factory()->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'fr_FR',
			)
		);
		wp_set_current_user( $user_id );

		$this->assertSame( 'en_US', get_locale(), 'The site locale should remain English.' );
		$this->assertSame( 'fr_FR', determine_locale(), 'The admin request should use the current user locale.' );

		delete_option( 'woocommerce_permalinks' );
		$translate_permalink_slugs = $this->get_french_permalink_slug_filter();

		add_filter( 'gettext_with_context', $translate_permalink_slugs, 10, 4 );
		try {
			$permalinks = wc_get_permalink_structure();
		} finally {
			remove_filter( 'gettext_with_context', $translate_permalink_slugs, 10 );
		}

		$saved_permalinks = get_option( 'woocommerce_permalinks' );

		$this->assertSame( 'product', $permalinks['product_base'], 'The returned product base should use the site locale.' );
		$this->assertSame( 'product', $saved_permalinks['product_base'], 'The stored product base should use the site locale.' );
		$this->assertSame( 'product-category', $saved_permalinks['category_base'], 'The stored category base should use the site locale.' );
		$this->assertSame( 'product-tag', $saved_permalinks['tag_base'], 'The stored tag base should use the site locale.' );
		$this->assertSame( 'fr_FR', determine_locale(), 'The admin request locale should be restored.' );
	}

	/**
	 * @testdox Should initialize missing permalink defaults without using an uninitialized WooCommerce instance.
	 */
	public function test_missing_permalink_defaults_do_not_use_uninitialized_woocommerce(): void {
		$user_id = self::factory()->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'fr_FR',
			)
		);
		wp_set_current_user( $user_id );
		delete_option( 'woocommerce_permalinks' );

		$accessor_only_woocommerce = new class() {
			/**
			 * Number of textdomain reloads.
			 *
			 * @var int
			 */
			public $load_plugin_textdomain_calls = 0;

			/**
			 * Record a textdomain reload.
			 */
			public function load_plugin_textdomain(): void {
				++$this->load_plugin_textdomain_calls;
			}
		};

		$instance_property = new ReflectionProperty( WooCommerce::class, '_instance' );
		$instance_property->setAccessible( true );
		$original_instance         = $instance_property->getValue();
		$woocommerce_global_is_set = array_key_exists( 'woocommerce', $GLOBALS );
		$original_woocommerce      = $GLOBALS['woocommerce'] ?? null;
		$translate_permalink_slugs = $this->get_french_permalink_slug_filter();

		$instance_property->setValue( null, $accessor_only_woocommerce );
		unset( $GLOBALS['woocommerce'] );
		add_filter( 'gettext_with_context', $translate_permalink_slugs, 10, 4 );

		try {
			$permalinks = wc_get_permalink_structure();
		} finally {
			remove_filter( 'gettext_with_context', $translate_permalink_slugs, 10 );
			$instance_property->setValue( null, $original_instance );

			if ( $woocommerce_global_is_set ) {
				$GLOBALS['woocommerce'] = $original_woocommerce;
			} else {
				unset( $GLOBALS['woocommerce'] );
			}
		}

		$this->assertSame( 'product', $permalinks['product_base'], 'The returned product base should still use the site locale.' );
		$this->assertSame( 'fr_FR', determine_locale(), 'The admin request locale should be restored.' );
		$this->assertSame( 0, $accessor_only_woocommerce->load_plugin_textdomain_calls, 'The function should not fall back to the WooCommerce accessor.' );
	}

	/**
	 * Multilingual plugins (WPML/Polylang-style) filter `locale` per visitor, to whatever
	 * language the current request is browsing in. Since the initialized defaults are persisted
	 * site-wide and this can run on any front-end request, the first visitor's language must not
	 * decide the store's permalink bases: initialization stays pinned to the deterministic site
	 * locale from the stored settings.
	 *
	 * @testdox Should ignore a per-visitor `locale` filter when initializing missing permalink defaults.
	 */
	public function test_missing_permalink_defaults_ignore_the_visitor_locale_filter(): void {
		delete_option( 'woocommerce_permalinks' );
		$filter_locale             = static fn(): string => 'fr_FR';
		$translate_permalink_slugs = $this->get_french_permalink_slug_filter();

		add_filter( 'locale', $filter_locale, 5 );
		add_filter( 'gettext_with_context', $translate_permalink_slugs, 10, 4 );
		try {
			$permalinks = wc_get_permalink_structure();
		} finally {
			remove_filter( 'gettext_with_context', $translate_permalink_slugs, 10 );
			remove_filter( 'locale', $filter_locale, 5 );
		}

		$this->assertSame( 'product', $permalinks['product_base'], 'The visitor language filter must not decide the stored defaults.' );
		$this->assertSame( 'product', get_option( 'woocommerce_permalinks' )['product_base'], 'The stored product base should use the deterministic site locale.' );
	}

	/**
	 * Temporary locale switches (user-locale emails, cross-blog loops) must not leak into the
	 * stored defaults either, and the switch itself must survive the initialization untouched.
	 *
	 * @testdox Should ignore an active temporary locale switch when initializing missing permalink defaults.
	 */
	public function test_missing_permalink_defaults_ignore_a_temporary_locale_switch(): void {
		global $wp_locale_switcher;

		$this->add_switchable_locale( 'fr_FR' );

		$this->assertTrue( switch_to_locale( 'fr_FR' ), 'The test should establish a temporary switch away from the site locale.' );
		delete_option( 'woocommerce_permalinks' );

		$translate_permalink_slugs = $this->get_french_permalink_slug_filter();

		add_filter( 'gettext_with_context', $translate_permalink_slugs, 10, 4 );
		try {
			$permalinks = wc_get_permalink_structure();

			$this->assertSame( 'product', $permalinks['product_base'], 'A temporary locale switch must not leak into the stored defaults.' );
			$this->assertSame( 'fr_FR', $wp_locale_switcher->get_switched_locale(), 'The temporary switch should remain on the stack.' );
		} finally {
			remove_filter( 'gettext_with_context', $translate_permalink_slugs, 10 );
			restore_previous_locale();
		}
	}

	/**
	 * The combination of a per-visitor `locale` filter and an active temporary switch is exactly
	 * where nondeterminism would creep in — neither may decide what gets persisted.
	 *
	 * @testdox Should ignore both a visitor locale filter and an active temporary switch together.
	 */
	public function test_missing_permalink_defaults_ignore_filter_and_temporary_switch_combined(): void {
		global $wp_locale_switcher;

		$this->add_switchable_locale( 'fr_FR' );

		$filter_locale = static fn(): string => 'de_DE';
		add_filter( 'locale', $filter_locale, 5 );

		$this->assertTrue( switch_to_locale( 'fr_FR' ), 'The test should establish a temporary switch away from the site locale.' );
		delete_option( 'woocommerce_permalinks' );

		$translate_permalink_slugs = $this->get_french_permalink_slug_filter();

		add_filter( 'gettext_with_context', $translate_permalink_slugs, 10, 4 );
		try {
			$permalinks = wc_get_permalink_structure();

			$this->assertSame( 'product', $permalinks['product_base'], 'Neither the locale filter nor the temporary switch may leak into the stored defaults.' );
			$this->assertSame( 'fr_FR', $wp_locale_switcher->get_switched_locale(), 'The temporary switch should remain on the stack.' );
		} finally {
			remove_filter( 'gettext_with_context', $translate_permalink_slugs, 10 );
			restore_previous_locale();
			remove_filter( 'locale', $filter_locale, 5 );
		}
	}

	/**
	 * @testdox Should preserve an existing locale switch when defaults already run in the site locale.
	 */
	public function test_missing_permalink_defaults_preserve_existing_site_locale_switch(): void {
		global $wp_locale_switcher;

		$user_id = self::factory()->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'fr_FR',
			)
		);
		wp_set_current_user( $user_id );

		$this->assertTrue( switch_to_locale( 'en_US' ), 'The test should establish an outer switch to the site locale.' );
		delete_option( 'woocommerce_permalinks' );

		try {
			$permalinks = wc_get_permalink_structure();

			$this->assertSame( 'product', $permalinks['product_base'], 'The product base should use the active site locale.' );
			$this->assertSame( 'en_US', $wp_locale_switcher->get_switched_locale(), 'The existing locale switch should remain on the stack.' );
		} finally {
			restore_previous_locale();
		}
	}

	/**
	 * @testdox Should initialize missing permalink defaults in the current site's locale on multisite.
	 */
	public function test_missing_permalink_defaults_use_current_site_locale_on_multisite(): void {
		$this->skipWithoutMultisite();

		$original_locale = $GLOBALS['locale'] ?? null;
		$subsite_id      = self::factory()->blog->create();
		$user_id         = self::factory()->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'fr_FR',
			)
		);
		wp_set_current_user( $user_id );
		switch_to_blog( $subsite_id );
		update_option( 'WPLANG', 'en_US' );
		delete_option( 'woocommerce_permalinks' );

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate cross-blog locale caching.
		$GLOBALS['locale']         = 'fr_FR';
		$translate_permalink_slugs = $this->get_french_permalink_slug_filter();

		add_filter( 'gettext_with_context', $translate_permalink_slugs, 10, 4 );
		try {
			$permalinks = wc_get_permalink_structure();
		} finally {
			remove_filter( 'gettext_with_context', $translate_permalink_slugs, 10 );
			restore_current_blog();

			if ( null === $original_locale ) {
				unset( $GLOBALS['locale'] );
			} else {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore test state.
				$GLOBALS['locale'] = $original_locale;
			}
		}

		$this->assertSame( 'product', $permalinks['product_base'], 'The product base should use the current subsite locale rather than the originating site locale cached in memory.' );
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
		$this->ensure_shop_page();
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
