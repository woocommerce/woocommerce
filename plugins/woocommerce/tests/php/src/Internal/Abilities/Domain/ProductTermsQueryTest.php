<?php
/**
 * Product term query ability tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\Domain\ProductTermsQuery;

/**
 * Tests product term reads through the registered ability.
 */
class ProductTermsQueryTest extends \WC_Unit_Test_Case {

	/**
	 * Set up ability registration.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * @testdox Should preserve the data returned by each existing term read, using include for individual terms.
	 * @testWith ["product_cat", "WC_REST_Product_Categories_Controller"]
	 *           ["product_tag", "WC_REST_Product_Tags_Controller"]
	 *           ["product_brand", "WC_REST_Product_Brands_Controller"]
	 * @param string $taxonomy Taxonomy.
	 * @param string $controller_class Existing read controller.
	 */
	public function test_output_parity( string $taxonomy, string $controller_class ): void {
		$id = self::factory()->term->create(
			array(
				'taxonomy'    => $taxonomy,
				'name'        => 'Summer',
				'description' => 'Seasonal products',
			)
		);
		update_term_meta( $id, 'display_type', 'both' );
		update_term_meta( $id, 'order', 7 );
		$controller = new $controller_class();
		$expected   = $controller->prepare_item_for_response( get_term( $id, $taxonomy ), new \WP_REST_Request() )->get_data();
		unset( $expected['image'] );

		$ability = new \WP_Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() );
		$result  = $ability->execute(
			array(
				'taxonomy' => $taxonomy,
				'include'  => array( $id ),
			)
		);
		$this->assertIsArray( $result );
		$this->assertCount( 1, $result['terms'] );
		foreach ( $expected as $key => $value ) {
			$this->assertSame( $value, $result['terms'][0][ $key ], $key );
		}
		$this->assertArrayNotHasKey( 'image', $result['terms'][0] );
	}

	/**
	 * @testdox Should filter, order and paginate product terms without leaking other taxonomies.
	 */
	public function test_filters_and_pagination(): void {
		$parent = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Parent',
			)
		);
		$first  = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Match A',
				'parent'   => $parent,
			)
		);
		$second = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Match B',
				'parent'   => $parent,
			)
		);
		$tag    = self::factory()->term->create(
			array(
				'taxonomy' => 'product_tag',
				'name'     => 'Match C',
			)
		);
		$input  = array(
			'taxonomy' => 'product_cat',
			'search'   => 'Match',
			'parent'   => $parent,
			'per_page' => 1,
			'page'     => 2,
			'orderby'  => 'name',
		);
		$result = ProductTermsQuery::execute( $input );
		$this->assertSame( $second, $result['terms'][0]['id'] );
		$this->assertSame( 2, $result['total_pages'] );
		$this->assertSame( 2, $result['total'] );
		$this->assertSame( 2, $result['page'] );

		$result = ProductTermsQuery::execute(
			array(
				'taxonomy' => 'product_cat',
				'include'  => array( $second, $first, $tag ),
				'orderby'  => 'include',
			)
		);
		$this->assertSame( array( $second, $first ), array_column( $result['terms'], 'id' ) );
		$result = ProductTermsQuery::execute(
			array(
				'taxonomy'   => 'product_cat',
				'include'    => array( $first ),
				'hide_empty' => true,
			)
		);
		$this->assertCount( 0, $result['terms'] );
	}

	/**
	 * @testdox Should support product, slug, exclude and offset filters.
	 */
	public function test_remaining_filters(): void {
		$first   = self::factory()->term->create(
			array(
				'taxonomy' => 'product_tag',
				'name'     => 'Alpha',
				'slug'     => 'alpha',
			)
		);
		$second  = self::factory()->term->create(
			array(
				'taxonomy' => 'product_tag',
				'name'     => 'Beta',
				'slug'     => 'beta',
			)
		);
		$product = \WC_Helper_Product::create_simple_product();
		wp_set_object_terms( $product->get_id(), array( $first, $second ), 'product_tag' );
		$result = ProductTermsQuery::execute(
			array(
				'taxonomy' => 'product_tag',
				'product'  => $product->get_id(),
				'slug'     => 'beta',
			)
		);
		$this->assertSame( array( $second ), array_column( $result['terms'], 'id' ) );
		$result = ProductTermsQuery::execute(
			array(
				'taxonomy' => 'product_tag',
				'slug'     => array( 'alpha', 'beta' ),
				'exclude'  => array( $first ),
			)
		);
		$this->assertSame( array( $second ), array_column( $result['terms'], 'id' ) );
		$result = ProductTermsQuery::execute(
			array(
				'taxonomy' => 'product_tag',
				'include'  => array( $first, $second ),
				'offset'   => 1,
				'order'    => 'desc',
			)
		);
		$this->assertSame( array( $first ), array_column( $result['terms'], 'id' ) );
	}

	/**
	 * @testdox Should enforce read permissions and validate taxonomy and pagination through the ability.
	 */
	public function test_permissions_and_validation(): void {
		$ability = new \WP_Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() );
		$this->assertWPError( $ability->execute( array( 'taxonomy' => 'category' ) ) );
		$this->assertWPError(
			$ability->execute(
				array(
					'taxonomy' => 'product_cat',
					'per_page' => 0,
				)
			)
		);
		$this->assertWPError( $ability->execute( array() ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );
		$this->assertWPError( $ability->execute( array( 'taxonomy' => 'product_cat' ) ) );
		wp_set_current_user( 0 );
		$this->assertWPError( $ability->execute( array( 'taxonomy' => 'product_cat' ) ) );
	}
}
