<?php

/**
 * Class WC_Shortcodes.
 *
 * @package WooCommerce\Tests\Shortcodes
 */
class WC_Test_Shortcodes extends WC_Unit_Test_Case {
	/**
	 * Test: WC_Shortcodes::product_categories().
	 */
	public function test_product_categories() {
		// Pre
		$categories = [
			(object)[
				'name' => 'out',
				'count' => 1,
			],
			(object)[
				'name' => 'empty',
				'count' => 0,
			],
			(object)[
				'name' => 'in',
			],
			(object)[
				'name' => 'in',
			],
			(object)[
				'name' => 'out',
			],
		];
		foreach ($categories as $i => &$category) {
			$category->term_id = $i;
			$category->taxonomy = 'product_cat';
			$category->slug = "{$category->name}-{$i}";
			if (! isset($category->count)) {
				$category->count = $i;
			}
		}
		$filtered_categories = array_slice($categories, 1, -1);
		$removed_categories = array_merge(
			[$categories[0]],
			array_slice($categories, -1)
		);

		// intercept `get_terms()`
		// WP_UnitTestCase should clear out hooks
		add_filter( 'get_terms', function( $terms, $taxonomy, $query_vars, $term_query ) use(&$categories) {
				if ( ['product_cat'] == $taxonomy ) {
					return $categories;
				} else {
					return $terms;
				}
			}, 10, 4 );

		// create a mock for the filter so we can test if it gets called
		$filter_prodcats = $this->getMockBuilder(stdClass::class)
			->setMethods(['__invoke'])
			->getMock();

		$filter_prodcats->expects($this->once())
			->method('__invoke')
			->willReturn( $filtered_categories );

		add_filter( 'woocommerce_product_categories', $filter_prodcats );

		$shortcode = new WC_Shortcodes();

		// In
		$result = $shortcode->product_categories( [] );

		// Post
		$doc = new DOMDocument();
		// generated HTML isn't valid, causing libxml to complain (about e.g. <mark> elements); since we don't care about that so much here, ignore errors
		$doc->loadHTML($result, LIBXML_NOWARNING | LIBXML_NOERROR);
		$xpather = new DOMXPath($doc);

		$this->assertSame( 2, $xpather->query('//a[contains(@href, "product_cat=in")]')->length, "Categories included should appear in output." );
		$this->assertSame( 0, $xpather->query('//a[contains(@href, "out")]')->length, "Categories filtered out shouldn't appear in output." );

		$this->assertSame( 0, $xpather->query('//a[contains(@href, "empty")]')->length, "Empty categories shouldn't appear in output, despite not being filtered out." );
	}
}
