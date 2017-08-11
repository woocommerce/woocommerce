<?php
/**
 * Init WooCommerce data importers.
 *
 * @author      Automattic
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Importers Class.
 */
class WC_Admin_Importers {

	/**
	 * Array of importer IDs.
	 *
	 * @var string[]
	 */
	protected $importers = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_to_menus' ) );
		add_action( 'admin_init', array( $this, 'register_importers' ) );
		add_action( 'admin_head', array( $this, 'hide_from_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_ajax_woocommerce_do_ajax_product_import', array( $this, 'do_ajax_product_import' ) );

		// Register WooCommerce importers.
		$this->importers['product_importer'] = array(
			'menu'       => 'edit.php?post_type=product',
			'name'       => __( 'Product Import', 'woocommerce' ),
			'capability' => 'edit_products',
			'callback'   => array( $this, 'product_importer' ),
		);
	}

	/**
	 * Add menu items for our custom importers.
	 */
	public function add_to_menus() {
		foreach ( $this->importers as $id => $importer ) {
			add_submenu_page( $importer['menu'], $importer['name'], $importer['name'], $importer['capability'], $id, $importer['callback'] );
		}
	}

	/**
	 * Hide menu items from view so the pages exist, but the menu items do not.
	 */
	public function hide_from_menus() {
		global $submenu;

		foreach ( $this->importers as $id => $importer ) {
			if ( isset( $submenu[ $importer['menu'] ] ) ) {
				foreach ( $submenu[ $importer['menu'] ] as $key => $menu ) {
					if ( $id === $menu[2] ) {
						unset( $submenu[ $importer['menu'] ][ $key ] );
					}
				}
			}
		}
	}

	/**
	 * Register importer scripts.
	 */
	public function admin_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'wc-product-import', WC()->plugin_url() . '/assets/js/admin/wc-product-import' . $suffix . '.js', array( 'jquery' ), WC_VERSION );
	}

	/**
	 * The product importer.
	 *
	 * This has a custom screen - the Tools > Import item is a placeholder.
	 * If we're on that screen, redirect to the custom one.
	 */
	public function product_importer() {
		if ( defined( 'WP_LOAD_IMPORTERS' ) ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=product&page=product_importer' ) );
			exit;
		}

		include_once( WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php' );
		include_once( WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php' );

		$importer = new WC_Product_CSV_Importer_Controller();
		$importer->dispatch();
	}

	/**
	 * Register WordPress based importers.
	 */
	public function register_importers() {
		if ( defined( 'WP_LOAD_IMPORTERS' ) ) {
			add_action( 'import_start', array( $this, 'post_importer_compatibility' ) );
			register_importer( 'woocommerce_product_csv',  __( 'WooCommerce products (CSV)', 'woocommerce' ),  __( 'Import <strong>products</strong> to your store via a csv file.', 'woocommerce' ),  array( $this, 'product_importer' ) );
			register_importer( 'woocommerce_tax_rate_csv', __( 'WooCommerce tax rates (CSV)', 'woocommerce' ), __( 'Import <strong>tax rates</strong> to your store via a csv file.', 'woocommerce' ), array( $this, 'tax_rates_importer' ) );
		}
	}

	/**
	 * The tax rate importer which extends WP_Importer.
	 */
	public function tax_rates_importer() {
		// Load Importer API
		require_once ABSPATH . 'wp-admin/includes/import.php';

		if ( ! class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';

			if ( file_exists( $class_wp_importer ) ) {
				require $class_wp_importer;
			}
		}

		// includes
		require( dirname( __FILE__ ) . '/importers/class-wc-tax-rate-importer.php' );

		// Dispatch
		$importer = new WC_Tax_Rate_Importer();
		$importer->dispatch();
	}

	/**
	 * When running the WP XML importer, ensure attributes exist.
	 *
	 * WordPress import should work - however, it fails to import custom product attribute taxonomies.
	 * This code grabs the file before it is imported and ensures the taxonomies are created.
	 */
	public function post_importer_compatibility() {
		global $wpdb;

		if ( empty( $_POST['import_id'] ) || ! class_exists( 'WXR_Parser' ) ) {
			return;
		}

		$id          = absint( $_POST['import_id'] );
		$file        = get_attached_file( $id );
		$parser      = new WXR_Parser();
		$import_data = $parser->parse( $file );

		if ( isset( $import_data['posts'] ) && ! empty( $import_data['posts'] ) ) {
			foreach ( $import_data['posts'] as $post ) {
				if ( 'product' === $post['post_type'] && ! empty( $post['terms'] ) ) {
					foreach ( $post['terms'] as $term ) {
						if ( strstr( $term['domain'], 'pa_' ) ) {
							if ( ! taxonomy_exists( $term['domain'] ) ) {
								$attribute_name = wc_sanitize_taxonomy_name( str_replace( 'pa_', '', $term['domain'] ) );

								// Create the taxonomy
								if ( ! in_array( $attribute_name, wc_get_attribute_taxonomies() ) ) {
									wc_create_attribute( array(
										'name'         => $attribute_name,
										'slug'         => $attribute_name,
										'type'         => 'select',
										'order_by'     => 'menu_order',
										'has_archives' => false,
									) );
								}

								// Register the taxonomy now so that the import works!
								register_taxonomy(
									$term['domain'],
									apply_filters( 'woocommerce_taxonomy_objects_' . $term['domain'], array( 'product' ) ),
									apply_filters( 'woocommerce_taxonomy_args_' . $term['domain'], array(
										'hierarchical' => true,
										'show_ui'      => false,
										'query_var'    => true,
										'rewrite'      => false,
									) )
								);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Ajax callback for importing one batch of products from a CSV.
	 */
	public function do_ajax_product_import() {
		global $wpdb;

		check_ajax_referer( 'wc-product-import', 'security' );

		if ( ! current_user_can( 'edit_products' ) || ! isset( $_POST['file'] ) ) {
			wp_die( -1 );
		}

		include_once( WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php' );
		include_once( WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php' );

		$file   = wc_clean( $_POST['file'] );
		$params = array(
			'delimiter'       => ! empty( $_POST['delimiter'] ) ? wc_clean( $_POST['delimiter'] ) : ',',
			'start_pos'       => isset( $_POST['position'] ) ? absint( $_POST['position'] ) : 0,
			'mapping'         => isset( $_POST['mapping'] ) ? (array) $_POST['mapping'] : array(),
			'update_existing' => isset( $_POST['update_existing'] ) ? (bool) $_POST['update_existing'] : false,
			'lines'           => apply_filters( 'woocommerce_product_import_batch_size', 30 ),
			'parse'           => true,
		);

		// Log failures.
		if ( 0 !== $params['start_pos'] ) {
			$error_log = array_filter( (array) get_user_option( 'product_import_error_log' ) );
		} else {
			$error_log = array();
		}

		$importer         = WC_Product_CSV_Importer_Controller::get_importer( $file, $params );
		$results          = $importer->import();
		$percent_complete = $importer->get_percent_complete();
		$error_log        = array_merge( $error_log, $results['failed'], $results['skipped'] );

		update_user_option( get_current_user_id(), 'product_import_error_log', $error_log );

		if ( 100 === $percent_complete ) {
			// Clear temp meta.
			$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_original_id' ) );
			$wpdb->query( "
				DELETE {$wpdb->posts}, {$wpdb->postmeta}, {$wpdb->term_relationships}
				FROM {$wpdb->posts}
				LEFT JOIN {$wpdb->term_relationships} ON ( {$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id )
				LEFT JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id )
				LEFT JOIN {$wpdb->term_taxonomy} ON ( {$wpdb->term_taxonomy}.term_taxonomy_id = {$wpdb->term_relationships}.term_taxonomy_id )
				LEFT JOIN {$wpdb->terms} ON ( {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id )
				WHERE {$wpdb->posts}.post_type IN ( 'product', 'product_variation' )
				AND {$wpdb->posts}.post_status = 'importing'
			" );

			// Send success.
			wp_send_json_success( array(
				'position'   => 'done',
				'percentage' => 100,
				'url'        => add_query_arg( array( 'nonce' => wp_create_nonce( 'product-csv' ) ), admin_url( 'edit.php?post_type=product&page=product_importer&step=done' ) ),
				'imported'   => count( $results['imported'] ),
				'failed'     => count( $results['failed'] ),
				'updated'    => count( $results['updated'] ),
				'skipped'    => count( $results['skipped'] ),
			) );
		} else {
			wp_send_json_success( array(
				'position'   => $importer->get_file_position(),
				'percentage' => $percent_complete,
				'imported'   => count( $results['imported'] ),
				'failed'     => count( $results['failed'] ),
				'updated'    => count( $results['updated'] ),
				'skipped'    => count( $results['skipped'] ),
			) );
		}
	}
}

new WC_Admin_Importers();
