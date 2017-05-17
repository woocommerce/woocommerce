<?php
/**
 * Init WooCommerce data importers.
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

		// Load Importer API
		require_once ABSPATH . 'wp-admin/includes/import.php';

		if ( ! class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';

			if ( file_exists( $class_wp_importer ) ) {
				require $class_wp_importer;
			}
		}

		// includes
		require( dirname( __FILE__ ) . '/importers/class-wc-product-wp-importer.php' );

		// Dispatch
		$importer = new WC_Product_WP_Importer();
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
									$attribute = array(
										'attribute_label'   => $attribute_name,
										'attribute_name'    => $attribute_name,
										'attribute_type'    => 'select',
										'attribute_orderby' => 'menu_order',
										'attribute_public'  => 0,
									);
									$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );
									delete_transient( 'wc_attribute_taxonomies' );
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
}

new WC_Admin_Importers();
