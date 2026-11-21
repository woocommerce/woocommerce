import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/product-term-create';

const CATEGORIES = [ 'mcp-created-parent', 'mcp-created-child' ];
const ATTRIBUTE = 'mcp-term-create-color';

const removeAll = () =>
	Promise.all( [
		seed( 'remove-terms', { taxonomy: 'product_cat', slugs: CATEGORIES } ),
		seed( 'remove-attributes', { slugs: [ ATTRIBUTE ] } ),
	] );

describe( ABILITY, () => {
	let attribute;
	before( async () => {
		await removeAll();
		attribute = await seed( 'attribute', {
			name: 'MCP Term Create Color',
			slug: ATTRIBUTE,
		} );
	} );
	after( removeAll );

	test( 'creates a category with a slug made from the name, then a child of it', async () => {
		const { term: parent } = await execute( ABILITY, {
			taxonomy: 'product_cat',
			name: 'MCP Created Parent',
		} );
		const { term: child } = await execute( ABILITY, {
			taxonomy: 'product_cat',
			name: 'MCP Created Child',
			parent: parent.id,
			description: 'Below the parent',
		} );

		assert.equal( parent.slug, 'mcp-created-parent' );
		assert.equal( parent.parent, 0 );
		assert.deepEqual(
			{
				slug: child.slug,
				parent: child.parent,
				description: child.description,
				count: child.count,
			},
			{
				slug: 'mcp-created-child',
				parent: parent.id,
				description: 'Below the parent',
				count: 0,
			}
		);
	} );

	test( 'creates a term of a global attribute', async () => {
		const { term } = await execute( ABILITY, {
			taxonomy: attribute.taxonomy,
			name: 'MCP Teal',
		} );

		assert.equal( term.slug, 'mcp-teal' );
	} );

	test( 'refuses a name the taxonomy already has', () =>
		refused(
			ABILITY,
			{ taxonomy: 'product_cat', name: 'MCP Created Parent' },
			/already exists/
		) );

	test( 'refuses a parent for a tag', () =>
		refused(
			ABILITY,
			{ taxonomy: 'product_tag', name: 'MCP Tag', parent: 1 },
			/have no parent/
		) );

	test( 'refuses an unknown parent', () =>
		refused(
			ABILITY,
			{ taxonomy: 'product_cat', name: 'MCP Orphan', parent: 999999999 },
			/no term 999999999/
		) );

	test( 'refuses an attribute that does not exist', () =>
		refused(
			ABILITY,
			{ taxonomy: 'pa_mcp-not-an-attribute', name: 'MCP Teal' },
			/does not exist/
		) );
} );
