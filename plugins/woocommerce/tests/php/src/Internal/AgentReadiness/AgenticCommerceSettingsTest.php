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
			$this->set_permalink_structure( '' );
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
	 * @testdox Readiness check is added only while the agent_readiness feature is enabled, with no menu item, as a WooCommerce screen that keeps Settings highlighted.
	 */
	public function test_readiness_page_only_with_feature_enabled(): void {
		$page = wc_get_container()->get( ReadinessCheckPage::class );
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'no' );
		remove_all_actions( 'admin_menu' );
		$page->handle_init();
		$this->assertFalse( has_action( 'admin_menu', array( $page, 'handle_admin_menu' ) ), 'No page with the feature off' );

		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		$page->handle_init();
		$this->assertNotFalse( has_action( 'admin_menu', array( $page, 'handle_admin_menu' ) ) );

		// The menu globals aren't rolled back between tests, so put them back after.
		$globals = array( 'menu', 'submenu', 'admin_page_hooks', '_registered_pages', '_parent_pages' );
		$saved   = array_map( static fn( string $name ) => $GLOBALS[ $name ] ?? null, array_combine( $globals, $globals ) );
		try {
			$GLOBALS['submenu'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Start from an empty menu; restored below.
			add_menu_page( 'WooCommerce', 'WooCommerce', 'manage_woocommerce', 'woocommerce' );
			$page->handle_admin_menu();
			$hook = 'woocommerce_page_' . ReadinessCheckPage::PAGE_SLUG;
			$this->assertContains( ReadinessCheckPage::PAGE_SLUG, array_column( $GLOBALS['submenu']['woocommerce'], 2 ), 'Added under WooCommerce, so WordPress opens that menu on it' );
			$page->handle_admin_head();
			$this->assertNotContains( ReadinessCheckPage::PAGE_SLUG, array_column( $GLOBALS['submenu']['woocommerce'] ?? array(), 2 ), 'No menu item once the menu prints: AI tools opens the page' );
			$this->assertArrayHasKey( $hook, $GLOBALS['_registered_pages'], 'The page still opens' );
			$this->assertContains( $hook, $page->handle_woocommerce_screen_ids( array() ), 'WooCommerce styles load on the page' );
			set_current_screen( $hook );
			$this->assertSame( 'wc-settings', $page->handle_submenu_file( null ), 'Settings stays highlighted' );
			set_current_screen( 'dashboard' );
			$this->assertSame( 'index.php', $page->handle_submenu_file( 'index.php' ), 'Other screens keep theirs' );
		} finally {
			remove_filter( 'submenu_file', array( $page, 'handle_submenu_file' ) );
			foreach ( $saved as $name => $value ) {
				$GLOBALS[ $name ] = $value; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring what the test changed.
			}
		}
		$this->assertSame( admin_url( 'admin.php?page=wc-agent-readiness' ), ReadinessCheckPage::url() );
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
		$this->assertStringContainsString( 'page=wc-agent-readiness">Readiness check</a> <mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--off">Not checked yet</mark>', $html, 'Status links to Readiness check, not yet run' );
		$this->assertStringNotContainsString( 'View products', $html );
		$this->assertStringContainsString( 'Published returns page', $html );
		$this->assertStringNotContainsString( 'Draft returns page', $html );
	}

	/**
	 * @testdox The page shows the three counts from the stored report, the switch and failing checks.
	 */
	public function test_output_with_report_and_failing_checks(): void {
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
		$this->assertStringContainsString( 'class="wc-agentic-commerce-missing"', $html, 'A missing required policy page is marked on its field' );
		$this->assertStringContainsString( 'Required. Choose a published page so AI assistants can shop your store.', $html );
		$this->assertLessThan( strpos( $html, 'wc-agentic-commerce-item--open' ), strrpos( $html, 'wc-agentic-commerce-item--required' ), 'Required steps come first' );
		$this->assertMatchesRegularExpression( '/(Choose a|Publish your) privacy policy page/', $html, 'An open check says what to do' );
		$this->assertStringContainsString( 'Store address for AI assistants', $html );
		$this->assertStringContainsString( 'page=wc-settings&#038;tab=agentic-commerce">AI tools</a> <span aria-hidden="true">/</span> <span aria-current="page">AI shopping</span></div>', $html, 'AI shopping has a breadcrumb back to the landing page' );
		$this->assertStringContainsString( '<h2>Security</h2>', $html, 'Placeholder until WOOAI-1620' );
		$this->assertStringNotContainsString( 'Universal Commerce Protocol (UCP) is off', $html, 'UCP is the switch, not a listed check' );
	}

	/**
	 * @testdox With every required check passing, open checks are only Recommended and passing ones are Done.
	 */
	public function test_output_on_a_ready_store(): void {
		StoreConditionsTest::make_store_ready( $this );

		$html = $this->render_page();

		$this->assertMatchesRegularExpression( '#<p><strong>(\d+ steps? left\.|Everything AI assistants need is set up\.)</strong></p>#', $html );
		$this->assertStringContainsString( 'badge--off">Off</mark>', $html, 'Status is Off while UCP is off and nothing required fails' );
		$this->assertStringContainsString( 'badge--pass">Pass</mark>', $html, 'Completed steps are marked Pass' );
		$this->assertStringNotContainsString( 'wc-agentic-commerce-missing', $html );
		$this->assertStringNotContainsString( 'wc-agentic-commerce-item--required', $html );
		$this->assertMatchesRegularExpression( '#Show \d+ completed steps?</span>#', $html, 'Completed steps are folded' );
		$this->assertStringContainsString( 'Privacy policy page is selected', $html, 'Done rows are named by their passing state' );
		$this->assertStringNotContainsString( 'wc-agentic-commerce-notice', $html, 'No coming soon notice on a live store' );

		$landing = $this->render_page( '' );
		$this->assertMatchesRegularExpression( '#<h2>AI shopping</h2></div><p[^>]*>Make your products available to AI shopping assistants\.</p>.*>Set up AI shopping</a>#s', $landing, 'With UCP off and nothing required failing, the landing card has no badge and offers set up' );
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
		$this->assertStringContainsString( 'It\'s still a draft. AI assistants only link to published pages.', $html );
		$this->assertStringContainsString( 'post=' . $draft . '&amp;action=edit">Publish your refund and returns policy page</a>', $html, 'The step links to the page editor' );

		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(),
			)
		);
		$this->assertStringContainsString( 'post=' . $draft . '&amp;action=edit">Edit page</a>', $this->render_readiness(), 'On Readiness check and in Fix first, the link says it opens the page editor' );
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

		$html = $this->render_readiness();

		$this->assertStringContainsString( 'Sitemap lists your products', $html );
		$this->assertStringContainsString( 'Couldn\'t check', $html );
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

		$live = $this->render_readiness();
		update_option( 'woocommerce_coming_soon', 'yes' );
		$coming_soon = $this->render_readiness();

		$this->assertStringContainsString( 'This couldn\'t be checked.', $live );
		$this->assertStringNotContainsString( 'Launch your store', $live );
		$this->assertStringContainsString( 'Launch your store to run this check.', $coming_soon );
		$this->assertStringNotContainsString( 'This couldn\'t be checked.', $coming_soon );
	}

	/**
	 * @testdox With UCP already on and a required check failing, the switch stays on and Status needs attention, without saying assistants can shop.
	 */
	public function test_output_switch_on_with_failing_checks(): void {
		$html = $this->render_page( AgenticCommerceSettings::SHOPPING_SECTION, true );

		$this->assertStringContainsString( 'aria-checked="true"', $html );
		$this->assertStringNotContainsString( 'AI assistants can now find and shop your store.', $html );
		$this->assertStringContainsString( 'badge--check">Needs attention</mark>', $html );

		$landing = $this->render_page( '', true );
		$this->assertMatchesRegularExpression( '#<h2>AI shopping</h2> <mark[^>]*>Needs attention</mark>.*AI assistants can\'t shop your store yet\. \d+ steps? left\..*section=shopping">Manage</a>#s', $landing, 'The landing card says so too, and Manage opens AI shopping' );
	}

	/**
	 * @testdox Store readiness badges each merchant question, marks a likely conflict with the page but names no plugin, hides checks whose prerequisite failed, and folds passed checks away.
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

		$html = $this->render_readiness();

		foreach ( array( 'Can AI assistants visit your store?', 'Can AI assistants find your store?', 'Can AI assistants read your product details?', 'Can shoppers buy through AI assistants?' ) as $question ) {
			$this->assertStringContainsString( $question, $html );
		}
		$this->assertStringContainsString( 'Agent shopping is off, so the UCP checks don\'t run.', $html, 'UCP is off in this test' );
		$this->assertMatchesRegularExpression( '#Can AI assistants read your product details\?</h2> <mark[^>]*>1 issue</mark>#', $html, 'The failing check is the issue; the unrun one is hidden' );
		$this->assertMatchesRegularExpression( '#Can AI assistants find your store\?</h2> <mark[^>]*>All passed</mark>#', $html );
		$this->assertStringContainsString( 'Affects: the product details AI assistants read', $html, 'A failing check says what it affects' );
		$this->assertStringContainsString( 'Affects: how AI assistants find and read your store', $this->render_readiness_with_check( 'sitemap_lists_products' ), 'or, without its own line, what its kind of check affects' );
		$this->assertStringContainsString( 'Seen on: <a href="https://example.org/product/mug/">https://example.org/product/mug/</a>', $html, 'A conflict names the page' );
		$this->assertStringNotContainsString( 'Yoast SEO', $html, 'No plugin is named, even from an older stored report' );
		$this->assertStringNotContainsString( 'product_offer_jsonld name', $html, 'Its prerequisite failed' );
		$this->assertStringContainsString( 'product_jsonld_present label', $html, 'A failing check is named by its failing title' );
		$this->assertStringNotContainsString( 'product_jsonld_present name', $html );
		$this->assertStringNotContainsString( 'sitemap_lists_products label', $html, 'A passed check keeps its passing title' );
		$this->assertMatchesRegularExpression( '#<summary><span class="wc-agentic-commerce-more__show">Show 2 more checks</span><span class="wc-agentic-commerce-more__hide">Hide</span></summary>.*sitemap_lists_products name.*</details>#s', $html, 'Passed checks are folded behind Show / Hide' );
		$this->assertStringNotContainsString( 'sitemap_lists_products why', $html, 'A passed scanned check drops its failure-worded why too' );
		$this->assertStringContainsString( 'Order confirmation emails are on', $html );
		$this->assertStringNotContainsString( 'Shoppers who buy through an agent get no confirmation from your store.', $html, 'A passed setup step drops its failure-worded why, as in Status' );
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

		$html = $this->render_readiness();

		$this->assertStringContainsString( 'What does your store share with AI assistants?</h2></div>', $html, 'No badge: nothing here passes or fails' );
		$this->assertStringContainsString( 'Optional files some AI tools look for. Nothing to fix. 1 of 3 found.', $html );
		$this->assertStringContainsString( 'Show the 3 items</span>', $html );
		$this->assertMatchesRegularExpression( '#item--pass.*ai\.txt usage file.*item--off.*AGENTS\.md.*item--off.*TDMRep#s', $html, 'Every item, found or not' );
		$this->assertMatchesRegularExpression( '#Can AI assistants find your store\?</h2> <mark[^>]*>All passed</mark>#', $html, 'Not counted as a result; the passing search engine step is' );
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

		$this->assertStringContainsString( 'Nothing to fix. 0 of 1 found.', $this->render_readiness() );

		update_option(
			'woocommerce_agent_readiness_report',
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(),
				'declared'     => array(),
			)
		);
		$this->assertStringNotContainsString( 'What does your store share with AI assistants?', $this->render_readiness() );
	}

	/**
	 * @testdox Fix first lists the failing required steps, then the top 3 issues: what stops every sale, then what keeps products off a channel, each ranked by its share of sales.
	 */
	public function test_output_fix_first_ranks_by_sales(): void {
		$this->make_every_step_pass();
		update_option( AgenticCommerceSettings::TERMS_PAGE_OPTION, 0 );
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

		$html = $this->render_readiness();
		$this->assertStringContainsString( 'Ranked by what each fix is worth in sales.', $html );
		preg_match( '#<div class="wc-agentic-commerce-card wc-agentic-commerce-fix-first.*?</ol></div>#s', $html, $fix_first );
		$this->assertNotEmpty( $fix_first, 'Readiness check opens with Fix first' );
		$titles = array();
		preg_match_all( '#<p class="wc-agentic-commerce-fix-first__title">(.*?)</p>#', $fix_first[0], $titles );
		$this->assertSame(
			array( 'Choose a terms and conditions page', 'Allow guest checkout', '5 products have no main image', '2 products have no brand' ),
			$titles[1],
			'The required step, then 3 issues: guest checkout stops every sale, then the not listable checks by share; GTIN, a gap, is left out despite its larger share'
		);
		$this->assertStringContainsString( '18% of sales</mark>', $fix_first[0] );
		$this->assertStringContainsString( '18% of your sales in the last 90 days. missing_images why. missing_images fix.', $fix_first[0], 'The first share says over which period, then why and what to do' );
		$this->assertStringContainsString( '5% of your sales. missing_brand why. missing_brand fix.', $fix_first[0], 'The next ones give only the share' );

		$report['checks'][1]['sales_share'] = 0.0;
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$html = $this->render_readiness();
		$this->assertStringContainsString( 'No sales</mark>', $html, 'Products that sold nothing in the period say so, not 0%' );
		$this->assertStringContainsString( 'No sales. missing_brand why. missing_brand fix.', $html );
		$this->assertStringNotContainsString( '0% of', $html );
		$report['checks'][1]['sales_share'] = 0.05;
		$this->assertStringContainsString( 'Open settings</a>', $fix_first[0], 'A setup step links to where to fix it' );
		$this->assertStringContainsString( '<p class="description">5 products</p>', $fix_first[0], 'A product issue names how many products it affects' );
		$this->assertStringNotContainsString( 'under 1%', $html, 'Shares are in Fix first only, not on the product card' );

		$report['sales'] = null;
		foreach ( $report['checks'] as &$row ) {
			$row['sales_share'] = null;
		}
		unset( $row );
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$html = $this->render_readiness();
		$this->assertStringContainsString( 'Ranked by how many products each issue affects. Turn on Analytics to rank by sales instead.', $html );
		$this->assertStringContainsString( '5 products</mark>', $html, 'Without sales, an issue shows how many products it affects' );
		$this->assertStringNotContainsString( 'of your sales', $html );

		$report['sales'] = array(
			'days'   => 90,
			'orders' => 30,
		);
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$this->assertStringContainsString( 'Ranked by how many products each issue affects.</p>', $this->render_readiness(), 'Orders with no share to rank by, such as free products or counts changed since the check' );

		$report['sales'] = array(
			'days'   => 90,
			'orders' => 12,
		);
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$this->assertStringContainsString( 'Only 12 orders in the last 90 days, too few to rank by sales, so issues are ranked by how many products they affect.', $this->render_readiness() );

		$report['sales'] = array(
			'days'   => 90,
			'orders' => 0,
		);
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$this->assertStringContainsString( 'No sales in the last 90 days, so issues are ranked by how many products they affect.', $this->render_readiness() );

		unset( $report['sales'] );
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$html = $this->render_readiness();
		$this->assertStringContainsString( 'Ranked by how many products each issue affects.</p>', $html, 'A report from before sales were read' );
		$this->assertStringNotContainsString( 'Turn on Analytics', $html, 'doesn\'t say Analytics is off' );
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

		preg_match( '#<div class="wc-agentic-commerce-card wc-agentic-commerce-fix-first.*?</ol></div>#s', $this->render_readiness(), $fix_first );
		$this->assertNotEmpty( $fix_first );
		preg_match_all( '#<li class="wc-agentic-commerce-fix-first__tier"[^>]*>(.*?)</li>.*?__title">(.*?)</p>(.*?)</div>#s', $fix_first[0], $rows, PREG_SET_ORDER );
		$this->assertSame(
			array(
				array( 'Stops every sale', 'pages_public label', '' ),
				array( 'Products that can\'t be listed', 'product_shipping_schema label', 'All products' ),
			),
			array_map( static fn( array $row ): array => array( $row[1], $row[2], wp_strip_all_tags( $row[3] ) ), $rows ),
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
		$this->assertStringNotContainsString( 'wc-agentic-commerce-fix-first', $this->render_readiness() );
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
	}

	/**
	 * @testdox Readiness check shows what each channel can list and each failing check, naming the channel that can't list it, with a filtered list link, without the alt text check.
	 */
	public function test_output_product_checks_and_store_check_detail(): void {
		$product = WC_Helper_Product::create_simple_product();
		$image   = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) );
		$product->set_image_id( $image );
		$product->save();
		update_option( 'blog_public', '0' );
		wc_get_container()->get( AgentReadiness::class )->refresh_report();

		$html = $this->render_readiness();

		$this->assertGreaterThan( 0, array_column( get_option( AgentReadiness::REPORT_OPTION )['checks'], 'count', 'id' )['image_alt_text'] ?? 0, 'The scan found the missing alt text' );
		$this->assertMatchesRegularExpression( '#Can AI shopping apps list your products\?</h2> <mark[^>]*>\d+ issues?</mark>#', $html, 'Products have their own question' );
		$this->assertStringContainsString( '<span>ChatGPT</span> <p><strong>0</strong> of 1 can be listed</p>', $html, 'The count reads as one line, with no link of its own' );
		$this->assertMatchesRegularExpression( '#aria-current="page">Readiness check</span></nav>.*wc-agentic-commerce-toolbar"><p>Last checked .*wc-agentic-commerce-fix-first.*<div class="wc-agentic-commerce-card wc-agentic-commerce-question" id="wc-agentic-commerce-group-products">#s', $html, 'When it last ran, Fix first, then a card per question' );
		$this->assertStringContainsString( 'ChatGPT can\'t list 1 product without a brand', $html, 'The title names the channel that can\'t list it' );
		$this->assertMatchesRegularExpression( '#Google and [^<]*Copilot still list it, with a warning\.#', $html, 'and the ones that still do' );
		$this->assertStringNotContainsString( 'Required by', $html );
		$this->assertStringNotContainsString( 'Harder to find on', $html, 'Only channels that can\'t list the product are tagged' );
		$this->assertStringContainsString( 'View 1 product</a>', $html );
		$this->assertStringContainsString( 'agent_readiness=', $html, 'The link opens the Products list filtered to the failing products' );
		$this->assertStringNotContainsString( 'main image has no alt text', $html, 'Store readiness covers alt text' );
		$this->assertStringNotContainsString( 'post=' . $product->get_id(), $html, 'No example products' );
		$this->assertStringContainsString( '<p>Last checked today at ', $html );
		$this->assertStringContainsString( 'Let search engines index your store', $this->render_page(), 'Hidden from search engines is a Recommended setup step' );
	}

	/**
	 * @testdox AI tools opens on a landing page with AI shopping and a Readiness check summary; AI shopping's Status links to Readiness check with the store and product issues counted together.
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
		$this->assertTrue( $this->save_hidden, 'Nothing on the landing page saves' );
		$this->assertMatchesRegularExpression( '#<h2>AI shopping</h2>.*section=shopping">Set up AI shopping</a>.*<h2>Readiness check</h2>#s', $html, 'The AI shopping card, then Readiness check' );
		$this->assertMatchesRegularExpression( '#<details><summary class="wc-agentic-commerce-summary__row"><span[^>]*>Can AI assistants find your store\?</span> <mark[^>]*>1 issue</mark></summary><ul[^>]*><li><a href="[^"]*page=wc-agent-readiness\#wc-agentic-commerce-group-find">No llms.txt file</a>#', $html, 'A question with issues opens on them, each linking to its card' );
		$this->assertMatchesRegularExpression( '#<div class="wc-agentic-commerce-summary__row"><span[^>]*>Can AI assistants visit your store\?</span> <mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--info">All passed</mark></div>#', $html, 'One without issues doesn\'t open' );
		$this->assertMatchesRegularExpression( '#Can AI shopping apps list your products\?</span> <mark[^>]*>1 issue</mark>#', $html );
		$this->assertStringContainsString( 'page=wc-agent-readiness">View all checks</a>', $html );
		$this->assertStringNotContainsString( 'What does your store share', $html, 'No row when the report lists no declared items' );

		$html = $this->render_page();
		$this->assertMatchesRegularExpression( '#page=wc-agent-readiness">Readiness check</a> <mark[^>]*>2 issues</mark>#', $html, 'One store and one product issue; the alt text check is not counted' );
		$this->assertSame( 1, substr_count( $html, 'wc-agentic-commerce-results"><li>' ), 'One results row' );
		$this->assertFalse( $this->save_hidden, 'AI shopping saves the store policy fields' );
		$this->assertStringContainsString( '<span aria-current="page">AI shopping</span>', $html );
		$this->assertStringNotContainsString( '<h2>Readiness check</h2>', $html, 'AI tools has no Readiness check card, only the Status link' );
		$this->assertStringNotContainsString( 'No llms.txt file', $html, 'The checks themselves are on Readiness check' );
		$this->assertStringNotContainsString( 'wc-agentic-commerce-fix-first', $html );

		$html = $this->render_readiness();
		$this->assertStringContainsString( '<h1 class="screen-reader-text">Readiness check</h1>', $html );
		$this->assertStringContainsString( '<a href="' . esc_url( AgenticCommerceSettings::url() ) . '">AI tools</a>', $html, 'The breadcrumb leads back to AI tools' );
		$this->assertMatchesRegularExpression( '#Can AI shopping apps list your products\?</h2> <mark[^>]*>1 issue</mark>#', $html );
		$this->assertLessThan( strpos( $html, 'Can shoppers buy through AI assistants?' ), strpos( $html, 'Can AI shopping apps list your products?' ), 'Products come before Can shoppers buy through AI assistants?' );
		$this->assertStringContainsString( '<p class="description">2 products</p>', $html, 'A report without stored failures has no filtered list to link to' );
		$this->assertStringContainsString( '2 products have no brand', $html, 'Product check titles lead with the count' );
		$this->assertStringNotContainsString( 'View 2 products', $html );
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

		$this->assertMatchesRegularExpression( '#Readiness check</a> <mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--info">No issues</mark>#', $this->render_page() );
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

		$html = $this->render_readiness();

		$this->assertStringNotContainsString( 'product_shipping_schema', $html );
		$this->assertStringNotContainsString( 'Coming soon', $html );
		$this->assertMatchesRegularExpression( '#Readiness check</a> <mark[^>]*>1 issue</mark>#', $this->render_page(), 'Status counts the same checks' );
		$this->assertMatchesRegularExpression( '#Can shoppers buy through AI assistants\?</h2> <mark[^>]*>1 issue</mark>#', $html );
		$this->assertStringContainsString( 'policy_pages_linked fix', $html );
		$this->assertStringContainsString( '>Go to Media library</a>', $html, 'A fix in the media library says where it goes' );
		$this->assertStringContainsString( '>Check again</button>', $html, 'Once a check has run, the button runs it again' );
	}

	/**
	 * @testdox While a scan waits, Run check is disabled and the page says so; once it stalls, the button stays disabled and the page says scheduled actions aren't running.
	 */
	public function test_output_run_pending_and_stalled(): void {
		delete_option( AgentReadiness::REPORT_OPTION );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );

		$landing = $this->render_page( '' );
		$this->assertStringContainsString( '<p>Not checked yet</p>', $landing, 'The landing page summary says no check has run' );
		$this->assertStringContainsString( '>Run check</button>', $landing, 'and offers to run one' );
		$this->assertStringNotContainsString( 'wc-agentic-commerce-summary__row', $landing, 'with no question rows' );
		$this->assertStringNotContainsString( 'View all checks', $landing );
		$this->assertSame( substr_count( $landing, '<div' ), substr_count( $landing, '</div>' ), 'Its cards are closed' );

		$html = $this->render_readiness();
		$this->assertDoesNotMatchRegularExpression( '/wc-agentic-commerce-run"[^>]*disabled/', $html );
		$this->assertStringContainsString( 'Run a check to see what AI assistants get', $html, 'No results before the first scan' );
		$this->assertStringContainsString( '<p>Not checked yet</p>', $html );
		$this->assertStringNotContainsString( 'Can AI shopping apps list your products?', $html );

		wc_get_container()->get( ScheduledScan::class )->queue();
		$html = $this->render_readiness();
		$this->assertStringContainsString( 'Checking your store in the background.', $html );
		$this->assertMatchesRegularExpression( '/wc-agentic-commerce-run"[^>]*disabled/', $html );

		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		$this->age_finished_actions();
		as_schedule_single_action( time() - ScheduledScan::STALLED_AFTER - 60, ScheduledScan::RUN_NOW_HOOK, array(), ScheduledScan::GROUP );
		$html = $this->render_readiness();
		$this->assertStringContainsString( 'The check has not started because scheduled actions', $html );
		$this->assertMatchesRegularExpression( '/wc-agentic-commerce-run"[^>]*disabled/', $html );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
	}

	/**
	 * Date every finished action an hour back, so only what the test does next
	 * counts as recent queue activity.
	 */
	private function age_finished_actions(): void {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}actionscheduler_actions SET last_attempt_gmt = %s WHERE status = 'complete'", gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) ) );
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
	 * @testdox The switch stays disabled until the required checks pass.
	 */
	public function test_switch_disabled_until_required_checks_pass(): void {
		update_option( 'woocommerce_coming_soon', 'yes' );

		$blocked = $this->render_page();
		$this->assertMatchesRegularExpression( '/<button[^>]*wc-agentic-commerce-ucp-switch[^>]*disabled=([\'"])disabled\\1/', $blocked );

		StoreConditionsTest::make_store_ready( $this );
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
	 * Render WooCommerce > Readiness check.
	 */
	private function render_readiness(): string {
		// Localized data accumulates on a registered handle, so start each render clean.
		wp_deregister_script( 'wc-agentic-commerce-settings' );
		ob_start();
		wc_get_container()->get( ReadinessCheckPage::class )->output();
		return self::plain_apostrophes( (string) ob_get_clean() );
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
	 * Render Readiness check with one failing scanned check, which has no affects line of its own.
	 *
	 * @param string $id Check id.
	 */
	private function render_readiness_with_check( string $id ): string {
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
		return $this->render_readiness();
	}
}
