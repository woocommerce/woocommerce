<?php

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplateRegistry;

/**
 * Block template controller.
 */
class BlockTemplatesController {

    /**
     * Block template registry
     *
     * @var BlockTemplateRegistry
     */
    private $block_template_registry;

    /**
     * Block template transformer.
     *
     * @var TemplateTransformer
     */
    private $template_transformer;

    /**
     * Init.
     */
    public function init( $block_template_registry, $template_transformer ) {
        $this->block_template_registry = $block_template_registry;
        $this->template_transformer    = $template_transformer;
        add_action( 'rest_api_init', array( $this, 'register_templates' ) );
    }

    /**
     * Register templates in the blocks endpoint.
     */
    public function register_templates() {
        $templates = $this->block_template_registry->get_all_registered();

        foreach ( $templates as $template ) {
            add_filter( 'pre_get_block_templates', function( $query_result, $query, $template_type ) use( $template ) {
                if ( ! isset( $query['area'] ) || $query['area'] !== $template->get_area() ) {
                    return $query_result;
                }

                $wp_block_template = $this->template_transformer->transform( $template );
                $query_result[]    = $wp_block_template;
        
                return $query_result;
            }, 10, 3 );
        }
    }

}