<?php
/**
 * Class WC_Settings_Example file.
 *
 * @package WooCommerce\Tests\Settings
 */

declare(strict_types=1);

/**
 * Helper class to test base functionality of WC_Settings_Page.
 */
class WC_Settings_Migration_Test extends WC_Settings_Page {
	// phpcs:disable Squiz.Commenting.FunctionComment.Missing

	public function __construct() {
		$this->id    = 'migration';
		$this->label = 'Migration Test';
		parent::__construct();

		add_action( 'woocommerce_admin_field_foobar_section_custom_type_field', array( $this, 'foobar_section_custom_type_field' ) );
	}

	protected function get_settings_for_default_section() {
		return array(
			array(
				'title' => 'Default Section',
				'type'  => 'text',
			),
		);
	}

	protected function get_settings_for_foobar_section() {
		return array(
			array(
				'title' => 'Foobar Section',
				'type'  => 'text',
			),
			array(
				'title' => 'Custom Type Field',
				'type'  => 'foobar_section_custom_type_field',
			),
		);
	}

	protected function get_settings_for_custom_view_with_parent_output_section() {
		return array(
			array(
				'title' => 'Custom View With Parent Output',
				'type'  => 'text',
			),
		);
	}

	protected function get_settings_for_custom_view_without_parent_output_section() {
		// Expect not to be rendered since it doesn't call parent::output().
		return array(
			array(
				'title' => 'Custom View Without Parent Output',
				'type'  => 'text',
			),
		);
	}

	protected function get_own_sections() {
		return array(
			''                                  => 'Default',
			'foobar'                            => 'Foobar',
			'custom_view_with_parent_output'    => 'Custom View With Parent Output',
			'custom_view_without_parent_output' => 'Custom View Without Parent Output',
		);
	}

	public function output() {
		global $current_section;

		if ( 'custom_view_without_parent_output' === $current_section ) {
			?>
			<div>Custom View Without Parent Output</div>
			<?php
			return;
		}

		parent::output();

		if ( 'custom_view_with_parent_output' === $current_section ) {
			?>
			<div>Custom View With Parent Output</div>
			<?php
			return;
		}
	}

	public function foobar_section_custom_type_field( $setting ) {
		?>
		<div><?php echo esc_html( $setting['title'] ); ?></div>
		<?php
	}

	// phpcs:enable Squiz.Commenting.FunctionComment.Missing
}
