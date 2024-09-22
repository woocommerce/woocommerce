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

use Automattic\WooCommerce\Utilities\ArrayUtil;

/**
 * WC_Admin_Reports Class.
 */
class WC_Admin_Reports {

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

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- We're deprecating this usage of filter. The proper one is described in `plugins/woocommerce/src/Admin/API/Reports/Controller.php`.
		$filtered_reports = apply_filters( 'woocommerce_admin_reports', $reports );
		/*
		 * Check if there is any use of the legacy `woocommerce_admin_reports` filter to send a deprecation warning.
		 * We will remove non-compliant entries twice.
		 * First, here to check if there are any reports-specific, non-analytics changes to the original array.
		 * Then send the non-sanitized array to the `woocommerce_reports_charts` filter to ensure 100% backward compatibility.
		 * Then, we will sanitize again the result of both filters.
		 */
		$filtered_legacy_reports = $filtered_reports;
		foreach ( $filtered_legacy_reports as $key => &$report_group ) {
			// Remove entries not related to reports.
			if ( ! isset( $report_group['reports'] ) ) {
				unset( $filtered_legacy_reports[ $key ] );
				continue;
			}
		}
		// deep_compare_array_diff does not check additional entries.
		$changed = ArrayUtil::deep_compare_array_diff( $reports, $filtered_legacy_reports, true ) ||
					ArrayUtil::deep_compare_array_diff( $filtered_legacy_reports, $reports, true );
		if ( $changed ) {
			if ( WP_DEBUG && apply_filters( 'deprecated_hook_trigger_error', true ) ) { // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- This is the use of the filter spefied in WP.
				$message = sprintf(
					/* translators: 1: WordPress hook name, 2: Version number. */
					__( 'The use of %1$s hook for "Reports" pages is <strong>deprecated</strong> since version %2$s. The entire Reports are deprecated and will be removed in future versions. Use Analytics instead.', 'woocommerce' ),
					'woocommerce_admin_reports',
					'9.5.0'
				);

				wp_trigger_error( '', $message, E_USER_DEPRECATED );
			}
		}

		$filtered_reports = apply_filters_deprecated(
			'woocommerce_reports_charts',
			array( $filtered_reports ),
			'9.5.0',
			null,
			'Reports are deprecated and will be removed in future versions. Use Analytics instead.',
		);

		foreach ( $filtered_reports as $key => &$report_group ) {
			// Silently ignore unrelated entries.
			if ( ! isset( $report_group['reports'] ) ) {
				unset( $filtered_reports[ $key ] );
				continue;
			}
			if ( isset( $report_group['charts'] ) ) {
				$report_group['reports'] = $report_group['charts'];
			}

			foreach ( $report_group['reports'] as &$report ) {
				if ( isset( $report['function'] ) ) {
					$report['callback'] = $report['function'];
				}
			}
		}

		return $filtered_reports;
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
