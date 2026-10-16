/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useCreateProductByType } from '../use-create-product-by-type';
import { createNoticesFromResponse } from '../../../../lib/notices';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn(),
} ) );

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	navigateTo: jest.fn(),
} ) );

jest.mock( '../../../../lib/notices', () => ( {
	createNoticesFromResponse: jest.fn(),
} ) );

describe( 'useCreateProductByType', () => {
	it( 'should stay busy after navigating to the editor', async () => {
		( useDispatch as jest.Mock ).mockReturnValue( {
			createProductFromTemplate: jest.fn().mockResolvedValue( { id: 7 } ),
		} );
		const { result } = renderHook( () => useCreateProductByType() );

		await act( () => result.current.createProductByType( 'physical' ) );

		expect( result.current.requestingType ).toBe( 'physical' );
	} );

	it( 'should clear the busy state when the request fails', async () => {
		( useDispatch as jest.Mock ).mockReturnValue( {
			createProductFromTemplate: jest
				.fn()
				.mockRejectedValue( new Error( 'failed' ) ),
		} );
		const { result } = renderHook( () => useCreateProductByType() );

		await act( () => result.current.createProductByType( 'physical' ) );

		expect( result.current.requestingType ).toBeNull();
		expect( createNoticesFromResponse ).toHaveBeenCalled();
	} );
} );
