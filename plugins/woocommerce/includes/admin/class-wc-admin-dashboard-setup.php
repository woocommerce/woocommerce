<?php
/**
 * Admin Dashboard - Setup
 *
 * @package     WooCommerce\Admin
 * @version     2.1.0
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Admin_Dashboard_Setup', false ) ) :

	/**
	 * WC_Admin_Dashboard_Setup Class.
	 */
	class WC_Admin_Dashboard_Setup {

		/**
		 * Check for task list initialization.
		 */
		private bool $initialized = false;

		/**
		 * The task list.
		 */
		private $task_list = null;

		/**
		 * The tasks.
		 */
		private $tasks = null;

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

			$button_link            = $this->get_button_link( $task );
			$task_header            = $this->get_task_header( $task );
			$task_is_in_progress    = $task->is_in_progress();
			$task_in_progress_label = $task_is_in_progress ? $task->in_progress_label() : '';
			$completed_tasks_count  = $this->get_completed_tasks_count();
			$step_number            = $completed_tasks_count + 1;
			$tasks_count            = count( $this->get_tasks() );

			// Given 'r' (circle element's r attr), dashoffset = ((100-$desired_percentage)/100) * PI * (r*2).
			$progress_percentage = ( $completed_tasks_count / $tasks_count ) * 100;
			$circle_r            = 6.5;
			$circle_dashoffset   = ( ( 100 - $progress_percentage ) / 100 ) * ( pi() * ( $circle_r * 2 ) );

			include __DIR__ . '/views/html-admin-dashboard-setup.php';
		}

		/**
		 * Get dashboard widget header data for a task.
		 *
		 * @param Task $task Task.
		 * @return array
		 */
		private function get_task_header( $task ) {
			$asset_url    = WC()->plugin_url() . '/assets/';
			$task_json    = $task->get_json();
			$task_headers = array(
				'store_details'        => array(
					'title'        => __( 'First, tell us about your store', 'woocommerce' ),
					'content'      => __( 'Get your store up and running in no time. Add your store’s address to set up shipping, tax and payments faster.', 'woocommerce' ),
					'button_label' => __( 'Add details', 'woocommerce' ),
					'image_url'    => $asset_url . 'images/task_list/store-details-illustration.png',
					'image_alt'    => __( 'Store location illustration', 'woocommerce' ),
				),
				'customize-store'      => array(
					'title'        => __( 'Start customizing your store', 'woocommerce' ),
					'content'      => __( 'Quickly create a beautiful looking store using our built-in store designer, or select a pre-built theme and customize it to fit your brand.', 'woocommerce' ),
					'button_label' => __( 'Start customizing', 'woocommerce' ),
					'image_url'    => $asset_url . 'images/task_list/customize-store-illustration.svg',
					'image_alt'    => __( 'Customize your store illustration', 'woocommerce' ),
				),
				'tax'                  => array(
					'title'        => __( 'Configure your tax settings', 'woocommerce' ),
					'content'      => __( 'Choose to set up your tax rates manually, or use one of our tax automation tools.', 'woocommerce' ),
					'button_label' => __( 'Collect sales tax', 'woocommerce' ),
					'image_url'    => $asset_url . 'images/task_list/tax-illustration.svg',
					'image_alt'    => __( 'Tax illustration', 'woocommerce' ),
				),
				'shipping'             => array(
					'title'        => __( 'Get your products shipped', 'woocommerce' ),
					'content'      => __( 'Choose where and how you’d like to ship your products, along with any fixed or calculated rates.', 'woocommerce' ),
					'button_label' => __( 'Start shipping', 'woocommerce' ),
					'image_url'    => $asset_url . 'images/task_list/shipping-illustration.svg',
					'image_alt'    => __( 'Shipping illustration', 'woocommerce' ),
				),
				'marketing'            => array(
					'title'        => __( 'Reach more customers', 'woocommerce' ),
					'content'      => __( 'Start growing your business by showcasing your products on social media and Google, boost engagement with email marketing, and more!', 'woocommerce' ),
					'button_label' => __( 'Grow your business', 'woocommerce' ),
					'image_url'    => $asset_url . 'images/task_list/sales-illustration.svg',
					'image_alt'    => __( 'Marketing illustration', 'woocommerce' ),
				),
				'payments'             => array(
					'title'        => __( 'It’s time to get paid', 'woocommerce' ),
					'content'      => __( 'Give your customers an easy and convenient way to pay! Set up one (or more!) of our fast and secure online or in person payment methods.', 'woocommerce' ),
					'button_label' => __( 'Get paid', 'woocommerce' ),
					'image_url'    => $asset_url . 'images/task_list/payment-illustration.svg',
					'image_alt'    => __( 'Payment illustration', 'woocommerce' ),
				),
				'woocommerce-payments' => array(
					'title'        => __( 'It’s time to get paid', 'woocommerce' ),
					'content'      => __( 'Power your payments with a simple, all-in-one option. Verify your business details to start managing transactions with WooCommerce Payments.', 'woocommerce' ),
					'button_label' => __( 'Get paid', 'woocommerce' ),
					'image_url'    => $asset_url . 'images/task_list/payment-illustration.svg',
					'image_alt'    => __( 'Payment illustration', 'woocommerce' ),
				),
				'products'             => array(
					'title'        => __( 'List your products', 'woocommerce' ),
					'content'      => __( 'Start selling by adding products or services to your store. Choose to list products manually, or import them from a different store.', 'woocommerce' ),
					'button_label' => __( 'Add products', 'woocommerce' ),
					'image_url'    => $asset_url . 'images/task_list/sales-section-illustration.svg',
					'image_alt'    => __( 'Products illustration', 'woocommerce' ),
				),
				'purchase'             => array(
					'title'        => $task->get_title(),
					'content'      => __( 'Good choice! You chose to add amazing new features to your store. Continue to checkout to complete your purchase.', 'woocommerce' ),
					'button_label' => __( 'Continue', 'woocommerce' ),
					'image_url'    => $asset_url . 'images/task_list/purchase-illustration.png',
					'image_alt'    => __( 'Purchase illustration', 'woocommerce' ),
				),
				'launch-your-store'    => array(
					'title'        => __( 'Your store is ready for launch!', 'woocommerce' ),
					'content'      => __( 'It’s time to celebrate – you’re ready to launch your store! Woo! Hit the button to preview your store and make it public.', 'woocommerce' ),
					'button_label' => __( 'Launch store', 'woocommerce' ),
					'image_url'    => $asset_url . 'images/task_list/launch-your-store-illustration.svg',
					'image_alt'    => __( 'Launch your store illustration', 'woocommerce' ),
				),
			);

			return $task_headers[ $task->get_id() ] ?? array(
				'title'        => $task->get_title(),
				'content'      => $task_json['content'] ?? '',
				'button_label' => $task_json['actionLabel'] ?? $task->get_title(),
				'image_url'    => $asset_url . 'images/dashboard-widget-setup.png',
				'image_alt'    => __( 'WooCommerce setup illustration', 'woocommerce' ),
			);
		}

		/**
		 * Get the button link for a given task.
		 *
		 * @param Task $task Task.
		 * @return string
		 */
		public function get_button_link( $task ) {
			// Check if core profiler needs completion and redirect to it.
			if ( class_exists( OnboardingProfile::class ) ) {
				if ( OnboardingProfile::needs_completion() ) {
					return wc_admin_url( '&path=/setup-wizard' );
				}
			}

			$url = (string) $task->get_json()['actionUrl'];

			if ( substr( $url, 0, 4 ) === 'http' ) {
				return $url;
			} elseif ( $url ) {
				return wc_admin_url( '&path=' . $url );
			}

			return admin_url( 'admin.php?page=wc-admin&task=' . $task->get_id() );
		}

		/**
		 * Get the task list.
		 *
		 * @return array
		 */
		public function get_task_list() {
			if ( $this->task_list || $this->initialized ) {
				return $this->task_list;
			}

			$this->set_task_list( TaskLists::get_list( 'setup' ) );
			$this->initialized = true;
			return $this->task_list;
		}

		/**
		 * Set the task list.
		 */
		public function set_task_list( $task_list ) {
			return $this->task_list = $task_list;
		}

		/**
		 * Get the tasks.
		 *
		 * @return array
		 */
		public function get_tasks() {
			if ( $this->tasks ) {
				return $this->tasks;
			}

			$this->tasks = $this->get_task_list()->get_viewable_tasks();
			return $this->tasks;
		}

		/**
		 * Return # of completed tasks
		 *
		 * @return integer
		 */
		public function get_completed_tasks_count() {
			$completed_tasks = array_filter(
				$this->get_tasks(),
				function( $task ) {
					return $task->is_complete();
				}
			);

			return count( $completed_tasks );
		}

		/**
		 * Get the next task.
		 *
		 * @return Task|null
		 */
		private function get_next_task() {
			foreach ( $this->get_tasks() as $task ) {
				if ( false === $task->is_complete() ) {
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
		public function should_display_widget() {
			if ( ! class_exists( 'Automattic\WooCommerce\Admin\Features\Features' ) || ! class_exists( 'Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists' ) ) {
				return false;
			}

			if ( ! Features::is_enabled( 'onboarding' ) || ! WC()->is_wc_admin_active() ) {
				return false;
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return false;
			}

			if ( ! $this->get_task_list() || $this->get_task_list()->is_hidden() || $this->get_task_list()->is_complete() ) {
				return false;
			}

			return true;
		}

	}

endif;

return new WC_Admin_Dashboard_Setup();
