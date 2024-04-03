/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react-hooks/dom';
import { waitFor } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';
import { requestJwt } from '@automattic/jetpack-ai-client';

/**
 * Internal dependencies
 */
import {
	BackgroundRemovalParams,
	useBackgroundRemoval,
} from './useBackgroundRemoval';

// Mocking the apiFetch function
jest.mock( '@wordpress/api-fetch', () =>
	jest.fn().mockResolvedValue( {
		blob: () =>
			Promise.resolve(
				new Blob( [ new ArrayBuffer( 51200 ) ], {
					type: 'image/jpeg',
				} )
			),
	} )
);
jest.mock( '@automattic/jetpack-ai-client', () => ( {
	requestJwt: jest.fn() as jest.MockedFunction< typeof requestJwt >,
} ) );

describe( 'useBackgroundRemoval hook', () => {
	let mockRequestParams: BackgroundRemovalParams;

	beforeEach( () => {
		jest.resetAllMocks();
		// Initialize with valid parameters (50kb image file).
		const imageFile = new File( [ new ArrayBuffer( 51200 ) ], 'test.png', {
			type: 'image/png',
		} );

		mockRequestParams = {
			imageFile,
		};
		(
			requestJwt as jest.MockedFunction< typeof requestJwt >
		 ).mockResolvedValue( {
			token: 'fake_token',
			blogId: '0',
			expire: 0,
		} );
	} );

	it( 'should initialize with correct default values', () => {
		const { result } = renderHook( () => useBackgroundRemoval() );
		expect( result.current.imageData ).toBeNull();
		expect( result.current.loading ).toBeFalsy();
	} );

	it( 'should return error on empty token', async () => {
		(
			requestJwt as jest.MockedFunction< typeof requestJwt >
		 ).mockResolvedValue( {
			token: '',
			blogId: '0',
			expire: 0,
		} );
		const { result } = renderHook( () => useBackgroundRemoval() );
		await expect(
			act( async () => {
				await result.current.fetchImage( mockRequestParams );
			} )
		).rejects.toThrow( 'Invalid token' );
	} );

	it( 'should handle invalid file type', async () => {
		mockRequestParams.imageFile = new File(
			[ new ArrayBuffer( 51200 ) ],
			'test.txt',
			{ type: 'text/plain' }
		);
		const { result } = renderHook( () => useBackgroundRemoval() );
		await expect(
			act( async () => {
				await result.current.fetchImage( mockRequestParams );
			} )
		).rejects.toThrow( 'Invalid image file' );
	} );

	it( 'should return error on image file too small', async () => {
		mockRequestParams.imageFile = new File(
			[ new ArrayBuffer( 1024 ) ],
			'test.png',
			{ type: 'image/png' }
		); // 1KB
		const { result } = renderHook( () => useBackgroundRemoval() );
		await expect(
			act( async () => {
				await result.current.fetchImage( mockRequestParams );
			} )
		).rejects.toThrow( 'Image file too small, must be at least 5KB' );
	} );

	it( 'should return error on image file too large', async () => {
		mockRequestParams.imageFile = new File(
			[ new ArrayBuffer( 10240 * 1024 * 2 ) ],
			'test.png',
			{ type: 'image/png' }
		); // 10MB
		const { result } = renderHook( () => useBackgroundRemoval() );
		await expect(
			act( async () => {
				await result.current.fetchImage( mockRequestParams );
			} )
		).rejects.toThrow( 'Image file too large, must be under 10MB' );
	} );

	it( 'should set loading to true when fetchImage is called', async () => {
		(
			apiFetch as jest.MockedFunction< typeof apiFetch >
		 ).mockResolvedValue( {
			blob: () =>
				Promise.resolve(
					new Blob( [ new ArrayBuffer( 51200 ) ], {
						type: 'image/jpeg',
					} )
				),
		} );

		const { result } = renderHook( () => useBackgroundRemoval() );
		await act( async () => {
			result.current.fetchImage( mockRequestParams );
			await waitFor( () =>
				expect( result.current.loading ).toBeTruthy()
			);
		} );
		expect( requestJwt ).toHaveBeenCalled();
	} );

	it( 'should handle successful API call', async () => {
		(
			apiFetch as jest.MockedFunction< typeof apiFetch >
		 ).mockResolvedValue( {
			blob: () =>
				Promise.resolve(
					new Blob( [ new ArrayBuffer( 51200 ) ], {
						type: 'image/jpeg',
					} )
				),
		} );

		const { result } = renderHook( () => useBackgroundRemoval() );
		await act( async () => {
			await result.current.fetchImage( mockRequestParams );
		} );
		expect( result.current.loading ).toBe( false );
		expect( result.current.imageData ).toBeInstanceOf( Blob );
	} );

	it( 'should handle API errors', async () => {
		(
			apiFetch as jest.MockedFunction< typeof apiFetch >
		 ).mockImplementation( () => {
			throw new Error( 'API Error' );
		} );

		const { result } = renderHook( () => useBackgroundRemoval() );
		try {
			await act( async () => {
				await result.current.fetchImage( mockRequestParams );
			} );
		} catch ( error: unknown ) {
			if ( error instanceof Error ) {
				expect( error.message ).toBe( 'API Error' );
			}
		}
		await waitFor( () => expect( result.current.loading ).toBeFalsy() );
		expect( result.current.imageData ).toBe( null );
	} );
} );
