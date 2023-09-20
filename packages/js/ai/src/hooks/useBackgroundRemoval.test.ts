/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react-hooks/dom';
import { waitFor } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	BackgroundRemovalParams,
	useBackgroundRemoval,
} from './useBackgroundRemoval';
import { requestJetpackToken } from '../utils/requestJetpackToken';

// Mocking the apiFetch function
jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '../utils/requestJetpackToken' );
const mockedRequestJetpackToken = requestJetpackToken as jest.MockedFunction<
	typeof requestJetpackToken
>;

describe( 'useBackgroundRemoval hook', () => {
	let mockRequestParams: BackgroundRemovalParams;

	beforeEach( () => {
		jest.resetAllMocks();
		// Initialize with valid parameters (50kb image file).
		const imageFile = new File( [ new ArrayBuffer( 51200 ) ], 'test.png', {
			type: 'image/png',
		} );
		const returnedImageType = 'jpeg';
		const returnedImageSize = 'hd';

		mockRequestParams = {
			imageFile,
			returnedImageType,
			returnedImageSize,
		};
		mockedRequestJetpackToken.mockResolvedValue( { token: 'fake_token' } );
	} );

	it( 'should initialize with correct default values', () => {
		const { result } = renderHook( () => useBackgroundRemoval() );
		expect( result.current.imageData ).toBeNull();
		expect( result.current.loading ).toBeFalsy();
		expect( result.current.error ).toBeNull();
	} );

	it( 'should return error on empty token', async () => {
		mockedRequestJetpackToken.mockResolvedValue( { token: '' } );
		const { result } = renderHook( () => useBackgroundRemoval() );
		act( () => {
			result.current.fetchImage( mockRequestParams );
		} );
		await waitFor( () =>
			expect( result.current.error ).toEqual(
				new Error( 'Invalid token' )
			)
		);
	} );

	it( 'should handle invalid file type', async () => {
		mockRequestParams.imageFile = new File(
			[ new ArrayBuffer( 51200 ) ],
			'test.txt',
			{ type: 'text/plain' }
		);

		const { result } = renderHook( () => useBackgroundRemoval() );

		await act( async () => {
			result.current.fetchImage( mockRequestParams );
		} );

		expect( result.current.error ).toEqual(
			new Error( 'Invalid image file' )
		);
	} );

	it( 'should return error on image file too small', async () => {
		mockRequestParams.imageFile = new File(
			[ new ArrayBuffer( 1024 ) ],
			'test.png',
			{ type: 'image/png' }
		); // 1KB

		const { result } = renderHook( () => useBackgroundRemoval() );

		await act( async () => {
			result.current.fetchImage( mockRequestParams );
		} );

		expect( result.current.error ).toEqual(
			new Error( 'Image file too small, must be at least 5KB' )
		);
	} );

	it( 'should return error on image file too large', async () => {
		mockRequestParams.imageFile = new File(
			[ new ArrayBuffer( 10240 * 1024 * 2 ) ],
			'test.png',
			{ type: 'image/png' }
		); // 10MB

		const { result } = renderHook( () => useBackgroundRemoval() );

		await act( async () => {
			result.current.fetchImage( mockRequestParams );
		} );

		expect( result.current.error ).toEqual(
			new Error( 'Image file too large, must be under 10MB' )
		);
	} );

	it( 'should set loading to true when fetchImage is called', async () => {
		const { result } = renderHook( () => useBackgroundRemoval() );
		act( () => {
			result.current.fetchImage( mockRequestParams );
		} );
		await waitFor( () => expect( result.current.loading ).toBeTruthy() );
		expect( mockedRequestJetpackToken ).toHaveBeenCalled();
	} );

	it( 'should handle successful API call', async () => {
		(
			apiFetch as jest.MockedFunction< typeof apiFetch >
		 ).mockResolvedValue(
			new Blob( [ new ArrayBuffer( 51200 ) ], { type: 'image/jpeg' } )
		);
		const { result } = renderHook( () => useBackgroundRemoval() );
		await act( async () => {
			result.current.fetchImage( mockRequestParams );
		} );
		expect( result.current.loading ).toBe( false );
		expect( result.current.error ).toBe( null );
		expect( result.current.imageData ).toBeInstanceOf( Blob );
	} );

	it( 'should handle API errors', async () => {
		(
			apiFetch as jest.MockedFunction< typeof apiFetch >
		 ).mockRejectedValue( new Error( 'API Error' ) );
		const { result } = renderHook( () => useBackgroundRemoval() );
		await act( async () => {
			result.current.fetchImage( mockRequestParams );
		} );
		expect( result.current.loading ).toBe( false );
		expect( result.current.error ).not.toBeNull();
		expect( result.current.imageData ).toBe( null );
	} );
} );
