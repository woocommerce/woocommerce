<?php
/**
 * QueryInfoExtractor tests — interact with webonyx AST nodes whose properties
 * (selectionSet, fieldNodes, variableValues, …) are camelCase by design.
 *
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Api\Infrastructure;

use Automattic\WooCommerce\Api\Infrastructure\QueryInfoExtractor;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\OperationDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SelectionSetNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Parser;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ResolveInfo;
use WC_Unit_Test_Case;

/**
 * Tests for {@see QueryInfoExtractor}. These exercise the AST → query-info
 * tree transformation that mappers consume to skip work for unselected fields.
 */
class QueryInfoExtractorTest extends WC_Unit_Test_Case {
	/**
	 * Parse a GraphQL operation and return the top-level FieldNode plus the
	 * fragment-definition map that ResolveInfo would expose.
	 *
	 * @param string $source A GraphQL document containing one query.
	 * @return array{0: FieldNode, 1: array<string, FragmentDefinitionNode>}
	 */
	private function parse_top_field( string $source ): array {
		/** @var DocumentNode $doc */
		$doc       = Parser::parse( $source, array( 'noLocation' => true ) );
		$operation = null;
		$fragments = array();
		foreach ( $doc->definitions as $def ) {
			if ( $def instanceof OperationDefinitionNode ) {
				$operation = $def;
			} elseif ( $def instanceof FragmentDefinitionNode ) {
				$fragments[ $def->name->value ] = $def;
			}
		}
		$this->assertNotNull( $operation );
		$selections = iterator_to_array( $operation->selectionSet->selections );
		$top_field  = $selections[0];
		$this->assertInstanceOf( FieldNode::class, $top_field );
		return array( $top_field, $fragments );
	}

	/**
	 * @testdox extract returns true for leaf fields with no args or sub-selections.
	 */
	public function test_extract_marks_leaf_fields_as_true(): void {
		[ $field ] = $this->parse_top_field( '{ widget(id: 1) { id name } }' );

		$tree = QueryInfoExtractor::extract( $field->selectionSet, array() );

		$this->assertSame( true, $tree['id'] ?? null );
		$this->assertSame( true, $tree['name'] ?? null );
	}

	/**
	 * @testdox extract recurses into sub-selections.
	 */
	public function test_extract_recurses_into_sub_selections(): void {
		[ $field ] = $this->parse_top_field( '{ widget { reviews { nodes { id body } } } }' );

		$tree = QueryInfoExtractor::extract( $field->selectionSet, array() );

		$this->assertIsArray( $tree['reviews'] );
		$this->assertIsArray( $tree['reviews']['nodes'] );
		$this->assertSame( true, $tree['reviews']['nodes']['id'] ?? null );
		$this->assertSame( true, $tree['reviews']['nodes']['body'] ?? null );
	}

	/**
	 * @testdox extract captures field arguments under __args.
	 */
	public function test_extract_captures_field_arguments(): void {
		[ $field ] = $this->parse_top_field( '{ root { reviews(first: 5, search: "abc") { nodes { id } } } }' );

		$tree = QueryInfoExtractor::extract( $field->selectionSet, array() );

		$this->assertArrayHasKey( '__args', $tree['reviews'] );
		$this->assertSame( 5, $tree['reviews']['__args']['first'] ?? null );
		$this->assertSame( 'abc', $tree['reviews']['__args']['search'] ?? null );
	}

	/**
	 * @testdox extract resolves variable references in arguments.
	 */
	public function test_extract_resolves_variables(): void {
		[ $field ] = $this->parse_top_field( 'query Q($n: Int) { root { reviews(first: $n) { nodes { id } } } }' );

		$tree = QueryInfoExtractor::extract( $field->selectionSet, array( 'n' => 42 ) );

		$this->assertSame( 42, $tree['reviews']['__args']['first'] ?? null );
	}

	/**
	 * @testdox extract represents inline fragments under "...TypeName" keys.
	 */
	public function test_extract_emits_inline_fragments_with_typename_prefix(): void {
		[ $field ] = $this->parse_top_field(
			'{ thing { id ... on Widget { color } ... on Gadget { parts_count } } }'
		);

		$tree = QueryInfoExtractor::extract( $field->selectionSet, array() );

		$this->assertSame( true, $tree['id'] ?? null );
		$this->assertArrayHasKey( '...Widget', $tree );
		$this->assertSame( true, $tree['...Widget']['color'] ?? null );
		$this->assertArrayHasKey( '...Gadget', $tree );
		$this->assertSame( true, $tree['...Gadget']['parts_count'] ?? null );
	}

	/**
	 * @testdox extract expands named fragment spreads inline into the parent.
	 */
	public function test_extract_inlines_named_fragment_spreads(): void {
		[ $field, $fragments ] = $this->parse_top_field(
			'query Q { thing { ...Core } } fragment Core on Widget { id name }'
		);

		$tree = QueryInfoExtractor::extract( $field->selectionSet, array(), $fragments );

		$this->assertSame( true, $tree['id'] ?? null );
		$this->assertSame( true, $tree['name'] ?? null );
	}

	/**
	 * @testdox extract merges overlapping selections from a fragment spread without dropping detail.
	 */
	public function test_extract_merges_overlapping_fragment_selections(): void {
		[ $field, $fragments ] = $this->parse_top_field(
			'query Q { thing { reviews { nodes { id } } ...AlsoReviews } } '
			. 'fragment AlsoReviews on Widget { reviews { nodes { body } } }'
		);

		$tree = QueryInfoExtractor::extract( $field->selectionSet, array(), $fragments );

		$this->assertIsArray( $tree['reviews']['nodes'] ?? null );
		$this->assertSame( true, $tree['reviews']['nodes']['id'] ?? null );
		$this->assertSame( true, $tree['reviews']['nodes']['body'] ?? null );
	}

	/**
	 * @testdox extract returns an empty array for null selection sets.
	 */
	public function test_extract_handles_null_selection_set(): void {
		$this->assertSame( array(), QueryInfoExtractor::extract( null, array() ) );
	}

	/**
	 * @testdox extract_from_info attaches __args from the top-level args.
	 */
	public function test_extract_from_info_includes_top_level_args(): void {
		[ $field ] = $this->parse_top_field( '{ widget(id: 7) { name } }' );

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- webonyx ResolveInfo properties.
		$info                 = $this->createMock( ResolveInfo::class );
		$info->fieldNodes     = new \ArrayObject( array( $field ) );
		$info->variableValues = array();
		$info->fragments      = array();
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$tree = QueryInfoExtractor::extract_from_info( $info, array( 'id' => 7 ) );

		$this->assertSame( 7, $tree['__args']['id'] ?? null );
		$this->assertSame( true, $tree['name'] ?? null );
	}

	/**
	 * @testdox extract_from_info skips __args when the args array is empty.
	 */
	public function test_extract_from_info_omits_args_when_empty(): void {
		[ $field ] = $this->parse_top_field( '{ widget { name } }' );

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- webonyx ResolveInfo properties.
		$info                 = $this->createMock( ResolveInfo::class );
		$info->fieldNodes     = new \ArrayObject( array( $field ) );
		$info->variableValues = array();
		$info->fragments      = array();
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$tree = QueryInfoExtractor::extract_from_info( $info, array() );

		$this->assertArrayNotHasKey( '__args', $tree );
	}
}
