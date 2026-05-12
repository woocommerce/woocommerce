/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import type { Action } from '@wordpress/dataviews';
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	duplicateProductAction,
	moveToTrashAction,
	useProductActions,
} from './actions';
import type { ProductEntityRecord } from '../fields/types';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

jest.mock( '@wordpress/core-data', () => ( {
	store: 'mock-core-store',
} ) );

jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn(),
} ) );

jest.mock( '@wordpress/notices', () => ( {
	store: 'mock-notices-store',
} ) );

jest.mock( '../lock-unlock', () => {
	const useHistory = jest.fn();
	const useLocation = jest.fn();

	return {
		unlock: jest.fn( () => ( {
			useHistory,
			useLocation,
		} ) ),
		__mockUseHistory: useHistory,
		__mockUseLocation: useLocation,
	};
} );

jest.mock( '@wordpress/i18n', () => ( {
	__: jest.fn( ( message ) => message ),
	_x: jest.fn( ( message ) => message ),
	_n: jest.fn( ( singular, plural, count ) =>
		count === 1 ? singular : plural
	),
	sprintf: jest.fn( ( message, ...values ) =>
		values.reduce(
			( result, value ) =>
				result.replace( /%[0-9]*\$?[sd]/, String( value ) ),
			message
		)
	),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getAdminLink: jest.fn( ( path ) => path ),
} ) );

const { __mockUseHistory: mockUseHistory, __mockUseLocation: mockUseLocation } =
	jest.requireMock( '../lock-unlock' ) as {
		__mockUseHistory: jest.Mock;
		__mockUseLocation: jest.Mock;
	};
const mockedApiFetch = jest.mocked( apiFetch );

function getCallbackAction( action: Action< ProductEntityRecord > ) {
	return action as Action< ProductEntityRecord > & {
		callback: (
			items: ProductEntityRecord[],
			context: {
				onActionPerformed?: ( items: ProductEntityRecord[] ) => void;
			}
		) => Promise< void >;
	};
}

describe( 'product list actions', () => {
	const product = {
		id: 12,
		status: 'draft',
		name: 'Beanie',
	} as ProductEntityRecord;
	const hoodie = {
		id: 34,
		status: 'draft',
		name: 'Hoodie',
	} as ProductEntityRecord;

	const deleteEntityRecord = jest.fn();
	const invalidateResolution = jest.fn();
	const invalidateResolutionForStoreSelector = jest.fn();
	const createSuccessNotice = jest.fn();
	const createErrorNotice = jest.fn();
	const onActionPerformed = jest.fn();
	const navigate = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();
		mockUseHistory.mockReturnValue( {
			navigate,
		} );
		mockUseLocation.mockReturnValue( {
			path: '/products',
			query: {
				activeView: 'draft',
			},
		} );

		( dispatch as jest.Mock ).mockImplementation( ( storeName ) => {
			if ( storeName === 'mock-core-store' ) {
				return {
					deleteEntityRecord,
					invalidateResolution,
					invalidateResolutionForStoreSelector,
				};
			}

			if ( storeName === 'mock-notices-store' ) {
				return {
					createSuccessNotice,
					createErrorNotice,
				};
			}

			return {};
		} );
	} );

	it( 'opens quick edit panel when the Quick edit action is triggered', () => {
		const { result } = renderHook( () => useProductActions() );
		const quickEditProductAction = result.current.find(
			( action ) => action.id === 'quick-edit-product'
		);

		expect( quickEditProductAction ).toBeDefined();

		if ( ! quickEditProductAction ) {
			throw new Error( 'Quick edit action not found.' );
		}

		getCallbackAction( quickEditProductAction ).callback( [ product ], {
			onActionPerformed,
		} );

		expect( navigate ).toHaveBeenCalledWith(
			'/products?activeView=draft&postId=12&quickEdit=true'
		);
		expect( onActionPerformed ).toHaveBeenCalledWith( [ product ] );
	} );

	it( 'exposes the Quick edit action as a bulk action', () => {
		const { result } = renderHook( () => useProductActions() );
		const quickEditProductAction = result.current.find(
			( action ) => action.id === 'quick-edit-product'
		);

		expect( quickEditProductAction?.supportsBulk ).toBe( true );
	} );

	it( 'opens quick edit panel with all selected products when triggered as a bulk action', () => {
		const { result } = renderHook( () => useProductActions() );
		const quickEditProductAction = result.current.find(
			( action ) => action.id === 'quick-edit-product'
		);

		expect( quickEditProductAction ).toBeDefined();

		if ( ! quickEditProductAction ) {
			throw new Error( 'Quick edit action not found.' );
		}

		getCallbackAction( quickEditProductAction ).callback(
			[ product, hoodie ],
			{
				onActionPerformed,
			}
		);

		expect( navigate ).toHaveBeenCalledWith(
			'/products?activeView=draft&postId=12%2C34&quickEdit=true'
		);
		expect( onActionPerformed ).toHaveBeenCalledWith( [ product, hoodie ] );
	} );

	it( 'opens product editor when the Edit action is triggered', () => {
		const { result } = renderHook( () => useProductActions() );
		const editProductAction = result.current.find(
			( action ) => action.id === 'edit-product'
		);

		expect( editProductAction ).toBeDefined();

		if ( ! editProductAction ) {
			throw new Error( 'Edit action not found.' );
		}

		const originalLocation = window.location;
		Object.defineProperty( window, 'location', {
			writable: true,
			value: { href: '' },
		} );

		getCallbackAction( editProductAction ).callback( [ product ], {
			onActionPerformed,
		} );

		expect( window.location.href ).toBe( 'post.php?post=12&action=edit' );
		expect( onActionPerformed ).toHaveBeenCalledWith( [ product ] );

		Object.defineProperty( window, 'location', {
			writable: true,
			value: originalLocation,
		} );
	} );

	it( 'duplicates products through the WooCommerce duplicate endpoint', async () => {
		const duplicatedProduct = {
			...product,
			id: 99,
		} as ProductEntityRecord;
		mockedApiFetch.mockResolvedValue( duplicatedProduct );

		await getCallbackAction( duplicateProductAction() ).callback(
			[ product ],
			{
				onActionPerformed,
			}
		);

		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/products/12/duplicate',
			method: 'POST',
		} );
		expect( invalidateResolutionForStoreSelector ).toHaveBeenCalledWith(
			'getEntityRecords'
		);
		expect( createSuccessNotice ).toHaveBeenCalledWith(
			'"Beanie" duplicated successfully.',
			expect.objectContaining( {
				type: 'snackbar',
				id: 'duplicate-product-action',
				actions: expect.any( Array ),
			} )
		);
		expect( onActionPerformed ).toHaveBeenCalledWith( [
			duplicatedProduct,
		] );
		expect( createErrorNotice ).not.toHaveBeenCalled();
	} );

	it( 'shows an error notice when duplication fails', async () => {
		mockedApiFetch.mockRejectedValueOnce( new Error( 'Duplicate failed' ) );

		await getCallbackAction( duplicateProductAction() ).callback(
			[ product ],
			{
				onActionPerformed,
			}
		);

		expect( createSuccessNotice ).not.toHaveBeenCalled();
		expect( createErrorNotice ).toHaveBeenCalledWith(
			'Failed to duplicate "Beanie".',
			{
				type: 'snackbar',
				id: 'duplicate-product-error',
			}
		);
		expect( onActionPerformed ).not.toHaveBeenCalled();
	} );

	it( 'moves products to trash through coreStore root/product and refreshes the query', async () => {
		deleteEntityRecord.mockResolvedValue( { id: 12 } );

		await getCallbackAction( moveToTrashAction() ).callback( [ product ], {
			onActionPerformed,
		} );

		expect( deleteEntityRecord ).toHaveBeenCalledWith(
			'root',
			'product',
			12,
			{
				force: false,
				throwOnError: true,
			}
		);

		expect( createSuccessNotice ).toHaveBeenCalledWith(
			'Product successfully deleted',
			{ type: 'snackbar' }
		);
		expect( onActionPerformed ).toHaveBeenCalledWith( [ product ] );
		expect( createErrorNotice ).not.toHaveBeenCalled();
	} );
} );
