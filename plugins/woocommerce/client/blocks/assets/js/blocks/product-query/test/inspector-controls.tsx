import { describe, expect, it, vi, type Mock } from 'vitest';
const { mockAllowedControls } = vi.hoisted( () => {
	const mockAllowedControls = [ 'wooInherit', 'onSale' ];
	return {
		mockAllowedControls,
	};
} );

/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { isSiteEditorPage } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import { useAllowedControls } from '../utils';
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useSelect: vi.fn( ( select ) =>
			select( () => ( {
				getActiveBlockVariation: () => ( {
					allowedControls: mockAllowedControls,
				} ),
			} ) )
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/utils', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/utils' ) ),
		isSiteEditorPage: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const mockIsSiteEditorPage = isSiteEditorPage as Mock;
describe( 'Product Query inspector controls', () => {
	it( 'shows only query inheritance for inherited Site Editor queries', () => {
		mockIsSiteEditorPage.mockReturnValue( true );
		const { result } = renderHook( () =>
			useAllowedControls( {
				query: {
					inherit: true,
				},
			} as never )
		);
		expect( result.current ).toEqual( [ 'wooInherit' ] );
	} );
	it( 'restores advanced controls when Site Editor query inheritance is disabled', () => {
		mockIsSiteEditorPage.mockReturnValue( true );
		const { result, rerender } = renderHook(
			( { inherit } ) =>
				useAllowedControls( {
					query: {
						inherit,
					},
				} as never ),
			{
				initialProps: {
					inherit: true,
				},
			}
		);
		rerender( {
			inherit: false,
		} );
		expect( result.current ).toEqual( mockAllowedControls );
	} );
	it( 'removes only query inheritance from Post Editor controls', () => {
		mockIsSiteEditorPage.mockReturnValue( false );
		const { result } = renderHook( () =>
			useAllowedControls( {
				query: {
					inherit: true,
				},
			} as never )
		);
		expect( result.current ).toEqual( [ 'onSale' ] );
	} );
} );
