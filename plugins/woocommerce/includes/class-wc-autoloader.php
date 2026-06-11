<?php
/**
 * WooCommerce Autoloader.
 *
 * @package WooCommerce\Classes
 * @version 2.3.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * Autoloader class.
 */
class WC_Autoloader {

	/**
	 * Map of lowercased class names to file paths, relative to the includes directory, for classes whose
	 * files live outside the directories covered by the prefix rules in autoload(), or whose file names
	 * don't follow the standard `class-{name}.php` convention (interfaces, traits, abstract classes,
	 * data stores).
	 *
	 * These files used to be loaded unconditionally on every request from WooCommerce::includes().
	 * Mapping them here allows them to be loaded on demand instead. The map is checked before the
	 * WC_ prefix rules, since some of these classes (Abstract_WC_*) don't use the WC_ prefix.
	 *
	 * @var array<string, string>
	 */
	private const CLASS_MAP = array(
		// Interfaces.
		'wc_abstract_order_data_store_interface'        => 'interfaces/class-wc-abstract-order-data-store-interface.php',
		'wc_coupon_data_store_interface'                => 'interfaces/class-wc-coupon-data-store-interface.php',
		'wc_customer_data_store_interface'              => 'interfaces/class-wc-customer-data-store-interface.php',
		'wc_customer_download_data_store_interface'     => 'interfaces/class-wc-customer-download-data-store-interface.php',
		'wc_customer_download_log_data_store_interface' => 'interfaces/class-wc-customer-download-log-data-store-interface.php',
		'wc_importer_interface'                         => 'interfaces/class-wc-importer-interface.php',
		'wc_log_handler_interface'                      => 'interfaces/class-wc-log-handler-interface.php',
		'wc_logger_interface'                           => 'interfaces/class-wc-logger-interface.php',
		'wc_object_data_store_interface'                => 'interfaces/class-wc-object-data-store-interface.php',
		'wc_order_data_store_interface'                 => 'interfaces/class-wc-order-data-store-interface.php',
		'wc_order_item_data_store_interface'            => 'interfaces/class-wc-order-item-data-store-interface.php',
		'wc_order_item_product_data_store_interface'    => 'interfaces/class-wc-order-item-product-data-store-interface.php',
		'wc_order_item_type_data_store_interface'       => 'interfaces/class-wc-order-item-type-data-store-interface.php',
		'wc_order_refund_data_store_interface'          => 'interfaces/class-wc-order-refund-data-store-interface.php',
		'wc_payment_token_data_store_interface'         => 'interfaces/class-wc-payment-token-data-store-interface.php',
		'wc_product_data_store_interface'               => 'interfaces/class-wc-product-data-store-interface.php',
		'wc_product_variable_data_store_interface'      => 'interfaces/class-wc-product-variable-data-store-interface.php',
		'wc_queue_interface'                            => 'interfaces/class-wc-queue-interface.php',
		'wc_shipping_zone_data_store_interface'         => 'interfaces/class-wc-shipping-zone-data-store-interface.php',
		'wc_webhook_data_store_interface'               => 'interfaces/class-wc-webhooks-data-store-interface.php',
		// Traits.
		'wc_item_totals'                                => 'traits/trait-wc-item-totals.php',
		// Abstract classes.
		'wc_abstract_order'                             => 'abstracts/abstract-wc-order.php',
		'wc_abstract_privacy'                           => 'abstracts/abstract-wc-privacy.php',
		'wc_address_provider'                           => 'abstracts/abstract-wc-address-provider.php',
		'wc_background_process'                         => 'abstracts/class-wc-background-process.php',
		'wc_data'                                       => 'abstracts/abstract-wc-data.php',
		'wc_deprecated_hooks'                           => 'abstracts/abstract-wc-deprecated-hooks.php',
		'wc_integration'                                => 'abstracts/abstract-wc-integration.php',
		'wc_log_handler'                                => 'abstracts/abstract-wc-log-handler.php',
		'wc_object_query'                               => 'abstracts/abstract-wc-object-query.php',
		'wc_payment_gateway'                            => 'abstracts/abstract-wc-payment-gateway.php',
		'wc_payment_token'                              => 'abstracts/abstract-wc-payment-token.php',
		'wc_product'                                    => 'abstracts/abstract-wc-product.php',
		'wc_session'                                    => 'abstracts/abstract-wc-session.php',
		'wc_settings_api'                               => 'abstracts/abstract-wc-settings-api.php',
		'wc_shipping_method'                            => 'abstracts/abstract-wc-shipping-method.php',
		'wc_widget'                                     => 'abstracts/abstract-wc-widget.php',
		// Data stores.
		'abstract_wc_order_data_store_cpt'              => 'data-stores/abstract-wc-order-data-store-cpt.php',
		'abstract_wc_order_item_type_data_store'        => 'data-stores/abstract-wc-order-item-type-data-store.php',
		'wc_coupon_data_store_cpt'                      => 'data-stores/class-wc-coupon-data-store-cpt.php',
		'wc_customer_data_store'                        => 'data-stores/class-wc-customer-data-store.php',
		'wc_customer_data_store_session'                => 'data-stores/class-wc-customer-data-store-session.php',
		'wc_customer_download_data_store'               => 'data-stores/class-wc-customer-download-data-store.php',
		'wc_customer_download_log_data_store'           => 'data-stores/class-wc-customer-download-log-data-store.php',
		'wc_data_store_wp'                              => 'data-stores/class-wc-data-store-wp.php',
		'wc_order_data_store_cpt'                       => 'data-stores/class-wc-order-data-store-cpt.php',
		'wc_order_item_coupon_data_store'               => 'data-stores/class-wc-order-item-coupon-data-store.php',
		'wc_order_item_data_store'                      => 'data-stores/class-wc-order-item-data-store.php',
		'wc_order_item_fee_data_store'                  => 'data-stores/class-wc-order-item-fee-data-store.php',
		'wc_order_item_product_data_store'              => 'data-stores/class-wc-order-item-product-data-store.php',
		'wc_order_item_shipping_data_store'             => 'data-stores/class-wc-order-item-shipping-data-store.php',
		'wc_order_item_tax_data_store'                  => 'data-stores/class-wc-order-item-tax-data-store.php',
		'wc_order_refund_data_store_cpt'                => 'data-stores/class-wc-order-refund-data-store-cpt.php',
		'wc_payment_token_data_store'                   => 'data-stores/class-wc-payment-token-data-store.php',
		'wc_product_data_store_cpt'                     => 'data-stores/class-wc-product-data-store-cpt.php',
		'wc_product_grouped_data_store_cpt'             => 'data-stores/class-wc-product-grouped-data-store-cpt.php',
		'wc_product_variable_data_store_cpt'            => 'data-stores/class-wc-product-variable-data-store-cpt.php',
		'wc_product_variation_data_store_cpt'           => 'data-stores/class-wc-product-variation-data-store-cpt.php',
		'wc_shipping_zone_data_store'                   => 'data-stores/class-wc-shipping-zone-data-store.php',
		'wc_webhook_data_store'                         => 'data-stores/class-wc-webhook-data-store.php',
		// Core payment gateways.
		'wc_payment_gateway_cc'                         => 'gateways/class-wc-payment-gateway-cc.php',
		'wc_payment_gateway_echeck'                     => 'gateways/class-wc-payment-gateway-echeck.php',
		// Tracks.
		'wc_site_tracking'                              => 'tracks/class-wc-site-tracking.php',
		'wc_tracks'                                     => 'tracks/class-wc-tracks.php',
		'wc_tracks_client'                              => 'tracks/class-wc-tracks-client.php',
		'wc_tracks_event'                               => 'tracks/class-wc-tracks-event.php',
		'wc_tracks_footer_pixel'                        => 'tracks/class-wc-tracks-footer-pixel.php',
		// Queue.
		'wc_action_queue'                               => 'queue/class-wc-action-queue.php',
		'wc_queue'                                      => 'queue/class-wc-queue.php',
		// Other classes in subdirectories not covered by the prefix rules.
		'wc_blocks_utils'                               => 'blocks/class-wc-blocks-utils.php',
		'wc_shop_customizer'                            => 'customizer/class-wc-shop-customizer.php',
		// Note: WC_Marketplace_Updater is intentionally NOT mapped here. Its file calls
		// WC_Marketplace_Updater::load() at include time, so it must only be loaded on admin-side
		// requests via WooCommerce::includes(). Mapping it would let a frontend autoload pull in that
		// admin-only boot work, undermining the request-type gating.
	);

	/**
	 * Path to the includes directory.
	 *
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor.
	 */
	public function __construct() {
		if ( function_exists( '__autoload' ) ) {
			spl_autoload_register( '__autoload' );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->include_path = untrailingslashit( plugin_dir_path( WC_PLUGIN_FILE ) ) . '/includes/';
	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @param  string $class Class name.
	 * @return string
	 */
	private function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( '_', '-', $class ) . '.php';
	}

	/**
	 * Include a class file.
	 *
	 * @param  string $path File path.
	 * @return bool Successful or not.
	 */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once $path;
			return true;
		}
		return false;
	}

	/**
	 * Auto-load WC classes on demand to reduce memory consumption.
	 *
	 * @param string $class Class name.
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );

		// Check the explicit class map first: it covers classes whose file locations can't be derived
		// from the prefix rules below, including the Abstract_WC_* classes that lack the WC_ prefix.
		if ( isset( self::CLASS_MAP[ $class ] ) ) {
			$this->load_file( $this->include_path . self::CLASS_MAP[ $class ] );
			return;
		}

		if ( 0 !== strpos( $class, 'wc_' ) ) {
			return;
		}

		// The Legacy REST API was removed in WooCommerce 9.0, but some servers still have
		// the includes/class-wc-api.php file after they upgrade, which causes a fatal error when executing
		// "class_exists('WC_API')". This will prevent this error, while still making the class visible
		// when it's provided by the WooCommerce Legacy REST API plugin.
		if ( 'wc_api' === $class ) {
			return;
		}

		// If the class is already loaded from a merged package, prevent autoloader from loading it as well.
		if ( \Automattic\WooCommerce\Packages::should_load_class( $class ) ) {
			return;
		}

		$file = $this->get_file_name_from_class( $class );
		$path = '';

		if ( 0 === strpos( $class, 'wc_addons_gateway_' ) ) {
			$path = $this->include_path . 'gateways/' . substr( str_replace( '_', '-', $class ), 18 ) . '/';
		} elseif ( 0 === strpos( $class, 'wc_gateway_' ) ) {
			$path = $this->include_path . 'gateways/' . substr( str_replace( '_', '-', $class ), 11 ) . '/';
		} elseif ( 0 === strpos( $class, 'wc_shipping_' ) ) {
			$path = $this->include_path . 'shipping/' . substr( str_replace( '_', '-', $class ), 12 ) . '/';
		} elseif ( 0 === strpos( $class, 'wc_shortcode_' ) ) {
			$path = $this->include_path . 'shortcodes/';
		} elseif ( 0 === strpos( $class, 'wc_meta_box' ) ) {
			$path = $this->include_path . 'admin/meta-boxes/';
		} elseif ( 0 === strpos( $class, 'wc_admin' ) ) {
			$path = $this->include_path . 'admin/';
		} elseif ( 0 === strpos( $class, 'wc_payment_token_' ) ) {
			$path = $this->include_path . 'payment-tokens/';
		} elseif ( 0 === strpos( $class, 'wc_log_handler_' ) ) {
			$path = $this->include_path . 'log-handlers/';
		} elseif ( 0 === strpos( $class, 'wc_integration' ) ) {
			$path = $this->include_path . 'integrations/' . substr( str_replace( '_', '-', $class ), 15 ) . '/';
		} elseif ( 0 === strpos( $class, 'wc_notes_' ) ) {
			$path = $this->include_path . 'admin/notes/';
		} elseif ( 0 === strpos( $class, 'wc_rest_' ) ) {
			// Handle REST API controllers in subdirectories.
			// For V4 controllers, check if the feature is enabled first.
			if ( false !== strpos( $class, '_v4_' ) ) {
				// Only load V4 controllers if the feature is enabled.
				if ( Features::is_enabled( 'rest-api-v4' ) ) {
					$rest_controller_paths = array(
						'rest-api/Controllers/Version4/',
					);

					foreach ( $rest_controller_paths as $rest_path ) {
						if ( $this->load_file( $this->include_path . $rest_path . $file ) ) {
							return;
						}
					}

					// Also check subdirectories recursively for V4.
					$this->load_rest_v4_controller_recursively( $file );
				}
			} else {
				// For non-V4 controllers, load normally.
				$rest_controller_paths = array(
					'rest-api/Controllers/Version1/',
					'rest-api/Controllers/Version2/',
					'rest-api/Controllers/Version3/',
					'rest-api/Controllers/Telemetry/',
				);

				foreach ( $rest_controller_paths as $rest_path ) {
					if ( $this->load_file( $this->include_path . $rest_path . $file ) ) {
						return;
					}
				}
			}
		}

		if ( empty( $path ) || ! $this->load_file( $path . $file ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}

	/**
	 * Recursively load REST API V4 controllers from subdirectories.
	 *
	 * @param string $file File name to search for.
	 */
	private function load_rest_v4_controller_recursively( $file ): bool {
		$v4_base_path = $this->include_path . 'rest-api/Controllers/Version4/';

		// Use RecursiveDirectoryIterator to search subdirectories.
		if ( is_dir( $v4_base_path ) ) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $v4_base_path, RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ( $iterator as $dir_info ) {
				if ( $dir_info->isDir() ) {
					$subdir_path = $dir_info->getPathname() . '/';
					if ( $this->load_file( $subdir_path . $file ) ) {
						return true;
					}
				}
			}
		}
		return false;
	}
}

new WC_Autoloader();
