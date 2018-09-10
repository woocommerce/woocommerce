<?php
/**
 * REST API Reports controller
 *
 * Handles requests to the reports endpoint.
 *
 * @package WooCommerce/API
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Reports controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Reports_V2_Controller
 */
class WC_REST_Reports_Controller extends WC_REST_Reports_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Get reports list.
	 *
	 * @since 3.5.0
	 * @return array
	 */
	protected function get_reports() {
		$reports = parent::get_reports();

		$reports[] = array(
			'slug'        => 'orders/count',
			'description' => __( 'Orders stats count.', 'woocommerce' ),
		);
		$reports[] = array(
			'slug'        => 'products/count',
			'description' => __( 'Customers stats count.', 'woocommerce' ),
		);
		$reports[] = array(
			'slug'        => 'customers/count',
			'description' => __( 'Customers stats count.', 'woocommerce' ),
		);
		$reports[] = array(
			'slug'        => 'coupons/count',
			'description' => __( 'Coupons stats count.', 'woocommerce' ),
		);
		$reports[] = array(
			'slug'        => 'reviews/count',
			'description' => __( 'Reviews stats count.', 'woocommerce' ),
		);
		$reports[] = array(
			'slug'        => 'categories/count',
			'description' => __( 'Categories stats count.', 'woocommerce' ),
		);
		$reports[] = array(
			'slug'        => 'tags/count',
			'description' => __( 'Tags stats count.', 'woocommerce' ),
		);
		$reports[] = array(
			'slug'        => 'attributes/count',
			'description' => __( 'Attributes stats count.', 'woocommerce' ),
		);

		return $reports;
	}
}
