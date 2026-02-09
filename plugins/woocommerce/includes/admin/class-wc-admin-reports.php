<?php
/**
 * Admin Reports
 *
 * Functions used for displaying sales and customer reports in admin.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce\Admin\Reports
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Admin_Reports', false ) ) {
	return;
}

/**
 * WC_Admin_Reports Class.
 */
class WC_Admin_Reports {

	/**
	 * Register the proper hook handlers.
	 */
	public static function register_hook_handlers() {
		add_filter( 'woocommerce_after_dashboard_status_widget_parameter', array( __CLASS__, 'get_report_instance' ) );
		add_filter( 'woocommerce_dashboard_status_widget_reports', array( __CLASS__, 'replace_dashboard_status_widget_reports' ) );
	}

	/**
	 * Get an instance of WC_Admin_Report.
	 *
	 * @return WC_Admin_Report
	 */
	public static function get_report_instance() {
		include_once __DIR__ . '/reports/class-wc-admin-report.php';
		return new WC_Admin_Report();
	}

	/**
	 * Filter handler for replacing the data of the status widget on the Dashboard page.
	 *
	 * @param array $status_widget_reports The data to display in the status widget.
	 */
	public static function replace_dashboard_status_widget_reports( $status_widget_reports ) {
		$report = self::get_report_instance();

		include_once __DIR__ . '/reports/class-wc-report-sales-by-date.php';

		$sales_by_date                 = new WC_Report_Sales_By_Date();
		$sales_by_date->start_date     = strtotime( gmdate( 'Y-m-01', current_time( 'timestamp' ) ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$sales_by_date->end_date       = strtotime( gmdate( 'Y-m-d', current_time( 'timestamp' ) ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$sales_by_date->chart_groupby  = 'day';
		$sales_by_date->group_by_query = 'YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)';

		$status_widget_reports['net_sales_link']      = 'admin.php?page=wc-reports&tab=orders&range=month';
		$status_widget_reports['top_seller_link']     = 'admin.php?page=wc-reports&tab=orders&report=sales_by_product&range=month&product_ids=';
		$status_widget_reports['lowstock_link']       = 'admin.php?page=wc-reports&tab=stock&report=low_in_stock';
		$status_widget_reports['outofstock_link']     = 'admin.php?page=wc-reports&tab=stock&report=out_of_stock';
		$status_widget_reports['report_data']         = $sales_by_date->get_report_data();
		$status_widget_reports['get_sales_sparkline'] = array( $report, 'get_sales_sparkline' );

		return $status_widget_reports;
	}

	/**
	 * Handles output of the reports page in admin.
	 */
	public static function output() {
		$reports        = self::get_reports();
		$first_tab      = array_keys( $reports );
		$current_tab    = ! empty( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $reports ) ? sanitize_title( $_GET['tab'] ) : $first_tab[0];
		$current_report = isset( $_GET['report'] ) ? sanitize_title( $_GET['report'] ) : current( array_keys( $reports[ $current_tab ]['reports'] ) );

		include_once dirname( __FILE__ ) . '/reports/class-wc-admin-report.php';
		include_once dirname( __FILE__ ) . '/views/html-admin-page-reports.php';
	}

	/**
	 * Returns the definitions for the reports to show in admin.
	 *
	 * @return array
	 */
	public static function get_reports() {
		$reports = array(
			'orders'    => array(
				'title'   => __( 'Orders', 'woocommerce' ),
				'reports' => array(
					'sales_by_date'     => array(
						'title'       => __( 'Sales by date', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' ),
					),
					'sales_by_product'  => array(
						'title'       => __( 'Sales by product', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' ),
					),
					'sales_by_category' => array(
						'title'       => __( 'Sales by category', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' ),
					),
					'coupon_usage'      => array(
						'title'       => __( 'Coupons by date', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' ),
					),
					'downloads'         => array(
						'title'       => __( 'Customer downloads', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' ),
					),
				),
			),
			'customers' => array(
				'title'   => __( 'Customers', 'woocommerce' ),
				'reports' => array(
					'customers'     => array(
						'title'       => __( 'Customers vs. guests', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' ),
					),
					'customer_list' => array(
						'title'       => __( 'Customer list', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' ),
					),
				),
			),
			'stock'     => array(
				'title'   => __( 'Stock', 'woocommerce' ),
				'reports' => array(
					'low_in_stock' => array(
						'title'       => __( 'Low in stock', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' ),
					),
					'out_of_stock' => array(
						'title'       => __( 'Out of stock', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' ),
					),
					'most_stocked' => array(
						'title'       => __( 'Most stocked', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' ),
					),
				),
			),
		);

		if ( wc_tax_enabled() ) {
			$reports['taxes'] = array(
				'title'   => __( 'Taxes', 'woocommerce' ),
				'reports' => array(
					'taxes_by_code' => array(
						'title'       => __( 'Taxes by code', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' ),
					),
					'taxes_by_date' => array(
						'title'       => __( 'Taxes by date', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' ),
					),
				),
			);
		}

		/**
		 * Filter the list and add reports to the legacy _WooCommerce > Reports_.
		 *
		 * Array items should be in the format of
		 *
		 * $reports['automatewoo'] = array(
		 *     'title'   => 'AutomateWoo',
		 *     'reports' => array(
		 *         'runs_by_date' => array(
		 *             'title'       => __( 'Workflow Runs', 'automatewoo' ),
		 *             'description' => '',
		 *             'hide_title'  => false,
		 *             'callback'    => array( $this, 'get_runs_by_date' ),
		 *         ),
		 *         // ...
		 *     ),
		 * );
		 *
		 * This filter has a colliding name with the one in Automattic\WooCommerce\Admin\API\Reports\Controller.
		 * To make sure your code runs in the context of the legacy _WooCommerce > Reports_ screen, and not the REST endpoint,
		 * use the following:
		 *
		 * add_filter( 'woocommerce_admin_reports',
		 *     function( $reports ) {
		 *         if ( is_admin() ) {
		 *             // ...
		 *
		 * @param array $reports The associative array of reports.
		 */
		$reports = apply_filters( 'woocommerce_admin_reports', $reports );
		$reports = apply_filters( 'woocommerce_reports_charts', $reports ); // Backwards compatibility.

		foreach ( $reports as $key => &$report_group ) {
			if ( isset( $report_group['charts'] ) ) {
				$report_group['reports'] = $report_group['charts'];
			}

			// Silently ignore reports given for the filter in Automattic\WooCommerce\Admin\API\Reports\Controller.
			if ( ! isset( $report_group['reports'] ) ) {
				unset( $reports[ $key ] );
				continue;
			}

			foreach ( $report_group['reports'] as &$report ) {
				if ( isset( $report['function'] ) ) {
					$report['callback'] = $report['function'];
				}
			}
		}

		return $reports;
	}

	/**
	 * Get a report from our reports subfolder.
	 *
	 * @param string $name
	 */
	public static function get_report( $name ) {
		$name  = sanitize_title( str_replace( '_', '-', $name ) );
		$class = 'WC_Report_' . str_replace( '-', '_', $name );

		include_once apply_filters( 'wc_admin_reports_path', 'reports/class-wc-report-' . $name . '.php', $name, $class );

		if ( ! class_exists( $class ) ) {
			return;
		}

		$report = new $class();
		$report->output_report();
	}
}
