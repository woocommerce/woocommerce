/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { isSiteEditorPage } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import { useAllowedControls } from '../utils';

const mockAllowedControls = [ 'wooInherit', 'onSale' ];

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn( ( select ) =>
		select( () => ( {
			getActiveBlockVariation: () => ( {
				allowedControls: mockAllowedControls,
			} ),
		} ) )
	),
} ) );

jest.mock( '@woocommerce/utils', () => ( {
	...jest.requireActual( '@woocommerce/utils' ),
	isSiteEditorPage: jest.fn(),
} ) );

const mockIsSiteEditorPage = isSiteEditorPage as jest.Mock;

describe( 'Product Query inspector controls', () => {
	it( 'shows only query inheritance for inherited Site Editor queries', () => {
		mockIsSiteEditorPage.mockReturnValue( true );

		const { result } = renderHook( () =>
			useAllowedControls( {
				query: { inherit: true },
			} as never )
		);

		expect( result.current ).toEqual( [ 'wooInherit' ] );
	} );

	it( 'restores advanced controls when Site Editor query inheritance is disabled', () => {
		mockIsSiteEditorPage.mockReturnValue( true );

		const { result, rerender } = renderHook(
			( { inherit } ) =>
				useAllowedControls( {
					query: { inherit },
				} as never ),
			{ initialProps: { inherit: true } }
		);

		rerender( { inherit: false } );

		expect( result.current ).toEqual( mockAllowedControls );
	} );

	it( 'removes only query inheritance from Post Editor controls', () => {
		mockIsSiteEditorPage.mockReturnValue( false );

		const { result } = renderHook( () =>
			useAllowedControls( {
				query: { inherit: true },
			} as never )
		);

		expect( result.current ).toEqual( [ 'onSale' ] );
	} );
} );
