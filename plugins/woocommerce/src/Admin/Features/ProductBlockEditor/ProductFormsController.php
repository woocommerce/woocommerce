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
	 * Set up the product forms controller.
	 */
	public function init() { // phpcs:ignore WooCommerce.Functions.InternalInjectionMethod.MissingFinal, WooCommerce.Functions.InternalInjectionMethod.MissingInternalTag -- Not an injection.
		// Migrate form templates after WooCommerce plugin update.
		add_action( 'upgrader_process_complete', array( $this, 'migrate_form_templates' ), 10, 2 );
	}

	/**
	 * Migrate form templates.
	 *
	 * @param \WP_Upgrader $upgrader The WP_Upgrader instance.
	 * @param array        $hook_extra Extra arguments passed to hooked filters.
	 * @return void
	 */
	public function migrate_form_templates( \WP_Upgrader $upgrader, array $hook_extra ) {
		// Check if the action is an `update` or `install` action for a plugin.
		if (
			'install' !== $hook_extra['action'] &&
			'update' !== $hook_extra['action'] ||
			'plugin' !== $hook_extra['type']
		) {
			return;
		}

		$updated_plugins = $hook_extra['plugins'];

		// Check if WooCommerce plugin was updated.
		if ( ! in_array( 'woocommerce/woocommerce.php', $updated_plugins, true ) ) {
			return;
		}

		/**
		 * Allow extend the list of templates that should be auto-generated.
		 *
		 * @since 9.1.0
		 * @param array $templates List of templates to auto-generate.
		 */
		$templates = apply_filters(
			'woocommerce_product_form_templates',
			array(
				'simple',
			)
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

			// If the post already exists, skip.
			if ( ! empty( $posts ) ) {
				continue;
			}

			// Insert the post.
			$post = wp_insert_post(
				array(
					'post_title'   => $file_data['title'],
					'post_name'    => $slug,
					'post_status'  => 'publish',
					'post_type'    => 'product_form',
					'post_content' => BlockTemplateUtils::get_template_content( $file_path ),
					'post_excerpt' => __( 'Template auto-generated for the Product Form Template system', 'woocommerce' ),
				)
			);
		}
	}
}
