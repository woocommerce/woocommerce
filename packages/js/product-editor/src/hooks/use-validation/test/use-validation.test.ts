/**
 * External dependencies
 */
import {
	RenderHookResult,
	act,
	renderHook,
} from '@testing-library/react-hooks';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useValidation } from '../use-validation';
import { ValidationError } from '../types';

jest.mock( '@wordpress/data', () => ( {
	useDispatch: jest.fn(),
} ) );

describe( 'useValidation', () => {
	const useDispatchMock = useDispatch as jest.Mock;
	const lockPostSaving = jest.fn();
	const unlockPostSaving = jest.fn();
	let hookResult = {} as RenderHookResult< unknown, ValidationError >;

	beforeEach( () => {
		useDispatchMock.mockReturnValue( {
			lockPostSaving,
			unlockPostSaving,
		} );
	} );

	afterEach( async () => {
		jest.clearAllMocks();
	} );

	describe( 'sync', () => {
		it( 'should lock the editor if validate returns an error', async () => {
			const validationError = 'Invalid name';

			await act( async () => {
				hookResult = renderHook( () =>
					useValidation( 'product/name', () => validationError )
				);
			} );

			const { result } = hookResult;

			expect( result.current ).toBe( validationError );
			expect( lockPostSaving ).toHaveBeenCalled();
			expect( unlockPostSaving ).not.toHaveBeenCalled();
		} );

		it( 'should unlock the editor if validate returns no error', async () => {
			await act( async () => {
				hookResult = renderHook( () =>
					useValidation( 'product/name', () => undefined )
				);
			} );

			const { result } = hookResult;

			expect( result.current ).toBeUndefined();
			expect( lockPostSaving ).not.toHaveBeenCalled();
			expect( unlockPostSaving ).toHaveBeenCalled();
		} );
	} );

	describe( 'async', () => {
		it( 'should lock the editor if validate resolves an error', async () => {
			const validationError = 'Invalid name';

			await act( async () => {
				hookResult = renderHook( () =>
					useValidation( 'product/name', () =>
						Promise.resolve( validationError )
					)
				);
			} );

			const { result } = hookResult;

			expect( result.current ).toBe( validationError );
			expect( lockPostSaving ).toHaveBeenCalled();
			expect( unlockPostSaving ).not.toHaveBeenCalled();
		} );

		it( 'should lock the editor if validate rejects', async () => {
			const validationError = 'Invalid name';

			await act( async () => {
				hookResult = renderHook( () =>
					useValidation( 'product/name', () =>
						Promise.reject( validationError )
					)
				);
			} );

			const { result } = hookResult;

			expect( result.current ).toBe( validationError );
			expect( lockPostSaving ).toHaveBeenCalled();
			expect( unlockPostSaving ).not.toHaveBeenCalled();
		} );

		it( 'should unlock the editor if validate resolves undefined', async () => {
			await act( async () => {
				hookResult = renderHook( () =>
					useValidation( 'product/name', () =>
						Promise.resolve( undefined )
					)
				);
			} );

			const { result } = hookResult;

			expect( result.current ).toBeUndefined();
			expect( lockPostSaving ).not.toHaveBeenCalled();
			expect( unlockPostSaving ).toHaveBeenCalled();
		} );
	} );
} );
