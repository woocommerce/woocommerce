/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useApplyUpdate } from '../hooks/use-apply-update';

const createSuccessNoticeMock = jest.fn();
const createErrorNoticeMock = jest.fn();
const receiveEntityRecordsMock = jest.fn();

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '@wordpress/data', () => ( {
	useDispatch: () => ( {
		createSuccessNotice: createSuccessNoticeMock,
		createErrorNotice: createErrorNoticeMock,
		receiveEntityRecords: receiveEntityRecordsMock,
	} ),
	select: () => ( {
		getEntityRecord: () => ( { content: { raw: 'old content' } } ),
	} ),
} ) );
jest.mock( '@wordpress/notices', () => ( { store: 'core/notices' } ) );
jest.mock( '@wordpress/core-data', () => ( { store: 'core' } ) );

const mockedApiFetch = apiFetch as unknown as jest.Mock;

describe( 'useApplyUpdate — suppressSnackbarOnError option', () => {
	beforeEach( () => {
		createSuccessNoticeMock.mockClear();
		createErrorNoticeMock.mockClear();
		receiveEntityRecordsMock.mockClear();
		mockedApiFetch.mockReset();
	} );

	it( 'fires error snackbar by default when /apply fails', async () => {
		mockedApiFetch.mockRejectedValueOnce( new Error( 'boom' ) );

		const { result } = renderHook( () => useApplyUpdate( 42 ) );

		await act( async () => {
			await result.current.apply( [] );
		} );

		expect( createErrorNoticeMock ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'suppresses error snackbar when suppressSnackbarOnError is true', async () => {
		mockedApiFetch.mockRejectedValueOnce( new Error( 'boom' ) );

		const { result } = renderHook( () =>
			useApplyUpdate( 42, { suppressSnackbarOnError: true } )
		);

		await act( async () => {
			await result.current.apply( [] );
		} );

		expect( createErrorNoticeMock ).not.toHaveBeenCalled();
	} );

	it( 'still fires success snackbar when suppressSnackbarOnError is true', async () => {
		mockedApiFetch.mockResolvedValueOnce( {
			merged_content: 'merged',
			revision_id: 'rev1',
			version_to: '9.5',
			status: 'applied',
			structural_skipped: false,
			aliases_migrated: [],
		} );

		const { result } = renderHook( () =>
			useApplyUpdate( 42, { suppressSnackbarOnError: true } )
		);

		await act( async () => {
			await result.current.apply( [] );
		} );

		expect( createSuccessNoticeMock ).toHaveBeenCalledWith(
			'Update applied · customizations preserved',
			expect.objectContaining( { type: 'snackbar' } )
		);
	} );

	it( 'success snackbar copy is the new RSM-141 string', async () => {
		mockedApiFetch.mockResolvedValueOnce( {
			merged_content: 'merged',
			revision_id: 'rev1',
			version_to: '9.5',
			status: 'applied',
			structural_skipped: false,
			aliases_migrated: [],
		} );

		const { result } = renderHook( () => useApplyUpdate( 42 ) );

		await act( async () => {
			await result.current.apply( [] );
		} );

		expect( createSuccessNoticeMock ).toHaveBeenCalledWith(
			'Update applied · customizations preserved',
			expect.objectContaining( { type: 'snackbar' } )
		);
	} );

	it( 'snackbar copy drops the "customizations preserved" suffix when every choice is use_core', async () => {
		mockedApiFetch.mockResolvedValueOnce( {
			merged_content: 'merged',
			revision_id: 'rev1',
			version_to: '9.5',
			status: 'applied',
			structural_skipped: false,
			aliases_migrated: [],
		} );

		const { result } = renderHook( () => useApplyUpdate( 42 ) );

		await act( async () => {
			await result.current.apply( [
				{ path: [ 0 ], decision: 'use_core' },
				{ path: [ 1 ], decision: 'use_core' },
			] );
		} );

		expect( createSuccessNoticeMock ).toHaveBeenCalledWith(
			'Update applied',
			expect.objectContaining( { type: 'snackbar' } )
		);
	} );

	it( 'snackbar keeps the suffix when at least one choice is keep_yours', async () => {
		mockedApiFetch.mockResolvedValueOnce( {
			merged_content: 'merged',
			revision_id: 'rev1',
			version_to: '9.5',
			status: 'applied',
			structural_skipped: false,
			aliases_migrated: [],
		} );

		const { result } = renderHook( () => useApplyUpdate( 42 ) );

		await act( async () => {
			await result.current.apply( [
				{ path: [ 0 ], decision: 'use_core' },
				{ path: [ 1 ], decision: 'keep_yours' },
			] );
		} );

		expect( createSuccessNoticeMock ).toHaveBeenCalledWith(
			'Update applied · customizations preserved',
			expect.objectContaining( { type: 'snackbar' } )
		);
	} );
} );
