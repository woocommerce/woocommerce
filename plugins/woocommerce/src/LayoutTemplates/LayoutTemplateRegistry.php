<?php

namespace Automattic\WooCommerce\LayoutTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

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
	 * Register a single layout template.
	 *
	 * @param string                  $layout_template_id    Layout template ID.
	 * @param string                  $layout_template_area  Layout template area.
	 * @param LayoutTemplateInterface $template Layout template to register.
	 *
	 * @throws \ValueError If a layout template with the same ID already exists.
	 */
	public function register( $layout_template_id, $layout_template_area, $layout_template_class_name ) {
		if ( isset( $this->layout_templates_info[ $layout_template_id ] ) ) {
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
	 * Get a single layout template class name by ID.
	 *
	 * @param string $id Layout template ID.
	 *
	 * @return string|null
	 */
	public function get_class_name( string $id ): ?string {
		$layout_template_info = isset( $this->layout_templates_info[ $id ] ) ? $this->layout_templates_info[ $id ] : null;

		return ! empty( $template_template_info ) && isset( $layout_template_info['class_name'] )
			? $layout_template_info['class_name']
			: null;
	}

	/**
	 * Get marching layout template class names.
	 *
	 * @return string[]
	 */
	public function get_class_names( array $query_params ): array {
		$area_to_match = isset( $query_params['area'] ) ? $query_params['area'] : null;
		$id_to_match   = isset( $query_params['id'] ) ? $query_params['id'] : null;

		$class_names = array();

		foreach ( $this->layout_templates_info as $layout_template_info ) {
			if ( ! empty( $area_to_match ) && isset( $layout_template_info['area'] ) && $layout_template_info['area'] !== $area_to_match ) {
				continue;
			}

			if ( ! empty( $id_to_match ) && isset( $layout_template_info['id'] ) && $layout_template_info['id'] !== $id_to_match ) {
				continue;
			}

			$class_names[] = $layout_template_info['class_name'];
		}

		return $class_names;
	}
}
