/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../../types';

import { fieldExtensions } from '../field';

const makeProduct = (
	overrides: Partial< ProductEntityRecord > = {}
): ProductEntityRecord =>
	( {
		id: 1,
		type: 'simple',
		downloadable: false,
		visible_in_pos: true,
		...overrides,
	} as unknown as ProductEntityRecord );

describe( 'visible_in_pos field', () => {
	describe( 'isVisible', () => {
		const { isVisible } = fieldExtensions;

		it( 'returns true for simple non-downloadable products', () => {
			expect( isVisible!( makeProduct() ) ).toBe( true );
		} );

		it( 'returns true for variable non-downloadable products', () => {
			expect( isVisible!( makeProduct( { type: 'variable' } ) ) ).toBe(
				true
			);
		} );

		it( 'returns false for downloadable products', () => {
			expect( isVisible!( makeProduct( { downloadable: true } ) ) ).toBe(
				false
			);
		} );

		it( 'returns false for external products', () => {
			expect( isVisible!( makeProduct( { type: 'external' } ) ) ).toBe(
				false
			);
		} );

		it( 'returns false for grouped products', () => {
			expect( isVisible!( makeProduct( { type: 'grouped' } ) ) ).toBe(
				false
			);
		} );
	} );

	describe( 'getValue', () => {
		const { getValue } = fieldExtensions;

		it( 'returns the visible_in_pos value', () => {
			expect(
				getValue!( { item: makeProduct( { visible_in_pos: false } ) } )
			).toBe( false );
		} );

		it( 'defaults to true when undefined', () => {
			const product = makeProduct();
			// eslint-disable-next-line @typescript-eslint/no-dynamic-delete
			delete ( product as Record< string, unknown > ).visible_in_pos;
			expect( getValue!( { item: product } ) ).toBe( true );
		} );
	} );

	describe( 'setValue', () => {
		const { setValue } = fieldExtensions;

		it( 'returns visible_in_pos true', () => {
			expect( setValue!( { value: true } ) ).toEqual( {
				visible_in_pos: true,
			} );
		} );

		it( 'returns visible_in_pos false', () => {
			expect( setValue!( { value: false } ) ).toEqual( {
				visible_in_pos: false,
			} );
		} );
	} );

	it( 'uses the built-in toggle Edit component', () => {
		expect( fieldExtensions.Edit ).toBe( 'toggle' );
	} );
} );
