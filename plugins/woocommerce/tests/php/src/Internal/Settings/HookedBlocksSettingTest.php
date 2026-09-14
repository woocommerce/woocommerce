<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Settings;

use Automattic\WooCommerce\Internal\Settings\HookedBlocksSetting;
use WC_Admin_Settings;
use WC_Unit_Test_Case;

/**
 * Tests for the HookedBlocksSetting class.
 */
class HookedBlocksSettingTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var HookedBlocksSetting
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WC_Admin_Settings', false ) ) {
			require_once WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';
		}

		$this->sut = wc_get_container()->get( HookedBlocksSetting::class );
		delete_option( HookedBlocksSetting::OPTION_NAME );
	}

	/**
	 * @testdox Should not add any fields when the active theme is a classic theme.
	 */
	public function test_adds_no_fields_for_classic_themes(): void {
		switch_theme( 'storefront' );

		$fields = $this->sut->handle_woocommerce_general_settings( array() );

		$this->assertSame( array(), $fields, 'Classic themes should not get the header icons setting' );
	}

	/**
	 * @testdox Should show the checkbox reflecting the stored option on an uncustomized block theme header.
	 *
	 * @testWith ["8.4.0", "yes"]
	 *           ["no", "no"]
	 *           [null, "no"]
	 *
	 * @param string|null $stored   Stored option value, null when absent.
	 * @param string      $expected Expected checkbox value.
	 */
	public function test_shows_checkbox_for_uncustomized_block_theme( ?string $stored, string $expected ): void {
		switch_theme( 'twentytwentytwo' );
		if ( null !== $stored ) {
			update_option( HookedBlocksSetting::OPTION_NAME, $stored );
		}

		$field = $this->find_field( $this->sut->handle_woocommerce_general_settings( array() ), HookedBlocksSetting::FIELD_ID );

		$this->assertNotNull( $field, 'The checkbox should be shown for an uncustomized header' );
		$this->assertSame( 'checkbox', $field['type'] );
		$this->assertSame( $expected, $field['value'], 'The checkbox should reflect the stored option' );
	}

	/**
	 * @testdox Should show a Site Editor note instead of the checkbox when a header template part is customized.
	 */
	public function test_shows_note_instead_of_checkbox_for_customized_header(): void {
		switch_theme( 'twentytwentytwo' );
		update_option( HookedBlocksSetting::OPTION_NAME, '9.2.0' );
		$this->create_customized_header_part();

		$fields = $this->sut->handle_woocommerce_general_settings( array() );

		$this->assertNull( $this->find_field( $fields, HookedBlocksSetting::FIELD_ID ), 'The checkbox should be hidden for a customized header' );
		$note = $this->find_field( $fields, HookedBlocksSetting::NOTE_ID );
		$this->assertNotNull( $note, 'A note should be shown for a customized header' );
		$this->assertStringContainsString( 'site-editor.php', $note['text'], 'The note should link to the Site Editor' );
	}

	/**
	 * @testdox Should write the hooked blocks option on save without storing the virtual field.
	 *
	 * @testWith ["9.2.0", false, "no"]
	 *           ["no", false, "no"]
	 *           ["no", true, "stable"]
	 *           [null, true, "stable"]
	 *           ["8.4.0", true, "8.4.0"]
	 *
	 * @param string|null $stored   Stored option value before save, null when absent.
	 * @param bool        $checked  Whether the checkbox is submitted checked.
	 * @param string      $expected Expected option value after save; "stable" means the current stable WooCommerce version.
	 */
	public function test_save_writes_hooked_blocks_option( ?string $stored, bool $checked, string $expected ): void {
		switch_theme( 'twentytwentytwo' );
		if ( null !== $stored ) {
			update_option( HookedBlocksSetting::OPTION_NAME, $stored );
		}
		$data = $checked ? array( HookedBlocksSetting::FIELD_ID => 'yes' ) : array( 'unrelated_field' => '1' );

		WC_Admin_Settings::save_fields( $this->sut->handle_woocommerce_general_settings( array() ), $data );

		$this->assertSame( 'stable' === $expected ? WC()->stable_version() : $expected, get_option( HookedBlocksSetting::OPTION_NAME ) );
		$this->assertNull( $this->get_stored_field_value(), 'The virtual field should never be stored' );
	}

	/**
	 * @testdox Should not store the virtual field when WooCommerce creates default options on install.
	 */
	public function test_install_does_not_store_virtual_field(): void {
		switch_theme( 'twentytwentytwo' );

		( new \ReflectionMethod( \WC_Install::class, 'create_options' ) )->invoke( null );

		$this->assertNull( $this->get_stored_field_value(), 'The virtual field should not get a default option on install' );
	}

	/**
	 * Read the virtual field's row straight from the options table, bypassing the pre_option filter.
	 *
	 * @return string|null Stored value, or null when no row exists.
	 */
	private function get_stored_field_value(): ?string {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", HookedBlocksSetting::FIELD_ID ) );
	}

	/**
	 * Find a settings field by id.
	 *
	 * @param array  $fields Settings fields.
	 * @param string $id     Field id.
	 * @return array|null
	 */
	private function find_field( array $fields, string $id ): ?array {
		foreach ( $fields as $field ) {
			if ( ( $field['id'] ?? '' ) === $id ) {
				return $field;
			}
		}
		return null;
	}

	/**
	 * Save a customized header-area template part for the active theme.
	 */
	private function create_customized_header_part(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'wp_template_part',
				'post_name'    => 'header',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:navigation /-->',
			)
		);
		wp_set_post_terms( $post_id, get_stylesheet(), 'wp_theme' );
		wp_set_post_terms( $post_id, WP_TEMPLATE_PART_AREA_HEADER, 'wp_template_part_area' );
	}
}
