<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;
use Automattic\WooCommerce\Internal\AgentReadiness\ScheduledScan;
use Automattic\WooCommerce\Internal\AgentReadiness\UcpSwitch;
use Automattic\WooCommerce\Internal\Ucp\UcpDiscovery;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the agent readiness REST endpoints.
 */
class ReadinessRestControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * Each test gets a fresh REST server.
	 *
	 * @var bool
	 */
	protected static $reuse_rest_server = false;

	/**
	 * Keep the report tests offline, with no stored report or queued scan. Routes register on first dispatch.
	 */
	public function setUp(): void {
		parent::setUp();
		// Report tests stay offline: store output checks would load the test site's pages.
		StoreOutputChecksTest::block_requests();
		delete_option( AgentReadiness::REPORT_OPTION );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		as_unschedule_all_actions( ScheduledScan::DAILY_HOOK );
		parent::tearDown();
	}

	/**
	 * @testdox GET returns a null report before the first scan, then the stored report.
	 */
	public function test_get_returns_stored_report(): void {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness' ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertNull( $response->get_data()['report'] );
		$this->assertFalse( $response->get_data()['scan_pending'] );
		$this->assertFalse( $response->get_data()['scan_stalled'] );

		$report   = wc_get_container()->get( AgentReadiness::class )->refresh_report();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness' ) );
		$this->assertSame( $report['generated_at'], $response->get_data()['report']['generated_at'] );
	}

	/**
	 * @testdox GET view gives the Sales readiness page and the AI tools Overview what they show, as text and badges.
	 */
	public function test_get_view(): void {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness/view' ) );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'Not checked yet', $data['last_checked'] );
		$this->assertSame( 'Check your store', $data['run']['label'] );
		$this->assertNull( $data['fix_first'] );
		$this->assertSame( array(), $data['sections'] );
		$this->assertSame( 'AI shopping', $data['overview']['shopping']['title'] );
		$this->assertStringContainsString( 'path=/sales-readiness', $data['overview']['view_all']['href'] );

		wc_get_container()->get( AgentReadiness::class )->refresh_report();
		$data = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness/view' ) )->get_data();
		$this->assertSame( 'Check again', $data['run']['label'] );
		$this->assertSame( array( 'access', 'find', 'understand', 'products', 'buy' ), array_column( $data['sections'], 'id' ) );
		$this->assertArrayHasKey( 'intent', $data['sections'][0]['badge'] );
	}

	/**
	 * @testdox GET reads setup checks now, so a setting changed after the scan shows its current state.
	 */
	public function test_get_reads_store_checks_now(): void {
		StoreConditionsTest::make_store_ready( $this );
		wc_get_container()->get( AgentReadiness::class )->refresh_report();
		update_option( 'woocommerce_enable_guest_checkout', 'no' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness' ) );
		$checks   = array_column( $response->get_data()['report']['store_checks'], 'ok', 'id' );

		$this->assertFalse( $checks['guest_checkout'] );
	}

	/**
	 * @testdox POST scan queues a background scan and answers 202 with scan_pending.
	 */
	public function test_post_queues_scan(): void {
		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/agent-readiness/scan' ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertTrue( $response->get_data()['scan_pending'] );
		$this->assertTrue( as_has_scheduled_action( ScheduledScan::RUN_NOW_HOOK ) );
		$this->assertFalse( $response->get_data()['scan_stalled'] );
	}

	/**
	 * @testdox When the queued scan has stalled, POST leaves it queued and answers with scan_stalled.
	 */
	public function test_post_reports_stalled_scan(): void {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}actionscheduler_actions SET last_attempt_gmt = %s WHERE status IN ( 'complete', 'failed', 'in-progress' )", gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) ) );
		as_schedule_single_action( time() - ScheduledScan::STALLED_AFTER - 60, ScheduledScan::RUN_NOW_HOOK, array(), ScheduledScan::GROUP );

		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/agent-readiness/scan' ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertNull( $response->get_data()['report'], 'The scan must not run in the request.' );
		$this->assertTrue( $response->get_data()['scan_pending'] );
		$this->assertTrue( $response->get_data()['scan_stalled'] );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
	}

	/**
	 * @testdox A user without manage_woocommerce can't read the report or queue a scan.
	 */
	public function test_requires_manage_woocommerce(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$this->assertSame( 403, $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness' ) )->get_status() );
		$this->assertSame( 403, $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/agent-readiness/scan' ) )->get_status() );
		$this->assertSame( 403, $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness/view' ) )->get_status() );
		$this->assertSame( 403, $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness/support-report' ) )->get_status() );
		$this->assertFalse( as_has_scheduled_action( ScheduledScan::RUN_NOW_HOOK ) );
	}

	/**
	 * @testdox The support report before the first check has the header and says "Not checked yet", without queueing a check.
	 */
	public function test_support_report_before_first_check(): void {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness/support-report' ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertMatchesRegularExpression( '/^AIShoppingHealth_[a-z0-9.-]+_\d{4}-\d{2}-\d{2}T\d{2}-\d{2}-\d{2}\.txt$/', $data['filename'] );
		$this->assertStringContainsString( 'Site: ' . home_url(), $data['text'] );
		$this->assertStringContainsString( 'WooCommerce version: ' . WC_VERSION, $data['text'] );
		$this->assertStringContainsString( 'Last checked: Not checked yet', $data['text'] );
		$this->assertStringContainsString( "\nDaily checks: On\n", $data['text'], 'Support sees whether the store is checked each day' );
		update_option( ScheduledScan::DAILY_OPTION, 'no' );
		$this->assertStringContainsString( "\nDaily checks: Off\n", $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness/support-report' ) )->get_data()['text'] );
		delete_option( ScheduledScan::DAILY_OPTION );
		$this->assertStringContainsString( "AI shopping: Off\nStore visibility: Live", $data['text'] );
		$this->assertStringContainsString( "### Store checks ###\n\nNot checked yet", $data['text'] );
		$this->assertStringContainsString( '(launched)', $data['text'], 'Setup steps are read now, so they show before the first check.' );
		$this->assertStringNotContainsString( '### Product checks ###', $data['text'] );
		$this->assertFalse( as_has_scheduled_action( ScheduledScan::RUN_NOW_HOOK ), 'The download must not queue a check.' );
	}

	/**
	 * @testdox The support report is named after the page, and with no products checked it says so instead of marking every product check passed.
	 */
	public function test_support_report_with_no_products(): void {
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => '2026-11-18T10:42:00+00:00',
				'products'     => 0,
				'store_checks' => array(),
				'checks'       => array(
					array(
						'id'    => 'missing_images',
						'label' => 'Products with no main image',
						'count' => 0,
					),
				),
			),
			false
		);
		$text = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness/support-report' ) )->get_data()['text'];

		$this->assertStringStartsWith( "### AI shopping health ###\n", $text );
		$this->assertStringEndsWith( "\nNo products to check.\n", $text, 'After the Product checks heading and the app totals' );
		$this->assertStringNotContainsString( 'missing_images', $text, 'A count of 0 with nothing checked is not a pass' );
		$privacy = array_values( array_filter( explode( "\n", $text ), static fn( string $line ): bool => false !== strpos( $line, '(privacy_page)' ) ) );
		$this->assertStringStartsWith( 'Privacy policy page is selected and published (privacy_page):', $privacy[0] ?? '', 'The step is done only once the page is published' );
	}

	/**
	 * @testdox The support report lists each check with its status and what was found, product counts with their IDs, and hides a same-cause check.
	 */
	public function test_support_report_after_a_check(): void {
		$cap    = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Red cap' ) );
		$hoodie = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Blue hoodie' ) );
		update_option( 'blog_public', '0' );
		update_option( 'woocommerce_feature_ucp_enabled', 'no' );
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at'        => '2026-11-18T10:42:00+00:00',
				'truncated'           => true,
				'products'            => 3,
				'hidden_from_catalog' => 1,
				'unsupported_types'   => 2,
				'store_checks'        => array(
					array(
						'id'       => 'llms_txt_present',
						'ok'       => false,
						'blocks'   => 'discovery',
						'name'     => 'llms.txt',
						'why'      => 'Report why.',
						'conflict' => false,
						'source'   => '404 text/html',
					),
					array(
						'id'       => 'open_graph_tags',
						'ok'       => true,
						'blocks'   => 'discovery',
						'name'     => 'Open Graph tags',
						'why'      => '',
						'conflict' => false,
					),
					array(
						'id'       => 'llms_txt_valid',
						'ok'       => false,
						'blocks'   => 'discovery',
						'name'     => 'llms.txt starts with a heading',
						'why'      => '',
						'conflict' => false,
					),
					array(
						'id'       => 'sitemap_lists_products',
						'ok'       => null,
						'blocks'   => 'discovery',
						'name'     => 'Sitemap lists products',
						'why'      => '',
						'conflict' => false,
					),
					array(
						'id'       => 'product_shipping_schema',
						'ok'       => null,
						'pending'  => true,
						'blocks'   => 'discovery',
						'name'     => 'Shipping details',
						'why'      => '',
						'conflict' => false,
					),
					array(
						'id'       => 'ucp_profile',
						'ok'       => false,
						'blocks'   => 'ucp',
						'name'     => 'UCP profile',
						'why'      => '',
						'conflict' => false,
					),
					array(
						'id'       => 'ucp_endpoints_live',
						'ok'       => null,
						'blocks'   => 'ucp',
						'name'     => 'UCP endpoint answers',
						'why'      => '',
						'conflict' => false,
						'seen_at'  => home_url( '/wp-json/wc/ucp/v1/catalog/search' ),
						'source'   => '503 text/html',
					),
					array(
						'id'       => 'no_noindex_directives',
						'ok'       => false,
						'blocks'   => 'listing',
						'name'     => 'No noindex',
						'why'      => '',
						'conflict' => false,
					),
				),
				'channels'            => array(
					'chatgpt' => array(
						'name'         => 'ChatGPT',
						'listable'     => 1,
						'not_listable' => 2,
						'with_gaps'    => 1,
					),
				),
				'checks'              => array(
					array(
						'id'          => 'missing_brand',
						'label'       => 'Products with no brand',
						'count'       => 2,
						'product_ids' => array( $cap->get_id(), $hoodie->get_id() ),
					),
					array(
						'id'    => 'missing_images',
						'label' => 'Products with no image',
						'count' => 0,
					),
				),
			),
			false
		);

		// Only Cash on delivery is on: the step passes, and the report names it.
		$gateways = WC()->payment_gateways()->payment_gateways();
		$previous = array_map( static fn( $gateway ) => $gateway->enabled, $gateways );
		foreach ( $gateways as $gateway ) {
			$gateway->enabled = 'cod' === $gateway->id ? 'yes' : 'no';
		}
		try {
			$text = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness/support-report' ) )->get_data()['text'];
			foreach ( $gateways as $gateway ) {
				$gateway->enabled = 'no';
			}
			$none = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness/support-report' ) )->get_data()['text'];
		} finally {
			foreach ( $gateways as $key => $gateway ) {
				$gateway->enabled = $previous[ $key ];
			}
		}

		$this->assertMatchesRegularExpression( '/^[^\n]*\(payment_method\): ✔\nEnabled payment methods: Cash on delivery$/m', $text );
		$this->assertMatchesRegularExpression( '/^[^\n]*\(payment_method\): ❌[^\n]*\nEnabled payment methods: None$/m', $none );
		$this->assertMatchesRegularExpression( '/^UCP endpoint answers \(ucp_endpoints_live\): Couldn’t check Request: POST \/wp-json\/wc\/ucp\/v1\/catalog\/search Store response: 503 text\/html( \(not shown on the page\))?$/m', $text, 'Couldn\'t check says what the store answered, and where.' );
		$this->assertStringContainsString( 'Last checked: 2026-11-18T10:42:00+00:00', $text );
		$this->assertStringContainsString( 'Check status: Finished', $text );
		$this->assertStringContainsString( sprintf( "Check status: Finished\nChecked the %d products you changed most recently.", ProductScan::PRODUCT_LIMIT ), $text );
		$this->assertStringContainsString( 'Products checked: 3', $text );
		$this->assertStringContainsString( 'Hidden: 1', $text );
		$this->assertStringContainsString( 'Not supported: 2', $text );
		$this->assertMatchesRegularExpression( '/^[^\n]*\(search_visible\): ❌ AI assistants /m', $text, 'A failing setup step uses the page\'s words.' );
		$this->assertStringNotContainsString( 'Your pages couldn’t be checked', $text, 'Some checks ran.' );
		$this->assertMatchesRegularExpression( '/^[^\n]*\(ucp_on\): ❌ Some checks only run when AI shopping is on/m', $text );
		$this->assertMatchesRegularExpression( '/^llms\.txt \(llms_txt_present\): ❌ \S[^\n]*Store response: 404 text\/html$/m', $text );
		$this->assertMatchesRegularExpression( '/^Sitemap lists products \(sitemap_lists_products\): Couldn’t check( \(not shown on the page\))?$/m', $text );
		$this->assertMatchesRegularExpression( '/^Shipping details \(product_shipping_schema\): Not available yet \(not shown on the page\)$/m', $text );
		$this->assertMatchesRegularExpression( '/^Open Graph tags \(open_graph_tags\): ✔$/m', $text );
		$this->assertMatchesRegularExpression( '/^No noindex \(no_noindex_directives\): ❌[^\n]*\(not shown on the page\)$/m', $text );
		$this->assertMatchesRegularExpression( '/^llms\.txt starts with a heading \(llms_txt_valid\): ❌[^\n]*\(not shown on the page\)$/m', $text );
		$this->assertMatchesRegularExpression( '/^UCP profile \(ucp_profile\): ❌[^\n]*\(not shown on the page\)$/m', $text );
		$this->assertStringContainsString( 'ChatGPT (chatgpt): 1 can be listed, 2 can’t be listed, 1 listed with missing details', $text );
		$this->assertStringContainsString( sprintf( 'Products with no brand (missing_brand): ❌ 2 products. Products: %d (Red cap), %d (Blue hoodie)', $cap->get_id(), $hoodie->get_id() ), $text );
		$this->assertStringContainsString( 'Products with no image (missing_images): ✔', $text );
	}

	/**
	 * @testdox When no store check could run, the support report says why once, as the page does.
	 */
	public function test_support_report_says_why_no_check_ran(): void {
		$unchecked = array(
			array(
				'id'       => 'llms_txt_present',
				'ok'       => null,
				'blocks'   => 'discovery',
				'name'     => 'llms.txt',
				'why'      => '',
				'conflict' => false,
			),
			array(
				'id'       => 'product_shipping_schema',
				'ok'       => null,
				'pending'  => true,
				'blocks'   => 'discovery',
				'name'     => 'Shipping details',
				'why'      => '',
				'conflict' => false,
			),
			array(
				'id'       => 'open_graph_tags',
				'ok'       => null,
				'blocks'   => 'discovery',
				'name'     => 'Open Graph tags',
				'why'      => '',
				'conflict' => false,
			),
		);
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => '2026-11-18T10:42:00+00:00',
				'store_checks' => array_merge(
					array(
						array(
							'id'      => 'launched',
							'ok'      => true,
							'name'    => 'Store is launched',
							'fix_url' => '',
						),
					),
					$unchecked
				),
			),
			false
		);

		$text = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness/support-report' ) )->get_data()['text'];

		$this->assertStringContainsString( "### Store checks ###\n\nYour pages couldn’t be checked. Your hosting setup blocked the check from opening them.", $text );
		$this->assertSame( 1, substr_count( $text, 'Your pages couldn’t be checked' ) );

		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => '2026-11-18T10:42:00+00:00',
				'store_checks' => array_merge(
					array(
						array(
							'id'      => 'launched',
							'ok'      => false,
							'name'    => 'Store is launched',
							'fix_url' => '',
						),
					),
					$unchecked
				),
			),
			false
		);
		$text = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness/support-report' ) )->get_data()['text'];
		$this->assertStringContainsString( "### Store checks ###\n\nYour store wasn’t live at the last check. If your store is live now, these checks run at the next daily check. To run them now, select Check again.", $text );
		$this->assertStringNotContainsString( 'Your hosting setup blocked', $text );
		$this->assertStringContainsString( 'Open Graph tags (open_graph_tags): Not checked yet. Your store wasn’t live at the last check.', $text, 'Each check says so too, as on the page' );
		$this->assertStringContainsString( 'Shipping details (product_shipping_schema): Not available yet', $text, 'A check a coming update builds is still not available' );
	}

	/**
	 * @testdox In the support report, a product page check with no product to open says Not checked yet and why; one that tried and couldn't says Couldn't check.
	 */
	public function test_support_report_product_page_check_without_a_product(): void {
		$check = static fn( string $id, ?bool $ok, array $extra = array() ): array => $extra + array(
			'id'      => $id,
			'ok'      => $ok,
			'blocks'  => 'discovery',
			'name'    => $id,
			'why'     => '',
			'fix_url' => '',
		);
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => '2026-11-18T10:42:00+00:00',
				'store_checks' => array( $check( 'launched', true ), $check( 'canonical_url', null, array( 'no_product' => true ) ), $check( 'sitemap_lists_products', null ), $check( 'robots_txt', true ) ),
			),
			false
		);
		$text  = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness/support-report' ) )->get_data()['text'];
		$this->assertStringContainsString( 'canonical_url (canonical_url): Not checked yet. This check needs a product page.', $text );
		$this->assertStringContainsString( 'sitemap_lists_products (sitemap_lists_products): Couldn’t check', $text );
	}

	/**
	 * @testdox With no products, the support report marks the Product details checks as not shown, as the page shows a "no products" row instead.
	 */
	public function test_support_report_with_no_products_marks_product_details_not_shown(): void {
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => '2026-11-18T10:42:00+00:00',
				'products'     => 0,
				'store_checks' => array(
					array(
						'id'       => 'llms_txt_present',
						'ok'       => false,
						'group'    => 'find',
						'blocks'   => 'discovery',
						'name'     => 'llms.txt',
						'why'      => '',
						'conflict' => false,
					),
					array(
						'id'       => 'images_have_alt',
						'ok'       => false,
						'group'    => 'understand',
						'blocks'   => 'discovery',
						'name'     => 'Images have alt text',
						'why'      => '',
						'conflict' => false,
					),
				),
			),
			false
		);

		$text  = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness/support-report' ) )->get_data()['text'];
		$lines = array();
		foreach ( explode( "\n", $text ) as $line ) {
			if ( false !== strpos( $line, '(images_have_alt)' ) ) {
				$lines['images_have_alt'] = $line;
			} elseif ( false !== strpos( $line, '(llms_txt_present)' ) ) {
				$lines['llms_txt_present'] = $line;
			}
		}

		$this->assertArrayHasKey( 'images_have_alt', $lines );
		$this->assertArrayHasKey( 'llms_txt_present', $lines );
		$this->assertStringContainsString( '(not shown on the page)', $lines['images_have_alt'] );
		$this->assertStringNotContainsString( 'not shown on the page', $lines['llms_txt_present'] );
	}

	/**
	 * @testdox The support report holds no customer, order or account data, and no keys.
	 */
	public function test_support_report_has_no_personal_data_or_secrets(): void {
		global $wpdb;
		update_option( 'admin_email', 'owner-secret@example.com' );
		update_option( 'woocommerce_email_from_address', 'from-secret@example.com' );
		self::factory()->user->create(
			array(
				'role'       => 'customer',
				'user_email' => 'shopper-secret@example.com',
				'first_name' => 'Zebulon',
			)
		);
		$order = wc_create_order();
		$order->set_billing_email( 'billing-secret@example.com' );
		$order->set_billing_address_1( '42 Secret Street' );
		$order->save();
		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				'user_id'         => get_current_user_id(),
				'description'     => 'Support test',
				'permissions'     => 'read_write',
				'consumer_key'    => wc_api_hash( 'ck_secret_key_value' ),
				'consumer_secret' => 'cs_secret_value',
				'truncated_key'   => 'ecret_v',
			)
		);
		wc_get_container()->get( AgentReadiness::class )->refresh_report();

		$text = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness/support-report' ) )->get_data()['text'];

		foreach ( array( '42 Secret Street', 'secret', 'Zebulon', '@example.com', 'cs_', 'ck_' ) as $leak ) {
			$this->assertStringNotContainsString( $leak, $text );
		}
	}

	/**
	 * @testdox POST ucp answers 409 and leaves UCP off while a required setup check fails.
	 */
	public function test_ucp_switch_blocked_by_failing_check(): void {
		StoreConditionsTest::make_store_ready();
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'woocommerce_feature_ucp_enabled', 'no' );

		$response = $this->dispatch_ucp( true );
		$data     = $response->get_data();

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'woocommerce_ucp_required_checks_failing', $data['code'] );
		$this->assertSame( array( 'launched' ), $data['data']['failing_checks'] );
		$this->assertSame( 'no', get_option( 'woocommerce_feature_ucp_enabled' ) );
	}

	/**
	 * @testdox POST ucp turns UCP on once the required checks pass, and off again.
	 */
	public function test_ucp_switch_on_and_off(): void {
		StoreConditionsTest::make_store_ready();

		$response = $this->dispatch_ucp( true );
		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['enabled'] );
		$this->assertSame( UcpSwitch::STATUS_ACTIVE, $response->get_data()['status'] );
		$this->assertSame( 'yes', get_option( 'woocommerce_feature_ucp_enabled' ) );

		$response = $this->dispatch_ucp( false );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( UcpSwitch::STATUS_OFF, $response->get_data()['status'] );
		$this->assertSame( 'no', get_option( 'woocommerce_feature_ucp_enabled' ) );
	}

	/**
	 * @testdox When the UCP option can't be saved, POST ucp answers with an error, not with failing checks.
	 */
	public function test_ucp_switch_reports_a_failed_save(): void {
		StoreConditionsTest::make_store_ready();
		update_option( 'woocommerce_feature_ucp_enabled', 'no' );
		add_filter( 'pre_update_option_woocommerce_feature_ucp_enabled', static fn( $value, $old_value ) => $old_value, 10, 2 );

		$response = $this->dispatch_ucp( true );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_ucp_not_changed', $response->get_data()['code'] );
		$this->assertSame( 'no', get_option( 'woocommerce_feature_ucp_enabled' ) );
	}

	/**
	 * @testdox Turning UCP off works while a required check fails.
	 */
	public function test_ucp_switch_off_with_failing_check(): void {
		update_option( 'woocommerce_feature_ucp_enabled', 'yes' );

		$response = $this->dispatch_ucp( false );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'no', get_option( 'woocommerce_feature_ucp_enabled' ) );
	}

	/**
	 * @testdox Turning UCP on with Plain permalinks writes the discovery file, so the address is served without a permalink change. Multisite refuses it.
	 */
	public function test_turning_ucp_on_writes_the_discovery_file_for_plain_permalinks(): void {
		global $wp_rewrite;
		StoreConditionsTest::make_store_ready();
		$wp_rewrite->set_permalink_structure( '' );
		$path = get_temp_dir() . uniqid( 'wc-ucp-' ) . '/ucp';
		add_filter( 'woocommerce_ucp_discovery_file_path', fn() => $path );

		try {
			$response = $this->dispatch_ucp( true );

			if ( is_multisite() ) {
				// Multisite never writes the file, so plain permalinks keep the switch off.
				$this->assertSame( 409, $response->get_status() );
				$this->assertFileDoesNotExist( $path );
				return;
			}
			$this->assertSame( 200, $response->get_status() );
			$this->assertFileExists( $path );
			$this->assertSame( 'ok', UcpDiscovery::serve_status() );
		} finally {
			wp_delete_file( $path );
			$dir = dirname( $path );
			if ( is_dir( $dir ) ) {
				rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			}
		}
	}

	/**
	 * @testdox When the discovery file can't be written as UCP turns on, POST ucp answers needs attention, not active.
	 */
	public function test_turning_ucp_on_reports_a_failed_discovery_write(): void {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Multisite never writes the discovery file.' );
		}
		global $wp_rewrite;
		StoreConditionsTest::make_store_ready();
		$wp_rewrite->set_permalink_structure( '' );
		// A folder where the file goes: its parent is writable, so the switch is allowed, but the write fails.
		$path = get_temp_dir() . uniqid( 'wc-ucp-' ) . '/ucp';
		wp_mkdir_p( $path );
		add_filter( 'woocommerce_ucp_discovery_file_path', fn() => $path );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- The failed write warns, as on a real server.
		set_error_handler( static fn( int $severity ): bool => E_WARNING === $severity );

		try {
			$response = $this->dispatch_ucp( true );
		} finally {
			restore_error_handler();
			foreach ( array( $path, dirname( $path ) ) as $dir ) {
				if ( is_dir( $dir ) ) {
					rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
				}
			}
		}

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['enabled'] );
		$this->assertSame( UcpSwitch::STATUS_NEEDS_ATTENTION, $response->get_data()['status'] );
		$this->assertContains( 'ucp_discovery', $response->get_data()['failing_checks'] );
	}

	/**
	 * @testdox A user without manage_woocommerce can't switch UCP.
	 */
	public function test_ucp_switch_requires_manage_woocommerce(): void {
		StoreConditionsTest::make_store_ready();
		update_option( 'woocommerce_feature_ucp_enabled', 'no' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$this->assertSame( 403, $this->dispatch_ucp( true )->get_status() );
		$this->assertSame( 'no', get_option( 'woocommerce_feature_ucp_enabled' ) );
	}

	/**
	 * @testdox POST daily-checks turns the daily check off, which removes the scheduled run, and on again, which schedules it for a checked store.
	 */
	public function test_daily_checks_switch(): void {
		\WC_Helper_Product::create_simple_product();
		wc_get_container()->get( AgentReadiness::class )->refresh_report();
		$scheduled = wc_get_container()->get( ScheduledScan::class );
		$scheduled->set_daily_enabled( true );
		$this->assertIsInt( as_next_scheduled_action( ScheduledScan::DAILY_HOOK, array(), ScheduledScan::GROUP ) );

		$response = $this->dispatch_daily_checks( false );
		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['enabled'] );
		$this->assertFalse( as_has_scheduled_action( ScheduledScan::DAILY_HOOK, array(), ScheduledScan::GROUP ) );

		$scheduled->run();
		$this->assertFalse( as_has_scheduled_action( ScheduledScan::DAILY_HOOK, array(), ScheduledScan::GROUP ), 'A check run by hand does not bring the daily one back' );

		$response = $this->dispatch_daily_checks( true );
		$this->assertTrue( $response->get_data()['enabled'] );
		$this->assertSame( ScheduledScan::next_daily_run(), as_next_scheduled_action( ScheduledScan::DAILY_HOOK, array(), ScheduledScan::GROUP ) );
	}

	/**
	 * @testdox Before the first check, turning daily checks on queues nothing, and a user without manage_woocommerce can't change the setting.
	 */
	public function test_daily_checks_switch_before_first_check_and_permission(): void {
		delete_option( AgentReadiness::REPORT_OPTION );

		$this->assertTrue( $this->dispatch_daily_checks( true )->get_data()['enabled'] );
		$this->assertFalse( as_has_scheduled_action( ScheduledScan::DAILY_HOOK, array(), ScheduledScan::GROUP ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );
		$this->assertSame( 403, $this->dispatch_daily_checks( false )->get_status() );
		$this->assertTrue( ScheduledScan::daily_enabled() );
	}

	/**
	 * Send POST wc/v3/agent-readiness/daily-checks.
	 *
	 * @param bool $enabled Whether to check the store every day.
	 */
	private function dispatch_daily_checks( bool $enabled ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wc/v3/agent-readiness/daily-checks' );
		$request->set_param( 'enabled', $enabled );
		return $this->server->dispatch( $request );
	}

	/**
	 * Send POST wc/v3/agent-readiness/ucp.
	 *
	 * @param bool $enabled Whether to turn UCP on.
	 */
	private function dispatch_ucp( bool $enabled ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wc/v3/agent-readiness/ucp' );
		$request->set_param( 'enabled', $enabled );
		return $this->server->dispatch( $request );
	}
}
