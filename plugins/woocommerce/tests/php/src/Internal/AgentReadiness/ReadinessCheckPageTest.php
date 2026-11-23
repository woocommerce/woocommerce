<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessCheckPage;
use Automattic\WooCommerce\Internal\AgentReadiness\ScheduledScan;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreConditions;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Ucp\UcpDiscovery;
use WC_Unit_Test_Case;

/**
 * Tests for the Sales readiness view builder.
 */
class ReadinessCheckPageTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ReadinessCheckPage
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		StoreOutputChecksTest::block_requests();
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		delete_option( AgentReadiness::REPORT_OPTION );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		$this->sut = wc_get_container()->get( ReadinessCheckPage::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		delete_option( 'woocommerce_feature_agent_readiness_enabled' );
		parent::tearDown();
	}

	/**
	 * A scan whose is_pending answers come from a list, one per call.
	 *
	 * @param array<int, bool> $answers Pending answers in call order.
	 * @return ScheduledScan
	 */
	private function scan_with_pending_answers( array $answers ): ScheduledScan {
		$scan          = new class() extends ScheduledScan {
			/**
			 * Pending answers left to return.
			 *
			 * @var array<int, bool>
			 */
			public array $answers = array();

			/**
			 * Next answer from the list, or false when the list is empty.
			 */
			public function is_pending(): bool {
				return (bool) array_shift( $this->answers );
			}

			/**
			 * Never stalled in these race tests.
			 */
			public function is_stalled(): bool {
				return false;
			}
		};
		$scan->answers = $answers;
		return $scan;
	}

	/**
	 * Re-init the page with a scan stub that returns the given pending answers.
	 *
	 * @param array<int, bool> $answers Pending answers in call order.
	 */
	private function with_pending_answers( array $answers ): void {
		$this->sut->init(
			wc_get_container()->get( FeaturesController::class ),
			wc_get_container()->get( StoreConditions::class ),
			wc_get_container()->get( AgentReadiness::class ),
			$this->scan_with_pending_answers( $answers )
		);
	}

	/**
	 * @testdox A scan that finishes while the report is read still reports pending, so the client polls once more.
	 */
	public function test_view_keeps_pending_when_scan_finishes_during_report_read(): void {
		$this->with_pending_answers( array( true, false ) );

		$view = $this->sut->view();

		$this->assertTrue( $view['run']['pending'], 'Completion during the report read must not clear pending' );
	}

	/**
	 * @testdox A scan queued while the report is read still reports pending, so the client keeps polling.
	 */
	public function test_view_reports_pending_when_scan_is_queued_during_report_read(): void {
		$this->with_pending_answers( array( false, true ) );

		$view = $this->sut->view();

		$this->assertTrue( $view['run']['pending'], 'A queue during the report read must not hide the new scan' );
	}

	/**
	 * @testdox Need help? links to WooCommerce.com support, as WooCommerce > Home > Help does, off WordPress.com.
	 */
	public function test_view_links_support(): void {
		$this->assertFalse( UcpDiscovery::is_wordpress_com(), 'The test site is not on WordPress.com' );

		$this->assertSame( 'https://woocommerce.com/my-account/create-a-ticket/?utm_medium=product', $this->sut->view()['support_url'] );
	}

	/**
	 * @testdox With no scan before or after the report read, the view is not pending.
	 */
	public function test_view_is_not_pending_when_no_scan_runs(): void {
		$this->with_pending_answers( array( false, false ) );

		$view = $this->sut->view();

		$this->assertFalse( $view['run']['pending'], 'No scan must leave pending false' );
	}

	/**
	 * @testdox A passed setup step shows its step title and why, and the connection URL step doesn't use its failure wording.
	 */
	public function test_passed_setup_steps_keep_their_step_title(): void {
		$check_rows = new \ReflectionMethod( ReadinessCheckPage::class, 'check_rows' );
		$check_rows->setAccessible( true );
		$step = static fn( string $id, string $name, string $label, string $why ): array => array(
			'id'      => $id,
			'ok'      => true,
			'blocks'  => 'ucp',
			'level'   => StoreConditions::LEVEL_REQUIRED,
			'name'    => $name,
			'label'   => $label,
			'why'     => $why,
			'fix'     => '',
			'fix_url' => '',
		);
		$rows = array_column(
			$check_rows->invoke(
				null,
				array(
					$step( 'ucp_discovery', 'Use a readable permalink structure', 'Fix your connection URL', 'it doesn\'t load your store\'s details.' ),
					$step( 'guest_checkout', 'Guest checkout is allowed', 'Guest checkout is off', 'With guest checkout off, an AI assistant can check out only when …' ),
				),
				true
			),
			null,
			'id'
		);

		$this->assertSame( 'pass', $rows['ucp_discovery']['state'] );
		$this->assertSame( 'Use a readable permalink structure', $rows['ucp_discovery']['title'], 'Not the label, which names the failure' );
		$this->assertSame( 'Any structure except Plain lets AI assistants reach your store.', $rows['ucp_discovery']['text'] );
		$this->assertSame( 'Allow guest checkout', $rows['guest_checkout']['title'], 'The step title from the AI shopping checklist, not the report name' );
		$this->assertSame( 'Guest checkout lets shoppers from AI assistants pay without creating an account.', $rows['guest_checkout']['text'] );
	}

	/**
	 * @testdox Product details says which product pages it read, and says nothing for a report from before it read several.
	 */
	public function test_product_details_note_says_which_pages_were_read(): void {
		$note = new \ReflectionMethod( ReadinessCheckPage::class, 'sample_note' );
		$note->setAccessible( true );

		$this->assertSame( 'These checks look at a selection of your products, including your bestsellers and newest additions. If one product has an issue, others may too.', $note->invoke( null, array( 'pages_best_seller' => true ) ) );
		$this->assertSame( 'These checks look at a selection of your products, including your newest additions. If one product has an issue, others may too.', $note->invoke( null, array( 'pages_best_seller' => false ) ) );
		$this->assertNull( $note->invoke( null, array( 'store_checks' => array() ) ) );
	}

	/**
	 * @testdox A failing page check names the product whose page failed, and falls back to "on a product page" once it is gone, also for a check that reads the home page too.
	 */
	public function test_failing_page_check_names_its_product(): void {
		$failing_copy = new \ReflectionMethod( ReadinessCheckPage::class, 'failing_copy' );
		$failing_copy->setAccessible( true );
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_name( 'Blue hoodie & cap' );
		$product->save();
		$check = array(
			'id'       => 'product_jsonld_present',
			'ok'       => false,
			'name'     => 'name',
			'label'    => 'label',
			'why'      => 'why',
			'fix_url'  => '',
			'conflict' => false,
			'product'  => $product->get_id(),
		);

		$named = $failing_copy->invoke( null, $check );
		$this->assertStringStartsWith( 'On the Blue hoodie & cap page, the name, price, and stock aren’t in the page code', $named['text'] );
		$this->assertSame( 'Blue hoodie & cap', $named['tool']['text'], 'The product name is the linked text' );
		$this->assertSame( get_permalink( $product->get_id() ), $named['tool']['href'], 'The name links to the product page that failed' );
		$this->assertSame( 7, $named['tool']['at'], 'The link starts after "On the "' );

		$product->set_name( 'the' );
		$product->save();
		$this->assertSame( 7, $failing_copy->invoke( null, $check )['tool']['at'], 'A name that is also an earlier word in the line is linked where the name is' );

		$translate = static function ( $translation, $text ) {
			return 0 === strpos( (string) $text, 'On the %s page, the name, price, and stock' ) ? 'Na stronie „%s” brakuje nazwy, ceny i stanu.' : $translation;
		};
		add_filter( 'gettext', $translate, 10, 2 );
		$translated = $failing_copy->invoke( null, $check );
		remove_filter( 'gettext', $translate, 10 );
		$this->assertSame( 'Na stronie „the” brakuje nazwy, ceny i stanu.', $translated['text'] );
		$this->assertSame( 12, $translated['tool']['at'], 'In a translation the name can sit elsewhere, and the position counts characters, not bytes' );

		$emoji = static function ( $translation, $text ) {
			return 0 === strpos( (string) $text, 'On the %s page, the name, price, and stock' ) ? '😀 On the %s page, the name.' : $translation;
		};
		add_filter( 'gettext', $emoji, 10, 2 );
		$emoji_line = $failing_copy->invoke( null, $check );
		remove_filter( 'gettext', $emoji, 10 );
		$this->assertSame( '😀 On the the page, the name.', $emoji_line['text'] );
		$this->assertSame( 10, $emoji_line['tool']['at'], 'An emoji before the name counts as two UTF-16 code units, the index the page slices with' );

		$product->delete( true );
		$unnamed = $failing_copy->invoke( null, $check );
		$this->assertStringStartsWith( 'On a product page, the name, price, and stock aren’t in the page code', $unnamed['text'] );
		$this->assertNull( $unnamed['tool'], 'A deleted product has no link' );

		$check['id'] = 'viewport_meta';
		$this->assertStringStartsWith( 'On a product page, the code doesn’t tell browsers how to size it', $failing_copy->invoke( null, $check )['text'], 'A check that also reads the home page doesn\'t blame it for a deleted product\'s page' );
		$check['product'] = 0;
		$this->assertStringStartsWith( 'Your home page doesn’t tell browsers how to size it', $failing_copy->invoke( null, $check )['text'], 'With no product, the home page failed' );
	}
}
