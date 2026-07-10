<?php
/**
 * Tests for WC_Admin_Attributes.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

require_once WC_ABSPATH . '/includes/admin/class-wc-admin-attributes.php';

/**
 * WC_Admin_Attributes tests.
 */
class WC_Admin_Attributes_Test extends WC_Unit_Test_Case {

	/**
	 * Created attribute IDs to remove after each test.
	 *
	 * @var int[]
	 */
	private array $attribute_ids = array();

	/**
	 * Test attribute counter for unique slugs.
	 *
	 * @var int
	 */
	private static int $attribute_counter = 0;

	/**
	 * Clean up test attributes, filters, and attribute caches.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_attribute_taxonomy_row_actions' );

		foreach ( $this->attribute_ids as $attribute_id ) {
			wc_delete_attribute( $attribute_id );
		}

		$this->attribute_ids = array();

		parent::tearDown();
	}

	/**
	 * @testdox add_attribute() renders default edit and delete row actions for attribute taxonomies.
	 *
	 * @covers WC_Admin_Attributes::add_attribute()
	 */
	public function test_add_attribute_renders_default_row_actions(): void {
		$attribute_id        = $this->create_test_attribute( 'test_attr_default' );
		$expected_delete_url = wp_nonce_url(
			add_query_arg( 'delete', $attribute_id, 'edit.php?post_type=product&amp;page=product_attributes' ),
			'woocommerce-delete-attribute_' . $attribute_id
		);

		$output         = $this->render_add_attribute_page();
		$decoded_output = html_entity_decode( $output, ENT_QUOTES );

		$this->assertStringContainsString( '<div class="row-actions">', $output, 'Attribute rows should include row actions.' );
		$this->assertStringContainsString( '<span class="edit"><a href=', $output, 'The edit action should be rendered as a row action.' );
		$this->assertStringContainsString( '<span class="delete"><a class="delete" href=', $output, 'The delete action should preserve the delete class.' );
		$this->assertStringContainsString( 'edit.php?post_type=product&page=product_attributes&edit=' . $attribute_id, $decoded_output, 'The edit action should target the attribute edit screen.' );
		$this->assertStringContainsString( 'href="' . esc_url( $expected_delete_url ) . '"', $output, 'The delete action should retain its attribute-specific nonce.' );
	}

	/**
	 * @testdox add_attribute() filters attribute taxonomy row actions.
	 *
	 * @covers WC_Admin_Attributes::add_attribute()
	 */
	public function test_add_attribute_filters_attribute_taxonomy_row_actions(): void {
		$attribute_id      = $this->create_test_attribute( 'test_attr_filter' );
		$captured_actions  = null;
		$captured_tax      = null;
		$captured_taxonomy = null;

		add_filter(
			'woocommerce_attribute_taxonomy_row_actions',
			function ( array $actions, object $tax, string $taxonomy ) use ( $attribute_id, &$captured_actions, &$captured_tax, &$captured_taxonomy ): array {
				if ( $attribute_id !== (int) $tax->attribute_id ) {
					return $actions;
				}

				$captured_actions  = $actions;
				$captured_tax      = $tax;
				$captured_taxonomy = $taxonomy;
				unset( $actions['delete'] );
				$actions['sync'] = '<a href="' . esc_url( 'https://example.test/sync-attribute' ) . '">Sync</a>';

				return $actions;
			},
			10,
			3
		);

		$output         = $this->render_add_attribute_page();
		$decoded_output = html_entity_decode( $output, ENT_QUOTES );

		$this->assertIsArray( $captured_actions, 'The row actions filter should run for the test attribute.' );
		$this->assertArrayHasKey( 'edit', $captured_actions, 'Default edit action should be filterable.' );
		$this->assertArrayHasKey( 'delete', $captured_actions, 'Default delete action should be filterable.' );
		$this->assertSame( $attribute_id, (int) $captured_tax->attribute_id, 'The filter should receive the current attribute taxonomy object.' );
		$this->assertSame( 'pa_test_attr_filter', $captured_taxonomy, 'The filter should receive the full taxonomy name.' );
		$this->assertStringContainsString(
			'<span class="sync"><a href="https://example.test/sync-attribute">Sync</a></span>',
			$output,
			'Custom filtered actions should render as row actions.'
		);
		$this->assertStringNotContainsString(
			'edit.php?post_type=product&page=product_attributes&delete=' . $attribute_id,
			$decoded_output,
			'Filtered default actions should be removable.'
		);
	}

	/**
	 * Creates a global product attribute for the admin table.
	 *
	 * @param string $slug Attribute slug.
	 * @return int Created attribute ID.
	 */
	private function create_test_attribute( string $slug = '' ): int {
		++self::$attribute_counter;

		$slug         = '' === $slug ? 'test_attr_' . self::$attribute_counter : $slug;
		$attribute_id = wc_create_attribute(
			array(
				'name' => 'Test attribute ' . self::$attribute_counter,
				'slug' => $slug,
			)
		);

		$this->assertIsInt( $attribute_id, 'Test attribute should be created.' );
		$this->attribute_ids[] = $attribute_id;

		return $attribute_id;
	}

	/**
	 * Renders the add attribute admin page.
	 *
	 * @return string Rendered HTML.
	 */
	private function render_add_attribute_page(): string {
		ob_start();
		WC_Admin_Attributes::add_attribute();
		return (string) ob_get_clean();
	}
}
