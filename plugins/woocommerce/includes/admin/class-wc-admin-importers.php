<?php //phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration
/**
 * Init WooCommerce data importers.
 *
 * @package WooCommerce\Admin
 */

use Automattic\Jetpack\Constants;

defined( 'ABSPATH' ) || exit;

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
		if ( ! $this->import_allowed() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'add_to_menus' ) );
		add_action( 'admin_init', array( $this, 'register_importers' ) );
		add_action( 'admin_head', array( $this, 'hide_from_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_ajax_woocommerce_do_ajax_product_import', array( $this, 'do_ajax_product_import' ) );
		add_action( 'in_admin_footer', array( $this, 'track_importer_exporter_view' ) );

		// Register WooCommerce importers.
		$this->importers['product_importer'] = array(
			'menu'       => 'edit.php?post_type=product',
			'name'       => __( 'Product Import', 'woocommerce' ),
			'capability' => 'import',
			'callback'   => array( $this, 'product_importer' ),
		);
	}

	/**
	 * Return true if WooCommerce imports are allowed for current user, false otherwise.
	 *
	 * @return bool Whether current user can perform imports.
	 */
	protected function import_allowed() {
		return current_user_can( 'edit_products' ) && current_user_can( 'import' );
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
		$suffix  = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';
		$version = Constants::get_constant( 'WC_VERSION' );
		wp_register_script( 'wc-product-import', WC()->plugin_url() . '/assets/js/admin/wc-product-import' . $suffix . '.js', array( 'jquery' ), $version, true );
	}

	/**
	 * The product importer.
	 *
	 * This has a custom screen - the Tools > Import item is a placeholder.
	 * If we're on that screen, redirect to the custom one.
	 */
	public function product_importer() {
		if ( Constants::is_defined( 'WP_LOAD_IMPORTERS' ) ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=product&page=product_importer&source=wordpress-importer' ) );
			exit;
		}

		// phpcs:ignore
		if ( isset( $_GET['source'] ) && 'wordpress-importer' === sanitize_text_field( wp_unslash( $_GET['source'] ) ) ) {
			wc_admin_record_tracks_event( 'product_importer_view_from_wp_importer' );
		}

		include_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
		include_once WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php';

		$importer = new WC_Product_CSV_Importer_Controller();
		$importer->dispatch();
	}

	/**
	 * Register WordPress based importers.
	 */
	public function register_importers() {
		if ( Constants::is_defined( 'WP_LOAD_IMPORTERS' ) ) {
			add_action( 'import_start', array( $this, 'post_importer_compatibility' ) );
			register_importer( 'woocommerce_product_csv', __( 'WooCommerce products (CSV)', 'woocommerce' ), __( 'Import <strong>products</strong> to your store via a csv file.', 'woocommerce' ), array( $this, 'product_importer' ) );
			register_importer( 'woocommerce_tax_rate_csv', __( 'WooCommerce tax rates (CSV)', 'woocommerce' ), __( 'Import <strong>tax rates</strong> to your store via a csv file.', 'woocommerce' ), array( $this, 'tax_rates_importer' ) );
		}
	}

	/**
	 * The tax rate importer which extends WP_Importer.
	 */
	public function tax_rates_importer() {
		require_once ABSPATH . 'wp-admin/includes/import.php';

		if ( ! class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';

			if ( file_exists( $class_wp_importer ) ) {
				require $class_wp_importer;
			}
		}

		wc_admin_record_tracks_event( 'tax_rates_importer_view_from_wp_importer' );

		require __DIR__ . '/importers/class-wc-tax-rate-importer.php';

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

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['import_id'] ) || ! class_exists( 'WXR_Parser' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
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
								$attribute_name = wc_attribute_taxonomy_slug( $term['domain'] );

								// Create the taxonomy.
								if ( ! in_array( $attribute_name, wc_get_attribute_taxonomies(), true ) ) {
									wc_create_attribute(
										array(
											'name'         => $attribute_name,
											'slug'         => $attribute_name,
											'type'         => 'select',
											'order_by'     => 'menu_order',
											'has_archives' => false,
										)
									);
								}

								// Register the taxonomy now so that the import works!
								register_taxonomy(
									$term['domain'],
									// phpcs:ignore
									apply_filters( 'woocommerce_taxonomy_objects_' . $term['domain'], array( 'product' ) ),
									// phpcs:ignore
									apply_filters(
										'woocommerce_taxonomy_args_' . $term['domain'],
										array(
											'hierarchical' => true,
											'show_ui'      => false,
											'query_var'    => true,
											'rewrite'      => false,
										)
									)
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
		if ( ! $this->import_allowed() ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient privileges to import products.', 'woocommerce' ) ) );
		}

		include_once WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php';
		WC_Product_CSV_Importer_Controller::dispatch_ajax();
	}

	/**
	 * Track importer/exporter view.
	 *
	 * @return void
	 */
	public function track_importer_exporter_view() {
		$screen = get_current_screen();

		if ( ! isset( $screen->id ) ) {
			return;
		}

		// Don't track if we're in a specific import screen.
		// phpcs:ignore
		if ( isset( $_GET['import'] ) ) {
			return;
		}

		if ( 'import' === $screen->id || 'export' === $screen->id ) {
			wc_admin_record_tracks_event( 'wordpress_' . $screen->id . '_view' );
		}
	}
}

new WC_Admin_Importers();
