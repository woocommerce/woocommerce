/**
 * @jest-environment node
 */

/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );
jest.mock( '../shared', () => ( {
	addExitPageListener: jest.fn().mockImplementation( () => {} ),
	initProductScreenTracks: jest.fn().mockImplementation( () => {} ),
	getProductData: jest.fn().mockImplementation( () => ( { product_id: 1 } ) ),
} ) );

describe( 'Product Screen Tracking', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );
	it( 'should trigger product_edit_view event when productScreen.name is "edit"', () => {
		global.productScreen = { name: 'edit' };
		require( '../product-edit' );
		expect( recordEvent ).toHaveBeenCalledWith( 'product_edit_view', {
			product_id: 1,
		} );
	} );

	it( 'should not trigger product_edit_view event when productScreen.name is not "edit"', () => {
		global.productScreen = { name: '' };
		require( '../product-edit' );
		expect( recordEvent ).not.toHaveBeenCalled();
	} );
} );
