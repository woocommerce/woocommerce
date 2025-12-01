/**
 * Internal dependencies
 */

import {
	getOptionGroups,
	getOptionGroupsFromSteps,
} from '../get-option-groups';

describe( 'getOptionGroups', () => {
	it( 'should return nothing for unmatched opions', () => {
		const options = [ 'unknown-value' ];
		const result = getOptionGroups( options );
		expect( result ).toEqual( [] );
	} );
	it( 'should return option groups for matched options', () => {
		const options = [ 'woocommerce_store_address' ];
		const result = getOptionGroups( options );
		expect( result ).toEqual( [ 'General' ] );
	} );
} );

describe( 'getOptionGroupsFromSteps', () => {
	it( 'should return option groups from steps', () => {
		const steps = [
			{
				step: 'setSiteOptions',
				options: {
					woocommerce_store_address: '',
				},
			},
		];

		const result = getOptionGroupsFromSteps( steps );
		expect( result ).toEqual( [ 'General' ] );
	} );
} );
