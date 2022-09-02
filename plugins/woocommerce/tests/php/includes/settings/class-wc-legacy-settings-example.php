<?php
/**
 * Class WC_Legacy_Settings_Example file.
 *
 * @package WooCommerce\Tests\Settings
 */

/**
 * Helper class to test base functionality of WC_Settings_Page.
 * This simulates a legacy class that overrides the get_settings method directly.
 */
class WC_Legacy_Settings_Example extends WC_Settings_Page {
	// phpcs:disable Squiz.Commenting.FunctionComment.Missing

	public function __construct() {
		$this->id    = 'example';
		$this->label = 'Example';
		parent::__construct();
	}

	public function get_settings() {
		return array( 'foo' => 'bar' );
	}

	// phpcs:enable Squiz.Commenting.FunctionComment.Missing
}
