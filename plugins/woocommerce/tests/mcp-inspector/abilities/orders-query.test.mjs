import assert from 'node:assert/strict';
import { before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/orders-query';

const statusesOf = ( { orders } ) =>
	[ ...new Set( orders.map( ( { status } ) => status ) ) ].sort();

describe( ABILITY, () => {
	let fixtures;
	before( async () => {
		fixtures = await seed( 'orders' );
	} );

	// The fixtures' billing email keeps other orders in a long-lived dev environment out of the results.
	const query = ( parameters ) =>
		execute( ABILITY, {
			billing_email: fixtures.billing_email,
			...parameters,
		} );

	for ( const [ title, parameters, expected ] of [
		[
			'filters by billing email',
			{},
			[ 'completed', 'on-hold', 'processing' ],
		],
		[
			'filters by a list of statuses',
			{ status: [ 'processing', 'completed' ] },
			[ 'completed', 'processing' ],
		],
		[
			'filters by one status as a list of one',
			{ status: [ 'on-hold' ] },
			[ 'on-hold' ],
		],
		[
			'applies no status filter for an empty list',
			{ status: [] },
			[ 'completed', 'on-hold', 'processing' ],
		],
	] ) {
		test( title, async () => {
			assert.deepEqual(
				statusesOf( await query( parameters ) ),
				expected
			);
		} );
	}

	test( 'finds one order by its ID', async () => {
		const { orders } = await execute( ABILITY, {
			id: fixtures.ids.completed,
		} );

		assert.deepEqual(
			orders.map( ( { id, status } ) => ( { id, status } ) ),
			[ { id: fixtures.ids.completed, status: 'completed' } ]
		);
	} );

	test( 'sorts by ID, ascending', async () => {
		const ids = (
			await query( { orderby: 'id', order: 'asc' } )
		).orders.map( ( { id } ) => id );

		assert.deepEqual(
			ids,
			[ ...ids ].sort( ( a, b ) => a - b )
		);
	} );

	test( 'leaves line items out unless asked', async () => {
		const [ order ] = ( await query( { per_page: 1 } ) ).orders;
		const [ withItems ] = (
			await query( { per_page: 1, include_line_items: true } )
		).orders;

		assert.equal( order.line_items, undefined );
		assert.deepEqual( withItems.line_items, [] );
	} );

	test( 'refuses an unknown status', () =>
		refused( ABILITY, { status: [ 'not-a-status' ] }, /input\[status\]/ ) );
} );
