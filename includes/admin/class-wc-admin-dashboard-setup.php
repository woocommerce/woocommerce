<?php
/**
 * Admin Dashboard - Setup
 *
 * @package     WooCommerce\Admin
 * @version     2.1.0
 */

use Automattic\Jetpack\Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Admin_Dashboard_Setup', false ) ) :

	/**
	 * WC_Admin_Dashboard_Setup Class.
	 */
	class WC_Admin_Dashboard_Setup {

		/**
		 * List of tasks.
		 *
		 * @var array
		 */
		private $tasks = array(
			'store_details'        => array(
				'completed'   => false,
				'button_link' => 'admin.php?page=wc-admin&path=%2Fsetup-wizard',
			),
			'products'             => array(
				'completed'   => false,
				'button_link' => 'admin.php?page=wc-admin&task=products',
			),
			'woocommerce-payments' => array(
				'completed'   => false,
				'button_link' => 'admin.php?page=wc-admin&path=%2Fpayments%2Fconnect',
			),
			'payments'             => array(
				'completed'   => false,
				'button_link' => 'admin.php?page=wc-admin&task=payments',
			),
			'tax'                  => array(
				'completed'   => false,
				'button_link' => 'admin.php?page=wc-admin&task=tax',
			),
			'shipping'             => array(
				'completed'   => false,
				'button_link' => 'admin.php?page=wc-admin&task=shipping',
			),
			'appearance'           => array(
				'completed'   => false,
				'button_link' => 'admin.php?page=wc-admin&task=appearance',
			),
		);

		/**
		 * # of completed tasks.
		 *
		 * @var int
		 */
		private $completed_tasks_count = 0;

		/**
		 * WC_Admin_Dashboard_Setup constructor.
		 */
		public function __construct() {
			if ( $this->should_display_widget() ) {
				$this->populate_general_tasks();
				$this->populate_payment_tasks();
				$this->completed_tasks_count = $this->get_completed_tasks_count();
				add_meta_box(
					'wc_admin_dashboard_setup',
					__( 'WooCommerce Setup', 'woocommerce' ),
					array( $this, 'render' ),
					'dashboard',
					'normal',
					'high'
				);
			}
		}

		/**
		 * Render meta box output.
		 */
		public function render() {
			$version = Constants::get_constant( 'WC_VERSION' );
			wp_enqueue_style( 'wc-dashboard-setup', WC()->plugin_url() . '/assets/css/dashboard-setup.css', array(), $version );

			$task = $this->get_next_task();
			if ( ! $task ) {
				return;
			}

			$button_link           = $task['button_link'];
			$completed_tasks_count = $this->completed_tasks_count;
			$tasks_count           = count( $this->tasks );

			// Given 'r' (circle element's r attr), dashoffset = ((100-$desired_percentage)/100) * PI * (r*2).
			$progress_percentage = ( $completed_tasks_count / $tasks_count ) * 100;
			$circle_r            = 6.5;
			$circle_dashoffset   = ( ( 100 - $progress_percentage ) / 100 ) * ( pi() * ( $circle_r * 2 ) );

			include __DIR__ . '/views/html-admin-dashboard-setup.php';
		}

		/**
		 * Populate tasks from the database.
		 */
		private function populate_general_tasks() {
			$tasks = get_option( 'woocommerce_task_list_tracked_completed_tasks', array() );
			foreach ( $tasks as $task ) {
				if ( isset( $this->tasks[ $task ] ) ) {
					$this->tasks[ $task ]['completed']   = true;
					$this->tasks[ $task ]['button_link'] = wc_admin_url( $this->tasks[ $task ]['button_link'] );
				}
			}
		}

		/**
		 * Getter for $tasks
		 *
		 * @return array
		 */
		public function get_tasks() {
			return $this->tasks;
		}

		/**
		 * Return # of completed tasks
		 */
		public function get_completed_tasks_count() {
			$completed_tasks = array_filter(
				$this->tasks,
				function( $task ) {
					return $task['completed'];
				}
			);

			return count( $completed_tasks );
		}

		/**
		 * Get the next task.
		 *
		 * @return array|null
		 */
		private function get_next_task() {
			foreach ( $this->get_tasks() as $task ) {
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
			return WC()->is_wc_admin_active() &&
				'yes' !== get_option( 'woocommerce_task_list_complete' ) &&
				'yes' !== get_option( 'woocommerce_task_list_hidden' );
		}

		/**
		 * Populate payment tasks's visibility and completion
		 */
		private function populate_payment_tasks() {
			$is_woo_payment_installed = is_plugin_active( 'woocommerce-payments/woocommerce-payments.php' );
			$country                  = explode( ':', get_option( 'woocommerce_default_country', 'US:CA' ) )[0];

			// woocommerce-payments requires its plugin activated and country must be US.
			if ( ! $is_woo_payment_installed || 'US' !== $country ) {
				unset( $this->tasks['woocommerce-payments'] );
			}

			// payments can't be used when woocommerce-payments exists and country is US.
			if ( $is_woo_payment_installed && 'US' === $country ) {
				unset( $this->tasks['payments'] );
			}

			if ( isset( $this->tasks['payments'] ) ) {
				$gateways                             = WC()->payment_gateways->get_available_payment_gateways();
				$enabled_gateways                     = array_filter(
					$gateways,
					function ( $gateway ) {
						return 'yes' === $gateway->enabled;
					}
				);
				$this->tasks['payments']['completed'] = ! empty( $enabled_gateways );
			}

			if ( isset( $this->tasks['woocommerce-payments'] ) ) {
				$wc_pay_is_connected = false;
				if ( class_exists( '\WC_Payments' ) ) {
					$wc_payments_gateway = \WC_Payments::get_gateway();
					$wc_pay_is_connected = method_exists( $wc_payments_gateway, 'is_connected' )
						? $wc_payments_gateway->is_connected()
						: false;
				}
				$this->tasks['woocommerce-payments']['completed'] = $wc_pay_is_connected;
			}
		}
	}

endif;

return new WC_Admin_Dashboard_Setup();
