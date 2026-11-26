import assert from 'node:assert/strict';
import { before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/product-terms-query';

const slugsOf = ( { terms } ) => terms.map( ( { slug } ) => slug );

describe( ABILITY, () => {
	let tags;
	before( async () => {
		tags = await seed( 'product-tags' );
	} );

	const query = ( parameters ) =>
		execute( ABILITY, { taxonomy: 'product_tag', ...parameters } );

	for ( const [ title, parameters, expected ] of [
		[
			'filters by a list of slugs',
			{ slug: [ 'mcp-alpha', 'mcp-gamma' ] },
			[ 'mcp-alpha', 'mcp-gamma' ],
		],
		[
			'filters by one slug as a list of one',
			{ slug: [ 'mcp-beta' ] },
			[ 'mcp-beta' ],
		],
		[ 'searches by name', { search: 'mcp-gam' }, [ 'mcp-gamma' ] ],
	] ) {
		test( title, async () => {
			assert.deepEqual(
				slugsOf( await query( parameters ) ).sort(),
				expected
			);
		} );
	}

	test( 'excludes terms by ID', async () => {
		const [ alpha ] = ( await query( { slug: [ 'mcp-alpha' ] } ) ).terms;
		const data = await query( { slug: tags, exclude: [ alpha.id ] } );

		assert.deepEqual( slugsOf( data ).sort(), [ 'mcp-beta', 'mcp-gamma' ] );
	} );

	test( 'applies no slug filter for an empty list', async () => {
		const slugs = slugsOf( await query( { slug: [], per_page: 100 } ) );

		assert.deepEqual(
			tags.filter( ( slug ) => ! slugs.includes( slug ) ),
			[]
		);
	} );

	test( 'sorts by slug, descending', async () => {
		const data = await query( {
			slug: tags,
			orderby: 'slug',
			order: 'desc',
		} );

		assert.deepEqual( slugsOf( data ), [
			'mcp-gamma',
			'mcp-beta',
			'mcp-alpha',
		] );
	} );

	test( 'pages through the results with totals', async () => {
		const { terms, ...paging } = await query( {
			slug: tags,
			per_page: 2,
			page: 2,
		} );

		assert.equal( terms.length, 1 );
		assert.deepEqual( paging, {
			total: 3,
			total_pages: 2,
			page: 2,
			per_page: 2,
		} );
	} );

	test( 'refuses a taxonomy that is not a product taxonomy', () =>
		refused( ABILITY, { taxonomy: 'category' }, /input\[taxonomy\]/ ) );
} );
