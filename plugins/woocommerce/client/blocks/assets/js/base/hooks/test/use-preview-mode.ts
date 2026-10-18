/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import * as wpData from '@wordpress/data';

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import { usePreviewMode } from '../use-preview-mode';

describe( 'usePreviewMode', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should return true when isPreviewMode is true', () => {
		( wpData.useSelect as jest.Mock ).mockImplementation( ( callback ) =>
			callback( () => ( {
				getSettings: () => ( { isPreviewMode: true } ),
			} ) )
		);

		const { result } = renderHook( () => usePreviewMode() );
		expect( result.current ).toBe( true );
	} );

	it( 'should return true when __unstableIsPreviewMode is true (WP < 6.7 fallback)', () => {
		( wpData.useSelect as jest.Mock ).mockImplementation( ( callback ) =>
			callback( () => ( {
				getSettings: () => ( { __unstableIsPreviewMode: true } ),
			} ) )
		);

		const { result } = renderHook( () => usePreviewMode() );
		expect( result.current ).toBe( true );
	} );

	it( 'should return false when isPreviewMode is false', () => {
		( wpData.useSelect as jest.Mock ).mockImplementation( ( callback ) =>
			callback( () => ( {
				getSettings: () => ( { isPreviewMode: false } ),
			} ) )
		);

		const { result } = renderHook( () => usePreviewMode() );
		expect( result.current ).toBe( false );
	} );

	it( 'should return false when settings are empty or undefined', () => {
		( wpData.useSelect as jest.Mock ).mockImplementation( ( callback ) =>
			callback( () => ( {
				getSettings: () => ( {} ),
			} ) )
		);

		const { result } = renderHook( () => usePreviewMode() );
		expect( result.current ).toBe( false );
	} );
} );
