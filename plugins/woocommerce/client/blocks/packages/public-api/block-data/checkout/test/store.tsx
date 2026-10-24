/**
 * External dependencies
 */
import { select, dispatch } from '@wordpress/data';
import { waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { store } from '../index';

const wait = ( timeout = 0 ) =>
	new Promise( ( resolve ) => setTimeout( resolve, timeout ) );

describe( 'Checkout store', () => {
	describe( 'isCalculating', () => {
		it( 'correctly infers isCalculating from number of calculating items', () => {
			dispatch( store ).__internalStartCalculation();
			expect( select( store ).isCalculating() ).toBe( true );

			dispatch( store ).__internalFinishCalculation();
			expect( select( store ).isCalculating() ).toBe( false );
		} );

		it( 'correctly marks as calculating for duration of the promise using disableCheckoutFor thunk', async () => {
			dispatch( store ).disableCheckoutFor( async () => {
				await wait();
			} );
			expect( select( store ).isCalculating() ).toBe( true );
			await waitFor(
				() => {
					expect( select( store ).isCalculating() ).toBe( false );
				},
				// we don't need to wait for default 50ms
				{ interval: 10 }
			);
		} );

		it( 'disableCheckoutFor does not swallow errors', async () => {
			await expect( () =>
				dispatch( store ).disableCheckoutFor( async () => {
					await wait();
					expect( select( store ).isCalculating() ).toBe( true );
					await wait();
					throw new Error( 'test' );
				} )
			).rejects.toThrow( 'test' );

			expect( select( store ).isCalculating() ).toBe( false );
		} );
	} );
} );
