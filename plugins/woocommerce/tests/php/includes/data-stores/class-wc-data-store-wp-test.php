<?php declare( strict_types = 1 );

/**
 * Class WC_Data_Store_WP_Test
 */
final class WC_Data_Store_WP_Test extends WC_Unit_Test_Case {

	/**
	 * @return array
	 */
	public function provider_update_or_delete_post_meta(): array {
		return array(
			'empty string — key absent'  => array( static fn() => null, '', false, '' ),
			'empty string — key present' => array( static fn( $id ) => add_post_meta( $id, '_sku', 'old' ), '', true, '' ),
			'empty array — key absent'   => array( static fn() => null, array(), false, '' ),
			'empty array — key present'  => array( static fn( $id ) => add_post_meta( $id, '_sku', 'old' ), array(), true, '' ),
			'non-empty — key absent'     => array( static fn() => null, 'new-sku', true, 'new-sku' ),
			'non-empty — key present'    => array( static fn( $id ) => add_post_meta( $id, '_sku', 'old' ), 'new-sku', true, 'new-sku' ),
		);
	}

	/**
	 * @dataProvider provider_update_or_delete_post_meta
	 * @testdox update_or_delete_post_meta writes, deletes, or skips based on value and key presence.
	 *
	 * @param Closure $setup           Receives the product ID; sets up pre-existing meta if needed.
	 * @param mixed   $meta_value      Value passed to update_or_delete_post_meta.
	 * @param bool    $expected_result Expected boolean return value.
	 * @param mixed   $expected_stored Expected get_post_meta result after the call.
	 */
	public function test_update_or_delete_post_meta( Closure $setup, $meta_value, bool $expected_result, $expected_stored ): void {
		$store = new class() extends WC_Product_Data_Store_CPT {
			public function update_or_delete_post_meta( $product, $meta_key, $meta_value ): bool { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found, Squiz.Commenting.FunctionComment.Missing
				return parent::update_or_delete_post_meta( $product, $meta_key, $meta_value );
			}
		};

		$product = new WC_Product();
		$product->save();
		$product_id = $product->get_id();
		$setup( $product_id );

		$result = $store->update_or_delete_post_meta( $product, '_sku', $meta_value );

		$this->assertSame( $expected_result, $result );
		$this->assertSame( $expected_stored, get_post_meta( $product_id, '_sku', true ) );

		$product->delete();
	}

	/**
	 * Values that cannot be used as a string and used to raise an uncaught TypeError.
	 *
	 * `parse_date_for_wp_query()` guards its parsing with `catch ( Exception )`, but PHP 8 raises a
	 * `TypeError` when such a value reaches `preg_match()`. `TypeError` extends `Error`, so it
	 * escaped that guard and surfaced as a fatal error.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function provider_unusable_date_query_vars(): array {
		return array(
			'array'        => array( array( 'foo' ) ),
			'nested array' => array( array( array() ) ),
			'empty array'  => array( array() ),
			'object'       => array( new stdClass() ),
		);
	}

	/**
	 * @dataProvider provider_unusable_date_query_vars
	 * @testdox parse_date_for_wp_query treats values that cannot be used as a string like an unparseable date.
	 *
	 * @param mixed $query_var The malformed date value.
	 */
	public function test_parse_date_for_wp_query_handles_unusable_values( $query_var ): void {
		$store = new WC_Order_Data_Store_CPT();

		$expected = $store->parse_date_for_wp_query( 'not-a-date', 'post_date', array() );
		$actual   = $store->parse_date_for_wp_query( $query_var, 'post_date', array() );

		$this->assertSame(
			$expected,
			$actual,
			'Unusable date values should produce the same query as any other unparseable date.'
		);
	}

	/**
	 * @testdox parse_date_for_wp_query keeps working for values it already accepted.
	 */
	public function test_parse_date_for_wp_query_accepts_valid_values(): void {
		$store = new WC_Order_Data_Store_CPT();

		$from_string = $store->parse_date_for_wp_query( '2024-07-04', 'post_date', array() );
		$this->assertSame( '2024', $from_string['date_query'][0]['year'] );
		$this->assertSame( '7', $from_string['date_query'][0]['month'] );
		$this->assertSame( '4', $from_string['date_query'][0]['day'] );

		$from_datetime = $store->parse_date_for_wp_query(
			new WC_DateTime( '2024-07-04 12:00:00', new DateTimeZone( 'UTC' ) ),
			'post_date',
			array()
		);
		$this->assertNotEmpty( $from_datetime['date_query'], 'WC_DateTime values must still build a date query.' );

		$from_shorthand = $store->parse_date_for_wp_query( '>=2024-07-04', 'post_date', array() );
		$this->assertArrayHasKey( 'after', $from_shorthand['date_query'][0], 'Shorthand operators must still be honoured.' );
	}
}
