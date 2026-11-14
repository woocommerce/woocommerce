<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgenticCommerceSettings;
use Automattic\WooCommerce\Internal\AgentReadiness\AgenticCommerceSettingsPage;
use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessCheckPage;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessCopy;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessRule;
use Automattic\WooCommerce\Internal\AgentReadiness\ScheduledScan;
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
	 * Gateways and emails a test turned on, with the enabled value to put back.
	 *
	 * @var array<int, array{0:object,1:string}>
	 */
	private array $restore = array();

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
		delete_option( 'woocommerce_feature_ucp_enabled' );
		$this->sut = wc_get_container()->get( AgenticCommerceSettings::class );
	}

	/**
	 * Leave the admin screen, which is a global outside the rollback.
	 */
	public function tearDown(): void {
		try {
			set_current_screen( 'front' );
			foreach ( $this->restore as $item ) {
				$item[0]->enabled = $item[1];
			}
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
	 * @testdox The AI tools tab, and only that tab, gets its body class for the white panel.
	 */
	public function test_body_class_only_on_the_tab(): void {
		global $current_tab;
		$previous = $current_tab;
		try {
			$current_tab = AgenticCommerceSettings::TAB; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$this->assertStringContainsString( 'woocommerce-settings-agentic-commerce-tab', $this->sut->handle_admin_body_class( 'wp-admin' ) );

			$current_tab = 'checkout'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$this->assertSame( 'wp-admin', $this->sut->handle_admin_body_class( 'wp-admin' ) );
		} finally {
			$current_tab = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * @testdox Sales readiness is added only while the agent_readiness feature is enabled, as a WooCommerce Admin page under WooCommerce, before Settings.
	 */
	public function test_readiness_page_only_with_feature_enabled(): void {
		$page = wc_get_container()->get( ReadinessCheckPage::class );
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'no' );
		remove_all_actions( 'admin_menu' );
		$page->handle_init();
		$this->assertFalse( has_action( 'admin_menu', array( $page, 'handle_admin_menu' ) ), 'No page with the feature off' );

		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		$page->handle_init();
		$this->assertSame( ReadinessCheckPage::MENU_PRIORITY, has_action( 'admin_menu', array( $page, 'handle_admin_menu' ) ), 'After Reports (20), before Settings (50)' );

		// The menu globals aren't rolled back between tests, so put them back after.
		$globals = array( 'menu', 'submenu', 'admin_page_hooks', '_registered_pages', '_parent_pages', '_wp_real_parent_file', '_wp_submenu_nopriv' );
		$saved   = array_map( static fn( string $name ) => $GLOBALS[ $name ] ?? null, array_combine( $globals, $globals ) );
		try {
			$GLOBALS['submenu'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Start from an empty menu; restored below.
			add_menu_page( 'WooCommerce', 'WooCommerce', 'manage_woocommerce', 'woocommerce' );
			$page->handle_admin_menu();
			$this->assertSame( array( 'Sales readiness', 'manage_woocommerce', 'wc-admin&path=/sales-readiness' ), array_slice( end( $GLOBALS['submenu']['woocommerce'] ), 0, 3 ) );
		} finally {
			foreach ( $saved as $name => $value ) {
				$GLOBALS[ $name ] = $value; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring what the test changed.
			}
		}
		$this->assertSame( admin_url( 'admin.php?page=wc-admin&path=/sales-readiness' ), ReadinessCheckPage::url() );
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

		$this->assertStringContainsString( 'Product counts show after the first Sales readiness check.', $html );
		$this->assertStringContainsString( 'path=/sales-readiness">Sales readiness</a> <mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--off">Not checked yet</mark>', $html, 'Status links to Sales readiness, not yet run' );
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

		$this->assertStringContainsString( '<span>Available to AI assistants</span> <strong>1</strong> <a href="', $html );
		$stored              = get_option( AgentReadiness::REPORT_OPTION );
		$stored['products']  = ProductScan::PRODUCT_LIMIT;
		$stored['available'] = 8400;
		update_option( AgentReadiness::REPORT_OPTION, $stored );
		$this->assertStringContainsString( '<span>Available to AI assistants</span> <strong>8,400</strong> <a href="', $this->render_page(), 'The whole catalog, like the other two, not only the products the scan stopped at' );
		$this->assertMatchesRegularExpression( '#<span>Product type not supported yet <span[^>]*woocommerce-help-tip[^>]*AI assistants can find these products, but shoppers can.{1,7}t buy them through an assistant yet\.[^>]*></span></span> <strong>1</strong>#', $html, 'The tip says what not supported means' );
		$this->assertStringContainsString( 'agent_readiness=' . ProductScan::COUNT_UNSUPPORTED . '">View products</a>', $html, 'Each count links to its products' );
		$this->assertMatchesRegularExpression( '#<h2>Setup checklist</h2>\s*<mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--info">\s*\d+ steps? left\s*</mark>\s*</div>\s*<p>Finish \d+ required steps? to get your store ready for AI shopping\.</p>#', $html, 'The checklist says how many steps are left, then counts the required steps first, even with UCP off' );
		$this->assertMatchesRegularExpression( '#Use a secure connection \(HTTPS\) <mark[^>]*>Required</mark></p>.*?</div><a class="button wc-agentic-commerce-review" href="[^"]*site-health\.php">Review</a></li>#s', $html, 'A step is not a link itself: Review at the end of the row opens where to fix it' );
		$this->assertStringContainsString( 'aria-checked="false"', $html );
		$this->assertStringNotContainsString( 'to turn this on', $html );
		$this->assertMatchesRegularExpression( '#wc-agentic-commerce-item--required.*badge--check">Required</mark>#', $html, 'Failing required checks are marked Required' );
		$this->assertStringNotContainsString( 'Check</mark>', $html, 'Failing recommended checks have no badge' );
		$this->assertStringNotContainsString( 'Required.', $html, 'Policy pages are recommended, so no field is marked required' );
		$this->assertLessThan( strpos( $html, 'wc-agentic-commerce-item--open' ), strrpos( $html, 'wc-agentic-commerce-item--required' ), 'Required steps come first' );
		$this->assertLessThan( strpos( $html, 'AI assistants can only find your products when your store is live.' ), strpos( $html, 'Use a secure connection (HTTPS)' ), 'HTTPS is listed first, as in the design' );
		$this->assertMatchesRegularExpression( '/(Choose a|Publish your) privacy policy page/', $html, 'An open check says what to do' );
		$this->assertStringContainsString( 'Connection URL', $html );
		$this->assertStringContainsString( 'page=wc-settings&#038;tab=agentic-commerce">AI tools</a> <span aria-hidden="true">/</span> <span aria-current="page">AI shopping</span></div>', $html, 'AI shopping has a breadcrumb back to the landing page' );
		$this->assertStringNotContainsString( '<h2>Security</h2>', $html, 'No Security card until a UCP flow signs anything (WOOAI-1620)' );
		$this->assertStringNotContainsString( 'Universal Commerce Protocol (UCP) is off', $html, 'UCP is the switch, not a listed check' );
	}

	/**
	 * @testdox With every required check passing, open checks are only Recommended and passing ones are Done.
	 */
	public function test_output_on_a_ready_store(): void {
		StoreConditionsTest::make_store_ready();

		$html = $this->render_page();

		$this->assertMatchesRegularExpression( '#<p>Your store is ready for AI shopping\. (You have \d+ optional steps? left\.|Turn it on to let AI assistants find your products\.)</p>#', $html );
		$this->assertStringNotContainsString( 'badge--pass', $html, 'Completed steps need no badge, their marker says so' );
		$this->assertStringNotContainsString( 'wc-agentic-commerce-missing', $html );
		$this->assertStringNotContainsString( 'wc-agentic-commerce-item--required', $html );
		$this->assertMatchesRegularExpression( '#Show \d+ completed steps?</span>#', $html, 'Completed steps are folded' );
		$this->assertStringContainsString( 'Privacy policy page is selected', $html, 'Done rows are named by their passing state' );
		$this->assertStringNotContainsString( 'wc-agentic-commerce-notice', $html, 'No coming soon notice on a live store' );

		$shopping = $this->view()['overview']['shopping'];
		$this->assertNull( $shopping['badge'], 'With UCP off and nothing required failing, the Overview card has no badge' );
		$this->assertSame( 'Let shoppers find and buy your products through AI assistants.', $shopping['text'] );
		$this->assertSame( 'Set up AI shopping', $shopping['button']['text'], 'and offers set up' );
	}

	/**
	 * The design's five AI shopping scenarios: how the store is set up, the UCP setting, and what the Overview card and
	 * the Setup checklist say.
	 *
	 * @return array<string, array{0:string,1:string|null,2:string,3:string|null,4:string,5:string}>
	 */
	public function provider_shopping_scenarios(): array {
		return array(
			'not set up yet'      => array( 'every_step', null, '#^Let shoppers find and buy your products through AI assistants\.$#', null, 'Set up AI shopping', '#<p>Your store is ready for AI shopping\. Turn it on to let AI assistants find your products\.</p>#' ),
			'steps left'          => array( 'coming_soon', 'yes', '#^AI assistants can\'t shop your store yet\. \d+ steps? left\.$#', 'Needs attention', 'Manage', '#<p>Finish \d+ required steps? to get your store ready for AI shopping\.</p>#' ),
			'optional steps left' => array( 'optional_left', 'yes', '#^AI assistants can shop your store\. 1 step left\.$#', 'Active', 'Manage', '#<p>Your store is ready for AI shopping\. You have 1 optional step left\.</p>#' ),
			'all steps done'      => array( 'every_step', 'yes', '#^AI assistants can shop your store\.$#', 'Active', 'Manage', '#<p>Your store is ready for AI shopping\.</p>#' ),
			'turned off'          => array( 'every_step', 'no', '#^AI shopping is off\.$#', null, 'Manage', '#<p>Your store is ready for AI shopping\. Turn it on to let AI assistants find your products\.</p>#' ),
			'off, steps left'     => array( 'coming_soon', 'no', '#^AI shopping is off\.$#', 'Needs attention', 'Manage', '#<p>Finish \d+ required steps? to get your store ready for AI shopping\.</p>#' ),
		);
	}

	/**
	 * @testdox The AI shopping card and the Setup checklist follow the design in each scenario: $_dataName.
	 * @dataProvider provider_shopping_scenarios
	 *
	 * @param string      $store     How the store is set up.
	 * @param string|null $ucp       The stored UCP setting, or null when it was never saved.
	 * @param string      $text      The card text, as a pattern.
	 * @param string|null $badge     The card badge, or null for none.
	 * @param string      $button    The card button.
	 * @param string      $checklist The Setup checklist summary, as a pattern.
	 */
	public function test_shopping_scenarios( string $store, ?string $ucp, string $text, ?string $badge, string $button, string $checklist ): void {
		if ( 'coming_soon' === $store ) {
			remove_all_filters( 'pre_option_home' );
			update_option( 'woocommerce_coming_soon', 'yes' );
			update_option( 'home', 'http://example.org' );
		} else {
			$this->make_every_step_pass();
		}
		if ( 'optional_left' === $store ) {
			update_option( 'woocommerce_enable_guest_checkout', 'no' );
		}

		$html = $this->render_page( AgenticCommerceSettings::SHOPPING_SECTION, 'yes' === $ucp );
		if ( 'no' === $ucp ) {
			update_option( 'woocommerce_feature_ucp_enabled', 'no' );
		}
		$shopping = $this->view()['overview']['shopping'];

		$this->assertMatchesRegularExpression( $checklist, $html );
		$this->assertMatchesRegularExpression( $text, $shopping['text'] );
		$this->assertSame( $badge, $shopping['badge']['text'] ?? null );
		$this->assertSame( $button, $shopping['button']['text'] );
	}

	/**
	 * @testdox A missing policy page is recommended, so it does not block the switch or the required group.
	 */
	public function test_missing_policy_page_is_recommended(): void {
		StoreConditionsTest::make_store_ready();
		delete_option( AgenticCommerceSettings::PRIVACY_PAGE_OPTION );

		$html = $this->render_page();

		$this->assertStringNotContainsString( 'wc-agentic-commerce-item--required', $html );
		$this->assertMatchesRegularExpression( '#wc-agentic-commerce-item--open.*Choose a privacy policy page#s', $html );
		$this->assertDoesNotMatchRegularExpression( '#required steps?#', $html );
		$this->assertDoesNotMatchRegularExpression( '/<button[^>]*wc-agentic-commerce-ucp-switch[^>]*disabled=([\'"])disabled\\1/', $html );
	}

	/**
	 * @testdox In coming soon mode the switch card says so and links to Site visibility.
	 */
	public function test_output_coming_soon_notice_in_switch_card(): void {
		update_option( 'woocommerce_coming_soon', 'yes' );

		$html = $this->render_page();

		$this->assertMatchesRegularExpression( '/wc-agentic-commerce-switch-card.*wc-agentic-commerce-notice.*Change site visibility/s', $html );
		$this->assertLessThan( strpos( $html, 'wc-agentic-commerce-status-card' ), strpos( $html, 'wc-agentic-commerce-switch-card' ), 'The switch comes first' );
	}

	/**
	 * @testdox A chosen policy page that isn't published links to its editor.
	 */
	public function test_draft_policy_page_links_to_review_and_publish(): void {
		$this->make_every_step_pass();
		$draft = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
			)
		);
		update_option( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION, $draft );

		$html = $this->render_page();

		$this->assertStringContainsString( 'Publish your refund and returns policy page', $html );
		$this->assertStringContainsString( 'A refund policy needs to be available for AI assistants to share with shoppers.', $html, 'The refund page says why it matters, as in the design' );
		$this->assertMatchesRegularExpression( '#Publish your refund and returns policy page.*?href="[^"]*post=' . $draft . '&amp;action=edit">Review</a>#s', $html, 'Review opens the page editor' );

		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(),
			)
		);
		$view = $this->view();
		$this->assertNull( $view['fix_first'], 'A policy page is only recommended, so it stays out of Fix first' );
		$this->assertMatchesRegularExpression( '#"title":"Publish your refund and returns policy page".{0,600}?"href":"[^"]*post=' . $draft . '&action=edit","text":"Edit refund policy"#', (string) wp_json_encode( $view['sections'], JSON_UNESCAPED_SLASHES ), 'Readiness check links the step to the page editor, named for the policy' );
		$this->assertStringContainsString( 'A refund policy needs to be available for AI assistants to share with shoppers.', (string) wp_json_encode( $view['sections'], JSON_UNESCAPED_UNICODE ), 'The refund draft keeps its own line' );
	}

	/**
	 * @testdox Each draft policy page links to its editor with the policy's own link text.
	 */
	public function test_draft_policy_pages_name_their_edit_link(): void {
		$this->make_every_step_pass();
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(),
			)
		);
		$pages = array(
			AgenticCommerceSettings::PRIVACY_PAGE_OPTION => array( 'Publish your privacy policy page', 'Edit privacy policy' ),
			AgenticCommerceSettings::TERMS_PAGE_OPTION   => array( 'Publish your terms and conditions page', 'Edit terms and conditions' ),
			AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION => array( 'Publish your shipping policy page', 'Edit shipping policy' ),
		);
		foreach ( $pages as $option => $copy ) {
			$draft = self::factory()->post->create(
				array(
					'post_type'   => 'page',
					'post_status' => 'draft',
				)
			);
			update_option( $option, $draft );
			$sections = (string) wp_json_encode( $this->view()['sections'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			$this->assertMatchesRegularExpression( '#"title":"' . preg_quote( $copy[0], '#' ) . '".{0,600}?"href":"[^"]*post=' . $draft . '&action=edit","text":"' . preg_quote( $copy[1], '#' ) . '"#', $sections, $copy[1] );
			$this->assertStringContainsString( 'It\'s still a draft. AI assistants only link to published pages.', $sections, $copy[0] . ' keeps the default draft line' );
			wp_publish_post( $draft );
		}
	}

	/**
	 * @testdox A scanned check that couldn't run is shown as such; a stale setup result in the report is not read.
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
						'group'   => 'find',
						'ok'      => null,
						'level'   => 'recommended',
						'name'    => 'Sitemap lists your products',
						'fix_url' => '',
					),
				),
			),
			false
		);

		$titles = $this->titles( $this->view() );

		$this->assertContains( 'Sitemap lists your products', $titles );
		$this->assertSame( 'unknown', $this->row( 'Sitemap lists your products' )['state'] );
		$this->assertNotContains( 'Stale launched result', $titles, 'Live conditions are read now, not from the report' );
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
						'group'   => 'find',
						'ok'      => null,
						'level'   => 'recommended',
						'name'    => 'Sitemap lists your products',
						'fix_url' => '',
					),
				),
			),
			false
		);

		$live = $this->row( 'Sitemap lists your products' )['text'];
		update_option( 'woocommerce_coming_soon', 'yes' );
		$coming_soon = $this->row( 'Sitemap lists your products' )['text'];

		$this->assertSame( 'This couldn\'t be checked. The next check tries again.', $live );
		$this->assertSame( 'Launch your store to run this check.', $coming_soon );
	}

	/**
	 * @testdox With UCP already on and a required check failing, the switch stays on and the checklist asks for the required steps, without saying assistants can shop.
	 */
	public function test_output_switch_on_with_failing_checks(): void {
		remove_all_filters( 'pre_option_home' );
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'home', 'http://example.org' );
		$html = $this->render_page( AgenticCommerceSettings::SHOPPING_SECTION, true );

		$this->assertStringContainsString( 'aria-checked="true"', $html );
		$this->assertStringNotContainsString( 'AI assistants can now find and shop your store.', $html );
		$this->assertMatchesRegularExpression( '#<p>Finish \d+ required steps? to get your store ready for AI shopping\.</p>#', $html );

		$shopping = $this->view()['overview']['shopping'];
		$this->assertSame(
			array(
				'intent' => 'low',
				'text'   => 'Needs attention',
			),
			$shopping['badge'],
			'The Overview card says so too'
		);
		$this->assertMatchesRegularExpression( '#^AI assistants can\'t shop your store yet\. \d+ steps? left\.$#', $shopping['text'] );
		$this->assertSame( 'Manage', $shopping['button']['text'], 'and Manage opens AI shopping' );
		$this->assertStringContainsString( 'section=shopping', $shopping['button']['href'] );
	}

	/**
	 * @testdox Sales readiness badges each merchant question, marks a likely conflict with the page but names no plugin, hides checks whose prerequisite failed, and folds passed checks away.
	 */
	public function test_output_groups_summary_and_conflict(): void {
		$this->make_every_step_pass();
		$row = static fn( string $id, ?bool $ok, bool $conflict = false ): array => array(
			'id'       => $id,
			'ok'       => $ok,
			'blocks'   => 'discovery',
			'group'    => str_starts_with( $id, 'product_' ) ? 'understand' : 'find',
			'name'     => $id . ' name',
			'label'    => $id . ' label',
			'why'      => $id . ' why',
			'fix'      => $id . ' fix',
			'fix_url'  => '',
			'conflict' => $conflict,
		);
		update_option(
			'woocommerce_agent_readiness_report',
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(
					array_merge(
						$row( 'product_jsonld_present', false, true ),
						array(
							'seen_at' => 'https://example.org/product/mug/',
							// A report stored before plugin names were dropped.
							'plugins' => array( 'Yoast SEO', 'Rank Math SEO' ),
						)
					),
					$row( 'product_offer_jsonld', null ),
					$row( 'sitemap_lists_products', true ),
				),
			)
		);

		$view     = $this->view();
		$sections = array_column( $view['sections'], null, 'id' );
		$json     = wp_json_encode( $view );

		$this->assertSame(
			array( 'Store access', 'Can AI assistants find your store?', 'Can AI assistants read your product details?', 'Can AI assistants list your products?', 'Can shoppers buy through AI assistants?' ),
			array_column( $view['sections'], 'title' ),
			'Products come before Can shoppers buy through AI assistants?'
		);
		$this->assertSame( 'AI shopping is off, so we didn’t check your connection URL.', $sections['access']['note'], 'UCP is off in this test' );
		$this->assertSame(
			array(
				'intent' => 'low',
				'text'   => '1 issue',
			),
			$sections['understand']['badge'],
			'The failing check is the issue; the unrun one is hidden'
		);
		$this->assertSame(
			array(
				'intent' => 'stable',
				'text'   => 'All passed',
			),
			$sections['find']['badge']
		);
		$conflict = $sections['understand']['rows'][0];
		$this->assertSame( 'Add product details AI assistants can read', $conflict['title'], 'A failing check is headed by what to do' );
		$this->assertStringEndsWith( 'We found none on a recent product page.', $conflict['text'], 'then why it matters and what the check found' );
		$this->assertStringStartsWith( 'Test a product page in Google', $conflict['fix'], 'and the fix, since it has one' );
		$this->assertSame(
			array(
				'intent' => 'none',
				'text'   => 'Likely conflict',
			),
			$conflict['badge']
		);
		$this->assertSame(
			array(
				'href' => 'https://example.org/product/mug/',
				'text' => 'https://example.org/product/mug/',
			),
			$conflict['seen_at'],
			'A conflict names the page'
		);
		$report                               = get_option( 'woocommerce_agent_readiness_report' );
		$report['store_checks'][0]['seen_at'] = 'javascript:alert(1)';
		$report['store_checks'][0]['fix_url'] = 'javascript:alert(1)';
		update_option( 'woocommerce_agent_readiness_report', $report );
		$row = array_column( $this->view()['sections'], null, 'id' )['understand']['rows'][0];
		$this->assertNull( $row['seen_at'], 'A stored page that is not an http(s) URL is not linked' );
		$this->assertNull( $row['link'], 'nor is a stored fix URL' );
		$sitemap = $this->view_with_check( 'sitemap_lists_products' )['sections'][1]['rows'][0];
		$this->assertSame( 'Add a sitemap that lists your products', $sitemap['title'] );
		$this->assertStringContainsString( 'SEO plugin', $sitemap['fix'], 'A failed row says where to fix it' );
		$this->assertNull( $sitemap['badge'], 'Only a blocker is Required' );
		$this->assertStringNotContainsString( 'Yoast SEO', $json, 'No plugin is named, even from an older stored report' );
		$this->assertStringNotContainsString( 'product_offer_jsonld name', $json, 'Its prerequisite failed' );
		$this->assertStringNotContainsString( 'product_jsonld_present name', $json );
		$this->assertStringNotContainsString( 'sitemap_lists_products label', $json, 'A passed check keeps its passing title' );
		$this->assertSame( array(), $sections['find']['rows'], 'Nothing open under find' );
		$passed = array_column( $sections['find']['folded'], null, 'title' )['A sitemap lists your products'] ?? null;
		$this->assertNotNull( $passed, 'Passed checks are folded, under their passing title' );
		$this->assertSame( 'Crawlers use the sitemap to find new and changed products.', $passed['text'], 'with why it matters' );
		$this->assertStringNotContainsString( 'sitemap_lists_products why', $json, 'A passed scanned check drops the report\'s failure-worded why' );
		$this->assertStringContainsString( 'Order confirmation emails are on', $json );
		$this->assertStringNotContainsString( 'Shoppers who buy through an agent get no confirmation from your store.', $json, 'A passed setup step drops its failure-worded why, as in Status' );
	}

	/**
	 * @testdox What the store shares is information: how many items were found, every item behind a fold, and no badge.
	 */
	public function test_output_declared_block(): void {
		$this->make_every_step_pass();
		update_option(
			'woocommerce_agent_readiness_report',
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(),
				'declared'     => array(
					array(
						'id'    => 'ai-txt',
						'name'  => 'ai.txt usage file',
						'found' => true,
					),
					array(
						'id'    => 'agents-md',
						'name'  => 'AGENTS.md',
						'found' => false,
					),
					array(
						'id'    => 'tdmrep',
						'name'  => 'TDMRep',
						'found' => null,
					),
				),
			)
		);

		$view = $this->view();

		$this->assertSame( 'What does your store share with AI assistants?', $view['declared']['title'] );
		$this->assertArrayNotHasKey( 'badge', $view['declared'], 'No badge: nothing here passes or fails' );
		$this->assertSame( 'Optional files that some AI assistants look for. You don’t need to fix anything. Found 1 of 3.', $view['declared']['text'] );
		$this->assertSame( 'Show all 3 items', $view['declared']['more'] );
		$this->assertSame(
			array(
				array(
					'name'  => 'ai.txt usage file',
					'found' => true,
				),
				array(
					'name'  => 'AGENTS.md',
					'found' => false,
				),
				array(
					'name'  => 'TDMRep',
					'found' => false,
				),
			),
			$view['declared']['items'],
			'Every item, found or not'
		);
		$this->assertSame(
			array(
				'intent' => 'stable',
				'text'   => 'All passed',
			),
			array_column( $view['sections'], 'badge', 'id' )['find'],
			'Not counted as a result; the passing search engine step is'
		);
	}

	/**
	 * @testdox A bad stored entry isn't counted and doesn't break the page; with no items there is no card.
	 */
	public function test_output_declared_none_found_and_bad_entries(): void {
		update_option(
			'woocommerce_agent_readiness_report',
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(),
				'declared'     => array(
					array(
						'id'    => 'ai-txt',
						'name'  => 'ai.txt usage file',
						'found' => false,
					),
					'not an array',
				),
			)
		);

		$this->assertStringContainsString( 'You don’t need to fix anything. Found 0 of 1.', $this->view()['declared']['text'] );

		update_option(
			'woocommerce_agent_readiness_report',
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(),
				'declared'     => array(),
			)
		);
		$this->assertNull( $this->view()['declared'] );
	}

	/**
	 * @testdox Fix first lists the failing required steps, then the top 3 issues: what stops every sale, then what keeps products off a channel, each ranked by its share of sales.
	 */
	public function test_output_fix_first_ranks_by_sales(): void {
		$this->make_every_step_pass();
		remove_all_filters( 'pre_option_home' );
		update_option( 'home', 'http://example.org' );
		update_option( 'woocommerce_enable_guest_checkout', 'no' );
		$check  = static fn( string $id, int $count, ?float $share, string $effect ): array => array(
			'id'          => $id,
			'label'       => $id . ' label',
			'why'         => $id . ' why.',
			'fix'         => $id . ' fix.',
			'channels'    => array(
				'chatgpt' => array(
					'field'  => 'field',
					'effect' => $effect,
				),
			),
			'count'       => $count,
			'product_ids' => array(),
			'sales_share' => $share,
		);
		$report = array(
			'generated_at' => gmdate( 'c' ),
			'products'     => 40,
			'store_checks' => array(),
			'channels'     => array(
				'chatgpt' => array( 'name' => 'ChatGPT' ),
				'broken'  => 'not an array',
			),
			'checks'       => array(
				$check( 'missing_gtin', 30, 0.6, ReadinessRule::EFFECT_GAPS ),
				$check( 'missing_brand', 2, 0.05, ReadinessRule::EFFECT_NOT_LISTABLE ),
				$check( 'missing_images', 5, 0.18, ReadinessRule::EFFECT_NOT_LISTABLE ),
				$check( 'title_too_long', 1, 0.001, ReadinessRule::EFFECT_GAPS ),
			),
			'sales'        => array(
				'days'   => 90,
				'orders' => 30,
			),
		);
		update_option( AgentReadiness::REPORT_OPTION, $report );

		$fix_first = $this->view()['fix_first'];
		$this->assertSame( 'Start with the fixes that affect the most sales. Sales figures are from the last 90 days.', $fix_first['ranking'] );
		update_option( AgentReadiness::REPORT_OPTION, array_replace_recursive( $report, array( 'sales' => array( 'days' => 1 ) ) ) );
		$this->assertSame( 'Start with the fixes that affect the most sales. Sales figures are from the last 1 day.', $this->view()['fix_first']['ranking'], 'One day reads in the singular' );
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$this->assertSame(
			array( 'Use a secure connection (HTTPS)', 'Allow guest checkout', 'Add a main image to 5 products', 'Add a brand to 2 products' ),
			array_column( $fix_first['items'], 'title' ),
			'The required step, then 3 issues: guest checkout stops every sale, then the not listable checks by share; GTIN, a gap, is left out despite its larger share'
		);
		$this->assertSame( array( 'Required for AI shopping', 'Required for AI shopping', 'Needed to list products', 'Needed to list products' ), array_column( $fix_first['items'], 'tier' ) );
		$this->assertArrayNotHasKey( 'tier_badge', $fix_first['items'][0], 'The heading says Required for AI shopping, so no badge repeats it' );
		$this->assertSame( array( true, true, false, false ), array_column( $fix_first['items'], 'required' ), 'The Required for AI shopping heading carries the Required badge' );
		$this->assertArrayNotHasKey( 'impact', $fix_first['items'][2], 'The share of sales is in the line, so no badge repeats it' );
		$this->assertSame( '18% of your sales in the last 90 days. AI assistants only list products that have a main image.', $fix_first['items'][2]['why'], 'The first share says over which period, then why the issue matters' );
		$this->assertSame( '5% of your sales. ChatGPT only lists products that have a brand. In Google and Copilot, a brand makes products easier to find.', $fix_first['items'][3]['why'], 'The next ones give only the share' );
		$this->assertSame( 'Go to Accounts & Privacy', $fix_first['items'][1]['link']['text'], 'A setup step links to where to fix it' );
		$this->assertSame(
			array(
				'href' => '',
				'text' => '5 products',
			),
			$fix_first['items'][2]['link'],
			'A product issue names how many products it affects; no stored failures, so no list to link'
		);

		$report['checks'][1]['sales_share'] = 0.0;
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$json = wp_json_encode( $this->view() );
		$this->assertStringContainsString( 'No sales. ChatGPT only lists products that have a brand.', $json, 'Products that sold nothing in the period say so, not 0%' );
		$this->assertStringNotContainsString( '0% of', $json );
		$report['checks'][1]['sales_share'] = 0.003;
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$view = $this->view();
		$this->assertStringContainsString( 'under 1% of your sales', wp_json_encode( $view['fix_first'] ), 'A share under 1% is not rounded to 0%' );
		$this->assertStringNotContainsString( 'under 1%', wp_json_encode( $view['sections'] ), 'Shares are in Fix first only, not on the product card' );

		$report['sales'] = null;
		foreach ( $report['checks'] as &$row ) {
			$row['sales_share'] = null;
		}
		unset( $row );
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$fix_first = $this->view()['fix_first'];
		$this->assertSame( 'Ranked by how many products each issue affects. Turn on Analytics to rank by sales instead.', $fix_first['ranking'] );
		$this->assertStringNotContainsString( 'of your sales', wp_json_encode( $fix_first ) );

		$report['sales'] = array(
			'days'   => 90,
			'orders' => 30,
		);
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$this->assertSame( 'Ranked by how many products each issue affects.', $this->view()['fix_first']['ranking'], 'Orders with no share to rank by, such as free products or counts changed since the check' );

		$report['sales'] = array(
			'days'   => 90,
			'orders' => 12,
		);
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$this->assertSame( 'Only 12 orders in the last 90 days, too few to rank by sales, so issues are ranked by how many products they affect.', $this->view()['fix_first']['ranking'] );

		$report['sales'] = array(
			'days'   => 90,
			'orders' => 0,
		);
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$this->assertSame( 'No sales in the last 90 days, so issues are ranked by how many products they affect.', $this->view()['fix_first']['ranking'] );

		unset( $report['sales'] );
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$this->assertSame( 'Ranked by how many products each issue affects.', $this->view()['fix_first']['ranking'], 'A report from before sales were read doesn\'t say Analytics is off' );
	}

	/**
	 * @testdox In Fix first, store pages agents can't reach stop every sale; any other check that blocks listing keeps products off a channel.
	 */
	public function test_output_fix_first_tiers_store_checks(): void {
		$this->make_every_step_pass();
		$output = static fn( string $id, string $group ): array => array(
			'id'       => $id,
			'ok'       => false,
			'blocks'   => 'listing',
			'group'    => $group,
			'level'    => 'recommended',
			'name'     => $id . ' name',
			'label'    => $id . ' label',
			'why'      => $id . ' why.',
			'fix'      => $id . ' fix.',
			'fix_url'  => '',
			'conflict' => false,
		);
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'products'     => 4,
				'store_checks' => array( $output( 'other_listing_check', 'understand' ), $output( 'pages_public', 'access' ) ),
				'channels'     => array(),
				'checks'       => array(),
			)
		);

		$items = $this->view()['fix_first']['items'];
		$this->assertSame(
			array(
				array( 'Required for AI shopping', 'Let shoppers open store pages without logging in', true ),
				array( 'Needed to list products', 'other_listing_check label', false ),
			),
			array_map( static fn( array $item ): array => array( $item['tier'], $item['title'], $item['required'] ), $items ),
			'Store pages AI assistants can\'t reach are a blocker, so Required; a check without copy keeps the report\'s title'
		);
	}

	/**
	 * @testdox A store with no products shows the product cards' No products state, with a way to add one, instead of their checks.
	 */
	public function test_output_no_products(): void {
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'products'     => 0,
				'store_checks' => array(),
				'channels'     => array(),
				'checks'       => array(),
			)
		);

		$sections = array_column( $this->view()['sections'], null, 'id' );
		foreach ( array( 'products', 'understand' ) as $id ) {
			$this->assertSame( 'No products', $sections[ $id ]['badge']['text'], $id . ' says why it has no checks' );
			$this->assertSame( array(), array_merge( $sections[ $id ]['rows'], $sections[ $id ]['folded'] ) );
			$this->assertSame( 'You don’t have any products yet', $sections[ $id ]['empty']['title'] );
			$this->assertSame( 'Add a product', $sections[ $id ]['empty']['link']['text'] );
			$this->assertStringContainsString( 'post-new.php?post_type=product', $sections[ $id ]['empty']['link']['href'] );
		}
		$this->assertNull( $sections['find']['empty'], 'Store cards keep their checks' );
	}

	/**
	 * @testdox In Fix first, a store-wide listing issue ranks above product issues in the same tier, whatever their share of sales.
	 */
	public function test_output_fix_first_store_wide_issue_ranks_first(): void {
		$this->make_every_step_pass();
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'products'     => 40,
				'store_checks' => array(
					array(
						'id'       => 'store_wide',
						'ok'       => false,
						'blocks'   => 'listing',
						'group'    => 'understand',
						'level'    => 'recommended',
						'name'     => 'store_wide name',
						'label'    => 'store_wide label',
						'why'      => 'store_wide why.',
						'fix'      => 'store_wide fix.',
						'fix_url'  => '',
						'conflict' => false,
					),
				),
				'channels'     => array( 'chatgpt' => array( 'name' => 'ChatGPT' ) ),
				'checks'       => array(
					array(
						'id'          => 'missing_images',
						'label'       => 'missing_images label',
						'why'         => 'missing_images why.',
						'fix'         => 'missing_images fix.',
						'channels'    => array(
							'chatgpt' => array(
								'field'  => 'field',
								'effect' => ReadinessRule::EFFECT_NOT_LISTABLE,
							),
						),
						'count'       => 30,
						'product_ids' => array(),
						'sales_share' => 0.9,
					),
				),
				'sales'        => array(
					'days'   => 90,
					'orders' => 30,
				),
			)
		);

		$items = $this->view()['fix_first']['items'];
		$this->assertSame( 'store_wide label', $items[0]['title'], 'It keeps every product off, so it ranks as all sales' );
		$this->assertStringStartsWith( '90% of your sales', $items[1]['why'], 'The product issue below it gives its share in the line' );
	}

	/**
	 * @testdox Fix first items and check rows carry the check's id, so the views can track clicks without translated titles.
	 */
	public function test_view_items_carry_check_ids(): void {
		$this->make_every_step_pass();
		$output = static fn( string $id, string $group ): array => array(
			'id'       => $id,
			'ok'       => false,
			'blocks'   => 'listing',
			'group'    => $group,
			'level'    => 'recommended',
			'name'     => $id . ' name',
			'label'    => $id . ' label',
			'why'      => $id . ' why.',
			'fix'      => $id . ' fix.',
			'fix_url'  => '',
			'conflict' => false,
		);
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'products'     => 4,
				'store_checks' => array( $output( 'product_shipping_schema', 'buy' ), $output( 'pages_public', 'access' ) ),
				'channels'     => array(),
				'checks'       => array(),
			)
		);

		$view = $this->view();
		$this->assertSame( array( 'pages_public', 'product_shipping_schema' ), array_column( $view['fix_first']['items'], 'id' ) );
		$ids = array();
		foreach ( $view['sections'] as $section ) {
			foreach ( array_merge( $section['rows'], $section['folded'] ) as $row ) {
				$this->assertNotSame( '', $row['id'], $row['title'] . ' has no id' );
				$ids[] = $row['id'];
			}
		}
		$this->assertContains( 'pages_public', $ids );
	}

	/**
	 * @testdox Fix first is left out when nothing fails.
	 */
	public function test_output_fix_first_hidden_when_nothing_fails(): void {
		$this->make_every_step_pass();
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(),
				'checks'       => array(),
			)
		);
		$this->assertNull( $this->view()['fix_first'] );
	}

	/**
	 * @testdox A question's badge counts its issues; a check that couldn't run isn't an issue, nor a pass.
	 */
	public function test_group_badge(): void {
		$badge = new \ReflectionMethod( ReadinessCheckPage::class, 'group_badge' );
		$badge->setAccessible( true );
		$text = static fn( array $checks ): string => $badge->invoke( null, $checks )[1];
		$row  = static fn( ?bool $ok ): array => array( 'ok' => $ok );

		$this->assertSame( '2 issues', $text( array( $row( false ), $row( false ), $row( true ) ) ) );
		$this->assertSame( '1 issue', $text( array( $row( false ), $row( null ) ) ) );
		$this->assertSame( 'All passed', $text( array( $row( true ), $row( true ) ) ) );
		$this->assertSame( 'No issues', $text( array( $row( true ), $row( null ) ) ), 'A check that could not run is not an issue, nor a pass' );
		$this->assertSame( 'Not checked yet', $text( array() ) );
		$this->assertSame( 'Not checked yet', $text( array( $row( null ), $row( null ) ) ), 'Nothing ran, so nothing is shown as passed' );

		$map = new \ReflectionMethod( ReadinessCheckPage::class, 'badge_data' );
		$map->setAccessible( true );
		$this->assertSame( 'stable', $map->invoke( null, $badge->invoke( null, array( $row( true ) ) ) )['intent'], 'All passed is green, as in the design' );
		$this->assertSame( 'low', $map->invoke( null, $badge->invoke( null, array( $row( false ) ) ) )['intent'], 'Issues are low' );
		$this->assertSame( 'low', $map->invoke( null, array( 'fix', 'Required' ) )['intent'], 'Required is yellow, as in the design' );
		$this->assertSame( 'stable', $map->invoke( null, array( 'pass', 'Active' ) )['intent'] );
	}

	/**
	 * @testdox A fix for two products names both, and one for more than three gives the count.
	 */
	public function test_output_product_fix_names_a_few_products(): void {
		$first  = WC_Helper_Product::create_simple_product();
		$second = WC_Helper_Product::create_simple_product();
		$second->set_name( 'Red cap' );
		$second->save();
		wc_get_container()->get( AgentReadiness::class )->refresh_report();

		$titles = array();
		foreach ( $this->view()['sections'] as $section ) {
			$titles = array_merge( $titles, array_column( array_merge( $section['rows'], $section['folded'] ), 'title' ) );
		}
		$brand = array_values( array_filter( $titles, static fn( string $title ): bool => 0 === strpos( $title, 'Add a brand to ' ) ) );
		$this->assertCount( 1, $brand );
		$this->assertStringContainsString( $first->get_name(), $brand[0], 'Both failing products are named' );
		$this->assertStringContainsString( 'Red cap', $brand[0] );
		$this->assertStringContainsString( ' and ', $brand[0], 'Two names are joined with "and", in either order' );
		$named = array_filter( (array) $this->view()['fix_first']['items'], static fn( array $item ): bool => false !== strpos( $item['title'], 'Red cap' ) );
		$this->assertNotEmpty( $named, 'Fix first has a product fix that names the products' );

		WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_simple_product();
		wc_get_container()->get( AgentReadiness::class )->refresh_report();
		$this->row( 'Add a brand to 4 products' );
		$counted = array_filter( (array) $this->view()['fix_first']['items'], static fn( array $item ): bool => false !== strpos( $item['title'], ' 4 products' ) );
		$this->assertNotEmpty( $counted, 'Fix first has a product fix that counts the products' );
	}

	/**
	 * @testdox Sales readiness shows what each channel can list and each failing check, naming the channel that can't list it, with a filtered list link, without the alt text check.
	 */
	public function test_output_product_checks_and_store_check_detail(): void {
		$product = WC_Helper_Product::create_simple_product();
		$image   = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) );
		$product->set_image_id( $image );
		$product->save();
		update_option( 'blog_public', '0' );
		wc_get_container()->get( AgentReadiness::class )->refresh_report();

		$view     = $this->view();
		$products = array_column( $view['sections'], null, 'id' )['products'];
		$json     = wp_json_encode( $view );

		$this->assertGreaterThan( 0, array_column( get_option( AgentReadiness::REPORT_OPTION )['checks'], 'count', 'id' )['image_alt_text'] ?? 0, 'The scan found the missing alt text' );
		$this->assertMatchesRegularExpression( '#^\d+ issues?$#', $products['badge']['text'], 'Products have their own question' );
		$this->assertSame(
			array(
				'name'     => 'ChatGPT',
				'listable' => '0',
				'of'       => 'of 1 can be listed',
			),
			$products['channels'][0],
			'The count reads as one line, with no link of its own'
		);
		$this->assertStringStartsWith( 'Last checked today at ', $view['last_checked'] );
		$this->assertNotNull( $view['fix_first'], 'When it last ran, Fix first, then a card per question' );
		$brand = $this->row( 'Add a brand to ' . $product->get_name() );
		$this->assertSame( 'ChatGPT only lists products that have a brand. In Google and Copilot, a brand makes products easier to find.', $brand['text'], 'One failing product is named in the title; the text says why a brand matters' );
		$this->assertSame( '', $brand['fix'], 'Adding a brand needs no fix line' );
		$this->assertStringNotContainsString( 'Required by', $json );
		$this->assertStringNotContainsString( 'Harder to find on', $json, 'Only channels that can\'t list the product are tagged' );
		$this->assertSame( 'View 1 product', $brand['link']['text'] );
		$this->assertStringContainsString( 'agent_readiness=', $brand['link']['href'], 'The link opens the Products list filtered to the failing products' );
		$this->assertStringNotContainsString( 'main image has no alt text', $json, 'Store readiness covers alt text' );
		$this->assertStringNotContainsString( 'post=' . $product->get_id(), $json, 'No example products' );
		$this->assertStringContainsString( 'Let search engines index your store', $this->render_page(), 'Hidden from search engines is a Recommended setup step' );
		$this->assertNull( $view['checked_note'] );

		$stored              = get_option( AgentReadiness::REPORT_OPTION );
		$stored['truncated'] = true;
		update_option( AgentReadiness::REPORT_OPTION, $stored );
		$this->assertSame(
			sprintf( 'Checked the %s products you changed most recently.', number_format_i18n( ProductScan::PRODUCT_LIMIT ) ),
			$this->view()['checked_note'],
			'A report without the total keeps the short note'
		);
		$stored['available'] = 8400;
		update_option( AgentReadiness::REPORT_OPTION, $stored );
		$this->assertSame(
			sprintf( 'Checked the %s products you changed most recently, out of 8,400.', number_format_i18n( ProductScan::PRODUCT_LIMIT ) ),
			$this->view()['checked_note'],
			'With the total from the same scan'
		);
		$this->assertSame(
			sprintf( 'Based on your %s most recently changed products.', number_format_i18n( ProductScan::PRODUCT_LIMIT ) ),
			$this->view()['overview']['based_on'],
			'The AI tools card says it after its description'
		);
	}

	/**
	 * @testdox AI tools opens on the Overview, a React root; AI shopping's Status links to Sales readiness with the store and product issues counted together.
	 */
	public function test_sections_and_status_links(): void {
		$this->make_every_step_pass();
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(
					array(
						'id'      => 'llms_txt_present',
						'group'   => 'find',
						'ok'      => false,
						'blocks'  => 'discovery',
						'level'   => 'recommended',
						'name'    => 'Store serves an llms.txt file',
						'label'   => 'No llms.txt file',
						'why'     => '',
						'fix'     => '',
						'fix_url' => '',
					),
				),
				'checks'       => array(
					array(
						'id'    => 'image_alt_text',
						'count' => 3,
					),
					array(
						'id'    => 'missing_brand',
						'label' => 'Products with no brand',
						'count' => 2,
					),
				),
			),
			false
		);

		$this->assertSame( array( '', AgenticCommerceSettings::SHOPPING_SECTION ), array_keys( $this->page()->get_sections() ) );

		$html = $this->render_page( '' );
		$this->assertTrue( $this->save_hidden, 'Nothing on the Overview saves' );
		$this->assertStringContainsString( '<div id="wc-agent-readiness-overview"', $html, 'The Overview is React, mounted by the settings-embed script' );
		$this->assertMatchesRegularExpression( '#data-view="([^"]+)"#', $html );
		preg_match( '#data-view="([^"]+)"#', $html, $match );
		$this->assertSame( 'Set up AI shopping', json_decode( html_entity_decode( $match[1] ), true )['overview']['shopping']['button']['text'], 'The view comes with the page, so the cards draw without a request' );
		$this->assertStringNotContainsString( 'No llms.txt file', $html );

		$view = $this->view();
		$this->assertSame( 'Set up AI shopping', $view['overview']['shopping']['button']['text'] );
		$this->assertSame( 'View all checks', $view['overview']['view_all']['text'] );
		$this->assertStringContainsString( 'path=/sales-readiness', $view['overview']['view_all']['href'] );
		$badges = array_column( $view['sections'], 'badge', 'id' );
		$this->assertSame( '1 issue', $badges['find']['text'] );
		$this->assertSame(
			array(
				'intent' => 'stable',
				'text'   => 'All passed',
			),
			$badges['access']
		);
		$this->assertSame( '1 issue', $badges['products']['text'] );
		$this->assertNull( $view['declared'], 'No card when the report lists no declared items' );

		$html = $this->render_page();
		$this->assertMatchesRegularExpression( '#path=/sales-readiness">Sales readiness</a> <mark[^>]*>2 issues</mark>#', $html, 'One store and one product issue; the alt text check is not counted' );
		$this->assertSame( 1, substr_count( $html, 'wc-agentic-commerce-results"><li>' ), 'One results row' );
		$this->assertFalse( $this->save_hidden, 'AI shopping saves the store policy fields' );
		$this->assertStringContainsString( '<span aria-current="page">AI shopping</span>', $html );
		$this->assertStringNotContainsString( 'No llms.txt file', $html, 'The checks themselves are on Sales readiness' );

		$this->assertSame(
			array(
				'href' => '',
				'text' => '2 products',
			),
			$this->row( 'Add a brand to 2 products' )['link'],
			'Product check titles say what to do and to how many; a report without stored failures has no filtered list to link to'
		);
	}

	/**
	 * @testdox Status says no issues only when readiness checks ran; a scan whose checks couldn't run isn't shown as passed.
	 */
	public function test_status_link_when_checks_could_not_run(): void {
		$this->make_every_step_pass();
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(
					array(
						'id'      => 'llms_txt_present',
						'group'   => 'find',
						'ok'      => null,
						'blocks'  => 'discovery',
						'level'   => 'recommended',
						'name'    => 'Store serves an llms.txt file',
						'label'   => 'No llms.txt file',
						'why'     => '',
						'fix'     => '',
						'fix_url' => '',
					),
				),
			),
			false
		);

		$this->assertMatchesRegularExpression( '#Sales readiness</a> <mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--off">No issues</mark>#', $this->render_page() );
	}

	/**
	 * @testdox A missing returns policy in the product details shows once, as the refund and returns page step, while no page is chosen.
	 */
	public function test_returns_policy_shows_once_with_the_refund_page_step(): void {
		$this->make_every_step_pass();
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(
					array(
						'id'       => 'product_return_policy_schema',
						'ok'       => false,
						'blocks'   => 'checkout',
						'group'    => 'buy',
						'name'     => 'name',
						'label'    => 'label',
						'why'      => 'why',
						'fix'      => 'fix',
						'fix_url'  => '',
						'conflict' => false,
						'pending'  => false,
					),
				),
			),
			false
		);
		$title = ReadinessCopy::store_check( 'product_return_policy_schema' )['action'];

		$this->assertStringContainsString( $title, (string) wp_json_encode( $this->view() ), 'Shown while a refund and returns page is chosen' );

		delete_option( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION );
		$this->assertStringNotContainsString( $title, (string) wp_json_encode( $this->view() ), 'Left out while the step fails' );
	}

	/**
	 * @testdox Checks coming in a later update aren't shown or counted, and an open check names its fix and links to it.
	 */
	public function test_readiness_hides_pending_checks(): void {
		$this->make_every_step_pass();
		$row = static fn( string $id, ?bool $ok, bool $pending = false ): array => array(
			'id'      => $id,
			'ok'      => $ok,
			'blocks'  => 'checkout',
			'group'   => 'buy',
			'name'    => $id . ' name',
			'label'   => $id . ' label',
			'why'     => $id . ' why',
			'fix'     => $id . ' fix',
			'fix_url' => admin_url( 'upload.php' ),
			'pending' => $pending,
		);
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array( $row( 'policy_pages_linked', false ), $row( 'product_shipping_schema', null, true ) ),
			),
			false
		);

		$view = $this->view();
		$json = wp_json_encode( $view );

		$this->assertStringNotContainsString( 'product_shipping_schema', $json );
		$this->assertStringNotContainsString( 'Coming soon', $json );
		$this->assertMatchesRegularExpression( '#Sales readiness</a> <mark[^>]*>1 issue</mark>#', $this->render_page(), 'Status counts the same checks' );
		$this->assertSame( '1 issue', array_column( $view['sections'], 'badge', 'id' )['buy']['text'] );
		$row = $this->row( 'Link to your privacy and returns pages from your home page' );
		$this->assertSame( ReadinessCopy::store_check( 'policy_pages_linked' )['fix'], $row['fix'], 'The fix line comes from the copy, not the report' );
		$this->assertSame( 'Go to Media library', $row['link']['text'], 'A fix in the media library says where it goes' );
		$this->assertSame( 'Check again', $view['run']['label'], 'Once a check has run, the button runs it again' );
	}

	/**
	 * @testdox While a scan waits, Run check is disabled and the page says so; once it stalls, the button stays disabled and the page says scheduled actions aren't running.
	 */
	public function test_output_run_pending_and_stalled(): void {
		delete_option( AgentReadiness::REPORT_OPTION );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );

		$view = $this->view();
		$this->assertSame( 'Not checked yet', $view['last_checked'], 'No check has run' );
		$this->assertSame( 'Run checks', $view['run']['label'], 'and the page offers to run one' );
		$this->assertFalse( $view['run']['pending'] );
		$this->assertSame( 'Run a check to see what AI assistants get when they visit your store and read your products.', $view['intro'], 'No results before the first scan' );
		$this->assertSame( array(), $view['sections'] );
		$this->assertNull( $view['fix_first'] );

		wc_get_container()->get( ScheduledScan::class )->queue();
		$run = $this->view()['run'];
		$this->assertSame( 'Checking your store.', $run['message'], 'Read out to screen readers; the page shows a spinner' );
		$this->assertTrue( $run['pending'] );
		$this->assertFalse( $run['stalled'], 'A queued check is not stalled, so the page keeps polling' );

		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		$this->age_finished_actions();
		as_schedule_single_action( time() - ScheduledScan::STALLED_AFTER - 60, ScheduledScan::RUN_NOW_HOOK, array(), ScheduledScan::GROUP );
		$run = $this->view()['run'];
		$this->assertSame( 'The check can’t start because scheduled actions aren’t running on this site.', $run['message'] );
		$this->assertSame( 'Go to Scheduled Actions', $run['link']['text'] );
		$this->assertStringContainsString( 'tab=action-scheduler', $run['link']['href'] );
		$this->assertTrue( $run['pending'] );
		$this->assertTrue( $run['stalled'], 'and the page stops showing a spinner' );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
	}

	/**
	 * Date every finished action an hour back, so only what the test does next
	 * counts as recent queue activity.
	 */
	private function age_finished_actions(): void {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}actionscheduler_actions SET last_attempt_gmt = %s WHERE status IN ( 'complete', 'failed', 'in-progress' )", gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) ) );
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

		$settings = $this->page()->get_settings_for_section( AgenticCommerceSettings::SHOPPING_SECTION );
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
		foreach ( $this->page()->get_settings_for_section( AgenticCommerceSettings::SHOPPING_SECTION ) as $field ) {
			if ( AgenticCommerceSettings::TERMS_PAGE_OPTION === ( $field['id'] ?? '' ) ) {
				$terms_field = $field;
			}
		}
		$this->assertArrayNotHasKey( (string) $checkout, $terms_field['options'], 'The checkout page is not offered' );

		WC_Admin_Settings::save_fields(
			$this->page()->get_settings_for_section( AgenticCommerceSettings::SHOPPING_SECTION ),
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

		$settings = $this->page()->get_settings_for_section( AgenticCommerceSettings::SHOPPING_SECTION );
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
			$this->page()->get_settings_for_section( AgenticCommerceSettings::SHOPPING_SECTION ),
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
	 * Make every setup step pass, recommended ones too, so a test counts only the scanned checks.
	 */
	private function make_every_step_pass(): void {
		StoreConditionsTest::make_store_ready( $this );
		update_option( AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION, get_option( AgenticCommerceSettings::TERMS_PAGE_OPTION ) );
		update_option( 'blog_public', '1' );
		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
		$gateways = WC()->payment_gateways()->payment_gateways();
		$email    = WC()->mailer()->get_emails()['WC_Email_Customer_Processing_Order'];
		foreach ( array( reset( $gateways ), $email ) as $item ) {
			$this->restore[] = array( $item, $item->enabled );
			$item->enabled   = 'yes';
		}
	}

	/**
	 * A fresh settings page.
	 */
	private function page(): AgenticCommerceSettingsPage {
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		delete_option( 'woocommerce_feature_ucp_enabled' );
		$this->sut->handle_init();
		$pages = $this->sut->handle_woocommerce_get_settings_pages( array() );
		return $pages[0];
	}

	/**
	 * What Sales readiness and the Overview show, from ReadinessCheckPage::view().
	 *
	 * @return array<string, mixed>
	 */
	private function view(): array {
		return wc_get_container()->get( ReadinessCheckPage::class )->view();
	}

	/**
	 * The titles of every row in every section, open or folded.
	 *
	 * @param array<string, mixed> $view The view.
	 * @return array<int, string>
	 */
	private function titles( array $view ): array {
		$titles = array();
		foreach ( $view['sections'] as $section ) {
			$titles = array_merge( $titles, array_column( $section['rows'], 'title' ), array_column( $section['folded'], 'title' ) );
		}
		return $titles;
	}

	/**
	 * The row with this title, from any section.
	 *
	 * @param string $title Row title.
	 * @return array<string, mixed>
	 */
	private function row( string $title ): array {
		foreach ( $this->view()['sections'] as $section ) {
			foreach ( array_merge( $section['rows'], $section['folded'] ) as $row ) {
				if ( $title === $row['title'] ) {
					return $row;
				}
			}
		}
		$this->fail( 'No row titled ' . $title );
	}

	/**
	 * Read apostrophes the same on every WordPress: esc_html() writes the entity, and wp_kses_post() on WordPress 7.2 and later turns it back into a plain apostrophe.
	 *
	 * @param string $html Rendered page.
	 */
	private static function plain_apostrophes( string $html ): string {
		return str_replace( '&#039;', "'", $html );
	}

	/**
	 * Whether the last render_page() hid Save changes.
	 *
	 * @var bool
	 */
	private $save_hidden = false;

	/**
	 * Render a section of the tab: AI shopping, or '' for the landing page.
	 *
	 * @param string $section Section id.
	 * @param bool   $ucp_on  Whether UCP is on; page() turns it off.
	 */
	private function render_page( string $section = AgenticCommerceSettings::SHOPPING_SECTION, bool $ucp_on = false ): string {
		global $current_section;
		// Localized data accumulates on a registered handle, so start each render clean.
		wp_deregister_script( 'wc-agentic-commerce-settings' );
		$page = $this->page();
		if ( $ucp_on ) {
			update_option( 'woocommerce_feature_ucp_enabled', 'yes' );
		}
		unset( $GLOBALS['hide_save_button'] );
		$current_section = $section; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The section WooCommerce sets from the URL; reset below.
		try {
			ob_start();
			$page->output();
			return self::plain_apostrophes( (string) ob_get_clean() );
		} finally {
			$current_section   = ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reset after the render.
			$this->save_hidden = ! empty( $GLOBALS['hide_save_button'] );
			unset( $GLOBALS['hide_save_button'] );
		}
	}

	/**
	 * @testdox With "Discourage search engines" on, the noindex it prints is one issue: the setup step, not also the scanned check.
	 */
	public function test_noindex_from_discourage_search_engines_shows_once(): void {
		$this->make_every_step_pass();
		update_option( 'blog_public', '0' );
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(
					array(
						'id'      => 'no_noindex_directives',
						'ok'      => false,
						'blocks'  => 'listing',
						'group'   => 'access',
						'name'    => 'noindex name',
						'label'   => 'noindex label',
						'why'     => '',
						'fix'     => '',
						'fix_url' => '',
					),
				),
			)
		);

		$titles = array_column( $this->view()['fix_first']['items'], 'title' );
		$this->assertSame( array( 'Let search engines index your store' ), $titles );

		update_option( 'blog_public', '1' );
		$this->assertCount( 1, $this->view()['fix_first']['items'], 'A noindex from elsewhere, such as an SEO plugin, still shows' );
		$this->assertNotContains( 'Let search engines index your store', array_column( $this->view()['fix_first']['items'], 'title' ) );
	}

	/**
	 * The view with one failing scanned check, which has no affects line of its own.
	 *
	 * @param string $id Check id.
	 * @return array<string, mixed>
	 */
	private function view_with_check( string $id ): array {
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(
					array(
						'id'       => $id,
						'ok'       => false,
						'blocks'   => 'discovery',
						'group'    => 'find',
						'name'     => $id . ' name',
						'label'    => $id . ' label',
						'why'      => '',
						'fix'      => '',
						'fix_url'  => '',
						'conflict' => false,
					),
				),
			)
		);
		return $this->view();
	}
}
