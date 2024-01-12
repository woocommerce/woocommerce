<?php
/**
 * WooCommerce Product Editor Redirection Controller
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

/**
 * Handle redirecting to the old or new editor based on features and support.
 */
class RedirectionController {
	/**
	 * Registered product templates.
	 *
	 * @var array
	 */
	private $product_templates = array();

	/**
	 * Set up the hooks used for redirection.
	 */
	public function __construct() {
		if ( \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'product_block_editor' ) ) {
			add_action( 'current_screen', array( $this, 'maybe_redirect_to_new_editor' ), 30, 0 );
			add_action( 'current_screen', array( $this, 'redirect_non_supported_product_types' ), 30, 0 );
		} else {
			add_action( 'current_screen', array( $this, 'maybe_redirect_to_old_editor' ), 30, 0 );
		}
	}

	/**
	 * Check if the current screen is the legacy add product screen.
	 */
	protected function is_legacy_add_new_screen(): bool {
		$screen = get_current_screen();
		return 'post' === $screen->base && 'product' === $screen->post_type && 'add' === $screen->action;
	}

	/**
	 * Check if the current screen is the legacy edit product screen.
	 */
	protected function is_legacy_edit_screen(): bool {
		$screen = get_current_screen();
		return 'post' === $screen->base
			&& 'product' === $screen->post_type
			&& isset( $_GET['post'] )
			&& isset( $_GET['action'] )
			&& 'edit' === $_GET['action'];
	}

	/**
	 * Check if a product is supported by the new experience.
	 *
	 * @param integer $product_id Product ID.
	 */
	protected function is_product_supported( $product_id ): bool {
		$product = $product_id ? wc_get_product( $product_id ) : null;

		if ( is_null( $product ) ) {
			return false;
		}

		$digital_product     = $product->is_downloadable() || $product->is_virtual();
		$product_template_id = $product->get_meta( '_product_template_id' );

		foreach ( $this->product_templates as $product_template ) {
			if ( is_null( $product_template->get_layout_template_id() ) ) {
				continue;
			}

			$product_data      = $product_template->get_product_data();
			$product_data_type = $product_data['type'];
			// Treat a variable product as a simple product since there is not a product template
			// for variable products.
			$product_type = $product->get_type() === 'variable' ? 'simple' : $product->get_type();

			if ( isset( $product_data_type ) && $product_data_type !== $product_type ) {
				continue;
			}

			if ( isset( $product_template_id ) && $product_template_id === $product_template->get_id() ) {
				return true;
			}

			if ( isset( $product_data_type ) ) {
				if ( Features::is_enabled( 'product-virtual-downloadable' ) ) {
					return true;
				}
				return ! $digital_product;
			}
		}

		return false;
	}

	/**
	 * Check if a product is supported by the new experience.
	 *
	 * @param array $product_templates The registered product teamplates.
	 */
	public function set_product_templates( array $product_templates ): void {
		$this->product_templates = $product_templates;
	}

	/**
	 * Redirects from old product form to the new product form if the
	 * feature `product_block_editor` is enabled.
	 */
	public function maybe_redirect_to_new_editor(): void {
		if ( $this->is_legacy_add_new_screen() ) {
			wp_safe_redirect( admin_url( 'admin.php?page=wc-admin&path=/add-product' ) );
			exit();
		}

		if ( $this->is_legacy_edit_screen() ) {
			$product_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : null;
			if ( ! $this->is_product_supported( $product_id ) ) {
				return;
			}
			wp_safe_redirect( admin_url( 'admin.php?page=wc-admin&path=/product/' . $product_id ) );
			exit();
		}
	}

	/**
	 * Redirects from new product form to the old product form if the
	 * feature `product_block_editor` is enabled.
	 */
	public function maybe_redirect_to_old_editor(): void {
		$route = $this->get_parsed_route();

		if ( 'add-product' === $route['page'] ) {
			wp_safe_redirect( admin_url( 'post-new.php?post_type=product' ) );
			exit();
		}

		if ( 'product' === $route['page'] ) {
			wp_safe_redirect( admin_url( 'post.php?post=' . $route['product_id'] . '&action=edit' ) );
			exit();
		}
	}

	/**
	 * Get the parsed WooCommerce Admin path.
	 */
	protected function get_parsed_route(): array {
		if ( ! \Automattic\WooCommerce\Admin\PageController::is_admin_page() || ! isset( $_GET['path'] ) ) {
			return array(
				'page'       => null,
				'product_id' => null,
			);
		}

		$path        = esc_url_raw( wp_unslash( $_GET['path'] ) );
		$path_pieces = explode( '/', wp_parse_url( $path, PHP_URL_PATH ) );

		return array(
			'page'       => $path_pieces[1],
			'product_id' => 'product' === $path_pieces[1] ? absint( $path_pieces[2] ) : null,
		);
	}


	/**
	 * Redirect non supported product types to legacy editor.
	 */
	public function redirect_non_supported_product_types(): void {
		$route      = $this->get_parsed_route();
		$product_id = $route['product_id'];

		if ( 'product' === $route['page'] && ! $this->is_product_supported( $product_id ) ) {
			wp_safe_redirect( admin_url( 'post.php?post=' . $route['product_id'] . '&action=edit' ) );
			exit();
		}
	}

}
