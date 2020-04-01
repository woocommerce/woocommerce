<?php
/**
 * Class WC_Settings_Example file.
 *
 * @package WooCommerce\Tests\Settings
 */

/**
 * Helper class to test base functionality of WC_Settings_Page.
 */
class WC_Settings_Example extends WC_Settings_Page {
	// phpcs:disable Squiz.Commenting.FunctionComment.Missing

	// phpcs:disable Squiz.Commenting.VariableComment.Missing
	public $output_fields_argument;
	public $save_fields_argument;
	// phpcs:enable Squiz.Commenting.VariableComment.Missing

	public function __construct() {
		$this->id    = 'example';
		$this->label = 'Example';
		parent::__construct();

		$this->output_fields_argument = null;
		$this->save_fields_argument   = null;
	}

	protected function get_settings_for_default_section() {
		return array( 'key' => 'value' );
	}

	protected function get_settings_for_foobar_section() {
		return array( 'foo' => 'bar' );
	}

	protected function get_settings_for_section( $current_section ) {
		return array( "${current_section}_key" => "${current_section}_value" );
	}

	protected function get_own_sections() {
		$sections                = parent::get_own_sections();
		$sections['new_section'] = 'New Section';
		return $sections;
	}

	protected function output_fields( $settings ) {
		$this->output_fields_argument = $settings;
	}

	protected function save_fields( $settings ) {
		$this->save_fields_argument = $settings;
	}

	// phpcs:enable Squiz.Commenting.FunctionComment.Missing
}
