<?php
/**
 * Init WooCommerce data exporters.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Exporters Class.
 */
class WC_Admin_Exporters {

	/**
	 * Array of exporter IDs.
	 *
	 * @var string[]
	 */
	protected $exporters = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_to_menus' ) );
		add_action( 'admin_head', array( $this, 'hide_from_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_init', array( $this, 'download_export_file' ) );
		add_action( 'wp_ajax_woocommerce_do_ajax_product_export', array( $this, 'do_ajax_product_export' ) );

		// Register WooCommerce exporters.
		$this->exporters['product_exporter'] = array(
			'menu'       => 'edit.php?post_type=product',
			'name'       => __( 'Product Export', 'woocommerce' ),
			'capability' => 'edit_products',
			'callback'   => array( $this, 'product_exporter' ),
		);
	}

	/**
	 * Add menu items for our custom exporters.
	 */
	public function add_to_menus() {
		foreach ( $this->exporters as $id => $exporter ) {
			add_submenu_page( $exporter['menu'], $exporter['name'], $exporter['name'], $exporter['capability'], $id, $exporter['callback'] );
		}
	}

	/**
	 * Hide menu items from view so the pages exist, but the menu items do not.
	 */
	public function hide_from_menus() {
		global $submenu;

		foreach ( $this->exporters as $id => $exporter ) {
			if ( isset( $submenu[ $exporter['menu'] ] ) ) {
				foreach ( $submenu[ $exporter['menu'] ] as $key => $menu ) {
					if ( $id === $menu[2] ) {
						unset( $submenu[ $exporter['menu'] ][ $key ] );
					}
				}
			}
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function admin_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'wc-product-export', WC()->plugin_url() . '/assets/js/admin/wc-product-export' . $suffix . '.js', array( 'jquery' ), WC_VERSION );
		wp_localize_script( 'wc-product-export', 'wc_product_export_params', array(
			'export_nonce' => wp_create_nonce( 'wc-product-export' ),
		) );
	}

	/**
	 * Export page UI.
	 */
	public function product_exporter() {
		include_once( WC_ABSPATH . 'includes/export/class-wc-product-csv-exporter.php' );
		include_once( dirname( __FILE__ ) . '/views/html-admin-page-product-export.php' );
	}

	/**
	 * Serve the generated file.
	 */
	public function download_export_file() {
		if ( isset( $_GET['action'], $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'product-csv' ) && 'download_product_csv' === $_GET['action'] ) {
			include_once( WC_ABSPATH . 'includes/export/class-wc-product-csv-exporter.php' );
			$exporter = new WC_Product_CSV_Exporter();
			$exporter->export();
		}
	}

	/**
	 * AJAX callback for doing the actual export to the CSV file.
	 */
	public function do_ajax_product_export() {
		check_ajax_referer( 'wc-product-export', 'security' );

		if ( ! current_user_can( 'edit_products' ) ) {
			wp_die( -1 );
		}

		include_once( WC_ABSPATH . 'includes/export/class-wc-product-csv-exporter.php' );

		$step     = absint( $_POST['step'] );
		$exporter = new WC_Product_CSV_Exporter();

		if ( ! empty( $_POST['columns'] ) ) {
			$exporter->set_column_names( $_POST['columns'] );
		}

		if ( ! empty( $_POST['selected_columns'] ) ) {
			$exporter->set_columns_to_export( $_POST['selected_columns'] );
		}

		if ( ! empty( $_POST['export_meta'] ) ) {
			$exporter->enable_meta_export( true );
		}

		if ( ! empty( $_POST['export_types'] ) ) {
			$exporter->set_product_types_to_export( $_POST['export_types'] );
		}

		$exporter->set_page( $step );
		$exporter->generate_file();

		if ( 100 === $exporter->get_percent_complete() ) {
			wp_send_json_success( array(
				'step'       => 'done',
				'percentage' => 100,
				'url'        => add_query_arg( array( 'nonce' => wp_create_nonce( 'product-csv' ), 'action' => 'download_product_csv' ), admin_url( 'edit.php?post_type=product&page=product_exporter' ) ),
			) );
		} else {
			wp_send_json_success( array(
				'step'       => ++$step,
				'percentage' => $exporter->get_percent_complete(),
				'columns'    => $exporter->get_column_names(),
			) );
		}
	}
}

new WC_Admin_Exporters();
