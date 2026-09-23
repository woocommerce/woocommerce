<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use WC_Unit_Test_Case;

/**
 * Tests for the Product Collection block utilities.
 */
class UtilsTest extends WC_Unit_Test_Case {

	/**
	 * Block pattern registered by a test, unregistered on tear down.
	 *
	 * @var string|null
	 */
	private $registered_pattern = null;

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( $this->registered_pattern ) {
			unregister_block_pattern( $this->registered_pattern );
			$this->registered_pattern = null;
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should read the products per page of an inherited Product Collection from template content.
	 */
	public function test_reads_archive_products_per_page_from_inherited_collection(): void {
		$content = $this->get_collection_markup(
			array(
				'inherit'        => true,
				'archivePerPage' => 24,
			)
		);

		$this->assertSame(
			24,
			ProductCollectionUtils::get_archive_products_per_page_from_content( $content ),
			'The inherited collection should size the archive at 24 products per page.'
		);
	}

	/**
	 * @testdox Should ignore a products per page value on a collection that does not inherit the archive query.
	 */
	public function test_ignores_archive_products_per_page_on_custom_query(): void {
		$content = $this->get_collection_markup(
			array(
				'inherit'        => false,
				'archivePerPage' => 24,
			)
		);

		$this->assertNull(
			ProductCollectionUtils::get_archive_products_per_page_from_content( $content ),
			'A custom query has its own page size and must not touch the archive query.'
		);
	}

	/**
	 * @testdox Should return null when the inherited collection does not set products per page.
	 */
	public function test_returns_null_without_archive_products_per_page(): void {
		$content = $this->get_collection_markup(
			array(
				'inherit' => true,
				'perPage' => 9,
			)
		);

		$this->assertNull(
			ProductCollectionUtils::get_archive_products_per_page_from_content( $content ),
			'The unrelated custom-query perPage must never leak into the archive query.'
		);
	}

	/**
	 * @testdox Should reject a products per page value that is not a whole number within range.
	 * @dataProvider provide_invalid_products_per_page
	 *
	 * @param mixed $value The attribute value to reject.
	 */
	public function test_rejects_invalid_archive_products_per_page( $value ): void {
		$content = $this->get_collection_markup(
			array(
				'inherit'        => true,
				'archivePerPage' => $value,
			)
		);

		$this->assertNull(
			ProductCollectionUtils::get_archive_products_per_page_from_content( $content ),
			'Only whole numbers from 1 to 100 may size an archive page.'
		);
	}

	/**
	 * Values that must not become an archive page size.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function provide_invalid_products_per_page(): array {
		return array(
			'zero'         => array( 0 ),
			'negative'     => array( -5 ),
			'too large'    => array( 101 ),
			'fraction'     => array( 7.5 ),
			'text'         => array( 'twelve' ),
			'boolean'      => array( true ),
			'empty string' => array( '' ),
		);
	}

	/**
	 * @testdox Should accept the range boundaries and numeric strings.
	 * @testWith [1, 1]
	 *           [100, 100]
	 *           ["24", 24]
	 *
	 * @param mixed $value    The attribute value.
	 * @param int   $expected The page size it should produce.
	 */
	public function test_accepts_boundary_and_numeric_string_values( $value, int $expected ): void {
		$content = $this->get_collection_markup(
			array(
				'inherit'        => true,
				'archivePerPage' => $value,
			)
		);

		$this->assertSame( $expected, ProductCollectionUtils::get_archive_products_per_page_from_content( $content ) );
	}

	/**
	 * @testdox Should find the inherited collection when it is nested inside other blocks.
	 */
	public function test_finds_nested_inherited_collection(): void {
		$content = '<!-- wp:group --><div class="wp-block-group"><!-- wp:group --><div class="wp-block-group">'
			. $this->get_collection_markup(
				array(
					'inherit'        => true,
					'archivePerPage' => 12,
				)
			)
			. '</div><!-- /wp:group --></div><!-- /wp:group -->';

		$this->assertSame( 12, ProductCollectionUtils::get_archive_products_per_page_from_content( $content ) );
	}

	/**
	 * @testdox Should follow a block pattern to find the inherited collection.
	 */
	public function test_follows_block_pattern_to_inherited_collection(): void {
		$this->registered_pattern = 'woocommerce-tests/archive-collection-' . uniqid();
		register_block_pattern(
			$this->registered_pattern,
			array(
				'title'   => 'Archive collection',
				'content' => $this->get_collection_markup(
					array(
						'inherit'        => true,
						'archivePerPage' => 18,
					)
				),
			)
		);

		$content = sprintf( '<!-- wp:pattern {"slug":"%s"} /-->', $this->registered_pattern );

		$this->assertSame( 18, ProductCollectionUtils::get_archive_products_per_page_from_content( $content ) );
	}

	/**
	 * @testdox Should use the first inherited collection when a template holds more than one collection.
	 */
	public function test_uses_first_inherited_collection(): void {
		$content = $this->get_collection_markup(
			array(
				'inherit'        => false,
				'archivePerPage' => 3,
			)
		)
			. $this->get_collection_markup(
				array(
					'inherit'        => true,
					'archivePerPage' => 30,
				)
			)
			. $this->get_collection_markup(
				array(
					'inherit'        => true,
					'archivePerPage' => 40,
				)
			);

		$this->assertSame( 30, ProductCollectionUtils::get_archive_products_per_page_from_content( $content ) );
	}

	/**
	 * @testdox Should return null for empty content.
	 */
	public function test_returns_null_for_empty_content(): void {
		$this->assertNull( ProductCollectionUtils::get_archive_products_per_page_from_content( '' ) );
	}

	/**
	 * Serialized Product Collection block markup with the given query attributes.
	 *
	 * @param array $query Query attributes to merge into the base parsed block.
	 * @return string
	 */
	private function get_collection_markup( array $query ): string {
		$block                   = Utils::get_base_parsed_block();
		$block['attrs']['query'] = array_merge( $block['attrs']['query'], $query );

		return sprintf(
			'<!-- wp:woocommerce/product-collection %s --><div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template /--></div><!-- /wp:woocommerce/product-collection -->',
			wp_json_encode( $block['attrs'] )
		);
	}
}
