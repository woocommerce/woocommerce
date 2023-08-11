<?php

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplateRegistry;

/**
 * Block template registry.
 */
final class BlockTemplateRegistry {

    /**
	 * Class instance.
	 *
	 * @var BlockTemplateRegistry|null
	 */
	private static $instance = null;

    /**
     * Templates.
     */
    protected $templates = array();

    /**
	 * Get the instance of the class.
	 */
	public static function get_instance(): BlockTemplateRegistry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

    /**
     * Register a single template.
     *
     * @param array $template Template layout.
     */
    public function register( $template_class ) {
        $template = new $template_class();
        $this->templates[ $template->get_id() ] = $template;
    }

    /**
     * Get the registered templates.
     */
    public function get_all_registered(): array {
        return $this->templates;
    }

    /**
     * Get a single registered template.
     *
     * @param string $id ID of the template
     */
    public function get_registered( $id ): array {
        return isset( $this->templates[ $id ] ) ? $this->templates[ $id ] : null;
    }

}