/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useValidation } from '../use-validation';

jest.mock( '@wordpress/data', () => ( {
	useDispatch: jest.fn(),
} ) );

describe( 'useValidation', () => {
	const useDispatchMock = useDispatch as jest.Mock;
	const lockPostSaving = jest.fn();
	const unlockPostSaving = jest.fn();

	beforeEach( () => {
		useDispatchMock.mockReturnValue( {
			lockPostSaving,
			unlockPostSaving,
		} );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'sync', () => {
		it( 'should lock the editor if validate returns false', async () => {
			const { result, waitForNextUpdate } = renderHook( () =>
				useValidation( 'product/name', () => false )
			);

			await waitForNextUpdate();

			expect( result.current ).toBeFalsy();
			expect( lockPostSaving ).toHaveBeenCalled();
			expect( unlockPostSaving ).not.toHaveBeenCalled();
		} );

		it( 'should unlock the editor if validate returns true', async () => {
			const { result, waitForNextUpdate } = renderHook( () =>
				useValidation( 'product/name', () => true )
			);

			await waitForNextUpdate();

			expect( result.current ).toBeTruthy();
			expect( lockPostSaving ).not.toHaveBeenCalled();
			expect( unlockPostSaving ).toHaveBeenCalled();
		} );
	} );

	describe( 'async', () => {
		it( 'should lock the editor if validate resolves false', async () => {
			const { result, waitForNextUpdate } = renderHook( () =>
				useValidation( 'product/name', () => Promise.resolve( false ) )
			);

			await waitForNextUpdate();

			expect( result.current ).toBeFalsy();
			expect( lockPostSaving ).toHaveBeenCalled();
			expect( unlockPostSaving ).not.toHaveBeenCalled();
		} );

		it( 'should lock the editor if validate rejects', async () => {
			const { result, waitForNextUpdate } = renderHook( () =>
				useValidation( 'product/name', () => Promise.resolve( false ) )
			);

			await waitForNextUpdate();

			expect( result.current ).toBeFalsy();
			expect( lockPostSaving ).toHaveBeenCalled();
			expect( unlockPostSaving ).not.toHaveBeenCalled();
		} );

		it( 'should unlock the editor if validate resolves true', async () => {
			const { result, waitForNextUpdate } = renderHook( () =>
				useValidation( 'product/name', () => Promise.resolve( true ) )
			);

			await waitForNextUpdate();

			expect( result.current ).toBeTruthy();
			expect( lockPostSaving ).not.toHaveBeenCalled();
			expect( unlockPostSaving ).toHaveBeenCalled();
		} );
	} );
} );
