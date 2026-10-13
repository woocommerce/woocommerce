<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\SharedStores;

use Automattic\WooCommerce\Blocks\SharedStores\ProductScopes;
use Automattic\WooCommerce\Internal\ProductFilters\Params;
use WC_Helper_Product;
use WC_Unit_Test_Case;
use WP;
use WP_HTML_Tag_Processor;

/**
 * Tests for the ProductScopes naming helper.
 */
class ProductScopesTest extends WC_Unit_Test_Case {

	/**
	 * The name WordPress reports a rejected form name under.
	 */
	private const REPORTED_FUNCTION = ProductScopes::class . '::get_scope_name';

	/**
	 * The `$wp` global as it was before the test.
	 *
	 * @var WP|null
	 */
	private $original_wp;

	/**
	 * Number of incorrect-usage notices raised since the test started.
	 *
	 * @var int
	 */
	private $incorrect_usage_count = 0;

	/**
	 * IDs of the pages the test created.
	 *
	 * @var int[]
	 */
	private $page_ids = array();

	/**
	 * Set up the fixtures: the product attribute the filter parameters need, and a fresh filter parameter map.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_wp = $GLOBALS['wp'] ?? null;

		WC_Helper_Product::create_attribute( 'color', array( 'red', 'blue' ) );
		$this->clear_params_cache();

		$this->page_ids[] = self::factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_name'  => 'alpha',
				'post_title' => 'Alpha',
			)
		);
		$this->page_ids[] = self::factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_name'  => 'beta',
				'post_title' => 'Beta',
			)
		);

		add_action(
			'doing_it_wrong_run',
			function () {
				++$this->incorrect_usage_count;
			}
		);
	}

	/**
	 * Restore the globals and the cached filter parameter map.
	 */
	public function tearDown(): void {
		try {
			$this->replace_wp_global( $this->original_wp );
			$this->clear_params_cache();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should give the same name for the same arguments however many names were asked for before and in whatever order.
	 */
	public function test_names_are_stable_across_call_order_and_count(): void {
		$this->go_to( $this->page_url( 0 ) );

		$first_a  = ProductScopes::get_scope_name( 'form-a' );
		$first_b  = ProductScopes::get_scope_name( 'form-b' );
		$global   = ProductScopes::get_scope_name();
		$every_a  = ProductScopes::get_scope_name( 'form-a', false );
		$second_b = ProductScopes::get_scope_name( 'form-b' );
		$second_a = ProductScopes::get_scope_name( 'form-a' );
		ProductScopes::get_scope_name( 'form-a' );
		ProductScopes::get_scope_name( 'form-c', false );

		$this->assertSame( $first_a, $second_a, 'The same form name should keep its name.' );
		$this->assertSame( $first_b, $second_b, 'Asking for other names should not change a name.' );
		$this->assertSame( $first_a, ProductScopes::get_scope_name( 'form-a' ), 'Two callers passing the same form name should get one name.' );
		$this->assertNotSame( $first_a, $first_b, 'Different form names should get different names.' );
		$this->assertNotSame( $first_a, $every_a, 'The per-page and every-page names of a form name should differ.' );
		$this->assertSame( ProductScopes::GLOBAL_SCOPE_NAME, $global );
	}

	/**
	 * @testdox Should give a form name the same per-page name for every spelling of the same page: $label.
	 * @dataProvider same_page_spellings
	 *
	 * @param string $label Description of the spelling.
	 * @param string $query Query string to add to the page URL.
	 */
	public function test_same_page_gives_same_name( string $label, string $query ): void {
		$this->go_to( $this->page_url( 0 ) );
		$plain = ProductScopes::get_scope_name( 'sticky-bar' );

		$this->go_to( $this->page_url( 0, $query ) );

		$this->assertSame( $plain, ProductScopes::get_scope_name( 'sticky-bar' ) );
	}

	/**
	 * Spellings of the same page: a query string to add to the page's URL. Each variable the helper leaves out of
	 * the page is dropped on its own, so a spelling carrying several of them fails when any one is no longer dropped.
	 *
	 * @return array[]
	 */
	public function same_page_spellings(): array {
		return array(
			'no change'                     => array( 'the URL resolved again', '' ),
			'arguments that are not public' => array( 'wc-cache-bust, add-to-cart, Product Collection page 1 and cst, and a private variable', 'wc-cache-bust=1a2b3c&add-to-cart=12&query-2-page=1&cst&not_a_public_var=1' ),
			'pagination and sorting'        => array( 'paged, page, cpage, orderby and order', 'paged=3&page=2&cpage=2&orderby=price&order=desc' ),
			'catalog sorting default'       => array( 'Catalog Sorting back to default', 'orderby=menu_order&paged=1' ),
			'product filters'               => array( 'attribute, price, stock, rating and taxonomy filters', 'filter_color=red,blue&query_type_color=or&min_price=10&max_price=50&filter_stock_status=instock,onbackorder&rating_filter=4,5&categories=shoes&tags=sale&brands=acme' ),
		);
	}

	/**
	 * @testdox Should give a form name the same per-page name when pretty permalinks write the pagination in the path: $label.
	 * @dataProvider path_pagination_spellings
	 *
	 * @param string $label    Description of the spelling.
	 * @param string $path     Path to add to the page's permalink.
	 * @param string $variable The query variable WordPress resolves the path into.
	 */
	public function test_path_pagination_gives_same_name( string $label, string $path, string $variable ): void {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to( $this->page_url( 0 ) );
		$plain = ProductScopes::get_scope_name( 'sticky-bar' );

		$this->go_to( trailingslashit( $this->page_url( 0 ) ) . $path );

		$this->assertSame( '2', $GLOBALS['wp']->query_vars[ $variable ] ?? null, 'The path should resolve into the pagination variable.' );
		$this->assertSame( $plain, ProductScopes::get_scope_name( 'sticky-bar' ) );
	}

	/**
	 * Pagination spellings WordPress writes into the path under pretty permalinks.
	 *
	 * @return array[]
	 */
	public function path_pagination_spellings(): array {
		return array(
			'pagination'         => array( 'page/N/', 'page/2/', 'paged' ),
			'comment pagination' => array( 'comment-page-N/', 'comment-page-2/', 'cpage' ),
		);
	}

	/**
	 * @testdox Should give the same per-page name when a non-public key is added through the request filter.
	 */
	public function test_non_public_key_added_by_request_filter_does_not_change_the_page(): void {
		$url = $this->page_url( 0 );
		$this->go_to( $url );
		$plain = ProductScopes::get_scope_name( 'sticky-bar' );

		add_filter(
			'request',
			function ( $query_vars ) {
				$query_vars['not_a_public_var'] = 'abc';
				return $query_vars;
			}
		);
		$this->go_to( $url );

		$this->assertArrayHasKey( 'not_a_public_var', $GLOBALS['wp']->query_vars, 'The filter should have added the key.' );
		$this->assertSame( $plain, ProductScopes::get_scope_name( 'sticky-bar' ) );
	}

	/**
	 * @testdox Should give the same per-page name whatever order the public query variables were set in.
	 */
	public function test_order_of_query_variables_does_not_change_the_page(): void {
		$set_in_order = function ( array $keys ) {
			return function ( $query_vars ) use ( $keys ) {
				foreach ( $keys as $key ) {
					unset( $query_vars[ $key ] );
				}
				foreach ( $keys as $key ) {
					$query_vars[ $key ] = array_fill_keys( $keys, '1' );
				}
				return $query_vars;
			};
		};

		$callback = $set_in_order( array( 'year', 'monthnum' ) );
		add_filter( 'request', $callback );
		$this->go_to( $this->page_url( 0 ) );
		$first = ProductScopes::get_scope_name( 'sticky-bar' );
		remove_filter( 'request', $callback );

		$callback = $set_in_order( array( 'monthnum', 'year' ) );
		add_filter( 'request', $callback );
		$this->go_to( $this->page_url( 0 ) );

		$this->assertSame( array( 'monthnum', 'year' ), array_slice( array_keys( $GLOBALS['wp']->query_vars ), -2 ), 'The variables should be in the other order.' );
		$this->assertSame( $first, ProductScopes::get_scope_name( 'sticky-bar' ) );
	}

	/**
	 * @testdox Should give a form name a different per-page name on a different public query variable value.
	 */
	public function test_public_query_variable_makes_a_different_page(): void {
		$this->go_to( '/?s=shoes' );
		$shoes = ProductScopes::get_scope_name( 'sticky-bar' );
		$this->go_to( '/?s=hats' );
		$hats = ProductScopes::get_scope_name( 'sticky-bar' );

		$this->assertNotSame( $shoes, $hats );
	}

	/**
	 * @testdox Should give the same form name a different per-page name on a different page under $label permalinks.
	 * @testWith ["plain", ""]
	 *           ["pretty", "/%postname%/"]
	 *
	 * @param string $label     Description of the permalink setting.
	 * @param string $structure The permalink structure to set.
	 */
	public function test_different_pages_give_different_names( string $label, string $structure ): void {
		$this->set_permalink_structure( $structure );

		$this->go_to( $this->page_url( 0 ) );
		$first = ProductScopes::get_scope_name( 'sticky-bar' );
		$this->go_to( $this->page_url( 1 ) );
		$second = ProductScopes::get_scope_name( 'sticky-bar' );
		$this->go_to( $this->page_url( 0 ) );

		$this->assertNotSame( $first, $second );
		$this->assertSame( $first, ProductScopes::get_scope_name( 'sticky-bar' ), 'Returning to the first page should give its name back.' );
	}

	/**
	 * @testdox Should give an every-page name the same on different pages, different from the per-page names.
	 */
	public function test_every_page_name_is_the_same_on_different_pages(): void {
		$this->go_to( $this->page_url( 0 ) );
		$every_first  = ProductScopes::get_scope_name( 'sticky-bar', false );
		$per_page_one = ProductScopes::get_scope_name( 'sticky-bar' );
		$this->go_to( $this->page_url( 1 ) );
		$every_second = ProductScopes::get_scope_name( 'sticky-bar', false );
		$per_page_two = ProductScopes::get_scope_name( 'sticky-bar' );

		$this->assertSame( $every_first, $every_second, 'The every-page name should not depend on the page.' );
		$this->assertNotSame( $every_first, $per_page_one, 'The every-page name should differ from the name on the first page.' );
		$this->assertNotSame( $every_first, $per_page_two, 'The every-page name should differ from the name on the second page.' );
	}

	/**
	 * @testdox Should never give an every-page name that equals a per-page name: $form_name.
	 * @testWith ["sticky-bar"]
	 *           ["12"]
	 *           ["abcdef12"]
	 *
	 * @param string $form_name An accepted form name.
	 */
	public function test_every_page_name_never_equals_a_per_page_name( string $form_name ): void {
		$this->go_to( $this->page_url( 0 ) );
		$per_page = ProductScopes::get_scope_name( $form_name );
		$checked  = 0;

		// Every ending of the per-page name that is itself an accepted form name.
		for ( $offset = 1, $length = strlen( $per_page ); $offset < $length; $offset++ ) {
			$candidate = substr( $per_page, $offset );

			if ( 1 !== preg_match( '/\A[A-Za-z0-9_\/-]+\z/', $candidate ) ) {
				continue;
			}

			++$checked;
			$this->assertNotSame( $per_page, ProductScopes::get_scope_name( $candidate, false ), "The every-page name of '$candidate' should differ from the per-page name." );
		}

		$this->assertGreaterThan( 0, $checked, 'The name should have an ending that is an accepted form name.' );
	}

	/**
	 * @testdox Should return the global scope name without a form name, for either flag and on every page.
	 * @testWith [null]
	 *           [""]
	 *           ["   "]
	 *
	 * @param string|null $form_name A form name that counts as none.
	 */
	public function test_no_form_name_gives_the_global_scope_name( $form_name ): void {
		foreach ( array( 0, 1 ) as $page ) {
			$this->go_to( $this->page_url( $page ) );

			$this->assertSame( '_default', ProductScopes::GLOBAL_SCOPE_NAME );
			$this->assertSame( '_default', ProductScopes::get_scope_name( $form_name ), 'Default flag.' );
			$this->assertSame( '_default', ProductScopes::get_scope_name( $form_name, true ), 'Per-page flag.' );
			$this->assertSame( '_default', ProductScopes::get_scope_name( $form_name, false ), 'Every-page flag.' );
		}
		$this->assertSame( '_default', ProductScopes::get_scope_name(), 'No arguments.' );
	}

	/**
	 * @testdox Should never give the global scope name to an accepted form name, `_default` included.
	 * @testWith ["_default"]
	 *           ["_DEFAULT"]
	 *           ["0"]
	 *
	 * @param string $form_name An accepted form name.
	 */
	public function test_accepted_form_name_never_gives_the_global_scope_name( string $form_name ): void {
		$this->go_to( $this->page_url( 0 ) );

		$this->assertNotSame( ProductScopes::GLOBAL_SCOPE_NAME, ProductScopes::get_scope_name( $form_name ) );
		$this->assertNotSame( ProductScopes::GLOBAL_SCOPE_NAME, ProductScopes::get_scope_name( $form_name, false ) );
	}

	/**
	 * @testdox Should give one name to case variants and surrounding spaces of a form name, and to an integer and its string.
	 */
	public function test_form_names_are_normalized(): void {
		$this->go_to( $this->page_url( 0 ) );

		foreach ( array( true, false ) as $per_page ) {
			$expected = ProductScopes::get_scope_name( 'sticky-bar', $per_page );

			$this->assertNotNull( $expected );
			$this->assertSame( $expected, ProductScopes::get_scope_name( ' Sticky-Bar ', $per_page ) );
			$this->assertSame( $expected, ProductScopes::get_scope_name( 'STICKY-BAR', $per_page ) );
			$this->assertSame( ProductScopes::get_scope_name( '12', $per_page ), ProductScopes::get_scope_name( 12, $per_page ) );
		}
		$this->assertNotSame( ProductScopes::get_scope_name( '12' ), ProductScopes::get_scope_name( '012' ) );
	}

	/**
	 * @testdox Should reject a form name with a not allowed type or character with one notice and a null return.
	 * @dataProvider rejected_form_names
	 *
	 * @param mixed $form_name The rejected form name.
	 */
	public function test_rejected_form_name_raises_one_notice_and_returns_null( $form_name ): void {
		$this->setExpectedIncorrectUsage( self::REPORTED_FUNCTION );
		$this->go_to( $this->page_url( 0 ) );

		$result = ProductScopes::get_scope_name( $form_name );

		$this->assertNull( $result );
		$this->assertSame( 1, $this->incorrect_usage_count, 'Exactly one notice should be raised.' );

		$this->assertNull( ProductScopes::get_scope_name( $form_name, false ), 'The rejection should not depend on the flag.' );
		$this->assertSame( 2, $this->incorrect_usage_count, 'Each call should raise one notice.' );
		$this->assertNotNull( ProductScopes::get_scope_name( 'sticky-bar' ), 'Execution should continue with later calls.' );
	}

	/**
	 * Form names that are rejected.
	 *
	 * @return array[]
	 */
	public function rejected_form_names(): array {
		return array(
			'space inside'      => array( 'sticky bar' ),
			'dot'               => array( 'a.b' ),
			'apostrophe'        => array( "o'brien" ),
			'non-ASCII letters' => array( '名前' ),
			'surrounding tabs'  => array( "\tsticky-bar\t" ),
			'trailing newline'  => array( "sticky-bar\n" ),
			'float'             => array( 12.0 ),
			'bool true'         => array( true ),
			'array'             => array( array( 'sticky-bar' ) ),
		);
	}

	/**
	 * @testdox Should give names that read back unchanged as a JSON key and from a data-wp-context attribute.
	 */
	public function test_names_survive_json_and_context_attributes(): void {
		$this->go_to( $this->page_url( 0 ) );
		$form_name = 'shop/sticky-bar_2';

		foreach ( array( true, false ) as $per_page ) {
			$name = ProductScopes::get_scope_name( $form_name, $per_page );

			$this->assertIsString( $name );
			$this->assertNotSame( '', $name );

			$decoded = json_decode( (string) wp_json_encode( array( $name => true ) ), true );
			$this->assertSame( array( $name ), array_keys( $decoded ), 'The name should read back as a JSON key.' );

			$processor = new WP_HTML_Tag_Processor( '<div ' . wp_interactivity_data_wp_context( array( 'scopeName' => $name ) ) . '></div>' );
			$processor->next_tag();
			$context = json_decode( (string) $processor->get_attribute( 'data-wp-context' ), true );
			$this->assertSame( $name, $context['scopeName'] ?? null, 'The name should read back from the attribute.' );
		}
	}

	/**
	 * @testdox Should return names and raise nothing without a request: $label.
	 * @dataProvider contexts_without_a_parsed_request
	 *
	 * @param string   $label   Description of the context.
	 * @param callable $arrange Sets the context up.
	 */
	public function test_contexts_without_a_parsed_request_return_names_and_raise_nothing( string $label, callable $arrange ): void {
		$arrange( $this );

		$per_page = ProductScopes::get_scope_name( 'sticky-bar' );
		$every    = ProductScopes::get_scope_name( 'sticky-bar', false );

		$this->assertIsString( $per_page );
		$this->assertIsString( $every );
		$this->assertNotSame( $per_page, $every );
		$this->assertSame( 0, $this->incorrect_usage_count, 'No notice should be raised.' );
	}

	/**
	 * Contexts where no front-end request was parsed.
	 *
	 * @return array[]
	 */
	public function contexts_without_a_parsed_request(): array {
		return array(
			'no $wp global'   => array(
				'no $wp global',
				function ( self $test ) {
					$test->replace_wp_global( null );
				},
			),
			'unparsed WP'     => array(
				'unparsed WP',
				function ( self $test ) {
					$test->replace_wp_global( new WP() );
				},
			),
			'REST query vars' => array(
				'REST query vars',
				function ( self $test ) {
					$wp = new WP();
					$wp->add_query_var( 'rest_route' );
					$wp->query_vars = array( 'rest_route' => '/wc/store/v1/products' );
					$test->replace_wp_global( $wp );

					$test->assertContains( 'rest_route', $wp->public_query_vars, 'rest_route should be a public query variable.' );
					$test->assertSame( array( 'rest_route' => '/wc/store/v1/products' ), $wp->query_vars, 'The request should carry the REST route.' );
					$test->assertFalse( defined( 'REST_REQUEST' ), 'The arrangement should not serve a REST request.' );
				},
			),
		);
	}

	/**
	 * @testdox Should not read the product-filter parameter map for a request without query variables.
	 */
	public function test_does_not_read_the_filter_parameter_map_without_query_variables(): void {
		$this->replace_wp_global( new WP() );

		ProductScopes::get_scope_name( 'sticky-bar' );

		$this->assertSame( array(), $this->get_params_cache(), 'The helper should not warm the cached map.' );
	}

	/**
	 * The URL of one of the created pages, with a query string added.
	 *
	 * @param int    $index Index of the page.
	 * @param string $query Query string to add, without the leading separator.
	 * @return string The URL.
	 */
	private function page_url( int $index, string $query = '' ): string {
		$url = (string) get_permalink( $this->page_ids[ $index ] );

		if ( '' === $query ) {
			return $url;
		}

		return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . $query;
	}

	/**
	 * Replaces the `$wp` global.
	 *
	 * @param WP|null $wp The object to use, or null to remove the global.
	 */
	public function replace_wp_global( ?WP $wp ): void {
		if ( null === $wp ) {
			unset( $GLOBALS['wp'] );
			return;
		}

		$GLOBALS['wp'] = $wp; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Empties the product-filter parameter map Params keeps in a static property.
	 */
	private function clear_params_cache(): void {
		$property = new \ReflectionProperty( Params::class, 'params' );
		$property->setAccessible( true );
		$property->setValue( array() );
	}

	/**
	 * Reads the product-filter parameter map Params keeps in a static property.
	 *
	 * @return array The cached map.
	 */
	private function get_params_cache(): array {
		$property = new \ReflectionProperty( Params::class, 'params' );
		$property->setAccessible( true );

		return $property->getValue();
	}
}
