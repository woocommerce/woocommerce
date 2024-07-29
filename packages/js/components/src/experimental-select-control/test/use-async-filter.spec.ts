/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react-hooks';
import { useDebounce } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { useAsyncFilter } from '../';

jest.mock( '@wordpress/compose', () => ( {
	...jest.requireActual( '@wordpress/compose' ),
	useDebounce: jest.fn( ( cb: CallableFunction ) => cb ),
} ) );

describe( 'useAsyncFilter', () => {
	const filter = jest.fn();
	const onFilterStart = jest.fn();
	const onFilterEnd = jest.fn();
	const onFilterError = jest.fn();

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should filter the items successfully', async () => {
		const filteredItems: string[] = [];

		filter.mockResolvedValue( filteredItems );

		const { result } = renderHook( () =>
			useAsyncFilter( {
				filter,
			} )
		);

		const inputValue = 'Apple';

		await act( async () => {
			if ( result.current.onInputChange )
				result.current.onInputChange( inputValue, {} );
		} );

		expect( useDebounce ).toHaveBeenCalledWith(
			expect.any( Function ),
			250
		);
		expect( filter ).toHaveBeenCalledWith( inputValue );
	} );

	it( 'should trigger onFilterStart at the beginning of the filtering', async () => {
		const filteredItems: string[] = [];

		onFilterStart.mockImplementation( ( value = '' ) => {
			expect( filter ).not.toHaveBeenCalledWith( value );
		} );

		filter.mockImplementation( ( value = '' ) => {
			expect( onFilterStart ).toHaveBeenCalledWith( value );
			return Promise.resolve( filteredItems );
		} );

		const { result } = renderHook( () =>
			useAsyncFilter( {
				filter,
				onFilterStart,
			} )
		);

		const inputValue = 'Apple';

		await act( async () => {
			if ( result.current.onInputChange )
				result.current.onInputChange( inputValue, {} );
		} );

		expect( filter ).toHaveBeenCalledWith( inputValue );
	} );

	it( 'should trigger onFilterEnd when filtering is fulfilled', async () => {
		const filteredItems: string[] = [];

		filter.mockResolvedValue( filteredItems );

		const { result } = renderHook( () =>
			useAsyncFilter( {
				filter,
				onFilterEnd,
				onFilterError,
			} )
		);

		const inputValue = 'Apple';

		await act( async () => {
			if ( result.current.onInputChange )
				result.current.onInputChange( inputValue, {} );
		} );

		expect( onFilterEnd ).toHaveBeenCalledWith( filteredItems, inputValue );
		expect( onFilterError ).not.toHaveBeenCalled();
	} );

	it( 'should trigger onFilterError when filtering is rejected', async () => {
		const error = new Error();

		filter.mockRejectedValue( error );

		const { result } = renderHook( () =>
			useAsyncFilter( {
				filter,
				onFilterEnd,
				onFilterError,
			} )
		);

		const inputValue = 'Apple';

		await act( async () => {
			if ( result.current.onInputChange )
				result.current.onInputChange( inputValue, {} );
		} );

		expect( onFilterEnd ).not.toHaveBeenCalled();
		expect( onFilterError ).toHaveBeenCalledWith( error, inputValue );
	} );
} );
