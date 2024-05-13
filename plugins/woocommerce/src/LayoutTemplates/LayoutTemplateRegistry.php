<?php

namespace Automattic\WooCommerce\LayoutTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockTemplateLogger;

/**
 * Layout template registry.
 */
final class LayoutTemplateRegistry {

	/**
	 * Class instance.
	 *
	 * @var LayoutTemplateRegistry|null
	 */
	private static $instance = null;

	/**
	 * Layout templates info.
	 *
	 * @var array
	 */
	protected $layout_templates_info = array();

	/**
	 * Layout template instances.
	 *
	 * @var array
	 */
	protected $layout_template_instances = array();

	/**
	 * Get the instance of the class.
	 */
	public static function get_instance(): LayoutTemplateRegistry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Unregister all layout templates.
	 */
	public function unregister_all() {
		$this->layout_templates_info     = array();
		$this->layout_template_instances = array();
	}

	/**
	 * Check if a layout template is registered.
	 *
	 * @param string $layout_template_id Layout template ID.
	 */
	public function is_registered( $layout_template_id ): bool {
		return isset( $this->layout_templates_info[ $layout_template_id ] );
	}

	/**
	 * Register a single layout template.
	 *
	 * @param string $layout_template_id         Layout template ID.
	 * @param string $layout_template_area       Layout template area.
	 * @param string $layout_template_class_name Layout template class to register.
	 *
	 * @throws \ValueError If a layout template with the same ID already exists.
	 * @throws \ValueError If the specified layout template area is empty.
	 * @throws \ValueError If the specified layout template class does not exist.
	 * @throws \ValueError If the specified layout template class does not implement the BlockTemplateInterface.
	 */
	public function register( $layout_template_id, $layout_template_area, $layout_template_class_name ) {
		if ( $this->is_registered( $layout_template_id ) ) {
			throw new \ValueError( 'A layout template with the specified ID already exists in the registry.' );
		}

		if ( empty( $layout_template_area ) ) {
			throw new \ValueError( 'The specified layout template area is empty.' );
		}

		if ( ! class_exists( $layout_template_class_name ) ) {
			throw new \ValueError( 'The specified layout template class does not exist.' );
		}

		if ( ! is_subclass_of( $layout_template_class_name, BlockTemplateInterface::class ) ) {
			throw new \ValueError( 'The specified layout template class does not implement the BlockTemplateInterface.' );
		}

		$this->layout_templates_info[ $layout_template_id ] = array(
			'id'         => $layout_template_id,
			'area'       => $layout_template_area,
			'class_name' => $layout_template_class_name,
		);
	}

	/**
	 * Instantiate the matching layout templates and return them.
	 *
	 * @param array $query_params Query params.
	 */
	public function instantiate_layout_templates( array $query_params = array() ): array {
		// Make sure the block template logger is initialized before the templates are created,
		// so that the logger will collect the template events.
		$logger = BlockTemplateLogger::get_instance();

		$layout_templates = array();

		$layout_templates_info = $this->get_matching_layout_templates_info( $query_params );
		foreach ( $layout_templates_info as $layout_template_info ) {
			$layout_template = $this->get_layout_template_instance( $layout_template_info );

			$layout_template_id = $layout_template->get_id();

			$layout_templates[ $layout_template_id ] = $layout_template;

			$logger->log_template_events_to_file( $layout_template_id );
		}

		return $layout_templates;
	}

	/**
	 * Instantiate a single layout template and return it.
	 *
	 * @param array $layout_template_info Layout template info.
	 */
	private function get_layout_template_instance( $layout_template_info ): BlockTemplateInterface {
		$class_name = $layout_template_info['class_name'];

		// Return the instance if it already exists.

		$layout_template_instance = isset( $this->layout_template_instances[ $class_name ] )
			? $this->layout_template_instances[ $class_name ]
			: null;

		if ( ! empty( $layout_template_instance ) ) {
			return $layout_template_instance;
		}

		// Instantiate the layout template.

		$layout_template_instance                       = new $class_name();
		$this->layout_template_instances[ $class_name ] = $layout_template_instance;

		// Call the after instantiation hooks.

		/**
		 * Fires after a layout template is instantiated.
		 *
		 * @param string $layout_template_id Layout template ID.
		 * @param string $layout_template_area Layout template area.
		 * @param BlockTemplateInterface $layout_template Layout template instance.
		 *
		 * @since 8.6.0
		 */
		do_action( 'woocommerce_layout_template_after_instantiation', $layout_template_info['id'], $layout_template_info['area'], $layout_template_instance );

		// Call the old, deprecated, register hook.
		wc_do_deprecated_action( 'woocommerce_block_template_register', array( $layout_template_instance ), '8.6.0', 'woocommerce_layout_template_after_instantiation' );

		return $layout_template_instance;
	}

	/**
	 * Get matching layout templates info.
	 *
	 * @param array $query_params Query params.
	 */
	private function get_matching_layout_templates_info( array $query_params = array() ): array {
		$area_to_match = isset( $query_params['area'] ) ? $query_params['area'] : null;
		$id_to_match   = isset( $query_params['id'] ) ? $query_params['id'] : null;

		$matching_layout_templates_info = array();

		foreach ( $this->layout_templates_info as $layout_template_info ) {
			if ( ! empty( $area_to_match ) && $layout_template_info['area'] !== $area_to_match ) {
				continue;
			}

			if ( ! empty( $id_to_match ) && $layout_template_info['id'] !== $id_to_match ) {
				continue;
			}

			$matching_layout_templates_info[] = $layout_template_info;
		}

		return $matching_layout_templates_info;
	}
}
