<?php
/**
 * REST API Products Catalog controller (compatibility stub).
 *
 * @package WooCommerce\RestApi
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * REST API Products Catalog controller class.
 *
 * The real controller was removed in 11.0. This empty stub stays so an in-memory 10.9 controller
 * list, still naming this class while the files are swapped to 11.0 during an update, can
 * instantiate it instead of fataling on the deleted file. It registers nothing.
 *
 * 10.9 only added this entry when the `products-catalog-api` feature was enabled, which is off by
 * default — but the flag is flippable at runtime (WooCommerce Beta Tester, or any plugin filtering
 * `woocommerce_admin_get_feature_config`), and the feature config is resolved from the pre-swap
 * code, so trunk dropping the flag does not close the path.
 *
 * @deprecated 11.0.0
 */
class WC_REST_Products_Catalog_Controller {

	/**
	 * Register routes. Intentionally a no-op.
	 */
	public function register_routes(): void {}
}
