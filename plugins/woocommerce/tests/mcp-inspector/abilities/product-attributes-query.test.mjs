import assert from 'node:assert/strict';
import { before, describe, test } from 'node:test';
import { execute } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/product-attributes-query';

describe( ABILITY, () => {
	let id;
	before( async () => {
		( { id } = await seed( 'attribute', {
			name: 'MCP Finish',
			slug: 'mcp-finish',
			terms: [ 'Matte', 'Gloss', 'Satin' ],
		} ) );
	} );

	test( 'finds an attribute by its ID, with its terms in their order', async () => {
		const { attributes } = await execute( ABILITY, { include: [ id ] } );

		assert.deepEqual(
			attributes.map( ( attribute ) => ( {
				...attribute,
				terms: attribute.terms.map( ( { name } ) => name ),
			} ) ),
			[
				{
					id,
					name: 'MCP Finish',
					slug: 'pa_mcp-finish',
					type: 'select',
					order_by: 'menu_order',
					has_archives: false,
					term_count: 3,
					terms: [ 'Matte', 'Gloss', 'Satin' ],
				},
			]
		);
	} );

	test( 'searches the label, ignoring case', async () => {
		const { attributes } = await execute( ABILITY, {
			search: 'mcp fin',
		} );

		assert.deepEqual(
			attributes.map( ( attribute ) => attribute.id ),
			[ id ]
		);
	} );

	test( 'limits the terms of each attribute', async () => {
		const [ none ] = (
			await execute( ABILITY, {
				include: [ id ],
				terms_per_attribute: 0,
			} )
		).attributes;
		const [ two ] = (
			await execute( ABILITY, {
				include: [ id ],
				terms_per_attribute: 2,
			} )
		).attributes;

		assert.deepEqual( [ none.term_count, none.terms.length ], [ 3, 0 ] );
		assert.deepEqual( [ two.term_count, two.terms.length ], [ 3, 2 ] );
	} );

	test( 'returns nothing for an unknown ID', async () => {
		assert.deepEqual(
			( await execute( ABILITY, { include: [ 999999999 ] } ) ).attributes,
			[]
		);
	} );
} );
