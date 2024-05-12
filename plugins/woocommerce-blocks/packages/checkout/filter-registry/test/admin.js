/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
/**
 * Internal dependencies
 */
import { registerCheckoutFilters, applyCheckoutFilter } from '../';

jest.mock( '@woocommerce/settings', () => {
	const originalModule = jest.requireActual( '@woocommerce/settings' );
	return {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore We know @woocommerce/settings is an object.
		...originalModule,
		CURRENT_USER_IS_ADMIN: true,
	};
} );

describe( 'Checkout registry (as admin user)', () => {
	test( 'should throw if the filter throws and user is an admin', () => {
		expect.assertions( 1 );
		const filterName = 'ErrorTestFilter';
		const value = 'Hello World';
		registerCheckoutFilters( filterName, {
			[ filterName ]: () => {
				throw new Error( 'test error' );
			},
		} );

		try {
			applyCheckoutFilter( {
				filterName,
				defaultValue: value,
			} );
		} catch ( e ) {
			// eslint-disable-next-line  -- The toThrow helper does not stop wordpress/jest-console from erroring.
			expect( e.message ).toBe( 'test error' );
		}
	} );

	test( 'should throw if validation throws and user is an admin', () => {
		expect.assertions( 1 );
		const filterName = 'ValidationTestFilter';
		const value = 'Hello World';
		registerCheckoutFilters( filterName, {
			[ filterName ]: ( val ) => val,
		} );
		try {
			applyCheckoutFilter( {
				filterName,
				defaultValue: value,
				validation: () => {
					throw Error( 'validation error' );
				},
			} );
		} catch ( e ) {
			// eslint-disable-next-line  -- The toThrow helper does not stop wordpress/jest-console from erroring.
			expect( e.message ).toBe( 'validation error' );
		}
	} );
} );
