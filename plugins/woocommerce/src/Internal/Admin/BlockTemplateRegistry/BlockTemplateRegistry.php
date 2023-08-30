<?php

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplateRegistry;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

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
	 *
	 * @var array
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
	 * @param BlockTemplateInterface $template Template to register.
	 *
	 * @throws \ValueError If a template with the same ID already exists.
	 */
	public function register( BlockTemplateInterface $template ) {
		$id = $template->get_id();

		if ( isset( $this->templates[ $id ] ) ) {
			throw new \ValueError( 'A template with the specified ID already exists in the registry.' );
		}

		/**
		 * Fires when a template is registered.
		 *
		 * @param BlockTemplateInterface $template Template that was registered.
		 *
		 * @since 8.2.0
		 */
		do_action( 'woocommerce_block_template_register', $template );

		$this->templates[ $id ] = $template;
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
	 * @param string $id ID of the template.
	 */
	public function get_registered( $id ): BlockTemplateInterface {
		return isset( $this->templates[ $id ] ) ? $this->templates[ $id ] : null;
	}
}
