/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import type { Action } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { duplicateProductAction, moveToTrashAction } from './actions';
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

	const deleteEntityRecord = jest.fn();
	const invalidateResolution = jest.fn();
	const invalidateResolutionForStoreSelector = jest.fn();
	const createSuccessNotice = jest.fn();
	const createErrorNotice = jest.fn();
	const onActionPerformed = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();

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
