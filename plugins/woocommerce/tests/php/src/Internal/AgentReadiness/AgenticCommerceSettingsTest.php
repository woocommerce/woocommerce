<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgenticCommerceSettings;
use Automattic\WooCommerce\Internal\AgentReadiness\AgenticCommerceSettingsPage;
use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use WC_Admin_Settings;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the Agentic commerce settings tab.
 */
class AgenticCommerceSettingsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var AgenticCommerceSettings
	 */
	private $sut;

	/**
	 * Load the settings page base class and act as an admin in wp-admin.
	 */
	public function setUp(): void {
		parent::setUp();
		// Report tests stay offline: store output checks would load the test site's pages.
		StoreOutputChecksTest::block_requests();
		include_once WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';
		include_once WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php';
		set_current_screen( 'dashboard' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		update_option( 'woocommerce_feature_ucp_enabled', 'no' );
		$this->sut = wc_get_container()->get( AgenticCommerceSettings::class );
	}

	/**
	 * Leave the admin screen, which is a global outside the rollback.
	 */
	public function tearDown(): void {
		try {
			set_current_screen( 'front' );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox The tab is added only while the agent_readiness feature is enabled.
	 */
	public function test_tab_only_with_feature_enabled(): void {
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'no' );
		remove_all_filters( 'woocommerce_get_settings_pages' );
		$this->sut->handle_init();
		$this->assertFalse( has_filter( 'woocommerce_get_settings_pages' ), 'No tab with the feature off' );

		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		update_option( 'woocommerce_feature_ucp_enabled', 'no' );
		$this->sut->handle_init();
		$pages = apply_filters( 'woocommerce_get_settings_pages', array() );

		$this->assertCount( 1, $pages );
		$this->assertInstanceOf( AgenticCommerceSettingsPage::class, $pages[0] );
		$this->assertSame( 'agentic-commerce', $pages[0]->get_id() );
	}

	/**
	 * @testdox Before the first scan the page asks for one instead of showing counts; dropdowns list published pages only.
	 */
	public function test_output_without_report(): void {
		delete_option( AgentReadiness::REPORT_OPTION );
		self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Published returns page',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
				'post_title'  => 'Draft returns page',
			)
		);

		$html = $this->render_page();

		$this->assertStringContainsString( 'Product counts appear after the first agent readiness check.', $html );
		$this->assertStringNotContainsString( 'View products', $html );
		$this->assertStringContainsString( 'Published returns page', $html );
		$this->assertStringNotContainsString( 'Draft returns page', $html );
	}

	/**
	 * @testdox The page shows the three counts from the stored report, the switch and failing checks.
	 */
	public function test_output_with_report_and_failing_checks(): void {
		remove_all_filters( 'pre_option_home' );
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'home', 'http://example.org' );
		WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_external_product();
		wc_get_container()->get( AgentReadiness::class )->refresh_report();

		$html = $this->render_page();

		$this->assertStringContainsString( '<span>Available to agents</span> <strong>1</strong>', $html );
		$this->assertStringContainsString( '<span>Not supported yet</span> <strong>1</strong>', $html );
		$this->assertStringContainsString( 'Simple and variable products only, for now.', $html );
		$this->assertStringNotContainsString( 'aren&#039;t shown', $html );
		$this->assertStringContainsString( '<span>Available to agents</span> <strong>1</strong></li>', $html, 'No list matches the available count, so it has no link' );
		$this->assertStringContainsString( 'product_type=simple,variable&#038;product_visibility=exclude-from-catalog', $html, 'The hidden link matches its count' );
		$this->assertStringContainsString( 'aria-checked="false"', $html );
		$this->assertStringNotContainsString( 'to turn this on', $html );
		$this->assertMatchesRegularExpression( '/but \d+ required setup checks? below still needs? fixing\./', (string) wp_scripts()->get_data( 'wc-agentic-commerce-settings', 'data' ), 'Turning the switch on shows the failing checks without a reload' );
		$this->assertStringContainsString( 'wc-agentic-commerce-check--required', $html, 'Failing required checks are in the Required group' );
		$this->assertMatchesRegularExpression( '/(Choose a|Publish your) privacy policy page/', $html, 'An open check says what to do' );
		$this->assertStringContainsString( 'Test your setup', $html, 'Placeholder until WOOAI-1619' );
		$this->assertStringContainsString( '<h2>Security</h2>', $html, 'Placeholder until WOOAI-1620' );
		$this->assertStringNotContainsString( 'Universal Commerce Protocol (UCP) is off', $html, 'UCP is the switch, not a listed check' );
	}

	/**
	 * @testdox The switch message counts failing required checks, singular for one, and says ready for none.
	 */
	public function test_on_message_counts(): void {
		$on_message = new \ReflectionMethod( AgenticCommerceSettingsPage::class, 'on_message' );
		$on_message->setAccessible( true );

		$this->assertStringContainsString( 'but 1 required setup check below still needs fixing.', $on_message->invoke( null, 1 ) );
		$this->assertStringContainsString( 'but 3 required setup checks below still need fixing.', $on_message->invoke( null, 3 ) );
		$this->assertSame( 'AI agents can now find and shop your store.', $on_message->invoke( null, 0 ) );
	}

	/**
	 * @testdox With every required check passing, open checks are only Recommended and passing ones are Done.
	 */
	public function test_output_on_a_ready_store(): void {
		StoreConditionsTest::make_store_ready();

		$html = $this->render_page();

		$this->assertStringContainsString( 'Everything agents need is set up.', $html );
		$this->assertStringNotContainsString( 'wc-agentic-commerce-check--required', $html );
		$this->assertStringContainsString( 'wc-agentic-commerce-group--done', $html );
		$this->assertStringContainsString( 'Privacy policy page is selected', $html, 'Done rows are named by their passing state' );
		$this->assertStringNotContainsString( 'wc-agentic-commerce-notice', $html, 'No coming soon notice on a live store' );
	}

	/**
	 * @testdox A missing policy page is recommended, so it does not block the switch or the required group.
	 */
	public function test_missing_policy_page_is_recommended(): void {
		StoreConditionsTest::make_store_ready();
		delete_option( AgenticCommerceSettings::PRIVACY_PAGE_OPTION );

		$html = $this->render_page();

		$this->assertStringNotContainsString( 'wc-agentic-commerce-check--required', $html );
		$this->assertMatchesRegularExpression( '/wc-agentic-commerce-group--recommended.*Choose a privacy policy page/s', $html );
		$this->assertStringContainsString( 'Everything agents need is set up.', $html );
		$this->assertDoesNotMatchRegularExpression( '/<button[^>]*wc-agentic-commerce-ucp-switch[^>]*disabled=([\'"])disabled\\1/', $html );
	}

	/**
	 * @testdox In coming soon mode the switch card says so and links to Site visibility.
	 */
	public function test_output_coming_soon_notice_in_switch_card(): void {
		update_option( 'woocommerce_coming_soon', 'yes' );

		$html = $this->render_page();

		$this->assertMatchesRegularExpression( '/wc-agentic-commerce-switch-card.*wc-agentic-commerce-notice.*Go to Site visibility/s', $html );
		$this->assertLessThan( strpos( $html, 'wc-agentic-commerce-status-card' ), strpos( $html, 'wc-agentic-commerce-switch-card' ), 'The switch comes first' );
	}

	/**
	 * @testdox A chosen policy page that isn't published links to its editor.
	 */
	public function test_draft_policy_page_links_to_review_and_publish(): void {
		$draft = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
			)
		);
		update_option( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION, $draft );

		$html = $this->render_page();

		$this->assertStringContainsString( 'Publish your refund and returns policy page', $html );
		$this->assertStringContainsString( 'Review and publish', $html );
	}

	/**
	 * @testdox A store check from the last scan that couldn't run shows "Couldn't check" and doesn't block the switch.
	 */
	public function test_output_scanned_check_couldnt_check(): void {
		StoreConditionsTest::make_store_ready( $this );
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'store_checks' => array(
					array(
						'id'      => 'launched',
						'ok'      => false,
						'level'   => 'required',
						'name'    => 'Stale launched result',
						'fix_url' => '',
					),
					array(
						'id'      => 'sitemap_xml',
						'ok'      => null,
						'level'   => 'recommended',
						'name'    => 'Sitemap lists your products',
						'fix_url' => '',
					),
				),
			),
			false
		);

		$html = $this->render_page();

		$this->assertStringContainsString( 'Sitemap lists your products', $html );
		$this->assertStringContainsString( 'Couldn&#039;t check', $html );
		$this->assertStringNotContainsString( 'Stale launched result', $html, 'Live conditions are read now, not from the report' );
		$this->assertStringNotContainsString( 'to turn this on.', $html );
	}

	/**
	 * @testdox A check that couldn't run says why: launch the store in coming soon mode, otherwise the store couldn't load its page.
	 */
	public function test_output_couldnt_check_message_follows_coming_soon(): void {
		StoreConditionsTest::make_store_ready( $this );
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'store_checks' => array(
					array(
						'id'      => 'sitemap_xml',
						'ok'      => null,
						'level'   => 'recommended',
						'name'    => 'Sitemap lists your products',
						'fix_url' => '',
					),
				),
			),
			false
		);

		$live = $this->render_page();
		update_option( 'woocommerce_coming_soon', 'yes' );
		$coming_soon = $this->render_page();

		$this->assertStringContainsString( 'could not load its own page', $live );
		$this->assertStringNotContainsString( 'Launch your store', $live );
		$this->assertStringContainsString( 'Launch your store to run this check.', $coming_soon );
		$this->assertStringNotContainsString( 'could not load its own page', $coming_soon );
	}

	/**
	 * @testdox With UCP already on and a required check failing, the switch says the checks still need fixing.
	 */
	public function test_output_switch_on_with_failing_checks(): void {
		$page = $this->page();
		remove_all_filters( 'pre_option_home' );
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'home', 'http://example.org' );
		update_option( 'woocommerce_feature_ucp_enabled', 'yes' );

		ob_start();
		$page->output();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'aria-checked="true"', $html );
		$this->assertStringContainsString( 'required setup checks below still need fixing.', $html );
		$this->assertStringNotContainsString( 'AI agents can now find and shop your store.</p>', $html );
	}

	/**
	 * @testdox A stored draft page is listed in its own dropdown and kept when resubmitted, an empty choice clears a stored draft, and a new draft is not saved.
	 */
	public function test_save_keeps_draft_page_and_syncs_shared_options(): void {
		$draft     = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
				'post_title'  => 'Returns draft',
			)
		);
		$old_faq   = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
			)
		);
		$published = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		update_option( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION, $draft );
		update_option( AgenticCommerceSettings::FAQ_PAGE_OPTION, $old_faq );

		$settings = $this->page()->get_settings_for_section( '' );
		$refund   = current( wp_list_filter( $settings, array( 'id' => AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION ) ) );
		$terms    = current( wp_list_filter( $settings, array( 'id' => AgenticCommerceSettings::TERMS_PAGE_OPTION ) ) );
		$this->assertSame( 'Returns draft (Draft)', $refund['options'][ (string) $draft ], 'The stored draft is listed with its status' );
		$this->assertArrayNotHasKey( (string) $draft, $terms['options'], 'Other dropdowns list published pages only' );

		WC_Admin_Settings::save_fields(
			$settings,
			array(
				AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION => (string) $draft,
				AgenticCommerceSettings::PRIVACY_PAGE_OPTION => (string) $published,
				AgenticCommerceSettings::TERMS_PAGE_OPTION => (string) $draft,
				AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION => (string) $published,
				AgenticCommerceSettings::FAQ_PAGE_OPTION   => '',
				AgenticCommerceSettings::WARRANTY_PAGE_OPTION => (string) $published,
			)
		);

		$this->assertSame( $draft, (int) get_option( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION ), 'A stored draft page is kept' );
		$this->assertSame( $published, (int) get_option( AgenticCommerceSettings::PRIVACY_PAGE_OPTION ), 'Privacy policy is the WordPress option' );
		$this->assertSame( '', (string) get_option( AgenticCommerceSettings::TERMS_PAGE_OPTION, '' ), 'A draft page id is not saved' );
		$this->assertSame( $published, (int) get_option( AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION ) );
		$this->assertSame( '', (string) get_option( AgenticCommerceSettings::FAQ_PAGE_OPTION ), 'An empty choice clears a stored draft' );
		$this->assertSame( $published, (int) get_option( AgenticCommerceSettings::WARRANTY_PAGE_OPTION ) );
		$this->assertFalse( get_option( 'woocommerce_return_policy_summary' ), 'There is no return policy summary' );
	}

	/**
	 * @testdox The cart, checkout, shop and My account pages can't be picked as a policy page.
	 */
	public function test_store_pages_are_not_policy_pages(): void {
		$checkout = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		update_option( 'woocommerce_checkout_page_id', $checkout );
		$terms_field = array();
		foreach ( $this->page()->get_settings_for_section( '' ) as $field ) {
			if ( AgenticCommerceSettings::TERMS_PAGE_OPTION === ( $field['id'] ?? '' ) ) {
				$terms_field = $field;
			}
		}
		$this->assertArrayNotHasKey( (string) $checkout, $terms_field['options'], 'The checkout page is not offered' );

		WC_Admin_Settings::save_fields(
			$this->page()->get_settings_for_section( '' ),
			array( AgenticCommerceSettings::TERMS_PAGE_OPTION => (string) $checkout )
		);

		$this->assertNotSame( $checkout, (int) get_option( AgenticCommerceSettings::TERMS_PAGE_OPTION ), 'A posted checkout page id is not saved' );
	}

	/**
	 * @testdox A store page set as the terms page under Advanced > Page setup is listed, saving the tab keeps it, and Status asks for another page.
	 */
	public function test_save_keeps_store_page_set_as_terms_page(): void {
		$cart = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Cart',
			)
		);
		update_option( 'woocommerce_cart_page_id', $cart );
		update_option( AgenticCommerceSettings::TERMS_PAGE_OPTION, $cart );

		$settings = $this->page()->get_settings_for_section( '' );
		$terms    = current( wp_list_filter( $settings, array( 'id' => AgenticCommerceSettings::TERMS_PAGE_OPTION ) ) );
		$this->assertSame( 'Cart (Store page)', $terms['options'][ (string) $cart ] );
		$html = $this->render_page();
		$this->assertStringContainsString( 'Choose a terms and conditions page', $html );
		$this->assertStringNotContainsString( 'Publish your terms and conditions page', $html );

		WC_Admin_Settings::save_fields( $settings, array( AgenticCommerceSettings::TERMS_PAGE_OPTION => (string) $cart ) );

		$this->assertSame( $cart, (int) get_option( AgenticCommerceSettings::TERMS_PAGE_OPTION ) );
	}

	/**
	 * @testdox Clearing a published page on the tab clears it everywhere.
	 */
	public function test_save_clears_published_page(): void {
		$published = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		update_option( AgenticCommerceSettings::TERMS_PAGE_OPTION, $published );

		WC_Admin_Settings::save_fields(
			$this->page()->get_settings_for_section( '' ),
			array( AgenticCommerceSettings::TERMS_PAGE_OPTION => '' )
		);

		$this->assertSame( '', get_option( AgenticCommerceSettings::TERMS_PAGE_OPTION ) );
	}

	/**
	 * @testdox A shop manager cannot see or change the WordPress privacy policy page.
	 */
	public function test_privacy_page_needs_manage_privacy_options(): void {
		$privacy   = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		$published = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		update_option( AgenticCommerceSettings::PRIVACY_PAGE_OPTION, $privacy );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );

		$settings = $this->page()->get_settings_for_section( '' );
		WC_Admin_Settings::save_fields( $settings, array( AgenticCommerceSettings::PRIVACY_PAGE_OPTION => (string) $published ) );

		$this->assertNotContains( AgenticCommerceSettings::PRIVACY_PAGE_OPTION, array_column( $settings, 'id' ) );
		$this->assertSame( $privacy, (int) get_option( AgenticCommerceSettings::PRIVACY_PAGE_OPTION ) );
	}

	/**
	 * @testdox The switch stays disabled until the required checks pass.
	 */
	public function test_switch_disabled_until_required_checks_pass(): void {
		update_option( 'woocommerce_coming_soon', 'yes' );

		$blocked = $this->render_page();
		$this->assertMatchesRegularExpression( '/<button[^>]*wc-agentic-commerce-ucp-switch[^>]*disabled=([\'"])disabled\\1/', $blocked );

		StoreConditionsTest::make_store_ready();
		$ready = $this->render_page();
		$this->assertDoesNotMatchRegularExpression( '/<button[^>]*wc-agentic-commerce-ucp-switch[^>]*disabled=([\'"])disabled\\1/', $ready );
	}

	/**
	 * A fresh settings page.
	 */
	private function page(): AgenticCommerceSettingsPage {
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		update_option( 'woocommerce_feature_ucp_enabled', 'no' );
		$this->sut->handle_init();
		$pages = $this->sut->handle_woocommerce_get_settings_pages( array() );
		return $pages[0];
	}

	/**
	 * Render the default section.
	 */
	private function render_page(): string {
		// Localized data accumulates on a registered handle, so start each render clean.
		wp_deregister_script( 'wc-agentic-commerce-settings' );
		$page = $this->page();
		ob_start();
		$page->output();
		return (string) ob_get_clean();
	}
}
