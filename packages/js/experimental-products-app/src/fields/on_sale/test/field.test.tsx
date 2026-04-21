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
		on_sale: false,
		sale_price: '',
		...overrides,
	} as unknown as ProductEntityRecord );

describe( 'on_sale field', () => {
	it( 'has type boolean', () => {
		expect( fieldExtensions.type ).toBe( 'boolean' );
	} );

	describe( 'getValue', () => {
		const { getValue } = fieldExtensions;

		it( 'returns false when not on sale and no sale price', () => {
			expect(
				getValue!( {
					item: makeProduct( { on_sale: false, sale_price: '' } ),
				} )
			).toBe( false );
		} );

		it( 'returns true when on_sale is true', () => {
			expect(
				getValue!( { item: makeProduct( { on_sale: true } ) } )
			).toBe( true );
		} );

		it( 'returns true when sale_price is set', () => {
			expect(
				getValue!( {
					item: makeProduct( {
						on_sale: false,
						sale_price: '9.99',
					} ),
				} )
			).toBe( true );
		} );
	} );

	describe( 'setValue', () => {
		const { setValue } = fieldExtensions;

		it( 'sets on_sale true when enabling', () => {
			expect( setValue!( { value: true } ) ).toEqual( {
				on_sale: true,
			} );
		} );

		it( 'clears sale fields when disabling', () => {
			expect( setValue!( { value: false } ) ).toEqual( {
				on_sale: false,
				sale_price: '',
				date_on_sale_from: null,
				date_on_sale_to: null,
			} );
		} );
	} );

	it( 'uses the built-in toggle Edit component', () => {
		expect( fieldExtensions.Edit ).toBe( 'toggle' );
	} );
} );
