/**
 * External dependencies
 */
const assert = require( 'node:assert/strict' );
const test = require( 'node:test' );
const { runInNewContext } = require( 'node:vm' );

/**
 * Internal dependencies
 */
const waitForCheckoutUpdates = require( '../src/wait-for-checkout-updates' );

test( 'waits for classic requests and pending cart and checkout updates', async () => {
	const states = [
		[ {}, true ],
		[ { jQuery: { active: 0 } }, true ],
		[ { jQuery: { active: 1 } }, false ],
	];
	const withStores = ( cart, checkout ) => ( {
		wp: {
			data: {
				select: ( store ) =>
					store === 'wc/store/cart' ? cart : checkout,
			},
		},
	} );

	states.push(
		[ withStores( undefined, undefined ), true ],
		[ withStores( {}, {} ), true ],
		[
			{
				jQuery: { active: 0 },
				...withStores( { isCustomerDataUpdating: () => true } ),
			},
			false,
		],
		[ withStores( { getItemsPendingQuantityUpdate: () => [ 1 ] } ), false ],
		[ withStores( { getItemsPendingDelete: () => [ 1 ] } ), false ],
		[ withStores( { isShippingRateBeingSelected: () => true } ), false ],
		[ withStores( {}, { isCalculating: () => true } ), false ],
		[
			withStores(
				{ getItemsPendingQuantityUpdate: () => [] },
				{ isCalculating: () => false }
			),
			true,
		]
	);

	let waits = 0;
	await waitForCheckoutUpdates( {
		waitForFunction: async ( condition ) => {
			waits++;
			for ( const [ window, expected ] of states ) {
				assert.equal(
					runInNewContext( `(${ condition })()`, { window } ),
					expected
				);
			}
		},
	} );
	assert.equal( waits, 1 );
} );

test( 'propagates a browser wait failure to the caller', async () => {
	const timeout = new Error( 'Cart updates did not settle' );
	await assert.rejects(
		waitForCheckoutUpdates( {
			waitForFunction: async () => {
				throw timeout;
			},
		} ),
		( error ) => error === timeout
	);
} );
