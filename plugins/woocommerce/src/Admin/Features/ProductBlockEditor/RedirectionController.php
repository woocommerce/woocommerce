<?php
/**
 * WooCommerce Product Editor Redirection Controller
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

/**
 * Handle redirecting to the old or new editor based on features and support.
 */
class RedirectionController {

	/**
	 * Supported post types.
	 *
	 * @var array
	 */
	private $supported_post_types;

	/**
	 * Set up the hooks used for redirection.
	 *
	 * @param array $supported_post_types Array of supported post types.
	 */
	public function __construct( $supported_post_types ) {
		$this->supported_post_types = $supported_post_types;

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
		return $product && in_array( $product->get_type(), $this->supported_post_types, true );
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
