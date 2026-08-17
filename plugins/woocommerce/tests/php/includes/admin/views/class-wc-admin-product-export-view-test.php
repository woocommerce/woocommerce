<?php
/**
 * Tests for the Product Export admin view.
 *
 * @package WooCommerce\Tests\Admin\Views
 */

/**
 * Product Export admin view tests.
 */
class WC_Admin_Product_Export_View_Test extends WC_Unit_Test_Case {

	/**
	 * Load the dependencies used directly by the view.
	 */
	public function setUp(): void {
		parent::setUp();

		require_once WC_ABSPATH . 'includes/export/class-wc-product-csv-exporter.php';
		require_once WC_ABSPATH . 'includes/admin/class-wc-admin-exporters.php';
	}

	/**
	 * @testdox Selected products render the exact singular and plural export states.
	 */
	public function test_selected_products_render_exact_export_state(): void {
		$product_ids = array();

		try {
			$first_product = WC_Helper_Product::create_simple_product();
			$product_ids[] = $first_product->get_id();
			$second_product = WC_Helper_Product::create_simple_product();
			$product_ids[]  = $second_product->get_id();

			$singular_output = $this->render_export_view( array( $first_product->get_id() ) );

			$this->assertStringContainsString( 'You are about to export 1 product.', $singular_output );
			$this->assertSame( (string) $first_product->get_id(), $this->get_hidden_product_ids( $singular_output ) );
			$this->assertSelectedExportStructure( $singular_output );

			$plural_output = $this->render_export_view( array( $first_product->get_id(), $second_product->get_id() ) );

			$this->assertStringContainsString( 'You are about to export 2 products.', $plural_output );
			$this->assertSame(
				$first_product->get_id() . ',' . $second_product->get_id(),
				$this->get_hidden_product_ids( $plural_output )
			);
			$this->assertSelectedExportStructure( $plural_output );
		} finally {
			foreach ( array_reverse( $product_ids ) as $product_id ) {
				WC_Helper_Product::delete_product( $product_id );
			}
		}
	}

	/**
	 * @testdox No selected products render the default export state.
	 */
	public function test_no_selected_products_render_default_export_state(): void {
		$output = $this->render_export_view( array() );

		$this->assertStringNotContainsString( 'id="selected-product-export-notice"', $output );
		$this->assertNull( $this->get_hidden_product_ids( $output ) );
		$this->assertStringContainsString( 'containing a list of all products', $output );
		$this->assertStringContainsString( 'for="woocommerce-exporter-types"', $output );
		$this->assertStringContainsString( 'for="woocommerce-exporter-category"', $output );
	}

	/**
	 * Assert the selection-specific view structure.
	 *
	 * @param string $output Rendered view output.
	 */
	private function assertSelectedExportStructure( string $output ): void {
		$this->assertStringContainsString( 'id="selected-product-export-notice"', $output );
		$this->assertStringContainsString( 'clear your selection', $output );
		$this->assertStringContainsString( 'containing the selected products', $output );
		$this->assertStringNotContainsString( 'for="woocommerce-exporter-types"', $output );
		$this->assertStringNotContainsString( 'for="woocommerce-exporter-category"', $output );

		$this->assertMatchesRegularExpression(
			'/<a href="(?![^"]*product_ids=)[^"]*page=product_exporter[^"]*">clear your selection<\/a>/',
			$output,
			'The clear link should retain the exporter route without the selected product IDs.'
		);
	}

	/**
	 * Get the hidden selected product IDs from rendered markup.
	 *
	 * @param string $output Rendered view output.
	 * @return string|null
	 */
	private function get_hidden_product_ids( string $output ): ?string {
		$processor = new WP_HTML_Tag_Processor( $output );

		while ( $processor->next_tag( array( 'tag_name' => 'INPUT' ) ) ) {
			if ( 'product_ids' === $processor->get_attribute( 'name' ) ) {
				$value = $processor->get_attribute( 'value' );
				return is_string( $value ) ? $value : null;
			}
		}

		return null;
	}

	/**
	 * Render the Product Export view with an isolated admin request.
	 *
	 * @param int[] $product_ids Selected product IDs.
	 * @return string
	 */
	private function render_export_view( array $product_ids ): string {
		global $wp_scripts;

		$original_get            = $_GET;
		$original_request        = $_REQUEST;
		$original_user_id        = get_current_user_id();
		$original_buffer_level   = ob_get_level();
		$had_request_uri         = isset( $_SERVER['REQUEST_URI'] );
		$original_request_uri    = $had_request_uri ? $_SERVER['REQUEST_URI'] : null;
		$had_wp_scripts          = isset( $wp_scripts );
		$original_scripts_queue  = $had_wp_scripts ? $wp_scripts->queue : array();
		$original_scripts_to_do  = $had_wp_scripts ? $wp_scripts->to_do : array();
		$admin_user_id           = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$output                  = '';

		try {
			wp_set_current_user( $admin_user_id );

			$_GET = array( 'page' => 'product_exporter' );
			if ( $product_ids ) {
				$_GET['product_ids'] = implode( ',', $product_ids );
				$_GET['_wpnonce']     = wp_create_nonce( 'export-selected-products' );
			}
			$_REQUEST              = $_GET;
			$_SERVER['REQUEST_URI'] = add_query_arg( $_GET, '/wp-admin/admin.php' );

			ob_start();
			include WC_ABSPATH . 'includes/admin/views/html-admin-page-product-export.php';
			$output = (string) ob_get_clean();
		} finally {
			while ( ob_get_level() > $original_buffer_level ) {
				ob_end_clean();
			}

			$_GET     = $original_get;
			$_REQUEST = $original_request;
			if ( $had_request_uri ) {
				$_SERVER['REQUEST_URI'] = $original_request_uri;
			} else {
				unset( $_SERVER['REQUEST_URI'] );
			}

			if ( $had_wp_scripts ) {
				$wp_scripts->queue = $original_scripts_queue;
				$wp_scripts->to_do = $original_scripts_to_do;
			} else {
				unset( $wp_scripts );
			}

			wp_set_current_user( $original_user_id );
			wp_delete_user( $admin_user_id );
		}

		return $output;
	}
}
