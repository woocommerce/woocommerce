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
use Automattic\WooCommerce\Internal\AgentReadiness\RemoteRules;
use Automattic\WooCommerce\Internal\AgentReadiness\ScheduledScan;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreConditions;
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
		if ( is_multisite() ) {
			// On multisite only a super admin can choose the privacy policy page, as a single site's administrator can.
			grant_super_admin( get_current_user_id() );
		}
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
			foreach ( array_reverse( $this->restore ) as $item ) {
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
			$this->assertSame( array( 'AI sales readiness', 'manage_woocommerce', 'wc-admin&path=/sales-readiness' ), array_slice( end( $GLOBALS['submenu']['woocommerce'] ), 0, 3 ) );
		} finally {
			foreach ( $saved as $name => $value ) {
				$GLOBALS[ $name ] = $value; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring what the test changed.
			}
		}
		$this->assertSame( admin_url( 'admin.php?page=wc-admin&path=/sales-readiness' ), ReadinessCheckPage::url() );
	}

	/**
	 * @testdox Until the store is connected, the connection card posts the nonce-checked connect action.
	 */
	public function test_connect_view_posts_the_connect_action(): void {
		remove_all_actions( 'admin_post_' . AgenticCommerceSettings::CONNECT_ACTION );
		$this->sut->handle_init();
		$this->assertNotFalse( has_action( 'admin_post_' . AgenticCommerceSettings::CONNECT_ACTION ), 'The tab registers the connect action' );

		$view = AgenticCommerceSettings::connect_view();
		$this->assertFalse( $view['connected'], 'The test site is not connected to WordPress.com' );
		$this->assertSame( '', $view['notice'] );
		$this->assertSame( admin_url( 'admin-post.php' ), $view['url'] );
		$this->assertSame( AgenticCommerceSettings::CONNECT_ACTION, $view['action'] );
		$this->assertStringNotContainsString( '_wpnonce', $view['url'] );
		$this->assertSame( 1, wp_verify_nonce( $view['nonce'], AgenticCommerceSettings::CONNECT_ACTION ) );

		$processor = new \WP_HTML_Tag_Processor( $this->render_page( '' ) );
		$this->assertTrue(
			$processor->next_tag(
				array(
					'tag_name'   => 'div',
					'class_name' => 'wc-agent-readiness-overview',
				)
			),
			'The Overview gets the card data'
		);
		$connect = json_decode( (string) $processor->get_attribute( 'data-connect' ), true );
		$this->assertSame( $view, $connect );
	}

	/**
	 * @testdox Coming back from a failed attempt, or a success flag on an unconnected store, shows the error.
	 *
	 * @testWith ["error", "error"]
	 *           ["connected", "error"]
	 *           ["other", ""]
	 *
	 * @param string $result What the URL says.
	 * @param string $notice The notice the card shows.
	 */
	public function test_connect_view_notice( string $result, string $notice ): void {
		$_GET[ AgenticCommerceSettings::CONNECT_RESULT ] = $result;
		try {
			$this->assertSame( $notice, AgenticCommerceSettings::connect_view()['notice'] );
		} finally {
			unset( $_GET[ AgenticCommerceSettings::CONNECT_RESULT ] );
		}
	}

	/**
	 * @testdox Only users who can manage WooCommerce can start a connection.
	 */
	public function test_connect_needs_manage_woocommerce(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$_REQUEST['_wpnonce'] = wp_create_nonce( AgenticCommerceSettings::CONNECT_ACTION );
		try {
			$this->sut->handle_connect();
			$this->fail( 'The connect action stops users who cannot manage WooCommerce' );
		} catch ( \WPDieException $e ) {
			$this->assertStringContainsString( 'not allowed to connect', $e->getMessage() );
		} finally {
			unset( $_REQUEST['_wpnonce'] );
		}
	}

	/**
	 * @testdox When the site can't register with WordPress.com, connecting returns to the Overview with an error.
	 */
	public function test_connect_redirect_returns_with_an_error_when_registration_fails(): void {
		delete_option( 'jetpack_activation_source' );
		$this->assertSame(
			add_query_arg( AgenticCommerceSettings::CONNECT_RESULT, 'error', AgenticCommerceSettings::url() ),
			AgenticCommerceSettings::connect_redirect()
		);
		$this->assertSame( array( 'woocommerce', 'ai-tools' ), get_option( 'jetpack_activation_source' ) );
	}

	/**
	 * @testdox Only an https WordPress.com URL can be the connection redirect, and its scheme is lowercased.
	 *
	 * @testWith ["https://jetpack.wordpress.com/jetpack.authorize/1/?x=1", "https://jetpack.wordpress.com/jetpack.authorize/1/?x=1"]
	 *           ["HTTPS://jetpack.wordpress.com/path", "https://jetpack.wordpress.com/path"]
	 *           ["https://wordpress.com/jetpack/connect", "https://wordpress.com/jetpack/connect"]
	 *           ["http://jetpack.wordpress.com/path", ""]
	 *           ["https://evil.example/phish", ""]
	 *           ["https://wordpress.com.evil.example/", ""]
	 *           ["https://user@jetpack.wordpress.com/path", ""]
	 *           ["", ""]
	 *
	 * @param string $url      Candidate redirect.
	 * @param string $expected URL that may be followed.
	 */
	public function test_wordpress_com_https_url( string $url, string $expected ): void {
		$this->assertSame( $expected, AgenticCommerceSettings::wordpress_com_https_url( $url ) );
	}

	/**
	 * @testdox An authorization URL that leaves WordPress.com sends the merchant back to the Overview with an error.
	 */
	public function test_connect_redirect_rejects_an_offsite_authorization_url(): void {
		delete_option( 'jetpack_activation_source' );
		$property = new \ReflectionProperty( \Automattic\Jetpack\Connection\Manager::class, 'is_connected' );
		$property->setAccessible( true );
		$property->setValue( null, true );
		add_filter(
			'jetpack_build_authorize_url',
			static function () {
				return 'https://evil.example/phish';
			}
		);
		try {
			$this->assertSame(
				add_query_arg( AgenticCommerceSettings::CONNECT_RESULT, 'error', AgenticCommerceSettings::url() ),
				AgenticCommerceSettings::connect_redirect()
			);
		} finally {
			remove_all_filters( 'jetpack_build_authorize_url' );
			$property->setValue( null, null );
		}
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

		$shopping = $this->shopping();
		$json     = $this->shopping_json();

		$this->assertSame( 'Product counts show after the first AI sales readiness check.', $shopping['visibility']['empty'] );
		$this->assertSame( array(), $shopping['visibility']['counts'] );
		$this->assertStringNotContainsString( 'Sales readiness</a>', $json, 'No Sales readiness row at the end of the checklist' );
		$labels = array_column( $this->policy( $shopping, AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION )['options'], 'label' );
		$this->assertContains( 'Published returns page', $labels );
		$this->assertNotContains( 'Draft returns page', $labels );
		$this->assertSame( 'Choose a page', $labels[0], 'The empty choice comes first' );
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

		$shopping = $this->shopping();
		$json     = $this->shopping_json();
		$counts   = array_column( $shopping['visibility']['counts'], null, 'id' );

		$this->assertSame( '1', $counts[ ProductScan::COUNT_AVAILABLE ]['count'] );
		$stored              = get_option( AgentReadiness::REPORT_OPTION );
		$stored['products']  = ProductScan::PRODUCT_LIMIT;
		$stored['available'] = 8400;
		update_option( AgentReadiness::REPORT_OPTION, $stored );
		$this->assertSame( '8,400', array_column( $this->shopping()['visibility']['counts'], 'count', 'id' )[ ProductScan::COUNT_AVAILABLE ], 'The whole catalog, like the other two, not only the products the scan stopped at' );
		$this->assertSame( '1', $counts[ ProductScan::COUNT_UNSUPPORTED ]['count'] );
		$this->assertMatchesRegularExpression( "#^AI assistants can find these products, but shoppers can't buy them through an assistant yet\\.#", $counts[ ProductScan::COUNT_UNSUPPORTED ]['tip'], 'The tip says what not supported means' );
		$this->assertSame( '', $counts[ ProductScan::COUNT_AVAILABLE ]['tip'], 'Visible needs no tip' );
		$this->assertStringContainsString( 'agent_readiness=' . ProductScan::COUNT_UNSUPPORTED, $counts[ ProductScan::COUNT_UNSUPPORTED ]['link']['href'], 'Each count links to its products' );
		$this->assertSame( 'View products', $counts[ ProductScan::COUNT_UNSUPPORTED ]['link']['text'] );
		$checklist = $shopping['checklist'];
		$this->assertMatchesRegularExpression( '#^\d+ steps? left$#', $checklist['badge']['text'], 'The checklist says how many steps are left' );
		$this->assertMatchesRegularExpression( '#^Finish \d+ required steps? to get your store ready for AI shopping\.$#', $checklist['summary'], 'then counts the required steps first, even with UCP off' );
		$https = $this->step( $shopping, 'https' );
		$this->assertSame( 'Use a secure connection (HTTPS)', $https['title'] );
		$this->assertSame( 'View Site Health', $https['link']['text'], 'The button at the end of the row opens where to fix it, named as in the design' );
		$this->assertStringContainsString( 'site-health.php', $https['link']['href'] );
		$this->assertFalse( $shopping['ucp']['enabled'] );
		$this->assertStringNotContainsString( 'to turn this on', $json );
		$this->assertSame( 'Required', $https['badge']['text'], 'Failing required checks are marked Required' );
		$this->assertSame( 'required', $https['state'] );
		$this->assertStringNotContainsString( '"Check"', $json, 'Failing recommended checks have no badge' );
		$this->assertStringNotContainsString( 'Required.', $json, 'Policy pages are recommended, so no field is marked required' );
		$states = array_column( $checklist['steps'], 'state' );
		$this->assertLessThan( array_search( 'open', $states, true ), max( array_keys( $states, 'required', true ) ), 'Required steps come first' );
		$this->assertSame( 'https', $checklist['steps'][0]['id'], 'HTTPS is listed first, as in the design' );
		$this->assertMatchesRegularExpression( '/(Choose a|Publish your) privacy policy page/', $json, 'An open check says what to do' );
		$this->assertSame( 'Connection URL', $shopping['connection']['title'] );
		$this->assertSame( home_url( '/.well-known/ucp' ), $shopping['connection']['url'] );
		$this->assertSame( 'AI tools', $shopping['parent']['text'], 'AI shopping has a breadcrumb back to the landing page' );
		$this->assertSame( AgenticCommerceSettings::url(), $shopping['parent']['href'] );
		$this->assertSame( 'AI shopping', $shopping['title'] );
		$this->assertStringNotContainsString( 'Security', $json, 'No Security card until a UCP flow signs anything (WOOAI-1620)' );
		$this->assertStringNotContainsString( 'Universal Commerce Protocol (UCP) is off', $json, 'UCP is the switch, not a listed check' );
		$this->assertNotContains( 'ucp_on', array_column( $checklist['steps'], 'id' ) );
	}

	/**
	 * @testdox With every required check passing, open checks are only Recommended and passing ones are Done.
	 */
	public function test_output_on_a_ready_store(): void {
		$this->make_every_step_pass();

		$shopping  = $this->shopping();
		$checklist = $shopping['checklist'];

		$this->assertMatchesRegularExpression( '#^Your store is ready for AI shopping\. (You have \d+ optional steps? left\.|Turn it on to let AI assistants find your products\.)$#', $checklist['summary'] );
		$this->assertNotContains( 'required', array_column( $checklist['steps'], 'state' ) );
		$this->assertMatchesRegularExpression( '#^Show \d+ completed steps?$#', $checklist['show_done'], 'Completed steps are folded' );
		$this->assertSame( 'Hide completed steps', $checklist['hide_done'] );
		$privacy = current( wp_list_filter( $checklist['done'], array( 'id' => 'privacy_page' ) ) );
		$this->assertSame( 'Choose a privacy policy page', $privacy['title'], 'A completed step keeps its title and why it matters, as in the design' );
		$this->assertSame( 'Shoppers can read how you handle their information before they buy.', $privacy['text'] );
		$this->assertStringNotContainsString( 'Privacy policy page is selected', $this->shopping_json() );
		$this->assertNull( $shopping['coming_soon'], 'No coming soon notice on a live store' );
		$this->assertFalse( $shopping['ucp']['blocked'], 'The switch can be turned on' );

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
			'not set up yet'      => array( 'every_step', null, '#^Let shoppers find and buy your products through AI assistants\.$#', null, 'Set up AI shopping', '#^Your store is ready for AI shopping\. Turn it on to let AI assistants find your products\.$#' ),
			'steps left'          => array( 'coming_soon', 'yes', '#^AI assistants can\'t shop your store yet\. \d+ steps? left\.$#', 'Needs attention', 'Continue setup', '#^Finish \d+ required steps? to get your store ready for AI shopping\.$#' ),
			'optional steps left' => array( 'optional_left', 'yes', '#^AI assistants can shop your store\. 1 step left\.$#', 'Active', 'View settings', '#^Your store is ready for AI shopping\. You have 1 optional step left\.$#' ),
			'policy page left'    => array( 'policy_left', 'yes', '#^AI assistants can shop your store\. 1 step left\.$#', 'Active', 'Continue setup', '#^Finish 1 required step to get your store ready for AI shopping\.$#' ),
			'all steps done'      => array( 'every_step', 'yes', '#^AI assistants can shop your store\.$#', 'Active', 'View settings', '#^Your store is ready for AI shopping\.$#' ),
			'turned off'          => array( 'every_step', 'no', '#^AI shopping is off\.$#', null, 'View settings', '#^Your store is ready for AI shopping\. Turn it on to let AI assistants find your products\.$#' ),
			'off, steps left'     => array( 'coming_soon', 'no', '#^AI shopping is off\.$#', 'Needs attention', 'Continue setup', '#^Finish \d+ required steps? to get your store ready for AI shopping\.$#' ),
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
		if ( 'policy_left' === $store ) {
			delete_option( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION );
		}

		$page = $this->shopping( 'yes' === $ucp );
		if ( 'no' === $ucp ) {
			update_option( 'woocommerce_feature_ucp_enabled', 'no' );
		}
		$shopping = $this->view()['overview']['shopping'];

		$this->assertMatchesRegularExpression( $checklist, $page['checklist']['summary'] );
		$this->assertSame( 'yes' === $ucp, $page['ucp']['enabled'], 'The switch shows the setting' );
		$this->assertSame( 'coming_soon' === $store, null !== $page['coming_soon'], 'Coming soon mode has its notice' );
		$this->assertMatchesRegularExpression( $text, $shopping['text'] );
		$this->assertSame( $badge, $shopping['badge']['text'] ?? null );
		$this->assertSame( $button, $shopping['button']['text'] );
	}

	/**
	 * @testdox A missing policy page is a Required step, but doesn't keep AI shopping from being turned on.
	 */
	public function test_missing_policy_page_is_required_without_blocking(): void {
		$this->make_every_step_pass();
		delete_option( AgenticCommerceSettings::PRIVACY_PAGE_OPTION );

		$shopping = $this->shopping();
		$privacy  = $this->step( $shopping, 'privacy_page' );

		$this->assertSame( 'required', $privacy['state'] );
		$this->assertSame( 'Required', $privacy['badge']['text'] );
		$this->assertSame( 'Choose a privacy policy page', $privacy['title'] );
		$this->assertSame( 'Choose page', $privacy['link']['text'] );
		$this->assertSame( 'Choose a privacy policy page', $privacy['link']['sr'], 'Screen readers hear which policy' );
		$this->assertStringEndsWith( '#' . AgenticCommerceSettings::PRIVACY_PAGE_OPTION, $privacy['link']['href'], 'Opens the page on its field' );
		$this->assertStringStartsWith( 'Finish 1 required step', $shopping['checklist']['summary'] );
		$this->assertSame( array(), wc_get_container()->get( StoreConditions::class )->failing_required(), 'It doesn\'t block the switch' );
		$this->assertFalse( $shopping['ucp']['blocked'] );
	}

	/**
	 * @testdox In coming soon mode the switch card says so and links to Site visibility.
	 */
	public function test_output_coming_soon_notice_in_switch_card(): void {
		update_option( 'woocommerce_coming_soon', 'yes' );

		$shopping = $this->shopping();

		$this->assertSame( 'Your store is in coming soon mode, so AI assistants can’t shop it yet. Make your store live to let them in.', $shopping['coming_soon']['text'] );
		$this->assertSame( 'Change store visibility', $shopping['coming_soon']['link']['text'], 'As in the content review' );
		$this->assertStringContainsString( 'site-visibility', $shopping['coming_soon']['link']['href'] );
		$this->assertTrue( $shopping['ucp']['blocked'], 'A store that is not live can\'t turn AI shopping on' );
		$this->assertSame( 'Change store visibility', $this->step( $shopping, 'launched' )['link']['text'] );
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

		$shopping = $this->shopping();
		$refund   = $this->step( $shopping, 'refund_returns_page' );

		$this->assertSame( 'Publish your refund and returns policy page', $refund['title'] );
		$this->assertSame( array( 'A refund policy needs to be available for AI assistants to share with shoppers.' ), $refund['lines'], 'The refund page says why it matters, as in the design' );
		$this->assertStringContainsString( 'post=' . $draft . '&action=edit', $refund['link']['href'], 'Edit page opens the page editor' );
		$this->assertSame( 'Edit page', $refund['link']['text'] );
		$this->assertSame( 'Edit your refund and returns policy page', $refund['link']['sr'], 'screen readers also hear which policy' );
		$field = $this->policy( $shopping, AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION );
		$this->assertSame( (string) $draft, $field['value'], 'The draft stays chosen, so saving another field keeps it' );
		$this->assertMatchesRegularExpression( '#\(Draft\)$#', current( wp_list_filter( $field['options'], array( 'value' => (string) $draft ) ) )['label'] );

		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(),
			)
		);
		$view = $this->view();
		$this->assertSame( array( 'refund_returns_page' => 'Required for AI shopping' ), array_column( $view['fix_first']['items'], 'tier', 'id' ), 'A policy page is Required, so it leads Fix first' );
		$this->assertStringContainsString( 'Publish your refund and returns policy page', (string) wp_json_encode( array_column( $view['sections'], null, 'id' )['buy'] ), 'A setup step also shows in the card of the question it answers' );
		$copy = $this->step_copy( 'refund_returns_page' );
		$this->assertStringContainsString( 'post=' . $draft . '&action=edit', $copy['url'], 'Readiness check links the step to the page editor' );
		$this->assertSame( 'Edit refund policy', $copy['link'], 'named for the policy' );
		$this->assertSame( 'A refund policy needs to be available for AI assistants to share with shoppers.', $copy['text'], 'The refund draft keeps its own line' );
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
			AgenticCommerceSettings::PRIVACY_PAGE_OPTION => array( 'Publish your privacy policy page', 'Edit privacy policy', 'privacy_page', 'A privacy policy needs to be published for AI assistants to share it with shoppers.' ),
			AgenticCommerceSettings::TERMS_PAGE_OPTION   => array( 'Publish your terms and conditions page', 'Edit terms and conditions', 'terms_page', 'Terms and conditions need to be published for AI assistants to share them with shoppers.' ),
			AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION => array( 'Publish your shipping policy page', 'Edit shipping policy', 'shipping_policy_page', 'A shipping policy needs to be published for AI assistants to share it with shoppers.' ),
		);
		foreach ( $pages as $option => $copy ) {
			$draft = self::factory()->post->create(
				array(
					'post_type'   => 'page',
					'post_status' => 'draft',
				)
			);
			update_option( $option, $draft );
			$step = $this->step_copy( $copy[2] );
			$this->assertSame( $copy[0], $step['title'] );
			$this->assertStringContainsString( 'post=' . $draft . '&action=edit', $step['url'], $copy[1] );
			$this->assertSame( $copy[1], $step['link'] );
			$this->assertSame( $copy[3], $step['text'], $copy[0] . ' says why it needs publishing, as in the design' );
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
						'id'      => 'robots_txt',
						'group'   => 'find',
						'ok'      => true,
						'level'   => 'recommended',
						'name'    => 'Robots file allows AI assistants',
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
	 * @testdox A check that couldn't run says why on its row; a card none of whose checks ran says why once, for coming soon mode or a host that blocks the store's own pages.
	 */
	public function test_output_couldnt_check_message_follows_coming_soon(): void {
		StoreConditionsTest::make_store_ready( $this );
		$sitemap = array(
			'id'      => 'sitemap_xml',
			'group'   => 'find',
			'ok'      => null,
			'level'   => 'recommended',
			'name'    => 'Sitemap lists your products',
			'fix_url' => '',
		);
		$robots  = array(
			'id'      => 'robots_txt',
			'group'   => 'find',
			'ok'      => true,
			'level'   => 'recommended',
			'name'    => 'Robots file allows AI assistants',
			'fix_url' => '',
		);
		update_option( AgentReadiness::REPORT_OPTION, array( 'store_checks' => array( $sitemap, $robots ) ), false );
		$this->assertSame( 'This couldn\'t be checked. The next check tries again.', $this->row( 'Sitemap lists your products' )['text'] );

		update_option( AgentReadiness::REPORT_OPTION, array( 'store_checks' => array( $sitemap, array( 'ok' => null ) + $robots ) ), false );
		$card = array_column( $this->view()['sections'], null, 'id' )['find'];
		$this->assertSame( 'Couldn’t check', $card['badge']['text'] );
		$this->assertSame( array( 'We couldn’t check your pages' ), array_column( $card['rows'], 'title' ), 'One row for the card, not one per check' );
		$this->assertSame( 'Your hosting setup blocked the check from opening them. Try again later, or contact your hosting provider.', $card['rows'][0]['text'] );

		update_option( 'woocommerce_coming_soon', 'yes' );
		$card = array_column( $this->view()['sections'], null, 'id' )['find'];
		$this->assertSame( 'Your store is in coming soon mode', $card['rows'][0]['title'] );
		$this->assertSame( 'These checks run once your store is live.', $card['rows'][0]['text'] );
	}

	/**
	 * @testdox With UCP already on and a required check failing, the switch stays on and the checklist asks for the required steps, without saying assistants can shop.
	 */
	public function test_output_switch_on_with_failing_checks(): void {
		remove_all_filters( 'pre_option_home' );
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'home', 'http://example.org' );
		$page = $this->shopping( true );

		$this->assertTrue( $page['ucp']['enabled'] );
		$this->assertFalse( $page['ucp']['blocked'], 'It can always be turned off' );
		$this->assertSame( '', $page['ucp']['message'], 'It doesn\'t say AI assistants can shop' );
		$this->assertMatchesRegularExpression( '#^Finish \d+ required steps? to get your store ready for AI shopping\.$#', $page['checklist']['summary'] );

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
		$this->assertSame( 'Continue setup', $shopping['button']['text'], 'and Continue setup opens AI shopping while a required step is left' );
		$this->assertStringContainsString( 'section=shopping', $shopping['button']['href'] );

		$this->make_every_step_pass();
		$this->assertSame( 'AI shopping is on. AI assistants can now find and shop your store.', $this->shopping( true )['ucp']['message'], 'Once nothing required fails, it does' );
	}

	/**
	 * @testdox Sales readiness badges each merchant question, marks a likely conflict with the page but names no plugin, hides checks whose prerequisite failed, and folds passed checks away.
	 */
	public function test_output_groups_summary_and_conflict(): void {
		$this->make_every_step_pass();
		$mug = WC_Helper_Product::create_simple_product();
		$mug->set_name( 'Blue mug' );
		$mug->save();
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
							'product' => $mug->get_id(),
							// A report stored before plugin names were dropped.
							'plugins' => array( 'Yoast SEO', 'Rank Math SEO' ),
						)
					),
					$row( 'product_offer_jsonld', null ),
					$row( 'product_shipping_schema', null ),
					$row( 'product_buy_action_schema', null ),
					$row( 'sitemap_lists_products', true ),
				),
			)
		);

		$view     = $this->view();
		$sections = array_column( $view['sections'], null, 'id' );
		$json     = wp_json_encode( $view );

		$this->assertSame(
			array( 'Store access', 'Search and discovery', 'Product details', 'Listings in AI shopping apps', 'Buying through AI assistants' ),
			array_column( $view['sections'], 'title' ),
			'Listings come before Buying through AI assistants'
		);
		$this->assertSame( 'Some checks only run when AI shopping is on, like whether AI assistants can connect to your store and send shoppers to your checkout.', $sections['access']['note'], 'UCP is off in this test' );
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
		$this->assertSame( 'Let AI assistants read your product details', $conflict['title'], 'A failing check is headed by what to do' );
		$this->assertStringStartsWith( 'On the Blue mug page, the name, price, and stock aren’t in the page code', $conflict['text'], 'then why it matters and what the check found, naming the product from the stored report' );
		$this->assertSame( 'A theme or plugin may have turned this off. Check their settings for WooCommerce structured data.', $conflict['fix'], 'and the fix, since it has one' );
		$this->assertNull( $conflict['badge'], 'A likely conflict has no badge; the page it was seen on says where' );
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
		$this->assertStringNotContainsString( '"id":"product_shipping_schema"', $json, 'The shipping check needs product details too' );
		$this->assertStringNotContainsString( '"id":"product_buy_action_schema"', $json, 'and so does the buy action check' );
		$this->assertStringNotContainsString( 'sitemap_lists_products label', $json, 'A passed check keeps its passing title' );
		$this->assertSame( array(), $sections['find']['rows'], 'Nothing open under find' );
		$passed = array_column( $sections['find']['folded'], null, 'title' )['A sitemap lists your products'] ?? null;
		$this->assertNotNull( $passed, 'Passed checks are folded, under their passing title' );
		$this->assertSame( 'Search engines and AI assistants use it to find new and updated products.', $passed['text'], 'with why it matters' );
		$this->assertStringNotContainsString( 'sitemap_lists_products why', $json, 'A passed scanned check drops the report\'s failure-worded why' );
		$emails = array_column( $sections['buy']['folded'], null, 'id' )['order_emails'] ?? null;
		$this->assertNotNull( $emails, 'A passed setup step is folded in its question\'s card' );
		$this->assertSame( 'Turn on order confirmation emails', $emails['title'], 'under its step title, as in the AI shopping checklist' );
		$this->assertSame( 'An email lets customers know their order went through.', $emails['text'] );
		$this->assertStringNotContainsString( 'Order confirmation emails are on', $json, 'not the report name, which was never reviewed' );
		$this->assertStringNotContainsString( 'Shoppers who buy through an agent get no confirmation from your store.', $json, 'A passed setup step drops its failure-worded why' );
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
		$this->assertSame( 'Optional files that some AI assistants look for. You don’t need to fix anything. Found 1 of 3.', $view['declared']['text'] );
		$this->assertSame( 'Optional', $view['declared']['badge']['text'] );
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

		$report             = get_option( 'woocommerce_agent_readiness_report' );
		$report['declared'] = array(
			array(
				'id'    => 'llms-txt-woocommerce-guidance',
				'name'  => 'WooCommerce store guidance',
				'found' => false,
			),
		);
		update_option( 'woocommerce_agent_readiness_report', $report );
		$this->assertStringContainsString(
			'WooCommerce caches its llms.txt briefly',
			$this->view()['declared']['text'],
			'Missing WooCommerce guidance notes the short llms.txt cache'
		);
		$this->assertSame(
			array(
				'intent' => 'stable',
				'text'   => 'All passed',
			),
			array_column( $view['sections'], 'badge', 'id' )['find'],
			'Not counted as a result; the card holds only the passing search engine step'
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
	 * @testdox Fix first keeps ranking an issue by its share after its count changes, and names sales only when an issue on the card sold something.
	 */
	public function test_output_fix_first_stale_share_and_no_sales(): void {
		$this->make_every_step_pass();
		$check  = static fn( string $id, int $count, ?float $share, array $extra = array() ): array => $extra + array(
			'id'          => $id,
			'label'       => $id . ' label',
			'why'         => $id . ' why.',
			'fix'         => $id . ' fix.',
			'channels'    => array(
				'chatgpt' => array(
					'field'  => 'field',
					'effect' => ReadinessRule::EFFECT_NOT_LISTABLE,
				),
			),
			'count'       => $count,
			'product_ids' => array(),
			'sales_share' => $share,
		);
		$report = array(
			'generated_at' => gmdate( 'c' ),
			'products'     => 120,
			'store_checks' => array(),
			'channels'     => array( 'chatgpt' => array( 'name' => 'ChatGPT' ) ),
			'checks'       => array(
				$check( 'missing_brand', 30, 0.01 ),
				$check( 'missing_images', 99, null, array( 'ranked_share' => 0.6 ) ),
			),
			'sales'        => array(
				'days'   => 90,
				'orders' => 30,
			),
		);
		update_option( AgentReadiness::REPORT_OPTION, $report );

		$fix_first = $this->view()['fix_first'];
		$this->assertSame( array( 'missing_images', 'missing_brand' ), array_column( $fix_first['items'], 'id' ), 'An issue whose count changed since the check keeps the rank its sales gave it' );
		$this->assertStringNotContainsString( 'of your sales', $fix_first['items'][0]['why'], 'Its share is not shown, since it was measured on other products' );
		$this->assertStringStartsWith( '1% of your sales in the last 90 days.', $fix_first['items'][1]['why'], 'The first share shown says the period' );

		$report['checks'][0]['sales_share'] = 0.0;
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$fix_first = $this->view()['fix_first'];
		$this->assertSame( 'Ranked by how many products and AI apps each issue affects.', $fix_first['ranking'], 'No issue on the card sold anything, so sales did not decide the order' );
		$this->assertStringStartsWith( 'No sales in the last 90 days.', $fix_first['items'][1]['why'] );
	}

	/**
	 * @testdox Without sales, an issue that keeps products out of more AI apps ranks before one with more products in fewer apps.
	 */
	public function test_output_fix_first_weighs_ai_apps_without_sales(): void {
		$this->make_every_step_pass();
		$effect = static fn( string $effect ): array => array(
			'field'  => 'field',
			'effect' => $effect,
		);
		$check  = static fn( string $id, int $count, array $channels ): array => array(
			'id'          => $id,
			'label'       => $id . ' label',
			'why'         => $id . ' why.',
			'fix'         => $id . ' fix.',
			'channels'    => $channels,
			'count'       => $count,
			'product_ids' => array(),
			'sales_share' => null,
		);
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'products'     => 20,
				'store_checks' => array(),
				'channels'     => array(),
				'checks'       => array(
					$check( 'missing_brand', 5, array( 'chatgpt' => $effect( ReadinessRule::EFFECT_NOT_LISTABLE ) ) ),
					$check(
						'missing_images',
						3,
						array(
							'chatgpt' => $effect( ReadinessRule::EFFECT_NOT_LISTABLE ),
							'google'  => $effect( ReadinessRule::EFFECT_NOT_LISTABLE ),
							'copilot' => $effect( ReadinessRule::EFFECT_GAPS ),
						)
					),
				),
				'sales'        => null,
			)
		);

		$this->assertSame( array( 'missing_images', 'missing_brand' ), array_column( $this->view()['fix_first']['items'], 'id' ), '3 products in 3 apps come before 5 products in 1' );
	}

	/**
	 * @testdox Fix first lists the failing required steps, then the top 3 issues: what keeps products off a channel, then what makes them harder to find, each ranked by its share of sales.
	 */
	public function test_output_fix_first_ranks_by_sales(): void {
		$this->make_every_step_pass();
		remove_all_filters( 'pre_option_home' );
		update_option( 'home', 'http://example.org' );
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
			array( 'Use a secure connection (HTTPS)', 'Add a main image to 5 products', 'Add a brand to 2 products', 'Add barcode numbers to 30 products' ),
			array_column( $fix_first['items'], 'title' ),
			'The required step, then 3 issues: the not listable checks by share, then GTIN, a gap, despite its larger share; the title, a smaller gap, is left out'
		);
		$this->assertSame( array( 'Required for AI shopping', 'Needed to list products', 'Needed to list products', 'Helps shoppers find products' ), array_column( $fix_first['items'], 'tier' ) );
		$this->assertSame( array( 'Required', null, null, null ), array_map( static fn( array $item ): ?string => $item['badge']['text'] ?? null, $fix_first['items'] ), 'Only the Required heading has a badge' );
		$this->assertArrayNotHasKey( 'impact', $fix_first['items'][1], 'The share of sales is in the line, so no badge repeats it' );
		$this->assertSame( '18% of your sales in the last 90 days. AI shopping apps need a main image to list a product.', $fix_first['items'][1]['why'], 'The first share says over which period, then why the issue matters' );
		$this->assertSame( '5% of your sales. ChatGPT only lists products that have a brand. In Google and Copilot, a brand makes products easier to find.', $fix_first['items'][2]['why'], 'The next ones give only the share' );
		$this->assertNotEmpty( $fix_first['items'][0]['link']['text'] ?? '', 'A setup step links to where to fix it' );
		$this->assertSame(
			array(
				'href' => '',
				'text' => '5 products',
			),
			$fix_first['items'][1]['link'],
			'A product issue names how many products it affects; no stored failures, so no list to link'
		);

		$report['checks'][1]['sales_share'] = 0.0;
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$json = wp_json_encode( $this->view() );
		$this->assertStringContainsString( 'No sales. ChatGPT only lists products that have a brand.', $json, 'Products that sold nothing in the period say so, not 0%' );
		$this->assertStringNotContainsString( '"0% of', $json );
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
		$this->assertSame( 'Start with the fixes that affect the most products.', $fix_first['ranking'] );
		$this->assertSame( 'Turn on Analytics to see which fixes affect the most sales.', $fix_first['link']['text'] );
		$this->assertStringContainsString( 'tab=advanced&section=features', $fix_first['link']['href'], 'The link opens the Features settings, where Analytics is turned on' );
		$this->assertStringNotContainsString( 'of your sales', wp_json_encode( $fix_first ) );

		$report['sales'] = array(
			'days'   => 90,
			'orders' => 30,
		);
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$this->assertSame( 'Ranked by how many products and AI apps each issue affects.', $this->view()['fix_first']['ranking'], 'Orders with no share to rank by, such as free products or counts changed since the check' );

		$report['sales'] = array(
			'days'   => 90,
			'orders' => 12,
		);
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$this->assertSame( 'Start with the fixes that affect the most products. Sales rankings start once your store has enough sales.', $this->view()['fix_first']['ranking'] );
		$this->assertNull( $this->view()['fix_first']['link'], 'Analytics is on, so there is no link to turn it on' );

		$report['sales'] = array(
			'days'   => 90,
			'orders' => 0,
		);
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$this->assertSame( 'Start with the fixes that affect the most products. Sales rankings start once your store has enough sales.', $this->view()['fix_first']['ranking'], 'No sales reads like too few orders' );

		$report['sales'] = array(
			'days'     => 90,
			'orders'   => 0,
			'imported' => false,
		);
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$this->assertSame( 'Analytics hasn\'t imported all your past orders yet, so issues are ranked by how many products and AI apps they affect. Import them in Analytics > Settings.', $this->view()['fix_first']['ranking'], 'Missing orders don\'t read as no sales' );

		unset( $report['sales'] );
		update_option( AgentReadiness::REPORT_OPTION, $report );
		$this->assertSame( 'Start with the fixes that affect the most products. Check again to rank them by sales.', $this->view()['fix_first']['ranking'], 'A report from before sales were read doesn\'t say Analytics is off' );
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
				array( 'Needed to list products', 'other_listing_check label' ),
				array( 'Needed to list products', 'Let shoppers open store pages without logging in' ),
			),
			array_map( static fn( array $item ): array => array( $item['tier'], $item['title'] ), $items ),
			'Store pages that need a login keep products from being listed, but a members-only store chose that, so it isn\'t Required; a check without copy keeps the report\'s title'
		);

		update_option(
			RemoteRules::OPTION,
			array(
				'version'      => '2026-11-15',
				'checks'       => array(),
				'store_checks' => array(
					'pages_public'        => array( 'required' => false ),
					'other_listing_check' => array( 'required' => true ),
				),
			),
			false
		);
		$this->assertSame(
			array( 'other_listing_check', 'pages_public' ),
			array_column( $this->view()['fix_first']['items'], 'id' ),
			'The rules file sets which output checks are Required, as for setup steps'
		);
		$this->assertSame( array( 'Required for AI shopping', 'Needed to list products' ), array_column( $this->view()['fix_first']['items'], 'tier' ) );
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
	 * @testdox A store whose only product is grouped is not told it has no products.
	 */
	public function test_output_grouped_product_is_not_an_empty_store(): void {
		$grouped = new \WC_Product_Grouped();
		$grouped->set_name( 'Grouped only' );
		$grouped->save();

		$report = wc_get_container()->get( AgentReadiness::class )->get_report();
		$this->assertSame( 0, $report['products'], 'A grouped product is not one the scan checks' );
		$this->assertGreaterThan( 0, $report['unsupported_types'] );
		update_option( AgentReadiness::REPORT_OPTION, $report, false );

		$this->assert_products_are_unreadable();

		$report['unsupported_types']   = 0;
		$report['hidden_from_catalog'] = 1;
		update_option( AgentReadiness::REPORT_OPTION, $report, false );
		$this->assert_products_are_unreadable();
	}

	/**
	 * The product cards when the scan checked nothing because every product is hidden or unsupported.
	 */
	private function assert_products_are_unreadable(): void {
		$sections = array_column( $this->view()['sections'], null, 'id' );
		foreach ( array( 'products', 'understand' ) as $id ) {
			$this->assertSame( 'Not checked', $sections[ $id ]['badge']['text'], $id );
			$this->assertSame( 'None of your products can be checked yet', $sections[ $id ]['empty']['title'] );
			$this->assertSame( 'View product visibility', $sections[ $id ]['empty']['link']['text'] );
			$this->assertStringContainsString( 'section=' . AgenticCommerceSettings::SHOPPING_SECTION, $sections[ $id ]['empty']['link']['href'] );
			$this->assertStringContainsString( '#wc-agentic-commerce-visibility', $sections[ $id ]['empty']['link']['href'] );
			$this->assertStringNotContainsString( 'post-new.php', $sections[ $id ]['empty']['link']['href'] );
		}
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
		$this->assertSame( array( 'product_shipping_schema', 'pages_public' ), array_column( $view['fix_first']['items'], 'id' ) );
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
	 * @testdox With nothing to fix, there is no Fix first card, as in the design.
	 */
	public function test_output_fix_first_nothing_to_fix(): void {
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
		$this->assertSame( 'View product', $brand['link']['text'], 'One product, as in the design' );
		$this->assertStringContainsString( 'agent_readiness=', $brand['link']['href'], 'The link opens the Products list filtered to the failing products' );
		$this->assertStringNotContainsString( 'main image has no alt text', $json, 'Store readiness covers alt text' );
		$this->assertStringNotContainsString( 'post=' . $product->get_id(), $json, 'No example products' );
		$this->assertSame( 'Let search engines index your store', $this->step( $this->shopping(), 'search_visible' )['title'], 'Hidden from search engines is a setup step' );
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
	 * @testdox AI tools opens on the Overview and AI shopping, React roots with their data; the checks themselves are on Sales readiness.
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
		$this->assertSame( 'Set up AI shopping', json_decode( $this->data_view( $html, 'wc-agent-readiness-overview' ), true )['overview']['shopping']['button']['text'], 'The view comes with the page, so the cards draw without a request' );
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
			$badges['access'],
			'With AI shopping off, the card holds only the passing setup steps'
		);
		$this->assertSame( '1 issue', $badges['products']['text'] );
		$this->assertNull( $view['declared'], 'No card when the report lists no declared items' );

		$html = $this->render_page();
		$this->assertStringContainsString( '<div id="wc-ai-shopping"', $html, 'AI shopping is React too, mounted by the settings-embed script' );
		$this->assertTrue( $this->save_hidden, 'The form keeps only its nonce: Save is in the React page header' );
		$this->assertStringNotContainsString( 'Sales readiness</a>', $html, 'The checklist no longer ends with a Sales readiness row' );
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
	 * @testdox Guest checkout off is an Optional setup step, not Required in Fix first, including when this WooCommerce serves UCP checkout.
	 */
	public function test_guest_checkout_stays_optional(): void {
		$this->make_every_step_pass();
		update_option( 'woocommerce_enable_guest_checkout', 'no' );
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(),
			),
			false
		);

		$checks = array_column( wc_get_container()->get( StoreConditions::class )->all(), null, 'id' );
		$items  = array_column( $this->view()['fix_first']['items'] ?? array(), 'tier', 'id' );
		$this->assertFalse( StoreConditions::shows_required( $checks['guest_checkout'] ), 'Guest checkout stays Optional with AI shopping on' );
		$this->assertArrayNotHasKey( 'guest_checkout', $items, 'No heading says Required for an Optional step' );
		$this->assertSame( array(), wc_get_container()->get( StoreConditions::class )->failing_required(), 'It doesn\'t keep AI shopping from being turned on' );
		$guest = $this->step( $this->shopping(), 'guest_checkout' );
		$this->assertSame( 'Allow guest checkout', $guest['title'] );
		$this->assertSame( 'View account settings', $guest['link']['text'], 'The step\'s button is named as in the design' );
		$this->assertStringContainsString( 'tab=account', $guest['link']['href'] );
		$this->assertSame( 'open', $guest['state'] );
	}

	/**
	 * @testdox "Discourage search engines", a missing payment method and order emails that are off are Required in Fix first and the Setup checklist, but don't keep AI shopping from being turned on.
	 */
	public function test_search_and_payments_are_shown_required_without_blocking(): void {
		$this->make_every_step_pass();
		update_option( 'blog_public', '0' );
		$emails = array_intersect_key( WC()->mailer()->get_emails(), array_flip( array( 'WC_Email_Customer_Processing_Order', 'WC_Email_Customer_Completed_Order' ) ) );
		foreach ( $emails as $email ) {
			$this->restore[] = array( $email, $email->enabled );
			$email->enabled  = 'no';
		}
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			$this->restore[]  = array( $gateway, $gateway->enabled );
			$gateway->enabled = 'no';
		}
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(),
			),
			false
		);

		$items = array_column( $this->view()['fix_first']['items'], null, 'id' );
		$this->assertSame( 'Required for AI shopping', $items['search_visible']['tier'] );
		$this->assertSame( 'Required for AI shopping', $items['payment_method']['tier'] );
		$this->assertSame( 'Required for AI shopping', $items['order_emails']['tier'] );
		$this->assertSame( array(), wc_get_container()->get( StoreConditions::class )->failing_required(), 'None blocks the switch' );

		update_option( 'woocommerce_enable_guest_checkout', 'no' );
		$shopping = $this->shopping();
		$this->assertStringStartsWith( 'Finish 3 required steps', $shopping['checklist']['summary'] );
		$this->assertSame( array( 'search_visible', 'payment_method', 'order_emails', 'guest_checkout' ), array_column( $shopping['checklist']['steps'], 'id' ), 'They come before the optional guest checkout step' );
		$this->assertSame( array( 'required', 'required', 'required', 'open' ), array_column( $shopping['checklist']['steps'], 'state' ), 'All three stay Required (WOOAI-1681)' );
		foreach ( array(
			'search_visible' => array( 'Let search engines index your store', 'Change Reading settings' ),
			'payment_method' => array( 'Set up payments', 'View payment settings' ),
			'order_emails'   => array( 'Turn on order confirmation emails', 'View email settings' ),
		) as $id => $copy ) {
			$step = $this->step( $shopping, $id );
			$this->assertSame( $copy[0], $step['title'] );
			$this->assertSame( $copy[1], $step['link']['text'], $copy[0] . ': the button says where it goes, as in the design' );
		}
		$this->assertFalse( $shopping['ucp']['blocked'], 'None keeps the switch from being turned on' );
	}

	/**
	 * @testdox Order confirmation emails that are off are Required in Fix first, the Setup checklist and the buying card, ahead of every sale-ranked issue, with AI shopping on or off and with or without sales, and never keep AI shopping from being turned on.
	 *
	 * @dataProvider ai_shopping_and_sales
	 *
	 * @param bool $ucp_on     Whether AI shopping is on.
	 * @param bool $with_sales Whether the report has sales to rank by.
	 */
	public function test_order_emails_are_shown_required_without_blocking( bool $ucp_on, bool $with_sales ): void {
		$this->make_every_step_pass();
		$this->turn_off_order_emails();
		update_option( AgentReadiness::REPORT_OPTION, $this->report_with_listing_issue( $with_sales ), false );
		update_option( 'woocommerce_feature_ucp_enabled', $ucp_on ? 'yes' : 'no' );

		$view  = $this->view();
		$items = $view['fix_first']['items'];
		$this->assertSame( array( 'order_emails', 'missing_images' ), array_column( $items, 'id' ), 'The Required step comes before an issue with 90% of sales' );
		$this->assertSame( 'Required for AI shopping', $items[0]['tier'] );
		$this->assertSame( 'Required', $items[0]['badge']['text'] ?? null );
		$this->assertSame( 'Turn on order confirmation emails', $items[0]['title'] );
		$rows = array_column( array_column( $view['sections'], null, 'id' )['buy']['rows'], null, 'id' );
		$this->assertSame( 'Required', $rows['order_emails']['badge']['text'] ?? null, 'The buying card marks it Required too' );
		$this->assertSame( array(), wc_get_container()->get( StoreConditions::class )->failing_required(), 'It doesn\'t block the switch' );

		$shopping = $this->shopping( $ucp_on );
		$this->assertStringStartsWith( 'Finish 1 required step to get', $shopping['checklist']['summary'] );
		$this->assertSame( 'Required', $this->step( $shopping, 'order_emails' )['badge']['text'], 'The Setup checklist marks it Required' );
		$this->assertFalse( $shopping['ucp']['blocked'] );

		$this->make_every_step_pass();
		update_option( 'woocommerce_feature_ucp_enabled', $ucp_on ? 'yes' : 'no' );
		$view = $this->view();
		$this->assertSame( array( 'missing_images' ), array_column( $view['fix_first']['items'], 'id' ), 'Once the email is on, it leaves Fix first' );
		$this->assertStringContainsString( 'Turn on order confirmation emails', (string) wp_json_encode( array_column( $view['sections'], null, 'id' )['buy']['folded'] ) );
		$this->assertStringNotContainsString( 'required step', $this->shopping( $ucp_on )['checklist']['summary'] );
	}

	/**
	 * @testdox A failing setup step shows in both places: the Setup checklist and Fix first, and the card of the question it answers, which counts it in its issues; once it passes it folds there.
	 *
	 * @dataProvider ai_shopping_and_sales
	 *
	 * @param bool $ucp_on     Whether AI shopping is on.
	 * @param bool $with_sales Whether the report has sales to rank by.
	 */
	public function test_failing_setup_steps_show_and_count_in_their_question_card( bool $ucp_on, bool $with_sales ): void {
		$this->make_every_step_pass();
		$this->turn_off_order_emails();
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			$this->restore[]  = array( $gateway, $gateway->enabled );
			$gateway->enabled = 'no';
		}
		$scanned = array(
			'id'       => 'product_shipping_schema',
			'ok'       => false,
			'blocks'   => 'discovery',
			'group'    => 'buy',
			'level'    => 'recommended',
			'name'     => 'shipping name',
			'label'    => 'shipping label',
			'why'      => '',
			'fix'      => '',
			'fix_url'  => '',
			'conflict' => false,
		);
		update_option( AgentReadiness::REPORT_OPTION, $this->report_with_listing_issue( $with_sales, array( $scanned ) ), false );
		update_option( 'woocommerce_feature_ucp_enabled', $ucp_on ? 'yes' : 'no' );

		$view = $this->view();
		$buy  = array_column( $view['sections'], null, 'id' )['buy'];
		$rows = array_column( $buy['rows'], null, 'id' );
		$this->assertSame( array( 'payment_method', 'order_emails', 'product_shipping_schema' ), array_keys( $rows ), 'Setup steps sit in the buying card with its scanned check' );
		$this->assertSame( '3 issues', $buy['badge']['text'], 'The card counts the setup steps in its issues' );
		$this->assertSame( 'Set up payments', $rows['payment_method']['title'], 'Worded as in the Setup checklist' );
		$this->assertSame( 'required', $rows['payment_method']['state'] );
		$this->assertSame( array( 'payment_method', 'order_emails', 'missing_images' ), array_column( $view['fix_first']['items'], 'id' ), 'They stay in Fix first too; the store check with no sales link stays out' );
		$this->assertSame( 'Set up payments', $this->step( $this->shopping( $ucp_on ), 'payment_method' )['title'], 'And in the Setup checklist' );

		$this->make_every_step_pass();
		update_option( 'woocommerce_feature_ucp_enabled', $ucp_on ? 'yes' : 'no' );
		$buy = array_column( $this->view()['sections'], null, 'id' )['buy'];
		$this->assertSame( array( 'product_shipping_schema' ), array_column( $buy['rows'], 'id' ) );
		$this->assertSame( '1 issue', $buy['badge']['text'], 'A passed setup step isn\'t an issue' );
		$this->assertContains( 'payment_method', array_column( $buy['folded'], 'id' ), 'It folds with the passed checks' );
	}

	/**
	 * AI shopping on and off, each with and without sales to rank by.
	 *
	 * @return array<string, array{0:bool,1:bool}>
	 */
	public function ai_shopping_and_sales(): array {
		return array(
			'AI shopping on, with sales'     => array( true, true ),
			'AI shopping on, without sales'  => array( true, false ),
			'AI shopping off, with sales'    => array( false, true ),
			'AI shopping off, without sales' => array( false, false ),
		);
	}

	/**
	 * @testdox With AI shopping on, a failing connection URL is Required and in Fix first, and failing catalog search is a Warning that says what came back.
	 */
	public function test_failing_ucp_profile_is_required_and_endpoint_a_warning(): void {
		$this->make_every_step_pass();
		update_option( 'woocommerce_feature_ucp_enabled', 'yes' );
		$row = static fn( string $id ): array => array(
			'id'       => $id,
			'ok'       => false,
			'blocks'   => 'ucp',
			'group'    => 'access',
			'name'     => 'name',
			'label'    => 'label',
			'why'      => 'why',
			'fix'      => 'fix',
			'fix_url'  => '',
			'conflict' => false,
			'pending'  => false,
		);
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array( $row( 'ucp_profile' ), array( 'source' => '403 text/html' ) + $row( 'ucp_endpoints_live' ), $row( 'ucp_profile_version' ) ),
			),
			false
		);

		$view  = $this->view();
		$items = array_column( $view['fix_first']['items'], 'tier', 'id' );
		$this->assertSame( 'Required for AI shopping', $items['ucp_profile'] );
		$this->assertArrayNotHasKey( 'ucp_endpoints_live', $items, 'Catalog search can fail now and then with nothing changed, so it warns instead' );
		$this->assertArrayNotHasKey( 'ucp_profile_version', $items, 'A different version is likely another plugin, not a blocker' );
		$rows = array_column( array_column( $view['sections'], null, 'id' )['access']['rows'], null, 'id' );
		$this->assertSame( 'Required', $rows['ucp_profile']['badge']['text'] );
		$this->assertSame(
			array(
				'intent' => 'medium',
				'text'   => 'Warning',
			),
			$rows['ucp_endpoints_live']['badge']
		);
		$this->assertStringEndsWith( 'The last answer was 403 text/html.', $rows['ucp_endpoints_live']['text'] );
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
		$this->assertSame( '1 issue', array_column( $view['sections'], 'badge', 'id' )['buy']['text'] );
		$row = $this->row( 'Link to your privacy and returns pages from your home page' );
		$this->assertSame( ReadinessCopy::store_check( 'policy_pages_linked' )['fix'], $row['fix'], 'The fix line comes from the copy, not the report' );
		$this->assertSame( 'View images', $row['link']['text'], 'A fix in the media library says where it goes' );
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
		$this->assertSame( 'Check your store', $view['run']['label'], 'and the page offers to run one' );
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
		$this->assertSame( 'Checks can’t start because scheduled actions aren’t running on your site. Your hosting provider can usually turn them back on.', $run['message'] );
		$this->assertSame( 'View Scheduled Actions', $run['link']['text'] );
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
		$shopping = $this->shopping();
		$this->assertSame( 'Choose a terms and conditions page', $this->step( $shopping, 'terms_page' )['title'] );
		$this->assertStringNotContainsString( 'Publish your terms and conditions page', $this->shopping_json() );
		$this->assertSame( (string) $cart, $this->policy( $shopping, AgenticCommerceSettings::TERMS_PAGE_OPTION )['value'], 'Listed and chosen, so saving the tab keeps it' );

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

		$settings = $this->page()->get_settings_for_section( AgenticCommerceSettings::SHOPPING_SECTION );
		WC_Admin_Settings::save_fields( $settings, array( AgenticCommerceSettings::PRIVACY_PAGE_OPTION => (string) $published ) );

		$this->assertContains( AgenticCommerceSettings::TERMS_PAGE_OPTION, array_column( $settings, 'id' ), 'The section under test has its fields' );
		$this->assertNotContains( AgenticCommerceSettings::PRIVACY_PAGE_OPTION, array_column( $settings, 'id' ) );
		$this->assertSame( $privacy, (int) get_option( AgenticCommerceSettings::PRIVACY_PAGE_OPTION ) );
	}

	/**
	 * @testdox A shop manager with no privacy page sees the failing check and no link to a field they cannot use.
	 */
	public function test_privacy_check_without_manage_privacy_options_has_no_settings_link(): void {
		StoreConditionsTest::make_store_ready();
		delete_option( AgenticCommerceSettings::PRIVACY_PAGE_OPTION );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );

		$shopping = $this->shopping();
		$privacy  = $this->step( $shopping, 'privacy_page' );

		$this->assertSame( array( 'Someone who can manage privacy options needs to select a published privacy policy page under Settings > Privacy.' ), $privacy['lines'] );
		$this->assertNull( $privacy['link'], 'No link to a field they cannot use' );
		$this->assertNotContains( AgenticCommerceSettings::PRIVACY_PAGE_OPTION, array_column( $shopping['policies']['fields'], 'id' ), 'and no privacy field' );
	}

	/**
	 * @testdox A discovery file WooCommerce didn't write shows the removal instruction, not a permalinks link.
	 */
	public function test_ucp_file_in_the_way_shows_the_removal_instruction(): void {
		StoreConditionsTest::make_store_ready();
		$path = get_temp_dir() . uniqid( 'wc-ucp-' ) . '/.well-known/ucp';
		wp_mkdir_p( dirname( $path ) );
		file_put_contents( $path, '{}' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		add_filter( 'woocommerce_ucp_discovery_file_path', fn() => $path );

		try {
			$step = $this->step( $this->shopping(), 'ucp_discovery' );
			$this->assertStringContainsString( 'that WooCommerce didn’t create is loading instead', implode( ' ', $step['lines'] ) );
			$this->assertStringContainsString( $path, implode( ' ', $step['lines'] ) );
			$this->assertNull( $step['link'], 'No permalinks link' );
		} finally {
			wp_delete_file( $path );
			foreach ( array( dirname( $path ), dirname( $path, 2 ) ) as $dir ) {
				if ( is_dir( $dir ) ) {
					rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
				}
			}
		}
	}

	/**
	 * @testdox When the discovery file can't be written, the status row says to change permalinks or make the folder writable.
	 */
	public function test_ucp_no_file_shows_permalink_and_permission_fixes(): void {
		global $wp_rewrite;
		StoreConditionsTest::make_store_ready();
		$wp_rewrite->set_permalink_structure( '' );
		$locked = get_temp_dir() . uniqid( 'wc-ucp-locked-' );
		mkdir( $locked ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
		chmod( $locked, 0555 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
		add_filter( 'woocommerce_ucp_discovery_file_path', fn() => $locked . '/ucp' );

		try {
			if ( is_writable( $locked ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
				$this->markTestSkipped( 'The test runs as a user who can write any directory (root).' );
			}
			$step  = $this->step( $this->shopping(), 'ucp_discovery' );
			$lines = implode( ' ', $step['lines'] );
			$this->assertSame( 'Change permalinks', $step['link']['text'] );
			$this->assertStringContainsString( 'options-permalink.php', $step['link']['href'] );
			// On multisite one file can't serve every site, so the fix names only the permalink setting.
			if ( is_multisite() ) {
				$this->assertStringNotContainsString( 'writable', $lines );
			} else {
				$this->assertStringContainsString( 'writable', $lines );
			}
			$this->assertStringContainsString( 'index.php', $lines );
		} finally {
			chmod( $locked, 0755 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
			rmdir( $locked ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		}
	}

	/**
	 * @testdox The switch stays disabled until the required checks pass.
	 */
	public function test_switch_disabled_until_required_checks_pass(): void {
		update_option( 'woocommerce_coming_soon', 'yes' );

		$this->assertTrue( $this->shopping()['ucp']['blocked'] );

		StoreConditionsTest::make_store_ready();
		$this->assertFalse( $this->shopping()['ucp']['blocked'] );
	}

	/**
	 * @testdox A connection URL blocked by a file names that file, and does not offer permalinks.
	 */
	public function test_connection_url_file_step_names_the_file(): void {
		$step = $this->setup_step_copy(
			array(
				'id'      => 'ucp_discovery',
				'label'   => 'Fix your connection URL',
				'why'     => 'AI assistants use your connection URL to find your products, and it doesn\'t load your store\'s details.',
				'fix'     => 'Remove the /tmp/ucp file that WooCommerce didn\'t write. It is being served instead of your store profile.',
				'fix_url' => '',
			)
		);

		$this->assertSame( 'Fix your connection URL', $step['title'] );
		$this->assertStringContainsString( 'didn\'t write', $step['fix'] );
		$this->assertSame( '', $step['url'] );
		$this->assertSame( '', $step['link'] );
	}

	/**
	 * @testdox A connection URL with nothing serving it offers Change permalinks and says why.
	 */
	public function test_connection_url_permalink_step_offers_permalinks(): void {
		$step = $this->setup_step_copy(
			array(
				'id'      => 'ucp_discovery',
				'label'   => 'Use a readable permalink structure',
				'why'     => 'Any structure except Plain lets AI assistants reach your store.',
				'fix'     => 'Choose any structure except Plain under Settings > Permalinks. Structures that start with index.php don\'t work either. Or make /tmp writable by the web server so WooCommerce can write the file.',
				'fix_url' => admin_url( 'options-permalink.php' ),
			)
		);

		$this->assertSame( 'Use a readable permalink structure', $step['title'] );
		$this->assertSame( 'Change permalinks', $step['link'] );
		$this->assertStringContainsString( 'options-permalink.php', $step['url'] );
		$this->assertStringContainsString( 'writable', $step['fix'] );
		$this->assertStringContainsString( 'index.php', $step['fix'] );
	}

	/**
	 * What Sales readiness says about a failing setup step, as Fix first would show it.
	 *
	 * @param string $id Setup step id.
	 * @return array<string, string>
	 */
	private function step_copy( string $id ): array {
		$step = current( wp_list_filter( wc_get_container()->get( StoreConditions::class )->all(), array( 'id' => $id ) ) );
		$this->assertIsArray( $step, 'No setup step with id ' . $id );
		return $this->setup_step_copy( $step );
	}

	/**
	 * What Sales readiness says about one setup step.
	 *
	 * @param array<string, mixed> $step Store condition.
	 * @return array<string, string>
	 */
	private function setup_step_copy( array $step ): array {
		$failing = new \ReflectionMethod( ReadinessCheckPage::class, 'failing_copy' );
		$failing->setAccessible( true );
		return $failing->invoke( null, $step );
	}

	/**
	 * Turn off the Processing and Completed order emails, so the order_emails step fails.
	 */
	private function turn_off_order_emails(): void {
		$emails = array_intersect_key( WC()->mailer()->get_emails(), array_flip( array( 'WC_Email_Customer_Processing_Order', 'WC_Email_Customer_Completed_Order' ) ) );
		foreach ( $emails as $email ) {
			$this->restore[] = array( $email, $email->enabled );
			$email->enabled  = 'no';
		}
	}

	/**
	 * A report with one product issue that keeps 30 products from being listed, with 90% of sales on
	 * them when the store has sales to rank by, and Analytics off otherwise.
	 *
	 * @param bool                            $with_sales   Whether the report has sales to rank by.
	 * @param array<int, array<string,mixed>> $store_checks Scanned store checks.
	 * @return array<string, mixed>
	 */
	private function report_with_listing_issue( bool $with_sales, array $store_checks = array() ): array {
		return array(
			'generated_at' => gmdate( 'c' ),
			'products'     => 40,
			'store_checks' => $store_checks,
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
					'sales_share' => $with_sales ? 0.9 : null,
				),
			),
			'sales'        => $with_sales ? array(
				'days'     => 90,
				'orders'   => 30,
				'imported' => true,
			) : null,
		);
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
	 * @testdox A page title is shown with its entities decoded, and an entity in a check label stays inside the JSON.
	 */
	public function test_page_title_with_entities_stays_inside_the_view(): void {
		$title  = 'Returns &quot;}, &quot;z&quot;: {&quot;a&quot;: &quot; &amp; &lt;b&gt; <i>';
		$id     = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $title,
			)
		);
		$stored = get_post( $id )->post_title;
		$this->assertStringContainsString( '&quot;', $stored, 'The title keeps its entities, as one saved without unfiltered_html can' );

		$view   = $this->shopping();
		$labels = array_merge( ...array_map( static fn( array $field ): array => array_column( $field['options'], 'label' ), $view['policies']['fields'] ) );
		$titles = array_values( array_filter( $labels, static fn( string $label ): bool => str_contains( $label, 'Returns' ) ) );
		$this->assertNotEmpty( $titles, 'The page is listed in the policy dropdowns' );
		foreach ( $titles as $label ) {
			$this->assertSame( 'Returns "}, "z": {"a": " & <b>', $label, 'The dropdown shows the title with its entities decoded' );
		}

		// ReadinessCopy has no wording for this check, so the stored label is what the Overview shows.
		$label = 'Blue &quot;}, &amp; Co <i>';
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'checks'       => array(
					array(
						'id'    => 'entity_in_label',
						'count' => 1,
						'label' => $label,
					),
				),
			),
			false
		);
		$html = $this->render_page( '' );
		$this->assertStringNotContainsString( '&amp;', $html, 'No & from the view is left for the browser to decode' );
		$overview = json_decode( $this->data_view( $html, 'wc-agent-readiness-overview' ), true );
		$this->assertIsArray( $overview, 'The Overview view still reads as JSON' );
		$products = current( array_filter( $overview['sections'], static fn( array $section ): bool => 'products' === $section['id'] ) );
		$this->assertIsArray( $products, 'The Overview lists product checks' );
		$this->assertContains( $label, array_column( $products['rows'], 'title' ), 'A check label with entities stays a label in the Overview' );
	}

	/**
	 * @testdox A stored policy page that no longer exists starts on "Choose a page", as the select did, and no policy field means no pages to list.
	 */
	public function test_policy_field_with_a_deleted_page_starts_on_choose_a_page(): void {
		update_option( AgenticCommerceSettings::FAQ_PAGE_OPTION, 999999 );

		$shopping = $this->shopping();
		$faq      = $this->policy( $shopping, AgenticCommerceSettings::FAQ_PAGE_OPTION );

		$this->assertSame( '', $faq['value'] );
		$this->assertSame( 'FAQ (optional)', $faq['label'] );
		$this->assertSame(
			array( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION, AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION, AgenticCommerceSettings::PRIVACY_PAGE_OPTION, AgenticCommerceSettings::TERMS_PAGE_OPTION, AgenticCommerceSettings::FAQ_PAGE_OPTION, AgenticCommerceSettings::WARRANTY_PAGE_OPTION ),
			array_column( $shopping['policies']['fields'], 'id' ),
			'The fields the settings form saves, in its order'
		);
		$this->assertSame( array( 0 ), array_keys( $faq['options'] ), 'Pages are a list, so their order holds in JavaScript; with no other pages only "Choose a page" is left' );
	}

	/**
	 * @testdox A policy field posts under its field_name when it has one, the key the settings form saves from.
	 */
	public function test_policy_field_posts_under_its_field_name(): void {
		$add = static function ( $settings ) {
			$settings[] = array(
				'id'         => 'example_policy_page_id',
				'field_name' => 'example_policy[page]',
				'title'      => 'Example policy',
				'type'       => 'select',
				'options'    => array( '' => 'Choose a page' ),
			);
			return $settings;
		};
		add_filter( 'woocommerce_get_settings_agentic-commerce', $add );

		try {
			$shopping = $this->shopping();
		} finally {
			remove_filter( 'woocommerce_get_settings_agentic-commerce', $add );
		}

		$this->assertSame( 'example_policy[page]', $this->policy( $shopping, 'example_policy_page_id' )['name'] );
		$this->assertSame( AgenticCommerceSettings::FAQ_PAGE_OPTION, $this->policy( $shopping, AgenticCommerceSettings::FAQ_PAGE_OPTION )['name'], 'Without one, the option id' );
	}

	/**
	 * @testdox A nested select id keeps its stored value after save and reload.
	 */
	public function test_nested_policy_select_survives_save_and_reload(): void {
		global $current_section;
		$page = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		$add  = static function ( $settings ) use ( $page ) {
			$settings[] = array(
				'id'      => 'example_policy[page]',
				'title'   => 'Example policy',
				'type'    => 'select',
				'options' => array(
					''             => 'Choose a page',
					(string) $page => 'Example page',
				),
			);
			return $settings;
		};
		add_filter( 'woocommerce_get_settings_agentic-commerce', $add );
		$_POST['example_policy']                             = array( 'page' => (string) $page );
		$_POST[ AgenticCommerceSettings::TERMS_PAGE_OPTION ] = (string) $page;

		$current_section = AgenticCommerceSettings::SHOPPING_SECTION; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The section WooCommerce sets from the URL; reset below.

		try {
			$this->page()->save();
			$shopping = $this->shopping();
		} finally {
			$current_section = ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reset after the save.
			unset( $_POST['example_policy'], $_POST[ AgenticCommerceSettings::TERMS_PAGE_OPTION ] );
			remove_filter( 'woocommerce_get_settings_agentic-commerce', $add );
		}

		$this->assertSame( array( 'page' => (string) $page ), get_option( 'example_policy' ), 'save_fields writes the nested option' );
		$this->assertSame( (string) $page, $this->policy( $shopping, 'example_policy[page]' )['value'], 'policy_fields reads the nested value back' );
	}

	/**
	 * @testdox Save on AI shopping saves the policy fields it posts and leaves a setting the page doesn't show as it was.
	 */
	public function test_save_keeps_a_filtered_setting_the_page_does_not_show(): void {
		global $current_section;
		$add = static function ( $settings ) {
			$settings[] = array(
				'id'   => 'example_agentic_checkbox',
				'type' => 'checkbox',
			);
			return $settings;
		};
		add_filter( 'woocommerce_get_settings_agentic-commerce', $add );
		update_option( 'example_agentic_checkbox', 'yes' );
		$page = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		$_POST[ AgenticCommerceSettings::TERMS_PAGE_OPTION ] = (string) $page;
		$current_section                                     = AgenticCommerceSettings::SHOPPING_SECTION; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The section WooCommerce sets from the URL; reset below.

		try {
			$this->page()->save();
		} finally {
			$current_section = ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reset after the save.
			unset( $_POST[ AgenticCommerceSettings::TERMS_PAGE_OPTION ] );
			remove_filter( 'woocommerce_get_settings_agentic-commerce', $add );
		}

		$this->assertSame( (string) $page, (string) get_option( AgenticCommerceSettings::TERMS_PAGE_OPTION ), 'The posted policy field is saved' );
		$this->assertSame( 'yes', get_option( 'example_agentic_checkbox' ), 'A setting the page does not post keeps its value' );
	}

	/**
	 * @testdox The switch links to how AI shopping works, and the connection URL to a place to test it.
	 */
	public function test_switch_and_connection_links(): void {
		$shopping = $this->shopping();

		$this->assertSame( 'Learn how it works', $shopping['ucp']['learn_more']['text'] );
		$this->assertSame( '/wc/v3/agent-readiness/ucp', $shopping['ucp']['path'], 'The switch saves on its own, as before' );
		$this->assertSame( 'Test the connection in UCP Playground', $shopping['connection']['test']['text'] );
		$this->assertSame( 'https://ucpplayground.com', $shopping['connection']['test']['href'] );
	}

	/**
	 * @testdox The admin footer is empty on the AI tools tab and on Sales readiness, and left alone elsewhere.
	 */
	public function test_admin_footer_only_hidden_on_ai_tools_and_sales_readiness(): void {
		$get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		try {
			foreach (
				array(
					array(
						array(
							'page' => 'wc-settings',
							'tab'  => AgenticCommerceSettings::TAB,
						),
						'',
					),
					array(
						array(
							'page'    => 'wc-settings',
							'tab'     => AgenticCommerceSettings::TAB,
							'section' => AgenticCommerceSettings::SHOPPING_SECTION,
						),
						'',
					),
					array(
						array(
							'page' => 'wc-admin',
							'path' => ReadinessCheckPage::PATH,
						),
						'',
					),
					array(
						array(
							'page' => 'wc-settings',
							'tab'  => 'general',
						),
						'Rate us',
					),
					array( array( 'page' => 'wc-admin' ), 'Rate us' ),
				) as $case
			) {
				$_GET = $case[0];
				$this->assertSame( $case[1], $this->sut->handle_admin_footer( 'Rate us' ), wp_json_encode( $case[0] ) );
			}
		} finally {
			$_GET = $get;
		}
	}

	/**
	 * What AI shopping hands its React view, read from the root it renders.
	 *
	 * @param bool $ucp_on Whether UCP is on.
	 * @return array<string, mixed>
	 */
	private function shopping( bool $ucp_on = false ): array {
		$html      = $this->render_page( AgenticCommerceSettings::SHOPPING_SECTION, $ucp_on );
		$processor = new \WP_HTML_Tag_Processor( $html );
		$this->assertTrue(
			$processor->next_tag(
				array(
					'tag_name'   => 'div',
					'class_name' => 'wc-ai-shopping-root',
				)
			),
			'AI shopping renders its view root'
		);
		$this->assertSame( 'wc-ai-shopping', $processor->get_attribute( 'id' ) );
		$this->assertStringContainsString( 'wc-settings-prevent-change-event', (string) $processor->get_attribute( 'class' ) );
		$view = json_decode( (string) $processor->get_attribute( 'data-view' ), true );
		$this->assertIsArray( $view );
		return $view;
	}

	/**
	 * The data-view attribute on a rendered root, decoded the way the browser reads it.
	 *
	 * @param string $html       Rendered markup.
	 * @param string $root_class Class of the root div.
	 */
	private function data_view( string $html, string $root_class ): string {
		$processor = new \WP_HTML_Tag_Processor( $html );
		$this->assertTrue(
			$processor->next_tag(
				array(
					'tag_name'   => 'div',
					'class_name' => $root_class,
				)
			),
			'The page renders its view root'
		);
		return (string) $processor->get_attribute( 'data-view' );
	}

	/**
	 * AI shopping's data as text, to look for wording anywhere in it.
	 */
	private function shopping_json(): string {
		return (string) wp_json_encode( $this->shopping(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * An open Setup checklist step.
	 *
	 * @param array<string, mixed> $shopping AI shopping's data.
	 * @param string               $id       Store condition id.
	 * @return array<string, mixed>
	 */
	private function step( array $shopping, string $id ): array {
		$step = current( wp_list_filter( $shopping['checklist']['steps'], array( 'id' => $id ) ) );
		$this->assertIsArray( $step, 'No open step ' . $id );
		return $step;
	}

	/**
	 * A store policy field.
	 *
	 * @param array<string, mixed> $shopping AI shopping's data.
	 * @param string               $id       Option name.
	 * @return array<string, mixed>
	 */
	private function policy( array $shopping, string $id ): array {
		$field = current( wp_list_filter( $shopping['policies']['fields'], array( 'id' => $id ) ) );
		$this->assertIsArray( $field, 'No policy field ' . $id );
		return $field;
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
	 * @testdox "Discourage search engines" shows as the setup step and in both the Store access card, where the noindex check it causes sits, and the find card, counted in each, Required and naming the setting once per row, with AI shopping on or off and with or without sales.
	 *
	 * @dataProvider ai_shopping_and_sales
	 *
	 * @param bool $ucp_on     Whether AI shopping is on.
	 * @param bool $with_sales Whether the report has sales to rank by.
	 */
	public function test_discourage_search_engines_shows_in_store_access( bool $ucp_on, bool $with_sales ): void {
		$this->make_every_step_pass();
		update_option( 'blog_public', '0' );
		$noindex = array(
			'id'       => 'no_noindex_directives',
			'ok'       => false,
			'blocks'   => 'listing',
			'group'    => 'access',
			'level'    => 'recommended',
			'name'     => 'noindex name',
			'label'    => 'noindex label',
			'why'      => '',
			'fix'      => '',
			'fix_url'  => '',
			'conflict' => false,
		);
		update_option( AgentReadiness::REPORT_OPTION, $this->report_with_listing_issue( $with_sales, array( $noindex ) ), false );
		update_option( 'woocommerce_feature_ucp_enabled', $ucp_on ? 'yes' : 'no' );

		$view     = $this->view();
		$sections = array_column( $view['sections'], null, 'id' );
		$rows     = array_column( $sections['access']['rows'], null, 'id' );
		$this->assertSame( array( 'search_visible' ), array_keys( $rows ), 'The setup step shows in Store access, and the noindex it prints doesn\'t show again' );
		$this->assertSame( 'Let search engines index your store', $rows['search_visible']['title'] );
		$this->assertSame( 'Required', $rows['search_visible']['badge']['text'] ?? null );
		$this->assertStringContainsString( 'Discourage search engines from indexing this site', $rows['search_visible']['fix'], 'The row names the setting to clear' );
		$this->assertSame( 'Change Reading settings', $rows['search_visible']['link']['text'] ?? null );
		$this->assertSame( '1 issue', $sections['access']['badge']['text'] );
		$find = array_column( $sections['find']['rows'], null, 'id' );
		$this->assertSame( array( 'search_visible' ), array_keys( $find ), 'It shows in the find card too' );
		$this->assertSame( $rows['search_visible'], $find['search_visible'], 'The same row, with one fix line and one link' );
		$this->assertSame( 1, substr_count( $find['search_visible']['fix'], 'Discourage search engines' ) );
		$this->assertSame( '1 issue', $sections['find']['badge']['text'], 'Each card it shows in counts it' );
		$this->assertSame( array( 'search_visible', 'missing_images' ), array_column( $view['fix_first']['items'], 'id' ) );
		$this->assertSame( 'Required for AI shopping', $view['fix_first']['items'][0]['tier'] );
		$this->assertSame( array(), wc_get_container()->get( StoreConditions::class )->failing_required(), 'It doesn\'t block the switch' );
		$step = $this->step( $this->shopping( $ucp_on ), 'search_visible' );
		$this->assertSame( array( 'Let search engines index your store', 'Required' ), array( $step['title'], $step['badge']['text'] ) );
		$this->assertStringContainsString( 'Discourage search engines from indexing this site', implode( ' ', $step['lines'] ), 'The Setup checklist shows it too, with the same fix line' );

		update_option( 'blog_public', '1' );
		$sections = array_column( $this->view()['sections'], null, 'id' );
		$this->assertSame( array( 'no_noindex_directives' ), array_column( $sections['access']['rows'], 'id' ), 'A noindex from elsewhere, such as an SEO plugin, shows in its place' );
		$this->assertContains( 'search_visible', array_column( $sections['access']['folded'], 'id' ), 'The passed step folds in Store access' );
		$this->assertContains( 'search_visible', array_column( $sections['find']['folded'], 'id' ), 'and in the find card' );
		$this->assertSame( 'All passed', $sections['find']['badge']['text'], 'It is no longer an issue in either card' );
	}

	/**
	 * @testdox Store pages that need a login are Recommended: in Fix first under Needed to list products with no Required badge, in Store access without one, never blocking AI shopping, with AI shopping on or off and with or without sales; once they open, the row folds.
	 *
	 * @dataProvider ai_shopping_and_sales
	 *
	 * @param bool $ucp_on     Whether AI shopping is on.
	 * @param bool $with_sales Whether the report has sales to rank by.
	 */
	public function test_login_wall_is_recommended( bool $ucp_on, bool $with_sales ): void {
		$this->make_every_step_pass();
		$wall = array(
			'id'       => 'pages_public',
			'ok'       => false,
			'blocks'   => 'listing',
			'group'    => 'access',
			'level'    => 'recommended',
			'name'     => 'Store pages open without logging in',
			'label'    => 'Store pages ask visitors to sign in',
			'why'      => '',
			'fix'      => '',
			'fix_url'  => '',
			'conflict' => false,
		);
		update_option( AgentReadiness::REPORT_OPTION, $this->report_with_listing_issue( $with_sales, array( $wall ) ), false );
		update_option( 'woocommerce_feature_ucp_enabled', $ucp_on ? 'yes' : 'no' );

		$view  = $this->view();
		$items = array_column( $view['fix_first']['items'], null, 'id' );
		$this->assertSame( 'Needed to list products', $items['pages_public']['tier'], 'It keeps products from being listed, so it stays in Fix first, but not as Required' );
		$this->assertNull( $items['pages_public']['badge'] );
		$this->assertNotContains( 'Required for AI shopping', array_column( $view['fix_first']['items'], 'tier' ) );
		$row = array_column( array_column( $view['sections'], null, 'id' )['access']['rows'], null, 'id' )['pages_public'];
		$this->assertSame( 'open', $row['state'] );
		$this->assertNull( $row['badge'], 'No Required badge in Store access' );
		$this->assertSame( 'Let shoppers open store pages without logging in', $row['title'] );
		$this->assertStringStartsWith( 'Check any membership, private store, or maintenance plugins', $row['fix'], 'A members-only store can keep its login' );
		$this->assertStringNotContainsString( 'need to be open', $row['text'] );
		$this->assertSame( array(), wc_get_container()->get( StoreConditions::class )->failing_required(), 'It doesn\'t block the switch' );
		$shopping = $this->shopping( $ucp_on );
		$this->assertStringNotContainsString( 'required step', $shopping['checklist']['summary'], 'It isn\'t a required step in the Setup checklist' );
		$this->assertFalse( $shopping['ucp']['blocked'] );
		$this->assertSame( $ucp_on ? 'Active' : 'Off', wc_get_container()->get( ReadinessCheckPage::class )->shopping_badge( wc_get_container()->get( StoreConditions::class )->all() )[1], 'AI shopping doesn\'t need attention for it' );

		update_option( AgentReadiness::REPORT_OPTION, $this->report_with_listing_issue( $with_sales, array( array( 'ok' => true ) + $wall ) ), false );
		$view = $this->view();
		$this->assertSame( array( 'missing_images' ), array_column( $view['fix_first']['items'], 'id' ), 'Once the pages open, it leaves Fix first' );
		$this->assertContains( 'pages_public', array_column( array_column( $view['sections'], null, 'id' )['access']['folded'], 'id' ) );
	}

	/**
	 * @testdox With "Discourage search engines" on, WordPress turns its sitemap off, so the missing product sitemap is that setup step, not also a sitemap row pointing at an SEO plugin.
	 */
	public function test_sitemap_off_from_discourage_search_engines_shows_once(): void {
		$this->make_every_step_pass();
		update_option( 'blog_public', '0' );
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(
					array(
						'id'      => 'sitemap_lists_products',
						'ok'      => false,
						'blocks'  => 'discovery',
						'group'   => 'find',
						'name'    => 'sitemap name',
						'label'   => 'sitemap label',
						'why'     => '',
						'fix'     => '',
						'fix_url' => '',
					),
					array(
						'id'      => 'sitemap_lastmod',
						'ok'      => false,
						'blocks'  => 'discovery',
						'group'   => 'find',
						'name'    => 'lastmod name',
						'label'   => 'lastmod label',
						'why'     => '',
						'fix'     => '',
						'fix_url' => '',
					),
				),
			)
		);
		$row_ids = static function ( array $view ): array {
			$ids = array();
			foreach ( $view['sections'] as $section ) {
				$ids = array_merge( $ids, array_column( $section['rows'], 'id' ) );
			}
			return $ids;
		};

		$view = $this->view();
		$this->assertSame( array( 'Let search engines index your store' ), array_column( $view['fix_first']['items'], 'title' ), 'Fix first names the box to untick' );
		$this->assertNotContains( 'sitemap_lists_products', $row_ids( $view ), 'and no card shows the sitemap row' );
		$this->assertNotContains( 'sitemap_lastmod', $row_ids( $view ), 'nor the sitemap dates row, which has no sitemap to read' );

		update_option( 'blog_public', '1' );
		$view = $this->view();
		$this->assertContains( 'sitemap_lists_products', $row_ids( $view ), 'With the box cleared, a sitemap that still leaves products out shows' );
		$rows = array();
		foreach ( $view['sections'] as $section ) {
			$rows = array_merge( $rows, $section['rows'] );
		}
		$row = $rows[ array_search( 'sitemap_lists_products', array_column( $rows, 'id' ), true ) ];
		$this->assertSame( 'Add a sitemap that lists your products', $row['title'], 'as its own issue' );
		$this->assertStringContainsString( 'SEO plugin', $row['fix'], 'with the fix for a sitemap plugin' );
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
