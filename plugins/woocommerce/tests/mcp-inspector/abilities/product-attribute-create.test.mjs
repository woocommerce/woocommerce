import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/product-attribute-create';

const SLUGS = [ 'mcp-created-size', 'mcp-taken' ];

describe( ABILITY, () => {
	before( async () => {
		await seed( 'remove-attributes', { slugs: SLUGS } );
		await seed( 'attribute', {
			name: 'MCP Taken',
			slug: 'mcp-taken',
		} );
	} );
	after( () => seed( 'remove-attributes', { slugs: SLUGS } ) );

	test( 'creates an attribute with a slug made from the name', async () => {
		const { attribute } = await execute( ABILITY, {
			name: 'MCP Created Size',
			order_by: 'name_num',
		} );

		assert.deepEqual(
			{ ...attribute, id: typeof attribute.id },
			{
				id: 'number',
				name: 'MCP Created Size',
				slug: 'pa_mcp-created-size',
				type: 'select',
				order_by: 'name_num',
				has_archives: false,
				term_count: 0,
				terms: [],
			}
		);
		assert.deepEqual(
			(
				await execute( 'woocommerce/product-attributes-query', {
					include: [ attribute.id ],
				} )
			).attributes.map( ( { name } ) => name ),
			[ 'MCP Created Size' ]
		);
	} );

	test( 'refuses a name another attribute has, ignoring case', () =>
		refused(
			ABILITY,
			{ name: 'mcp taken', slug: 'mcp-other' },
			/already exists/
		) );

	test( 'refuses a slug another attribute has', () =>
		refused(
			ABILITY,
			{ name: 'MCP Something Else', slug: 'mcp-taken' },
			/already in use/
		) );

	test( 'refuses a name without letters or numbers', () =>
		refused( ABILITY, { name: '!!!' }, /needs a slug/ ) );
} );
