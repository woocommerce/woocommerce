<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Products;
use WC_Product_Simple;
use WC_Unit_Test_Case;

/**
 * Tests for the Products onboarding task.
 */
class ProductsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Products
	 */
	private $sut;

	/**
	 * The parent task list used by the Products task.
	 *
	 * @var TaskList
	 */
	private $task_list;

	/**
	 * The redirect interceptor registered for the test.
	 *
	 * @var callable
	 */
	private $redirect_interceptor;

	/**
	 * The location of an intercepted redirect.
	 *
	 * @var string|null
	 */
	private $redirect_location;

	/**
	 * The status of an intercepted redirect.
	 *
	 * @var int|null
	 */
	private $redirect_status;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		delete_transient( Products::HAS_PRODUCT_TRANSIENT );
		delete_option( Task::COMPLETED_OPTION );
		update_option( TaskList::HIDDEN_OPTION, array( 'setup' ) );

		$this->redirect_location    = null;
		$this->redirect_status      = null;
		$this->redirect_interceptor = function ( $location, $status ) {
			$this->redirect_location = (string) $location;
			$this->redirect_status   = (int) $status;

			throw new \RuntimeException( 'Redirect intercepted.' );
		};
		add_filter( 'wp_redirect', $this->redirect_interceptor, 10, 2 );

		$this->task_list = new TaskList(
			array(
				'id'        => 'setup',
				'hidden_id' => 'setup',
			)
		);
		$this->sut       = new Products( $this->task_list );
	}

	/**
	 * @testdox Redirects an empty product list through the registered current_screen hook.
	 */
	public function test_redirects_empty_product_list_through_registered_current_screen_hook(): void {
		$this->trigger_current_screen( 'edit-product' );

		$this->assertSame(
			admin_url( 'admin.php?page=wc-admin&path=/add-product&task=products' ),
			$this->redirect_location,
			'An empty product list should redirect to the Products task.'
		);
		$this->assertSame( 302, $this->redirect_status, 'The Products task redirect should use the default 302 status.' );
	}

	/**
	 * @testdox Does not redirect non-product screens.
	 *
	 * @dataProvider non_product_screen_provider
	 *
	 * @param string $screen_id Screen ID to trigger.
	 */
	public function test_does_not_redirect_non_product_screens( string $screen_id ): void {
		$this->trigger_current_screen( $screen_id );

		$this->assertNull( $this->redirect_location, "The {$screen_id} screen should not redirect." );
		$this->assertNull( $this->redirect_status, "The {$screen_id} screen should not set a redirect status." );
	}

	/**
	 * Provides named non-product screens.
	 *
	 * @return array<string, array{screen_id: string}>
	 */
	public function non_product_screen_provider(): array {
		return array(
			'dashboard'         => array( 'screen_id' => 'dashboard' ),
			'non-product posts' => array( 'screen_id' => 'edit-post' ),
		);
	}

	/**
	 * @testdox Does not redirect the product list when a published product exists.
	 */
	public function test_does_not_redirect_product_list_when_published_product_exists(): void {
		$this->create_product( 'publish' );

		$this->trigger_current_screen( 'edit-product' );

		$this->assertNull( $this->redirect_location, 'A published product should keep the product list accessible.' );
		$this->assertNull( $this->redirect_status, 'A published product should not produce a redirect status.' );
	}

	/**
	 * @testdox Redirects the product list when only an auto-draft product exists.
	 */
	public function test_redirects_product_list_when_only_auto_draft_product_exists(): void {
		$this->create_product( 'auto-draft' );

		$this->trigger_current_screen( 'edit-product' );

		$this->assertSame(
			admin_url( 'admin.php?page=wc-admin&path=/add-product&task=products' ),
			$this->redirect_location,
			'An auto-draft alone should not keep the product list accessible.'
		);
		$this->assertSame( 302, $this->redirect_status, 'The auto-draft redirect should use the default 302 status.' );
	}

	/**
	 * @testdox Is always accessible when its parent task list is hidden.
	 */
	public function test_is_always_accessible_when_parent_task_list_is_hidden(): void {
		$this->assertTrue( $this->task_list->is_hidden(), 'The Products task parent list should use the persisted hidden-list state.' );
		$this->assertTrue( $this->sut->is_always_accessible(), 'The Products task should remain accessible when its parent list is hidden.' );
	}

	/**
	 * Creates a real simple product with the requested status.
	 *
	 * @param string $status Product status.
	 * @return int Product ID.
	 */
	private function create_product( string $status ): int {
		$product = new WC_Product_Simple();
		$product->set_name( "Products task {$status} fixture" );
		$product->set_status( $status );
		$product_id = $product->save();
		$this->assertGreaterThan( 0, $product_id, 'The product fixture should persist before the task reads it.' );

		return $product_id;
	}

	/**
	 * Triggers the registered current_screen callbacks for a WordPress screen.
	 *
	 * @param string $screen_id Screen ID to trigger.
	 */
	private function trigger_current_screen( string $screen_id ): void {
		try {
			set_current_screen( $screen_id );
		} catch ( \RuntimeException $exception ) {
			if ( 'Redirect intercepted.' !== $exception->getMessage() ) {
				throw $exception;
			}
		}
	}
}
