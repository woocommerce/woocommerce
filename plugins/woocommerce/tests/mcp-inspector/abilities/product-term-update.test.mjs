import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/product-term-update';

const SLUGS = [ 'mcp-update-parent', 'mcp-update-child', 'mcp-update-renamed' ];

const remove = () =>
	seed( 'remove-terms', { taxonomy: 'product_cat', slugs: SLUGS } );

describe( ABILITY, () => {
	let parent;
	let child;
	before( async () => {
		await remove();
		( { term: parent } = await execute( 'woocommerce/product-term-create', {
			taxonomy: 'product_cat',
			name: 'MCP Update Parent',
		} ) );
		( { term: child } = await execute( 'woocommerce/product-term-create', {
			taxonomy: 'product_cat',
			name: 'MCP Update Child',
			parent: parent.id,
		} ) );
	} );
	after( remove );

	const update = ( parameters ) =>
		execute( ABILITY, { taxonomy: 'product_cat', ...parameters } );

	test( 'changes the fields it is given and keeps the others', async () => {
		const { term } = await update( {
			id: child.id,
			name: 'MCP Update Renamed',
			slug: 'mcp-update-renamed',
			description: 'Renamed',
		} );

		assert.deepEqual(
			{
				name: term.name,
				slug: term.slug,
				description: term.description,
				parent: term.parent,
			},
			{
				name: 'MCP Update Renamed',
				slug: 'mcp-update-renamed',
				description: 'Renamed',
				parent: parent.id,
			}
		);
	} );

	test( 'moves the term to the top level', async () => {
		assert.equal(
			( await update( { id: child.id, parent: 0 } ) ).term.parent,
			0
		);
	} );

	test( 'refuses to make a term the parent of its ancestor', async () => {
		await update( { id: child.id, parent: parent.id } );

		await refused(
			ABILITY,
			{ taxonomy: 'product_cat', id: parent.id, parent: child.id },
			/cannot be its own parent/
		);
	} );

	test( 'refuses a slug another term has', () =>
		refused(
			ABILITY,
			{
				taxonomy: 'product_cat',
				id: child.id,
				slug: 'mcp-update-parent',
			},
			/already in use/
		) );

	test( 'refuses a call without a change', () =>
		refused(
			ABILITY,
			{ taxonomy: 'product_cat', id: child.id },
			/No change was sent/
		) );

	test( 'refuses a term of another taxonomy', () =>
		refused(
			ABILITY,
			{ taxonomy: 'product_tag', id: child.id, name: 'MCP Tag' },
			/no term \d+ in "product_tag"/
		) );
} );
