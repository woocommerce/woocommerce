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
			$this->set_permalink_structure( '' );
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
	 * @testdox The page shows the three counts from the stored report, the blocked switch and failing checks.
	 */
	public function test_output_with_report_and_failing_checks(): void {
		WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_external_product();
		wc_get_container()->get( AgentReadiness::class )->refresh_report();

		$html = $this->render_page();

		$this->assertStringContainsString( '<strong>1</strong> <span>Available to agents</span>', $html );
		$this->assertStringContainsString( '<strong>1</strong> <span>Product type agents can&#039;t sell yet</span>', $html );
		$this->assertStringContainsString( 'product_type=simple,variable', $html );
		$this->assertStringContainsString( 'required setup checks below to turn this on.', $html );
		$this->assertStringContainsString( 'aria-checked="false"', $html );
		$this->assertStringContainsString( 'wc-agentic-commerce-badge--fix', $html );
		$this->assertStringContainsString( 'Privacy policy page is selected', $html, 'Each condition is a row, named by its passing state' );
		$this->assertStringNotContainsString( 'Universal Commerce Protocol (UCP) is off', $html, 'UCP is the switch, not a listed check' );
	}

	/**
	 * @testdox With every required check passing, nothing blocks the switch and recommended checks show Check, not Fix.
	 */
	public function test_output_on_a_ready_store(): void {
		StoreConditionsTest::make_store_ready( $this );

		$html = $this->render_page();

		$this->assertStringContainsString( 'wc-agentic-commerce-badge--pass', $html );
		$this->assertStringContainsString( 'wc-agentic-commerce-badge--check', $html );
		$this->assertStringNotContainsString( 'wc-agentic-commerce-badge--fix', $html );
		$this->assertStringNotContainsString( 'to turn this on.', $html );
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
		update_option( 'woocommerce_feature_ucp_enabled', 'yes' );

		ob_start();
		$page->output();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'aria-checked="true"', $html );
		$this->assertStringContainsString( 'required setup checks below still need fixing.', $html );
		$this->assertStringNotContainsString( 'AI agents can now find and shop your store.</span>', $html );
	}

	/**
	 * @testdox Saving keeps a stored draft refund page the dropdown can't show, syncs the shared options and cuts the return summary to plain text.
	 */
	public function test_save_keeps_draft_page_and_syncs_shared_options(): void {
		$draft     = self::factory()->post->create(
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

		WC_Admin_Settings::save_fields(
			$this->page()->get_settings_for_section( '' ),
			array(
				AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION => '',
				AgenticCommerceSettings::PRIVACY_PAGE_OPTION => (string) $published,
				AgenticCommerceSettings::TERMS_PAGE_OPTION => (string) $draft,
				AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION => (string) $published,
				AgenticCommerceSettings::FAQ_PAGE_OPTION   => (string) $published,
				AgenticCommerceSettings::RETURN_POLICY_SUMMARY_OPTION => '<b>30-day</b> returns. ' . str_repeat( 'x', 400 ),
			)
		);

		$this->assertSame( $draft, (int) get_option( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION ), 'A stored draft page is kept' );
		$this->assertSame( $published, (int) get_option( AgenticCommerceSettings::PRIVACY_PAGE_OPTION ), 'Privacy policy is the WordPress option' );
		$this->assertSame( '', (string) get_option( AgenticCommerceSettings::TERMS_PAGE_OPTION, '' ), 'A draft page id is not saved' );
		$this->assertSame( $published, (int) get_option( AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION ) );
		$this->assertSame( $published, (int) get_option( AgenticCommerceSettings::FAQ_PAGE_OPTION ) );

		$summary = (string) get_option( AgenticCommerceSettings::RETURN_POLICY_SUMMARY_OPTION );
		$this->assertSame( AgenticCommerceSettings::RETURN_POLICY_SUMMARY_MAX_LENGTH, mb_strlen( $summary ), 'The summary is cut to 300 characters' );
		$this->assertStringStartsWith( '30-day returns.', $summary, 'The summary is plain text' );
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
		$page = $this->page();
		ob_start();
		$page->output();
		return (string) ob_get_clean();
	}
}
