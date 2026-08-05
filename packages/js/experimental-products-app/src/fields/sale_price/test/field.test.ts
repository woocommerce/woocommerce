/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../../types';

import { validatePrice } from '../../price/utils';

import { validateSalePrice } from '../validation';

function makeItem(
	overrides: Partial< ProductEntityRecord > = {}
): ProductEntityRecord {
	return {
		regular_price: '',
		sale_price: '',
		on_sale: false,
		...overrides,
	} as ProductEntityRecord;
}

describe( 'validatePrice', () => {
	it( 'returns null for empty string', () => {
		expect( validatePrice( '' ) ).toBeNull();
	} );

	it( 'returns null for whitespace-only string', () => {
		expect( validatePrice( '  ' ) ).toBeNull();
	} );

	it( 'returns null for undefined', () => {
		expect( validatePrice( undefined ) ).toBeNull();
	} );

	it( 'returns null for null', () => {
		expect( validatePrice( null ) ).toBeNull();
	} );

	it( 'returns null for valid integer', () => {
		expect( validatePrice( '10' ) ).toBeNull();
	} );

	it( 'returns null for valid decimal', () => {
		expect( validatePrice( '9.99' ) ).toBeNull();
	} );

	it( 'returns null for zero', () => {
		expect( validatePrice( '0' ) ).toBeNull();
	} );

	it( 'returns error for non-numeric string', () => {
		expect( validatePrice( 'abc' ) ).toBe( 'Please enter a valid price.' );
	} );

	it( 'returns error for negative number', () => {
		expect( validatePrice( '-5' ) ).toBe( 'Price must not be negative.' );
	} );
} );

describe( 'validateSalePrice', () => {
	it( 'returns null when both prices are empty', () => {
		expect( validateSalePrice( makeItem() ) ).toBeNull();
	} );

	it( 'returns null when sale price is empty', () => {
		const item = makeItem( { regular_price: '10' } );
		expect( validateSalePrice( item ) ).toBeNull();
	} );

	it( 'returns null when regular price is empty', () => {
		const item = makeItem( { sale_price: '5' } );
		expect( validateSalePrice( item ) ).toBeNull();
	} );

	it( 'returns null when sale price is lower than regular price', () => {
		const item = makeItem( { regular_price: '20', sale_price: '15' } );
		expect( validateSalePrice( item ) ).toBeNull();
	} );

	it( 'returns error when sale price equals regular price', () => {
		const item = makeItem( { regular_price: '20', sale_price: '20' } );
		expect( validateSalePrice( item ) ).toBe(
			'Sale price must be lower than the regular price.'
		);
	} );

	it( 'returns error when sale price exceeds regular price', () => {
		const item = makeItem( { regular_price: '10', sale_price: '15' } );
		expect( validateSalePrice( item ) ).toBe(
			'Sale price must be lower than the regular price.'
		);
	} );

	it( 'handles decimal prices correctly', () => {
		const item = makeItem( {
			regular_price: '9.99',
			sale_price: '9.98',
		} );
		expect( validateSalePrice( item ) ).toBeNull();
	} );

	it( 'returns error for equal decimal prices', () => {
		const item = makeItem( {
			regular_price: '9.99',
			sale_price: '9.99',
		} );
		expect( validateSalePrice( item ) ).toBe(
			'Sale price must be lower than the regular price.'
		);
	} );
} );
