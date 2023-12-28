<?php

namespace Automattic\WooCommerce\LayoutTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

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
	 * Get the instance of the class.
	 */
	public static function get_instance(): LayoutTemplateRegistry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
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
		$layout_templates = array();

		$class_names = $this->get_class_names( $query_params );
		foreach ( $class_names as $class_name ) {
			$layout_template    = $this->get_layout_template_instance( $class_name );
			$layout_templates[] = $layout_template;
		}

		return $layout_templates;
	}

	/**
	 * Instantiate a single layout template and return it.
	 *
	 * @param string $class_name Class name of the layout template.
	 */
	private function get_layout_template_instance( $class_name ) {
		return new $class_name();
	}

	/**
	 * Get matching layout template class names.
	 *
	 * @param array $query_params Query params.
	 */
	private function get_class_names( array $query_params = array() ): array {
		$area_to_match = isset( $query_params['area'] ) ? $query_params['area'] : null;
		$id_to_match   = isset( $query_params['id'] ) ? $query_params['id'] : null;

		$class_names = array();

		foreach ( $this->layout_templates_info as $id => $layout_template_info ) {
			if ( ! empty( $area_to_match ) && $layout_template_info['area'] !== $area_to_match ) {
				continue;
			}

			if ( ! empty( $id_to_match ) && $id !== $id_to_match ) {
				continue;
			}

			$class_names[] = $layout_template_info['class_name'];
		}

		return $class_names;
	}
}
