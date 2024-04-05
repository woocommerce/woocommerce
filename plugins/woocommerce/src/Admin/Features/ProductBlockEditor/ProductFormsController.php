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
	public function init() {
		add_action( 'admin_init', array( $this, 'maybe_create_product_forms' ) );
	}

	/**
	 * Create the product forms if they don't yet exist.
	 */
	public function maybe_create_product_forms() {
        $templates = apply_filters(
            'woocommerce_product_form_templates',
            array(
                'simple.php'
            )
        );
        foreach ( $templates as $template ) {
            $file_path = BlockTemplateUtils::get_block_template( $template );
            $file_data = BlockTemplateUtils::get_template_data( $file_path );
            $posts     = get_posts(
                array(
                    'name'           => $file_data['slug'],
                    'post_type'      => 'product_form',
                    'post_status'    => 'any',
                    'posts_per_page' => 1
                )
            );

            if ( ! empty( $posts ) ) {
                continue;
            }
            
            $post = wp_insert_post(
                array(
                    'post_title'  => $file_data['title'],
                    'post_name'   => $file_data['slug'],
                    'post_status' =>	'publish',
                    'post_type'   => 'product_form'
                )
            );
        }
	}

}
