import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * @vitest-environment node
 */

/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
vi.mock( '@woocommerce/tracks', () => {
	const mock = {
		recordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../shared', () => {
	const mock = {
		addExitPageListener: vi.fn().mockImplementation( () => {} ),
		initProductScreenTracks: vi.fn().mockImplementation( () => {} ),
		getProductData: vi.fn().mockImplementation( () => ( {
			product_id: 1,
		} ) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'Product Screen Tracking', () => {
	beforeEach( () => {
		vi.clearAllMocks();
	} );
	it( 'should trigger product_edit_view event when productScreen.name is "edit"', async () => {
		global.productScreen = {
			name: 'edit',
		};
		await import( '../product-edit' );
		expect( recordEvent ).toHaveBeenCalledWith( 'product_edit_view', {
			product_id: 1,
		} );
	} );
	it( 'should not trigger product_edit_view event when productScreen.name is not "edit"', async () => {
		global.productScreen = {
			name: '',
		};
		await import( '../product-edit' );
		expect( recordEvent ).not.toHaveBeenCalled();
	} );
} );
