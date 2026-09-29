<?php
/**
 * Tests for how array-valued event properties reach the pixel URL.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use WorDBless\BaseTestCase;

/**
 * Array props must arrive at Tracks as one comma-joined value under their own key.
 *
 * Two regressions are guarded here: the bracketed keys (`prop[0]`) that
 * `http_build_query()` produces for a raw array, which Tracks rejects, and the
 * double URL-encoding that left `%2F` in the stored value once decoded.
 */
class WC_Analytics_Tracking_Array_Props_Test extends BaseTestCase {

	/**
	 * Build the pixel URL's raw query string for the given event properties.
	 *
	 * @param array $event_properties Properties handed to `get_properties()`.
	 * @return string The query string exactly as it leaves `http_build_query()`.
	 */
	private function raw_pixel_query( array $event_properties ): string {
		$props = WC_Analytics_Tracking::get_properties( 'woocommerceanalytics_add_to_cart', $event_properties );
		$url   = Pixel_Builder::build_tracks_url( $props );

		$this->assertIsString( $url, 'The pixel URL must build for array props.' );

		return (string) wp_parse_url( $url, PHP_URL_QUERY );
	}

	/**
	 * Decode the pixel URL once, the way the Tracks endpoint does.
	 *
	 * @param array $event_properties Properties handed to `get_properties()`.
	 * @return array Query parameters as Tracks would read them.
	 */
	private function decoded_pixel_query( array $event_properties ): array {
		parse_str( $this->raw_pixel_query( $event_properties ), $query );

		return $query;
	}

	/**
	 * Indexed arrays reach Tracks as a plain comma-joined string.
	 */
	public function test_indexed_array_prop_decodes_once_to_the_joined_value(): void {
		$query = $this->decoded_pixel_query(
			array(
				'additional_blocks_on_cart_page' => array( 'woocommerce/cart-cross-sells-block', 'core/paragraph' ),
			)
		);

		$this->assertSame(
			'woocommerce/cart-cross-sells-block,core/paragraph',
			$query['additional_blocks_on_cart_page'],
			'A single decode must yield the block names, not a second layer of %2F and %2C.'
		);
	}

	/**
	 * No key in the pixel URL carries the brackets that make Tracks reject the event.
	 */
	public function test_array_props_never_produce_bracketed_keys(): void {
		$event_properties = array(
			'additional_blocks_on_cart_page'     => array( 'core/paragraph', 'core/group' ),
			'additional_blocks_on_checkout_page' => array(),
		);

		// Checked on the raw query string: parse_str() would fold `prop[0]` back into a nested array and hide the brackets.
		$raw = $this->raw_pixel_query( $event_properties );
		$this->assertDoesNotMatchRegularExpression( '/(^|&)[^=&]*(%5B|%5D|\[|\])[^=&]*=/i', $raw );

		$query = $this->decoded_pixel_query( $event_properties );
		$this->assertSame( '', $query['additional_blocks_on_checkout_page'], 'An empty array is an empty value under the original key.' );
	}

	/**
	 * Associative arrays keep their JSON form, still decoding once to valid JSON.
	 */
	public function test_associative_array_prop_decodes_once_to_json(): void {
		$query = $this->decoded_pixel_query( array( 'meta' => array( 'a' => 1, 'b' => 'x/y' ) ) );

		$this->assertSame( array( 'a' => 1, 'b' => 'x/y' ), json_decode( $query['meta'], true ) );
	}
}
