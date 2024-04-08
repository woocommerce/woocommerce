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
		add_filter( 'the_content', array( $this, 'replace_template_content' ) );
	}

	/**
	 * Create the product forms if they don't yet exist.
	 */
	public function maybe_create_product_forms() {
        $templates = apply_filters(
            'woocommerce_product_form_templates',
            array(
                'simple'
            )
        );
        foreach ( $templates as $slug ) {
            $file_path = BlockTemplateUtils::get_block_template( $slug );
            $file_data = BlockTemplateUtils::get_template_data( $file_path );
            $posts     = get_posts(
                array(
                    'name'           => $slug,
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
                    'post_title'   => $file_data['title'],
                    'post_name'    => $slug,
                    'post_status'  => 'publish',
                    'post_type'    => 'product_form',
                    'post_content' => BlockTemplateUtils::get_template_content( $file_path )
                )
            );
        }
	}

    public function replace_template_content( $content ) {
        global $post;

        if ( 'product_form' !== $post->post_type || $post->post_date !== $post->post_modified ) {
            return $content;
        }

        $file_path = BlockTemplateUtils::get_block_template( $post->post_name );

        if ( ! file_exists( $file_path ) ) {
            return $content;
        }

        return BlockTemplateUtils::get_template_content( $file_path );
    }

}
