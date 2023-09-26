<?php
/**
 *  Tests for the WC_Admin_Dashboard_Setup class.
 *
 * @package WooCommerce\Tests\Admin
 */

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList;

/**
 * Class WC_Admin_Dashboard_Setup_Test
 */
class WC_Admin_Dashboard_Setup_Test extends WC_Unit_Test_Case {

	/**
	 * Set up
	 */
	public function setUp(): void {
		// Set default country to non-US so that 'payments' task gets added but 'woocommerce-payments' doesn't,
		// by default it won't be considered completed but we can manually change that as needed.
		update_option( 'woocommerce_default_country', 'JP' );
		$password    = wp_generate_password( 8, false, false );
		$this->admin = wp_insert_user(
			array(
				'user_login' => "test_admin$password",
				'user_pass'  => $password,
				'user_email' => "admin$password@example.com",
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $this->admin );

		parent::setUp();
	}

	/**
	 * Tear down
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_available_payment_gateways' );

		parent::tearDown();
	}

	/**
	 * Includes widget class and return the class.
	 *
	 * @return WC_Admin_Dashboard_Setup
	 */
	public function get_widget() {
		return include __DIR__ . '/../../../../includes/admin/class-wc-admin-dashboard-setup.php';
	}

	/**
	 * Return widget output (HTML).
	 *
	 * @return string Render widget HTML
	 */
	public function get_widget_output() {
		update_option( 'woocommerce_task_list_hidden', 'no' );

		ob_start();
		$this->get_widget()->render();
		return ob_get_clean();
	}


	/**
	 * Given the task list is not hidden and is not complete, make sure the widget is rendered.
	 */
	public function test_widget_render() {
		// Force the "payments" task to be considered incomplete.
		add_filter(
			'woocommerce_available_payment_gateways',
			function() {
				return array();
			}
		);
		global $wp_meta_boxes;
		$task_list = $this->get_widget()->get_task_list();
		$task_list->unhide();

		$this->get_widget();
		$this->assertArrayHasKey( 'wc_admin_dashboard_setup', $wp_meta_boxes['dashboard']['normal']['high'] );
	}

	/**
	 * Tests widget does not display when task list is complete.
	 */
	public function test_widget_does_not_display_when_task_list_complete() {
		// phpcs:disable Squiz.Commenting
		$task_list = new class() {
			public function is_complete() {
				return true;
			}
			public function is_hidden() {
				return false;
			}
		};
		// phpcs:enable Squiz.Commenting
		$widget    = $this->get_widget();
		$widget->set_task_list( $task_list );

		$this->assertFalse( $widget->should_display_widget() );
	}

	/**
	 * Tests widget does not display when task list is hidden.
	 */
	public function test_widget_does_not_display_when_task_list_hidden() {
		$widget = $this->get_widget();
		$widget->get_task_list()->hide();

		$this->assertFalse( $widget->should_display_widget() );
	}

	/**
	 * Tests widget does not display when user cannot manage woocommerce.
	 */
	public function test_widget_does_not_display_when_missing_capabilities() {
		$password  = wp_generate_password( 8, false, false );
		$author    = wp_insert_user(
			array(
				'user_login' => "test_author$password",
				'user_pass'  => $password,
				'user_email' => "author$password@example.com",
				'role'       => 'author',
			)
		);
		wp_set_current_user( $author );

		$widget = $this->get_widget();

		$this->assertFalse( $widget->should_display_widget() );
	}

	/**
	 * Tests widget does not display when task list is unavailable.
	 */
	public function test_widget_does_not_display_when_no_task_list() {
		$widget = $this->get_widget();
		$widget->set_task_list( null );

		$this->assertFalse( $widget->should_display_widget() );
	}

	/**
	 * Tests the widget output when 1 task has been completed.
	 */
	public function test_initial_widget_output() {
		// Force the "payments" task to be considered incomplete.
		add_filter(
			'woocommerce_available_payment_gateways',
			function() {
				return array();
			}
		);

		$html = $this->get_widget_output();

		$required_strings = array(
			'Step \d+ of \d+',
			'You&#039;re almost there! Once you complete store setup you can start receiving orders.',
			'Start selling',
		);

		foreach ( $required_strings as $required_string ) {
			$this->assertMatchesRegularExpression( "/{$required_string}/", $html );
		}
	}

	/**
	 * Tests completed task count as it completes one by one
	 */
	public function test_widget_renders_completed_task_count() {
		// Force the "payments" task to be considered completed
		// by faking a valid payment gateway.
		add_filter(
			'woocommerce_available_payment_gateways',
			function() {
				return array(
					new class() extends WC_Payment_Gateway {
					},
				);
			}
		);

		$completed_tasks_count = $this->get_widget()->get_completed_tasks_count();
		$tasks_count           = count( $this->get_widget()->get_tasks() );
		$step_number           = $completed_tasks_count + 1;
		if ( $completed_tasks_count === $tasks_count ) {
			$this->assertEmpty( $this->get_widget_output() );
		} else {
			$this->assertMatchesRegularExpression( "/Step {$step_number} of 5/", $this->get_widget_output() );
		}
	}
}
