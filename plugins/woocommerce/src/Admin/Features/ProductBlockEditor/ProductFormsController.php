<?php
/**
 * WooCommerce Product Forms Controller
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

/**
 * Handle retrieval of product forms.
 */
class ProductFormsController {

	/**
	 * Product form templates.
	 *
	 * @var array
	 */
	private $product_form_templates = array(
		'simple',
	);

	/**
	 * Set up the product forms controller.
	 */
	public function init() { // phpcs:ignore WooCommerce.Functions.InternalInjectionMethod.MissingFinal, WooCommerce.Functions.InternalInjectionMethod.MissingInternalTag -- Not an injection.
		add_action( 'upgrader_process_complete', array( $this, 'migrate_templates_when_plugin_updated' ), 10, 2 );
	}

	/**
	 * Migrate form templates after WooCommerce plugin update.
	 *
	 * @param \WP_Upgrader $upgrader The WP_Upgrader instance.
	 * @param array        $hook_extra Extra arguments passed to hooked filters.
	 * @return void
	 */
	public function migrate_templates_when_plugin_updated( \WP_Upgrader $upgrader, array $hook_extra ) {
		/*
		 * Check whether $hook_extra['plugins'] contains the WooCommerce plugin.
		 * It seems that $hook_extra may be `null` in some cases.
		 * @see https://github.com/woocommerce/woocommerce/pull/48221#pullrequestreview-2102772713
		 */
		if ( ! isset( $hook_extra['plugins'] ) || ! is_array( $hook_extra['plugins'] ) ) {
			return;
		}

		// Check if WooCommerce plugin was updated.
		if (
			! in_array( 'woocommerce/woocommerce.php', $hook_extra['plugins'], true )
		) {
			return;
		}

		// Check if the action is an `update` or `install` action for a plugin.
		$action = isset( $hook_extra['action'] ) ? $hook_extra['action'] : '';

		if (
			'install' !== $action && 'update' !== $action ||
			'plugin' !== $hook_extra['type']
		) {
			return;
		}

		$this->migrate_product_form_posts();
	}

	/**
	 * Insert post form posts for each form template file.
	 *
	 * @return void
	 */
	public function migrate_product_form_posts() {
		/**
		 * Allow extend the list of templates that should be auto-generated.
		 *
		 * @since 9.1.0
		 * @param array $templates List of templates to auto-generate.
		 */
		$templates = apply_filters(
			'woocommerce_product_form_templates',
			$this->product_form_templates
		);

		foreach ( $templates as $slug ) {
			$file_path = BlockTemplateUtils::get_block_template_path( $slug );

			if ( ! $file_path ) {
				continue;
			}

			$file_data = BlockTemplateUtils::get_template_file_data( $file_path );

			$posts = get_posts(
				array(
					'name'           => $slug,
					'post_type'      => 'product_form',
					'post_status'    => 'any',
					'posts_per_page' => 1,
				)
			);

			/*
			 * Skip the post creation if the post already exists.
			 */
			if ( ! empty( $posts ) ) {
				continue;
			}

			$post = wp_insert_post(
				array(
					'post_title'   => $file_data['title'],
					'post_name'    => $slug,
					'post_status'  => 'publish',
					'post_type'    => 'product_form',
					'post_content' => BlockTemplateUtils::get_template_content( $file_path ),
					'post_excerpt' => __( 'Template auto-generated for the (PFT) Product Form Template system', 'woocommerce' ),
				)
			);
		}
	}
}
