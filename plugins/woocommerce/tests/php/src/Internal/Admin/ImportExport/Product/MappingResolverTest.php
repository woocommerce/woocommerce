<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\ImportExport\Product;

use Automattic\WooCommerce\Internal\Admin\ImportExport\Product\MappingResolver;
use WC_Unit_Test_Case;

/**
 * Tests for the MappingResolver class.
 */
class MappingResolverTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var MappingResolver
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new MappingResolver();
	}

	/**
	 * @testdox Should auto-map known English headers to their schema keys.
	 */
	public function test_resolve_auto_maps_known_headers(): void {
		$headers = array( 'SKU', 'Regular price', 'Meta: _foo', 'Attribute 1 name' );

		$result = $this->sut->resolve( $headers );

		$this->assertSame(
			array(
				'SKU'              => 'sku',
				'Regular price'    => 'regular_price',
				'Meta: _foo'       => 'meta:_foo',
				'Attribute 1 name' => 'attributes:name1',
			),
			$result['mapping']
		);
		$this->assertSame( array(), $result['unknown'], 'None of these headers should be reported as unknown' );
	}

	/**
	 * @testdox Should skip an unrecognised header by default.
	 */
	public function test_resolve_skips_unknown_header_by_default(): void {
		$result = $this->sut->resolve( array( 'Some Custom Column' ) );

		$this->assertSame( '', $result['mapping']['Some Custom Column'] );
		$this->assertSame( array( 'Some Custom Column' ), $result['unknown'] );
	}

	/**
	 * @testdox Should map an unrecognised header to meta when UNKNOWN_META is requested.
	 */
	public function test_resolve_maps_unknown_header_to_meta(): void {
		$result = $this->sut->resolve( array( 'Some Custom Column' ), array(), MappingResolver::UNKNOWN_META );

		$this->assertSame( 'meta:Some Custom Column', $result['mapping']['Some Custom Column'] );
		$this->assertSame( array( 'Some Custom Column' ), $result['unknown'] );
	}

	/**
	 * @testdox Should leave an empty header unmapped and unreported, even when UNKNOWN_META is requested.
	 */
	public function test_resolve_leaves_empty_header_unmapped(): void {
		$result = $this->sut->resolve( array( 'SKU', '' ), array(), MappingResolver::UNKNOWN_META );

		$this->assertSame( '', $result['mapping'][''] );
		$this->assertNotContains( '', $result['unknown'] );
	}

	/**
	 * @testdox Should let an override win over the auto-mapped target.
	 */
	public function test_resolve_override_wins_over_auto_map(): void {
		$result = $this->sut->resolve( array( 'SKU' ), array( 'SKU' => 'meta:sku_override' ) );

		$this->assertSame( 'meta:sku_override', $result['mapping']['SKU'] );
	}

	/**
	 * @testdox Should reject an override that differs from its column only by case, rather than silently ignoring it.
	 */
	public function test_resolve_throws_for_override_matching_no_header(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'No match for: Sku' );

		$this->sut->resolve( array( 'SKU' ), array( 'Sku' => 'meta:sku_override' ) );
	}

	/**
	 * @testdox Should treat override values of "-" and "" as skipping the column.
	 */
	public function test_resolve_dash_and_empty_override_skip_column(): void {
		$result = $this->sut->resolve(
			array( 'SKU', 'Name' ),
			array(
				'SKU'  => '-',
				'Name' => '',
			)
		);

		$this->assertSame( '', $result['mapping']['SKU'] );
		$this->assertSame( '', $result['mapping']['Name'] );
	}

	/**
	 * @testdox Should throw when an override target is not a valid schema key.
	 */
	public function test_resolve_throws_for_invalid_override_target(): void {
		$this->expectException( \InvalidArgumentException::class );

		$this->sut->resolve( array( 'SKU' ), array( 'SKU' => 'not_a_real_target' ) );
	}

	/**
	 * @testdox Should parse inline comma-separated Header=target pairs.
	 */
	public function test_parse_overrides_inline_pairs(): void {
		$result = $this->sut->parse_overrides( 'SKU=sku, Name = name' );

		$this->assertSame(
			array(
				'SKU'  => 'sku',
				'Name' => 'name',
			),
			$result
		);
	}

	/**
	 * @testdox Should parse an inline JSON object.
	 */
	public function test_parse_overrides_inline_json(): void {
		$result = $this->sut->parse_overrides( '{"SKU":"sku","Name":"name"}' );

		$this->assertSame(
			array(
				'SKU'  => 'sku',
				'Name' => 'name',
			),
			$result
		);
	}

	/**
	 * @testdox Should parse a JSON file referenced with an "@" prefix.
	 */
	public function test_parse_overrides_json_file(): void {
		$path = wp_tempnam( 'mapping-overrides.json' );
		file_put_contents( $path, wp_json_encode( array( 'SKU' => 'sku' ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		$result = $this->sut->parse_overrides( '@' . $path );

		wp_delete_file( $path );

		$this->assertSame( array( 'SKU' => 'sku' ), $result );
	}

	/**
	 * @testdox Should throw when the overrides file cannot be read.
	 */
	public function test_parse_overrides_throws_for_unreadable_file(): void {
		$this->expectException( \InvalidArgumentException::class );

		$this->sut->parse_overrides( '@/nonexistent/path/mapping.json' );
	}

	/**
	 * @testdox Should throw for malformed inline JSON.
	 */
	public function test_parse_overrides_throws_for_malformed_json(): void {
		$this->expectException( \InvalidArgumentException::class );

		$this->sut->parse_overrides( '{"SKU":' );
	}

	/**
	 * @testdox Should throw for a malformed inline pair without "=".
	 */
	public function test_parse_overrides_throws_for_malformed_pair(): void {
		$this->expectException( \InvalidArgumentException::class );

		$this->sut->parse_overrides( 'SKU-sku' );
	}

	/**
	 * @testdox Should validate plain, meta, attribute, and download targets.
	 */
	public function test_is_valid_target(): void {
		$this->assertTrue( $this->sut->is_valid_target( 'sku' ) );
		$this->assertTrue( $this->sut->is_valid_target( 'regular_price' ) );
		$this->assertTrue( $this->sut->is_valid_target( 'meta:_foo' ) );
		$this->assertTrue( $this->sut->is_valid_target( 'attributes:name1' ) );
		$this->assertTrue( $this->sut->is_valid_target( 'downloads:url2' ) );
		$this->assertFalse( $this->sut->is_valid_target( 'not_a_real_target' ) );
		$this->assertFalse( $this->sut->is_valid_target( 'attributes:name' ) );
	}
}
