/**
 * Internal dependencies
 */
import { getProductTitle } from '../get-product-title';

describe( 'getProductTitle', () => {
	it( 'should return the product name when one has been entered', () => {
		const title = getProductTitle( 'Fancy pants', 'simple', undefined );
		expect( title ).toBe( 'Fancy pants' );
	} );

	it( 'should return the entered product name when a persisted name exists', () => {
		const title = getProductTitle( 'Fancy pants', 'simple', 'Saved name' );
		expect( title ).toBe( 'Fancy pants' );
	} );

	it( 'should return the persisted name when no name is given', () => {
		const title = getProductTitle( '', 'simple', 'Saved name' );
		expect( title ).toBe( 'Saved name' );
	} );

	it( 'should return the product type add new string when set', () => {
		const title = getProductTitle( '', 'simple', undefined );
		expect( title ).toBe( 'New standard product' );
	} );

	it( 'should return the generic add new string when no type matches', () => {
		const title = getProductTitle( '', 'custom-type', undefined );
		expect( title ).toBe( 'New product' );
	} );

	it( 'should return the generic add new string when the product title is the auto draft title', () => {
		const title = getProductTitle( '', 'custom-type', 'AUTO-DRAFT' );
		expect( title ).toBe( 'New product' );
	} );
} );
