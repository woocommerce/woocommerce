<?php
/**
 * WooCommerce Layout Template Registry compatibility shim.
 */

namespace Automattic\WooCommerce\LayoutTemplates;

/**
 * Removed layout template registry.
 *
 * @deprecated 10.9.0 Block template extension APIs were deprecated. The block templates API was removed in 11.0.0 with no replacement.
 */
final class LayoutTemplateRegistry {
	/**
	 * Singleton instance.
	 *
	 * @var LayoutTemplateRegistry|null
	 */
	private static $instance = null;

	/**
	 * Whether the removal warning has already been logged for the current request.
	 *
	 * @var bool
	 */
	private static $removal_warning_logged = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		self::maybe_log_removal_warning();
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return LayoutTemplateRegistry
	 */
	public static function get_instance(): LayoutTemplateRegistry {
		$instance = self::$instance;

		if ( null === $instance ) {
			$instance       = new self();
			self::$instance = $instance;
		}

		self::maybe_log_removal_warning();

		return $instance;
	}

	/**
	 * Unregister all layout templates.
	 *
	 * @return void
	 */
	public function unregister_all() {
		self::maybe_log_removal_warning();
	}

	/**
	 * Check if a layout template is registered.
	 *
	 * @param string $layout_template_id Layout template ID.
	 * @return bool
	 */
	public function is_registered( $layout_template_id ): bool {
		unset( $layout_template_id );

		self::maybe_log_removal_warning();

		return false;
	}

	/**
	 * Register a single layout template.
	 *
	 * @param string $layout_template_id         Layout template ID.
	 * @param string $layout_template_area       Layout template area.
	 * @param string $layout_template_class_name Layout template class to register.
	 * @return void
	 */
	public function register( $layout_template_id, $layout_template_area, $layout_template_class_name ) {
		unset( $layout_template_id, $layout_template_area, $layout_template_class_name );

		self::maybe_log_removal_warning();
	}

	/**
	 * Instantiate the matching layout templates and return them.
	 *
	 * @param array $query_params Query params.
	 * @return array
	 */
	public function instantiate_layout_templates( array $query_params = array() ): array {
		unset( $query_params );

		self::maybe_log_removal_warning();

		return array();
	}

	/**
	 * Log a warning about the removed compatibility class.
	 */
	private static function maybe_log_removal_warning(): void {
		if ( self::$removal_warning_logged || ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		self::$removal_warning_logged = true;

		wc_get_logger()->warning(
			'Automattic\WooCommerce\LayoutTemplates\LayoutTemplateRegistry is a temporary compatibility shim and will be removed soon. Block template extension APIs were deprecated in WooCommerce 10.9.0, and the block templates API was removed in WooCommerce 11.0.0 with no replacement.',
			array( 'source' => 'block-templates' )
		);
	}
}
