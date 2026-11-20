<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessCheckPage;
use Automattic\WooCommerce\Internal\AgentReadiness\ScheduledScan;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreConditions;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
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
	 * @testdox A failing page check names the product whose page failed, and falls back to "a product page" once it is gone.
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

		$this->assertStringStartsWith( 'On the Blue hoodie & cap page, the name, price, and stock aren’t in the page code', $failing_copy->invoke( null, $check )['text'] );

		$product->delete( true );
		$this->assertStringStartsWith( 'A product page doesn’t include these details', $failing_copy->invoke( null, $check )['text'] );
	}
}
