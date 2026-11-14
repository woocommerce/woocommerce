/**
 * @jest-environment node
 */

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { getActivityPanelCounts } from '../resolvers';
import {
	getActivityPanelCountsSuccess,
	getActivityPanelCountsError,
} from '../actions';
import { NAMESPACE } from '../../constants';

describe( 'getActivityPanelCounts resolver', () => {
	it( 'fetches the combined counts and dispatches success', () => {
		const generator = getActivityPanelCounts();

		expect( generator.next().value ).toEqual(
			apiFetch( { path: `${ NAMESPACE }/activity-panel/counts` } )
		);

		const counts = {
			orders_to_fulfill_count: 2,
			reviews_to_moderate_count: 1,
			products_low_in_stock_count: 3,
		};

		expect( generator.next( counts ).value ).toEqual(
			getActivityPanelCountsSuccess( counts )
		);
		expect( generator.next().done ).toBe( true );
	} );

	it( 'dispatches an error when the fetch fails', () => {
		const generator = getActivityPanelCounts();
		generator.next();

		const error = new Error( 'Request failed' );

		expect( generator.throw( error ).value ).toEqual(
			getActivityPanelCountsError( error )
		);
		expect( generator.next().done ).toBe( true );
	} );
} );
