<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgenticCommerceSettings;
use Automattic\WooCommerce\Internal\AgentReadiness\AgenticCommerceSettingsPage;
use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessCheckPage;
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
		update_option( 'woocommerce_feature_ucp_enabled', 'no' );
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

		$this->assertStringContainsString( 'Product counts appear after the first readiness check.', $html );
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
		$this->assertStringContainsString( '<span>Product type not supported yet</span> <strong>1</strong>', $html );
		$this->assertStringContainsString( 'agent_readiness=' . ProductScan::COUNT_UNSUPPORTED . '">View products</a>', $html, 'Each count links to its products' );
		$this->assertStringContainsString( 'Supported now: simple and variable products.', $html );
		$this->assertMatchesRegularExpression( '#<p><strong>\d+ required steps? to fix so AI assistants can shop your store\.</strong></p>#', $html, 'Status counts the required steps first' );
		$this->assertStringContainsString( 'Fix anything marked &quot;Fix&quot;', $html );
		$this->assertMatchesRegularExpression( '#<h2>Status</h2>\s*<mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--check">Needs attention</mark>#', $html, 'A failing required step needs attention, even with UCP off' );
		$this->assertStringContainsString( 'aria-checked="false"', $html );
		$this->assertStringNotContainsString( 'to turn this on', $html );
		$this->assertMatchesRegularExpression( '#wc-agentic-commerce-item--required.*badge--fix">Fix</mark>#', $html, 'Failing required checks are marked Fix' );
		$this->assertMatchesRegularExpression( '#wc-agentic-commerce-item--open.*badge--check">Check</mark>#', $html, 'Failing recommended checks are marked Check' );
		$this->assertStringNotContainsString( 'Required.', $html, 'Policy pages are recommended, so no field is marked required' );
		$this->assertLessThan( strpos( $html, 'wc-agentic-commerce-item--open' ), strrpos( $html, 'wc-agentic-commerce-item--required' ), 'Required steps come first' );
		$this->assertMatchesRegularExpression( '/(Choose a|Publish your) privacy policy page/', $html, 'An open check says what to do' );
		$this->assertStringContainsString( 'Store address for AI assistants', $html );
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

		$this->assertMatchesRegularExpression( '#<p><strong>(\d+ steps? left\.|Everything AI assistants need is set up\.)</strong></p>#', $html );
		$this->assertStringContainsString( 'badge--off">Off</mark>', $html, 'Status is Off while UCP is off and nothing required fails' );
		$this->assertStringContainsString( 'badge--pass">Pass</mark>', $html, 'Completed steps are marked Pass' );
		$this->assertStringNotContainsString( 'wc-agentic-commerce-missing', $html );
		$this->assertStringNotContainsString( 'wc-agentic-commerce-item--required', $html );
		$this->assertMatchesRegularExpression( '#Show \d+ completed steps?</span>#', $html, 'Completed steps are folded' );
		$this->assertStringContainsString( 'Privacy policy page is selected', $html, 'Done rows are named by their passing state' );
		$this->assertStringNotContainsString( 'wc-agentic-commerce-notice', $html, 'No coming soon notice on a live store' );

		$shopping = $this->view()['overview']['shopping'];
		$this->assertNull( $shopping['badge'], 'With UCP off and nothing required failing, the Overview card has no badge' );
		$this->assertSame( 'Make your products available to AI shopping assistants.', $shopping['text'] );
		$this->assertSame( 'Set up AI shopping', $shopping['button']['text'], 'and offers set up' );
	}

	/**
	 * @testdox A missing policy page is recommended, so it does not block the switch or the required group.
	 */
	public function test_missing_policy_page_is_recommended(): void {
		StoreConditionsTest::make_store_ready();
		delete_option( AgenticCommerceSettings::PRIVACY_PAGE_OPTION );

		$html = $this->render_page();

		$this->assertStringNotContainsString( 'wc-agentic-commerce-item--required', $html );
		$this->assertMatchesRegularExpression( '#wc-agentic-commerce-item--open.*Choose a privacy policy page.*badge--check">Check</mark>#s', $html );
		$this->assertDoesNotMatchRegularExpression( '#required steps? to fix#', $html );
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
		$this->assertStringContainsString( 'It\'s still a draft. AI assistants only link to published pages.', $html );
		$this->assertStringContainsString( 'post=' . $draft . '&amp;action=edit">Publish your refund and returns policy page</a>', $html, 'The step links to the page editor' );

		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(),
			)
		);
		$view = $this->view();
		$this->assertNull( $view['fix_first'], 'A policy page is only recommended, so it stays out of Fix first' );
		$this->assertMatchesRegularExpression( '#"title":"Publish your refund and returns policy page".{0,600}?"href":"[^"]*post=' . $draft . '&action=edit","text":"Edit page"#', (string) wp_json_encode( $view['sections'], JSON_UNESCAPED_SLASHES ), 'Readiness check links the step to the page editor' );
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

		$live = $this->row( 'Sitemap lists your products' )['lines'];
		update_option( 'woocommerce_coming_soon', 'yes' );
		$coming_soon = $this->row( 'Sitemap lists your products' )['lines'];

		$this->assertSame( array( 'This couldn\'t be checked. The next check tries again.' ), $live );
		$this->assertSame( array( 'Launch your store to run this check.' ), $coming_soon );
	}

	/**
	 * @testdox With UCP already on and a required check failing, the switch stays on and Status needs attention, without saying assistants can shop.
	 */
	public function test_output_switch_on_with_failing_checks(): void {
		remove_all_filters( 'pre_option_home' );
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'home', 'http://example.org' );
		$html = $this->render_page( AgenticCommerceSettings::SHOPPING_SECTION, true );

		$this->assertStringContainsString( 'aria-checked="true"', $html );
		$this->assertStringNotContainsString( 'AI assistants can now find and shop your store.', $html );
		$this->assertStringContainsString( 'badge--check">Needs attention</mark>', $html );

		$shopping = $this->view()['overview']['shopping'];
		$this->assertSame(
			array(
				'intent' => 'medium',
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
			array( 'Can AI assistants visit your store?', 'Can AI assistants find your store?', 'Can AI assistants read your product details?', 'Can AI shopping apps list your products?', 'Can shoppers buy through AI assistants?' ),
			array_column( $view['sections'], 'title' ),
			'Products come before Can shoppers buy through AI assistants?'
		);
		$this->assertSame( 'Agent shopping is off, so the UCP checks don\'t run.', $sections['access']['note'], 'UCP is off in this test' );
		$this->assertSame(
			array(
				'intent' => 'medium',
				'text'   => '1 issue',
			),
			$sections['understand']['badge'],
			'The failing check is the issue; the unrun one is hidden'
		);
		$this->assertSame(
			array(
				'intent' => 'informational',
				'text'   => 'All passed',
			),
			$sections['find']['badge']
		);
		$conflict = $sections['understand']['rows'][0];
		$this->assertSame( 'product_jsonld_present label', $conflict['title'], 'A failing check is named by its failing title' );
		$this->assertSame( 'Affects: the product details AI assistants read', $conflict['affects'], 'A failing check says what it affects' );
		$this->assertSame(
			array(
				'href' => 'https://example.org/product/mug/',
				'text' => 'https://example.org/product/mug/',
			),
			$conflict['seen_at'],
			'A conflict names the page'
		);
		$this->assertSame( 'Affects: how AI assistants find and read your store', $this->view_with_check( 'sitemap_lists_products' )['sections'][1]['rows'][0]['affects'], 'or, without its own line, what its kind of check affects' );
		$this->assertStringNotContainsString( 'Yoast SEO', $json, 'No plugin is named, even from an older stored report' );
		$this->assertStringNotContainsString( 'product_offer_jsonld name', $json, 'Its prerequisite failed' );
		$this->assertStringNotContainsString( 'product_jsonld_present name', $json );
		$this->assertStringNotContainsString( 'sitemap_lists_products label', $json, 'A passed check keeps its passing title' );
		$this->assertSame( array(), $sections['find']['rows'], 'Nothing open under find' );
		$this->assertContains( 'sitemap_lists_products name', array_column( $sections['find']['folded'], 'title' ), 'Passed checks are folded' );
		$this->assertStringNotContainsString( 'sitemap_lists_products why', $json, 'A passed scanned check drops its failure-worded why too' );
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
		$this->assertSame( 'Optional files some AI tools look for. Nothing to fix. 1 of 3 found.', $view['declared']['text'] );
		$this->assertSame( 'Show the 3 items', $view['declared']['more'] );
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
				'intent' => 'informational',
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

		$this->assertStringContainsString( 'Nothing to fix. 0 of 1 found.', $this->view()['declared']['text'] );

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
			'channels'     => array( 'chatgpt' => array( 'name' => 'ChatGPT' ) ),
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
		$this->assertSame( 'Ranked by what each fix is worth in sales.', $fix_first['ranking'] );
		$this->assertSame(
			array( 'Move your store to HTTPS', 'Allow guest checkout', '5 products have no main image', '2 products have no brand' ),
			array_column( $fix_first['items'], 'title' ),
			'The required step, then 3 issues: guest checkout stops every sale, then the not listable checks by share; GTIN, a gap, is left out despite its larger share'
		);
		$this->assertSame( array( 'Stops every sale', 'Stops every sale', 'Products that can\'t be listed', 'Products that can\'t be listed' ), array_column( $fix_first['items'], 'tier' ) );
		$this->assertSame(
			array(
				'intent' => 'medium',
				'text'   => 'Required',
			),
			$fix_first['items'][0]['impact']
		);
		$this->assertNull( $fix_first['items'][1]['impact'], 'The tier heading already says guest checkout stops every sale' );
		$this->assertSame(
			array(
				'intent' => 'none',
				'text'   => '18% of sales',
			),
			$fix_first['items'][2]['impact']
		);
		$this->assertSame( '18% of your sales in the last 90 days. missing_images why. missing_images fix.', $fix_first['items'][2]['why'], 'The first share says over which period, then why and what to do' );
		$this->assertSame( '5% of your sales. missing_brand why. missing_brand fix.', $fix_first['items'][3]['why'], 'The next ones give only the share' );
		$this->assertSame( 'Open settings', $fix_first['items'][1]['link']['text'], 'A setup step links to where to fix it' );
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
		$this->assertStringContainsString( '"text":"No sales"', $json, 'Products that sold nothing in the period say so, not 0%' );
		$this->assertStringContainsString( 'No sales. missing_brand why. missing_brand fix.', $json );
		$this->assertStringNotContainsString( '0% of', $json );
		$report['checks'][1]['sales_share'] = 0.05;
		$this->assertStringNotContainsString( 'under 1%', $json, 'Shares are in Fix first only, not on the product card' );

		$report['sales'] = null;
		foreach ( $report['checks'] as &$row ) {
			$row['sales_share'] = null;
		}
		unset( $row );
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$fix_first = $this->view()['fix_first'];
		$this->assertSame( 'Ranked by how many products each issue affects. Turn on Analytics to rank by sales instead.', $fix_first['ranking'] );
		$this->assertSame(
			array(
				'intent' => 'none',
				'text'   => '5 products',
			),
			$fix_first['items'][2]['impact'],
			'Without sales, an issue shows how many products it affects'
		);
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
	 * @testdox In Fix first, store pages agents can't reach stop every sale; other listing checks, such as structured data, keep products off a channel.
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
				'store_checks' => array( $output( 'product_shipping_schema', 'buy' ), $output( 'pages_public', 'access' ) ),
				'channels'     => array(),
				'checks'       => array(),
			)
		);

		$items = $this->view()['fix_first']['items'];
		$this->assertSame(
			array(
				array( 'Stops every sale', 'pages_public label', null ),
				array( 'Products that can\'t be listed', 'product_shipping_schema label', 'All products' ),
			),
			array_map( static fn( array $item ): array => array( $item['tier'], $item['title'], $item['impact']['text'] ?? null ), $items ),
			'The heading says it stops every sale, so that item has no badge'
		);
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
	 * @testdox A question's badge counts its issues; a check that couldn't run isn't an issue.
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

		$map = new \ReflectionMethod( ReadinessCheckPage::class, 'badge_data' );
		$map->setAccessible( true );
		$this->assertSame( 'informational', $map->invoke( null, $badge->invoke( null, array( $row( true ) ) ) )['intent'], 'All passed stays neutral' );
		$this->assertSame( 'informational', $map->invoke( null, $badge->invoke( null, array( $row( true ), $row( null ) ) ) )['intent'], 'No issues is not a pass' );
		$this->assertSame( 'stable', $map->invoke( null, array( 'pass', 'Active' ) )['intent'] );
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
		$brand = $this->row( 'ChatGPT can\'t list 1 product without a brand' );
		$this->assertMatchesRegularExpression( '#^Google and [^<]*Copilot still list it, with a warning\.$#', $brand['lines'][0], 'The title names the channel that can\'t list it, and the ones that still do' );
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
			sprintf( 'Checked your %s most recently changed products.', number_format_i18n( ProductScan::PRODUCT_LIMIT ) ),
			$this->view()['checked_note']
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
		$this->assertStringNotContainsString( 'No llms.txt file', $html );

		$view = $this->view();
		$this->assertSame( 'Set up AI shopping', $view['overview']['shopping']['button']['text'] );
		$this->assertSame( 'View all checks', $view['overview']['view_all']['text'] );
		$this->assertStringContainsString( 'path=/sales-readiness', $view['overview']['view_all']['href'] );
		$badges = array_column( $view['sections'], 'badge', 'id' );
		$this->assertSame( '1 issue', $badges['find']['text'] );
		$this->assertSame(
			array(
				'intent' => 'informational',
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
			$this->row( '2 products have no brand' )['link'],
			'Product check titles lead with the count; a report without stored failures has no filtered list to link to'
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

		$this->assertMatchesRegularExpression( '#Sales readiness</a> <mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--info">No issues</mark>#', $this->render_page() );
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
		$row = $this->row( 'policy_pages_linked label' );
		$this->assertContains( 'policy_pages_linked fix', $row['lines'] );
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
		$this->assertSame( 'Run check', $view['run']['label'], 'and the page offers to run one' );
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
		$this->assertStringStartsWith( 'The check has not started because scheduled actions', $run['message'] );
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
		update_option( 'woocommerce_feature_ucp_enabled', 'no' );
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
