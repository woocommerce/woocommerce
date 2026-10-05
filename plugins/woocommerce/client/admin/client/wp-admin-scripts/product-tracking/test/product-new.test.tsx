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
	it( 'should trigger product_add_view event when productScreen.name is "new"', async () => {
		global.productScreen = {
			name: 'new',
		};
		await import( '../product-new' );
		expect( recordEvent ).toHaveBeenCalledWith( 'product_add_view' );
	} );
	it( 'should not trigger product_add_view event when productScreen.name is not "new"', async () => {
		global.productScreen = {
			name: '',
		};
		await import( '../product-new' );
		expect( recordEvent ).not.toHaveBeenCalled();
	} );
} );
