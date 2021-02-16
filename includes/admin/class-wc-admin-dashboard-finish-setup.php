<?php
/**
 * Admin Dashboard - Finish Setup
 *
 * @package     WooCommerce\Admin
 * @version     2.1.0
 */

use Automattic\Jetpack\Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Admin_Dashboard_Finish_Setup', false ) ) :

	/**
	 * WC_Admin_Dashboard_Setup Class.
	 */
	class WC_Admin_Dashboard_Finish_Setup {

		/**
		 * List of tasks.
		 *
		 * @var array
		 */
		private $tasks = array(
			'store_details' => array(
				'completed'   => false,
				'button_link' => 'admin.php?page=wc-admin&path=%2Fsetup-wizard',
			),
			'products'      => array(
				'completed'   => false,
				'button_link' => 'admin.php?page=wc-admin&task=products',
			),
			'tax'           => array(
				'completed'   => false,
				'button_link' => 'admin.php?page=wc-admin&task=tax',
			),
			'shipping'      => array(
				'completed'   => false,
				'button_link' => 'admin.php?page=wc-admin&task=shipping',
			),
			'appearance'    => array(
				'completed'   => false,
				'button_link' => 'admin.php?page=wc-admin&task=appearance',
			),
		);

		/**
		 * WC_Admin_Dashboard_Finish_Setup constructor.
		 */
		public function __construct() {
			if ( $this->should_display_widget() ) {
				$this->populate_tasks();
				$this->hook_meta_box();
			}
		}

		/**
		 * Hook meta_box
		 */
		public function hook_meta_box() {
			$version = Constants::get_constant( 'WC_VERSION' );

			wp_enqueue_style( 'wc-dashboard-finish-setup', WC()->plugin_url() . '/assets/css/dashboard-finish-setup.css', array(), $version );

			add_meta_box(
				'wc_admin_dasbharod_finish_setup',
				__( 'WooCommerce Setup', 'woocommerce' ),
				array( $this, 'render_meta_box' ),
				'dashboard',
				'normal',
				'high'
			);
		}

		/**
		 * Render meta box output.
		 */
		public function render_meta_box() {
			$total_number_of_tasks           = count( $this->tasks );
			$total_number_of_completed_tasks = count( $this->get_completed_tasks() );

			$task = $this->get_next_task();
			if ( ! $task ) {
				return;
			}

			$button_link = $task['button_link'];

			// Given 'r' (circle element's r attr), dashoffset = ((100-$desired_percentage)/100) * PI * (r*2).
			$progress_percentage = ( $total_number_of_completed_tasks / $total_number_of_tasks ) * 100;
			$circle_r            = 6.5;
			$circle_dashoffset   = ( ( 100 - $progress_percentage ) / 100 ) * ( pi() * ( $circle_r * 2 ) );

			require_once __DIR__ . '/views/html-admin-dashboard-finish-setup.php';
		}

		/**
		 * Populate tasks from the database.
		 */
		private function populate_tasks() {
			$tasks = get_option( 'woocommerce_task_list_tracked_completed_tasks', array() );
			foreach ( $tasks as $task ) {
				if ( isset( $this->tasks[ $task ] ) ) {
					$this->tasks[ $task ]['completed']   = true;
					$this->tasks[ $task ]['button_link'] = wc_admin_url( $this->tasks[ $task ]['button_link'] );
				}
			}
		}

		/**
		 * Return completed tasks
		 *
		 * @return array
		 */
		private function get_completed_tasks() {
			return array_filter(
				$this->tasks,
				function( $task ) {
					return $task['completed'];
				}
			);
		}

		/**
		 * Get the next task.
		 *
		 * @return array|null
		 */
		private function get_next_task() {
			foreach ( $this->tasks as $task ) {
				if ( false === $task['completed'] ) {
					return $task;
				}
			}

			return null;
		}

		/**
		 * Check to see if we should display the widget
		 *
		 * @return bool
		 */
		private function should_display_widget() {
			return true !== get_option( 'woocommerce_task_list_complete' ) && true !== get_option( 'woocommerce_task_list_hidden' );
		}
	}

endif;

return new WC_Admin_Dashboard_Finish_Setup();
