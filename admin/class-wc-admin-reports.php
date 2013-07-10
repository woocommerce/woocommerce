<?php
/**
 * Admin Reports
 *
 * Functions used for displaying sales and customer reports in admin.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Reports
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WC_Admin_Reports Class
 */
class WC_Admin_Reports {

	private $start_date;
	private $end_date;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'admin_menu', array( $this, 'add_menu_item' ), 20 );
		add_filter( 'woocommerce_screen_ids', array( $this, 'add_screen_id' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
	}

	/**
	 * Add menu item
	 */
	public function add_menu_item() {
		add_submenu_page( 'woocommerce', __( 'Reports', 'woocommerce' ),  __( 'Reports', 'woocommerce' ) , 'view_woocommerce_reports', 'wc_reports', array( $this, 'admin_page' ) );
	}

	/**
	 * Add screen ID
	 * @param array $ids
	 */
	public function add_screen_id( $ids ) {
		$wc_screen_id = strtolower( __( 'WooCommerce', 'woocommerce' ) );
		$ids[]        = $wc_screen_id . '_page_wc_reports';
		return $ids;
	}

	/**
	 * Script and styles
	 */
	public function scripts_and_styles() {
		$screen       = get_current_screen();
		$wc_screen_id = strtolower( __( 'WooCommerce', 'woocommerce' ) );
		$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( in_array( $screen->id, apply_filters( 'woocommerce_reports_screen_ids', array( $wc_screen_id . '_page_wc_reports' ) ) ) ) {
			wp_enqueue_script( 'wc-reports', WC()->plugin_url() . '/assets/js/admin/reports' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker' ), '1.0' );
			wp_enqueue_script( 'flot', WC()->plugin_url() . '/assets/js/admin/jquery.flot' . $suffix . '.js', array( 'jquery' ), '1.0' );
			wp_enqueue_script( 'flot-resize', WC()->plugin_url() . '/assets/js/admin/jquery.flot.resize' . $suffix . '.js', array('jquery', 'flot'), '1.0' );
			wp_enqueue_script( 'flot-time', WC()->plugin_url() . '/assets/js/admin/jquery.flot.time' . $suffix . '.js', array( 'jquery', 'flot' ), '1.0' );
			wp_enqueue_script( 'flot-pie', WC()->plugin_url() . '/assets/js/admin/jquery.flot.pie' . $suffix . '.js', array( 'jquery', 'flot' ), '1.0' );
			wp_enqueue_script( 'flot-stack', WC()->plugin_url() . '/assets/js/admin/jquery.flot.stack' . $suffix . '.js', array( 'jquery', 'flot' ), '1.0' );
		}
	}

	/**
	 * Returns the definitions for the reports to show in admin.
	 *
	 * @return array
	 */
	public function get_reports() {
		$reports = array(
			'orders'     => array(
				'title'  => __( 'Orders', 'woocommerce' ),
				'reports' => array(
					"sales_by_date"    => array(
						'title'       => __( 'Sales by date', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
					"sales_by_product"     => array(
						'title'       => __( 'Sales by product', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
					"sales_by_category" => array(
						'title'       => __( 'Sales by category', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
					"coupon_usage" => array(
						'title'       => __( 'Coupons by date', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					)
				)
			),
			'customers' => array(
				'title'  => __( 'Customers', 'woocommerce' ),
				'reports' => array(
					"customers" => array(
						'title'       => __( 'Customers vs. Guests', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
					"customer_list" => array(
						'title'       => __( 'Customer List', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
				)
			),
			'stock'     => array(
				'title'  => __( 'Stock', 'woocommerce' ),
				'reports' => array(
					"low_in_stock" => array(
						'title'       => __( 'Low in stock', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
					"out_of_stock" => array(
						'title'       => __( 'Out of stock', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
					"most_stocked" => array(
						'title'       => __( 'Most Stocked', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
				)
			)
		);

		if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) {
			$reports['taxes'] = array(
				'title'  => __( 'Tax', 'woocommerce' ),
				'reports' => array(
					"taxes_by_code" => array(
						'title'       => __( 'Taxes by code', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
					"taxes_by_date" => array(
						'title'       => __( 'Taxes by date', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
				)
			);
		}

		$reports = apply_filters( 'woocommerce_admin_reports', $reports );

		// Backwards compat
		$reports = apply_filters( 'woocommerce_reports_charts', $reports );

		foreach ( $reports as $key => $report_group ) {
			if ( isset( $reports[ $key ]['charts'] ) )
				$reports[ $key ]['reports'] = $reports[ $key ]['charts'];

			foreach ( $reports[ $key ]['reports'] as $report_key => $report ) {
				if ( isset( $reports[ $key ]['reports'][ $report_key ]['function'] ) )
					$reports[ $key ]['reports'][ $report_key ]['callback'] = $reports[ $key ]['reports'][ $report_key ]['function'];
			}
		}

		return $reports;
	}

	/**
	 * Handles output of the reports page in admin.
	 */
	public function admin_page() {
		$reports        = $this->get_reports();
		$first_tab      = array_keys( $reports );
		$current_tab    = ! empty( $_GET['tab'] ) ? sanitize_title( urldecode( $_GET['tab'] ) ) : $first_tab[0];
		$current_report = isset( $_GET['report'] ) ? sanitize_title( urldecode( $_GET['report'] ) ) : current( array_keys( $reports[ $current_tab ]['reports'] ) );

		include_once( 'reports/class-wc-admin-report.php' );
		include_once( 'views/html-admin-page-reports.php' );
	}

	/**
	 * Get a report from our reports subfolder
	 */
	public function get_report( $name ) {
		$name  = sanitize_title( str_replace( '_', '-', $name ) );
		$class = 'WC_Report_' . str_replace( '-', '_', $name );

		include_once( 'reports/class-wc-report-' . $name . '.php' );

		if ( ! class_exists( $class ) )
			return;

		$report = new $class();
		$report->output_report();
	}
}

new WC_Admin_Reports();