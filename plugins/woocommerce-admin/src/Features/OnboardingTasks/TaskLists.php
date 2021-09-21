<?php
/**
 * Handles storage and retrieval of task lists
 */

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks;

use Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats\DataStore as TaxDataStore;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\Onboarding;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Init as OnboardingTasks;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\StoreDetails;
use Automattic\WooCommerce\Admin\Features\RemoteFreeExtensions\Init as RemoteFreeExtensions;
use Automattic\WooCommerce\Admin\PluginsHelper;

/**
 * Task Lists class.
 */
class TaskLists {
	/**
	 * Class instance.
	 *
	 * @var TaskLists instance
	 */
	protected static $instance = null;

	/**
	 * An array of all registered lists.
	 *
	 * @var array
	 */
	protected static $lists = array();

	/**
	 * Get class instance.
	 */
	final public static function instance() {
		if ( ! static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Add a task list.
	 *
	 * @param array $args Task list properties.
	 * @return WP_Error|Task
	 */
	public static function add_list( $args ) {
		if ( isset( self::$lists[ $args['id'] ] ) ) {
			return new \WP_Error(
				'woocommerce_task_list_exists',
				__( 'Task list ID already exists', 'woocommerce-admin' )
			);
		}

		self::$lists[ $args['id'] ] = new TaskList( $args );
	}

	/**
	 * Add task to a given task list.
	 *
	 * @param string $list_id List ID to add the task to.
	 * @param array  $args Task properties.
	 * @return WP_Error|Task
	 */
	public static function add_task( $list_id, $args ) {
		if ( ! isset( self::$lists[ $list_id ] ) ) {
			return new \WP_Error(
				'woocommerce_task_list_invalid_list',
				__( 'Task list ID does not exist', 'woocommerce-admin' )
			);
		}

		self::$lists[ $list_id ]->add_task( $args );
	}

	/**
	 * Add default task lists.
	 */
	public static function add_defaults() {
		self::add_list(
			array(
				'id'    => 'setup',
				'title' => __( 'Get ready to start selling', 'woocommerce-admin' ),
			)
		);

		self::add_task( 'setup', StoreDetails::get_task() );
	}

	/**
	 * Get all task lists.
	 *
	 * @return array
	 */
	public static function get_all() {
		$profiler_data         = get_option( Onboarding::PROFILE_DATA_OPTION, array() );
		$installed_plugins     = PluginsHelper::get_installed_plugin_slugs();
		$product_types         = isset( $profiler_data['product_types'] ) ? $profiler_data['product_types'] : array();
		$allowed_product_types = Onboarding::get_allowed_product_types();
		$purchaseable_products = array();
		$remaining_products    = array();

		foreach ( $product_types as $product_type ) {

			if ( ! isset( $allowed_product_types[ $product_type ]['slug'] ) ) {
				continue;
			}

			$purchaseable_products[] = $allowed_product_types[ $product_type ];

			if ( ! in_array( $allowed_product_types[ $product_type ]['slug'], $installed_plugins, true ) ) {
				$remaining_products[] = $allowed_product_types[ $product_type ]['label'];
			}
		}

		$business_extensions = isset( $profiler_data['business_extensions'] ) ? $profiler_data['business_extensions'] : array();
		$product_query       = new \WC_Product_Query(
			array(
				'limit'  => 1,
				'return' => 'ids',
				'status' => array( 'publish' ),
			)
		);
		$products            = $product_query->get_products();
		$wc_pay_is_connected = false;
		if ( class_exists( '\WC_Payments' ) ) {
			$wc_payments_gateway = \WC_Payments::get_gateway();
			$wc_pay_is_connected = method_exists( $wc_payments_gateway, 'is_connected' )
				? $wc_payments_gateway->is_connected()
				: false;
		}
		$gateways                = WC()->payment_gateways->get_available_payment_gateways();
		$enabled_gateways        = array_filter(
			$gateways,
			function( $gateway ) {
				return 'yes' === $gateway->enabled;
			}
		);
		$can_use_automated_taxes = ! class_exists( 'WC_Taxjar' ) &&
			in_array( WC()->countries->get_base_country(), OnboardingTasks::get_automated_tax_supported_countries(), true );

		$marketing_extension_bundles        = RemoteFreeExtensions::get_extensions(
			array(
				'reach',
				'grow',
			)
		);
		$has_installed_marketing_extensions = array_reduce(
			$marketing_extension_bundles,
			function( $has_installed, $bundle ) {
				if ( $has_installed ) {
					return true;
				}
				foreach ( $bundle['plugins'] as $plugin ) {
					if ( $plugin->is_installed ) {
						return true;
					}
				}
				return false;
			},
			false
		);

		$task_lists = array(
			array(
				'id'         => 'setup',
				'isComplete' => get_option( 'woocommerce_task_list_complete' ) === 'yes',
				'title'      => __( 'Get ready to start selling', 'woocommerce-admin' ),
				'tasks'      => array(
					array(
						'id'          => 'store_details',
						'title'       => __( 'Store details', 'woocommerce-admin' ),
						'content'     => __(
							'Your store address is required to set the origin country for shipping, currencies, and payment options.',
							'woocommerce-admin'
						),
						'actionLabel' => __( "Let's go", 'woocommerce-admin' ),
						'actionUrl'   => '/setup-wizard',
						'isComplete'  => isset( $profiler_data['completed'] ) && true === $profiler_data['completed'],
						'isVisible'   => true,
						'time'        => __( '4 minutes', 'woocommerce-admin' ),
					),
					array(
						'id'            => 'purchase',
						'title'         => count( $remaining_products ) === 1
							? sprintf(
								/* translators: %1$s: list of product names comma separated, %2%s the last product name */
								__(
									'Add %s to my store',
									'woocommerce-admin'
								),
								$remaining_products[0]
							)
							: __(
								'Add paid extensions to my store',
								'woocommerce-admin'
							),
						'content'       => count( $remaining_products ) === 1
							? $purchaseable_products[0]['description']
							: sprintf(
								/* translators: %1$s: list of product names comma separated, %2%s the last product name */
								__(
									'Good choice! You chose to add %1$s and %2$s to your store.',
									'woocommerce-admin'
								),
								implode( ', ', array_slice( $remaining_products, 0, -1 ) ) . ( count( $remaining_products ) > 2 ? ',' : '' ),
								end( $remaining_products )
							),
						'actionLabel'   => __( 'Purchase & install now', 'woocommerce-admin' ),
						'actionUrl'     => '/setup-wizard',
						'isComplete'    => count( $remaining_products ) === 0,
						'isVisible'     => count( $purchaseable_products ) > 0,
						'time'          => __( '2 minutes', 'woocommerce-admin' ),
						'isDismissable' => true,
					),
					array(
						'id'         => 'products',
						'title'      => __( 'Add my products', 'woocommerce-admin' ),
						'content'    => __(
							'Start by adding the first product to your store. You can add your products manually, via CSV, or import them from another service.',
							'woocommerce-admin'
						),
						'isComplete' => 0 !== count( $products ),
						'isVisible'  => true,
						'time'       => __( '1 minute per product', 'woocommerce-admin' ),
					),
					array(
						'id'          => 'woocommerce-payments',
						'title'       => __( 'Get paid with WooCommerce Payments', 'woocommerce-admin' ),
						'content'     => __(
							"You're only one step away from getting paid. Verify your business details to start managing transactions with WooCommerce Payments.",
							'woocommerce-admin'
						),
						'actionLabel' => __( 'Finish setup', 'woocommerce-admin' ),
						'expanded'    => true,
						'isComplete'  => $wc_pay_is_connected,
						'isVisible'   => in_array( 'woocommerce-payments', $business_extensions, true ) &&
							in_array( 'woocommerce-payments', $installed_plugins, true ) &&
							in_array( WC()->countries->get_base_country(), OnboardingTasks::get_woocommerce_payments_supported_countries(), true ),
						'time'        => __( '2 minutes', 'woocommerce-admin' ),
					),
					array(
						'id'         => 'payments',
						'title'      => __( 'Set up payments', 'woocommerce-admin' ),
						'content'    => __(
							'Choose payment providers and enable payment methods at checkout.',
							'woocommerce-admin'
						),
						'isComplete' => ! empty( $enabled_gateways ),
						'isVisible'  => Features::is_enabled( 'payment-gateway-suggestions' ) &&
							(
								! in_array( 'woocommerce-payments', $business_extensions, true ) ||
								! in_array( 'woocommerce-payments', $installed_plugins, true ) ||
								! in_array( WC()->countries->get_base_country(), OnboardingTasks::get_woocommerce_payments_supported_countries(), true )
							),
						'time'       => __( '2 minutes', 'woocommerce-admin' ),
					),
					array(
						'id'          => 'tax',
						'title'       => __( 'Set up tax', 'woocommerce-admin' ),
						'content'     => $can_use_automated_taxes
							? __(
								'Good news! WooCommerce Services and Jetpack can automate your sales tax calculations for you.',
								'woocommerce-admin'
							)
							: __(
								'Set your store location and configure tax rate settings.',
								'woocommerce-admin'
							),
						'actionLabel' => $can_use_automated_taxes
							? __( 'Yes please', 'woocommerce-admin' )
							: __( "Let's go", 'woocommerce-admin' ),
						'isComplete'  => get_option( 'wc_connect_taxes_enabled' ) ||
							count( TaxDataStore::get_taxes( array() ) ) > 0 ||
							false !== get_option( 'woocommerce_no_sales_tax' ),
						'isVisible'   => true,
						'time'        => __( '1 minute', 'woocommerce-admin' ),
					),
					array(
						'id'          => 'shipping',
						'title'       => __( 'Set up shipping', 'woocommerce-admin' ),
						'content'     => __(
							"Set your store location and where you'll ship to.",
							'woocommerce-admin'
						),
						'actionUrl'   => count( \WC_Shipping_Zones::get_zones() ) > 0
							? admin_url( 'admin.php?page=wc-settings&tab=shipping' )
							: null,
						'actionLabel' => __( "Let's go", 'woocommerce-admin' ),
						'isComplete'  => count( \WC_Shipping_Zones::get_zones() ) > 0,
						'isVisible'   => in_array( 'physical', $product_types, true ) ||
							count(
								wc_get_products(
									array(
										'virtual' => false,
										'limit'   => 1,
									)
								)
							) > 0,
						'time'        => __( '1 minute', 'woocommerce-admin' ),
					),
					array(
						'id'         => 'marketing',
						'title'      => __( 'Set up marketing tools', 'woocommerce-admin' ),
						'content'    => __(
							'Add recommended marketing tools to reach new customers and grow your business',
							'woocommerce-admin'
						),
						'isComplete' => $has_installed_marketing_extensions,
						'isVisible'  => Features::is_enabled( 'remote-free-extensions' ) && count( $marketing_extension_bundles ) > 0,
						'time'       => __( '1 minute', 'woocommerce-admin' ),
					),
					array(
						'id'          => 'appearance',
						'title'       => __( 'Personalize my store', 'woocommerce-admin' ),
						'content'     => __(
							'Add your logo, create a homepage, and start designing your store.',
							'woocommerce-admin'
						),
						'actionLabel' => __( "Let's go", 'woocommerce-admin' ),
						'isComplete'  => get_option( 'woocommerce_task_list_appearance_complete' ),
						'isVisible'   => true,
						'time'        => __( '2 minutes', 'woocommerce-admin' ),
					),
				),
			),
		);

		return apply_filters( 'woocommerce_admin_onboarding_tasks', $task_lists );
	}

	/**
	 * Get visible task lists.
	 */
	public static function get_visible() {
		return array_filter(
			self::get_all(),
			function ( $task_list ) {
				return ! $task_list['isHidden'];
			}
		);
	}


	/**
	 * Retrieve a task list by ID.
	 *
	 * @param String $id Task list ID.
	 *
	 * @return TaskList|null
	 */
	public static function get_list( $id ) {
		foreach ( self::get_all() as $task_list ) {
			if ( $task_list['id'] === $id ) {
				return new TaskList( $task_list );
			}
		}

		return null;
	}

	/**
	 * Retrieve single task.
	 *
	 * @param String $id Task ID.
	 * @param String $task_list_id Task list ID.
	 *
	 * @return Object
	 */
	public static function get_task( $id, $task_list_id = null ) {
		$task_list = $task_list_id ? self::get_task_list_by_id( $task_list_id ) : null;

		if ( $task_list_id && ! $task_list ) {
			return null;
		}

		$tasks_to_search = $task_list ? $task_list['tasks'] : array_reduce(
			self::get_all(),
			function ( $all, $curr ) {
				return array_merge( $all, $curr['tasks'] );
			},
			array()
		);

		foreach ( $tasks_to_search as $task ) {
			if ( $id === $task['id'] ) {
				return $task;
			}
		}

		return null;
	}
}
