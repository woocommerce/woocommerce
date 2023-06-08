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
            $this->register( $instance->get_name(), $instance );
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

}