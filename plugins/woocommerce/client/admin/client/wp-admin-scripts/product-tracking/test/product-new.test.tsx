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
} ) );

describe( 'Product Screen Tracking', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );
	it( 'should trigger product_add_view event when productScreen.name is "new"', () => {
		global.productScreen = { name: 'new' };
		require( '../product-new' );
		expect( recordEvent ).toHaveBeenCalledWith( 'product_add_view' );
	} );

	it( 'should not trigger product_add_view event when productScreen.name is not "new"', () => {
		global.productScreen = { name: '' };
		require( '../product-new' );
		expect( recordEvent ).not.toHaveBeenCalled();
	} );
} );
