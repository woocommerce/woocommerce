<?php

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

/**
 * Product template registry.
 */
final class ProductTemplateRegistry {

    /**
	 * Class instance.
	 *
	 * @since 5.0.0
	 * @var ProductTemplateRegistry|null
	 */
	private static $instance = null;

    /**
     * Templates.
     */
    protected $templates = array();

    /**
	 * WooCommerce plugin slug
	 *
	 * @var string
	 */
	const PLUGIN_SLUG = 'woocommerce/woocommerce';

    /**
	 * Template typ.
	 *
	 * @var string
	 */
	const TEMPLATE_TYPE = 'woocommerce_product_editor_template';

    /**
	 * Get the instance of the class.
	 *
	 * @return ProductTemplateRegistry The main instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

    /**
     * Register the default core product templates.
     */
    public function register_core_templates() {
        $template_instances = array(
            new SimpleProductTemplate(),
            new VariableProductTemplate(),
        );

        foreach ( $template_instances as $instance ) {
            $this->register( $instance->get_slug(), $instance );
        }
    }

    /**
     * Register a single template.
     *
     * @param string $name Name of the template
     * @param array  $template Template layout.
     */
    public function register( $name, $template ) {
        $this->templates[ $name ] = $template;
    }

    /**
     * Get the registered templates.
     *
     * @return array Registered templates
     */
    public function get_all_registered() {
        return $this->templates;
    }

    /**
     * Get a single registered template.
     *
     * @param string $name Name of the template
     * @return array Template layout.
     */
    public function get_registered( $name ) {
        return isset( $this->templates[ $name ] ) ? $this->templates[ $name ] : null;
    }

    /**
     * Add the templates to the Gutenberg registry.
     */
    public function add_block_templates( $query_result, $query, $template_type ) {
        if ( ! isset( $query['post_type'] ) || $query['post_type'] !== self::TEMPLATE_TYPE ) {
            return $query_result;
        }

        foreach( $this->templates as $template ) {
            $block_template = $this->get_block_template( $template );
            $query_result[] = $block_template;
        }

        return $query_result;
    }

    /**
	 * Get the WP block template from a product template.
	 *
	 * @param object $product_template The product template.
	 *
	 * @return \WP_Block_Template Template.
	 */
	protected function get_block_template( $product_template ) {
		$template                 = new \WP_Block_Template();
		$template->id             = self::PLUGIN_SLUG . '//product-editor_' . $product_template->get_slug();
		$template->theme          = self::PLUGIN_SLUG;
		$template->content        = $product_template->get_parsed_template();
		$template->source         = 'plugin';
		$template->slug           = $product_template->get_slug();
		$template->type           = self::TEMPLATE_TYPE;
		$template->title          = $product_template->get_title();
		$template->description    = $product_template->get_description();
		$template->status         = 'publish';
		$template->has_theme_file = true;
		$template->origin         = 'plugin';
		$template->is_custom      = false; // Templates loaded from the filesystem aren't custom, ones that have been edited and loaded from the DB are.
		$template->post_types     = array(); // Don't appear in any Edit Post template selector dropdown.
		$template->area           = 'uncategorized';

		return $template;
	}

}