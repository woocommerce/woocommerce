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
		unset( $_GET['s'] );

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
	 * @testdox add_attribute() renders the standard admin search form and preserves page context.
	 *
	 * @covers WC_Admin_Attributes::add_attribute()
	 */
	public function test_add_attribute_renders_search_form(): void {
		$this->create_test_attribute( 'search_form_attribute' );

		$output = $this->render_add_attribute_page();

		$this->assertStringContainsString( '<form class="search-form wp-clearfix" method="get">', $output, 'The search should use the standard admin search form classes.' );
		$this->assertStringContainsString( '<input type="hidden" name="post_type" value="product" />', $output, 'The search form should preserve the product post type.' );
		$this->assertStringContainsString( '<input type="hidden" name="page" value="product_attributes" />', $output, 'The search form should preserve the Attributes page.' );
		$this->assertStringContainsString( '<label class="screen-reader-text" for="attribute-search-input">Search attributes:</label>', $output, 'The search input should have an accessible label.' );
		$this->assertStringContainsString( '<input type="search" id="attribute-search-input" name="s" value="" />', $output, 'The search form should submit the standard search parameter.' );
		$this->assertStringContainsString( 'id="search-submit"', $output, 'The search form should render the standard search submit button.' );
	}

	/**
	 * @testdox add_attribute() filters attributes by label case-insensitively.
	 *
	 * @covers WC_Admin_Attributes::add_attribute()
	 */
	public function test_add_attribute_filters_attributes_by_label(): void {
		$this->create_test_attribute( 'seasonal_tone', 'Summer Color' );
		$this->create_test_attribute( 'material_type', 'Material' );
		$_GET['s'] = 'COLOR';

		$output = $this->render_add_attribute_page();

		$this->assertStringContainsString( '>Summer Color</a>', $output, 'A partial case-insensitive label match should be rendered.' );
		$this->assertStringNotContainsString( '>Material</a>', $output, 'A nonmatching attribute should not be rendered.' );
	}

	/**
	 * @testdox add_attribute() filters attributes by slug case-insensitively.
	 *
	 * @covers WC_Admin_Attributes::add_attribute()
	 */
	public function test_add_attribute_filters_attributes_by_slug(): void {
		$this->create_test_attribute( 'summer_palette', 'Seasonal shade' );
		$this->create_test_attribute( 'material_type', 'Material' );
		$_GET['s'] = 'MER_PALETTE';

		$output = $this->render_add_attribute_page();

		$this->assertStringContainsString( '>Seasonal shade</a>', $output, 'A partial case-insensitive slug match should be rendered.' );
		$this->assertStringNotContainsString( '>Material</a>', $output, 'A nonmatching attribute should not be rendered.' );
	}

	/**
	 * @testdox add_attribute() renders all attributes when there is no search query.
	 *
	 * @covers WC_Admin_Attributes::add_attribute()
	 */
	public function test_add_attribute_renders_all_attributes_without_search_query(): void {
		$this->create_test_attribute( 'first_attribute', 'First attribute' );
		$this->create_test_attribute( 'second_attribute', 'Second attribute' );

		$output = $this->render_add_attribute_page();

		$this->assertStringContainsString( '>First attribute</a>', $output, 'The first attribute should be rendered without a search query.' );
		$this->assertStringContainsString( '>Second attribute</a>', $output, 'The second attribute should be rendered without a search query.' );
	}

	/**
	 * @testdox add_attribute() escapes the search query and renders the no-results state.
	 *
	 * @covers WC_Admin_Attributes::add_attribute()
	 */
	public function test_add_attribute_renders_escaped_search_query_and_no_results_state(): void {
		$this->create_test_attribute( 'material_type', 'Material' );
		$_GET['s'] = 'Color & "tone"';

		$output = $this->render_add_attribute_page();

		$this->assertStringContainsString( 'Search results for: <strong>Color &amp; &quot;tone&quot;</strong>', $output, 'The search subtitle should contain the escaped query.' );
		$this->assertStringContainsString( 'name="s" value="Color &amp; &quot;tone&quot;"', $output, 'The search input should retain the escaped query.' );
		$this->assertStringContainsString( 'No attributes found.', $output, 'An active search without matches should render the search-specific empty state.' );
		$this->assertStringNotContainsString( '>Material</a>', $output, 'An attribute that does not match the query should not be rendered.' );
	}

	/**
	 * @testdox add_attribute() preserves the empty collection message when no search is active.
	 *
	 * @covers WC_Admin_Attributes::add_attribute()
	 */
	public function test_add_attribute_renders_empty_collection_message_without_search(): void {
		$output = $this->render_add_attribute_page();

		$this->assertStringContainsString( 'No attributes currently exist.', $output, 'The original empty collection message should be preserved.' );
		$this->assertStringNotContainsString( '<form class="search-form wp-clearfix"', $output, 'The search form should be hidden when there are no attributes and no active search.' );
	}

	/**
	 * Creates a global product attribute for the admin table.
	 *
	 * @param string $slug Attribute slug.
	 * @param string $name Attribute name.
	 * @return int Created attribute ID.
	 */
	private function create_test_attribute( string $slug = '', string $name = '' ): int {
		++self::$attribute_counter;

		$slug         = '' === $slug ? 'test_attr_' . self::$attribute_counter : $slug;
		$name         = '' === $name ? 'Test attribute ' . self::$attribute_counter : $name;
		$attribute_id = wc_create_attribute(
			array(
				'name' => $name,
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
