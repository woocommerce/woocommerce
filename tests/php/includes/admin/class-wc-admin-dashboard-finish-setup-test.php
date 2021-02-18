<?php
/**
 *  Tests for the WC_Admin_Dashboard_Finish_Setup class.
 *
 * @package WooCommerce\Tests\Admin
 */

/**
 * Class WC_Admin_Dashboard_Finish_Setup_Test
 */
class WC_Admin_Dashboard_Finish_Setup_Test extends WC_Unit_Test_Case {

	/**
	 * Includes widget class and return the class.
	 *
	 * @return WC_Admin_Dashboard_Finish_Setup
	 */
	public function get_widget() {
		return include __DIR__ . '/../../../../includes/admin/class-wc-admin-dashboard-finish-setup.php';
	}

	/**
	 * Return widget output (HTML).
	 *
	 * @return string Render widget HTML
	 */
	public function get_widget_output() {
		update_option( 'woocommerce_task_list_complete', false );
		update_option( 'woocommerce_task_list_hidden', false );

		ob_start();
		$this->get_widget()->render();
		return ob_get_clean();
	}

	/**
	 * Tests widget does not get rendered when woocommerce_task_list_hidden or woocommerce_task_list_hidden
	 * is true.
	 *
	 * @dataProvider should_display_widget_data_provider
	 *
	 * @param array $options a set of options.
	 */
	public function test_widget_does_not_get_rendered( array $options ) {
		global $wp_meta_boxes;

		foreach ( $options as $name => $value ) {
			update_option( $name, $value );
		}

		$this->get_widget();
		$this->assertNull( $wp_meta_boxes );
	}

	/**
	 * Given both woocommerce_task_list_hidden and woocommerce_task_list_complete are false
	 * Then the widget should be added to the $wp_meta_boxes
	 */
	public function test_widget_gets_rendered_when_both_options_are_false() {
		global $wp_meta_boxes;
		update_option( 'woocommerce_task_list_complete', false );
		update_option( 'woocommerce_task_list_hidden', false );

		$this->get_widget();
		$this->assertArrayHasKey( 'wc_admin_dasbharod_finish_setup', $wp_meta_boxes['dashboard']['normal']['high'] );
	}

	/**
	 * Tests the widget output when 0 task has been completed.
	 */
	public function test_initial_widget_output() {
		$html = $this->get_widget_output();

		$required_strings = array(
			'Step 0 of 5',
			'You&#039;re almost there! Once you complete store setup you can start receiving orders.',
			'Start selling',
			'admin.php\?page=wc-admin&amp;path=%2Fsetup-wizard',
		);

		foreach ( $required_strings as $required_string ) {
			$this->assertRegexp( "/${required_string}/", $html );
		}
	}

	/**
	 * Tests completed task count as it completes one by one
	 */
	public function test_widget_renders_completed_task_count() {
		$completed_tasks = array();
		$tasks           = $this->get_widget()->get_tasks();
		$tasks_count     = count( $tasks );
		foreach ( $tasks as $key => $task ) {
			array_push( $completed_tasks, $key );
			update_option( 'woocommerce_task_list_tracked_completed_tasks', $completed_tasks );
			$completed_tasks_count = count( $completed_tasks );
			// When all tasks are completed, assert that the widget output is empty.
			// As widget won't be rendered when tasks are completed.
			if ( $completed_tasks_count === $tasks_count ) {
				$this->assertEmpty( $this->get_widget_output() );
			} else {
				$this->assertRegexp( "/Step ${completed_tasks_count} of 5/", $this->get_widget_output() );
			}
		}
	}


	/**
	 * Provides dataset that controls output of `should_display_widget`
	 */
	public function should_display_widget_data_provider() {
		// these dataset should not render the widget
		// we only want to render the widget when both options are set to false.
		return array(
			array(
				array(
					'woocommerce_task_list_complete' => true,
					'woocommerce_task_list_hidden'   => false,
				),
			),
			array(
				array(
					'woocommerce_task_list_complete' => false,
					'woocommerce_task_list_hidden'   => true,
				),
			),
		);
	}
}
