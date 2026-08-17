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
	 * The current screen before the test.
	 *
	 * @var \WP_Screen|null
	 */
	private $current_screen_backup;

	/**
	 * Whether the current screen global existed before the test.
	 *
	 * @var bool
	 */
	private $had_current_screen_global;

	/**
	 * Raw option states before the test.
	 *
	 * @var array<string, array{exists: bool, value: string|null, autoload: string|null}>
	 */
	private $option_state_backups = array();

	/**
	 * Product IDs created by the test.
	 *
	 * @var int[]
	 */
	private $product_ids = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->had_current_screen_global = array_key_exists( 'current_screen', $GLOBALS );
		$this->current_screen_backup     = $GLOBALS['current_screen'] ?? null;
		foreach ( $this->get_restored_option_names() as $option_name ) {
			$this->option_state_backups[ $option_name ] = $this->get_raw_option_state( $option_name );
		}
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
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'wp_redirect', $this->redirect_interceptor, 10 );
		$this->remove_products_task_hooks();

		foreach ( $this->product_ids as $product_id ) {
			wp_delete_post( $product_id, true );
		}

		foreach ( $this->option_state_backups as $option_name => $state ) {
			$this->restore_raw_option_state( $option_name, $state );
		}

		if ( $this->had_current_screen_global ) {
			$GLOBALS['current_screen'] = $this->current_screen_backup; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring the pre-test screen.
		} else {
			unset( $GLOBALS['current_screen'] );
		}

		parent::tearDown();
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
		$this->assertGreaterThan( 0, $product_id, 'The product fixture should persist before it is tracked for cleanup.' );
		$this->product_ids[] = $product_id;

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

	/**
	 * Removes every callback registered by the Products constructor.
	 */
	private function remove_products_task_hooks(): void {
		remove_action( 'admin_enqueue_scripts', array( $this->sut, 'possibly_add_import_return_notice_script' ) );
		remove_action( 'admin_enqueue_scripts', array( $this->sut, 'possibly_add_load_sample_return_notice_script' ) );
		remove_action( 'woocommerce_update_product', array( $this->sut, 'maybe_set_has_product_transient' ), 10 );
		remove_action( 'woocommerce_new_product', array( $this->sut, 'maybe_set_has_product_transient' ), 10 );
		remove_action( 'untrashed_post', array( $this->sut, 'maybe_set_has_product_transient_on_untrashed_post' ) );
		remove_action( 'current_screen', array( $this->sut, 'maybe_redirect_to_add_product_tasklist' ), 30 );
		remove_action( 'trashed_post', array( $this->sut, 'on_product_trashed' ) );
		remove_action( 'deleted_post_product', array( $this->sut, 'on_product_deleted' ) );
	}

	/**
	 * Gets every option row whose exact state must survive a test.
	 *
	 * @return string[] Option names.
	 */
	private function get_restored_option_names(): array {
		return array(
			Task::COMPLETED_OPTION,
			TaskList::HIDDEN_OPTION,
			'_transient_' . Products::HAS_PRODUCT_TRANSIENT,
			'_transient_timeout_' . Products::HAS_PRODUCT_TRANSIENT,
		);
	}

	/**
	 * Reads an option row without default filters or value coercion.
	 *
	 * @param string $option_name Option name.
	 * @return array{exists: bool, value: string|null, autoload: string|null}
	 */
	private function get_raw_option_state( string $option_name ): array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT option_value, autoload FROM {$wpdb->options} WHERE option_name = %s", $option_name ),
			ARRAY_A
		);

		return null === $row
			? array(
				'exists'   => false,
				'value'    => null,
				'autoload' => null,
			)
			: array(
				'exists'   => true,
				'value'    => $row['option_value'],
				'autoload' => $row['autoload'],
			);
	}

	/**
	 * Restores an option row without invoking sanitizers or changing autoload.
	 *
	 * @param string                                                         $option_name Option name.
	 * @param array{exists: bool, value: string|null, autoload: string|null} $state Raw option state.
	 */
	private function restore_raw_option_state( string $option_name, array $state ): void {
		global $wpdb;

		if ( ! $state['exists'] ) {
			$result = $wpdb->delete( $wpdb->options, array( 'option_name' => $option_name ) );
		} elseif ( $this->get_raw_option_state( $option_name )['exists'] ) {
			$result = $wpdb->update(
				$wpdb->options,
				array(
					'option_value' => $state['value'],
					'autoload'     => $state['autoload'],
				),
				array( 'option_name' => $option_name )
			);
		} else {
			$result = $wpdb->insert(
				$wpdb->options,
				array(
					'option_name'  => $option_name,
					'option_value' => $state['value'],
					'autoload'     => $state['autoload'],
				)
			);
		}

		$this->assertNotFalse( $result, "Failed to restore option {$option_name}." );
		wp_cache_delete( $option_name, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}
}
