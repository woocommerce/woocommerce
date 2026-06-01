/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import type { Action, ActionModal } from '@wordpress/dataviews';
import { renderHook } from '@testing-library/react';
import React from 'react';

/**
 * Internal dependencies
 */
import {
	duplicateProductAction,
	moveToTrashAction,
	permanentlyDeleteAction,
	restoreAction,
	quickEditAction,
	selectAllVariationsAction,
	useProductActions,
} from './actions';
import type { ProductEntityRecord } from '../fields/types';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

jest.mock( '@wordpress/components', () => ( {
	Button: 'button',
	__experimentalHStack: ( { children }: { children: React.ReactNode } ) =>
		children,
	__experimentalText: ( { children }: { children: React.ReactNode } ) =>
		children,
	__experimentalVStack: ( { children }: { children: React.ReactNode } ) =>
		children,
} ) );

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

function getActionLabel(
	action: Action< ProductEntityRecord >,
	items: ProductEntityRecord[]
) {
	return typeof action.label === 'string'
		? action.label
		: action.label( items );
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
	const blueVariation = {
		id: 56,
		parent_id: 78,
		status: 'publish',
		name: 'Blue hoodie',
		type: 'variation',
	} as ProductEntityRecord;
	const greenVariation = {
		id: 57,
		parent_id: 78,
		status: 'publish',
		name: 'Green hoodie',
		type: 'variation',
	} as ProductEntityRecord;
	const variableProduct = {
		id: 78,
		status: 'draft',
		name: 'Variable hoodie',
		type: 'variable',
		_embedded: {
			variations: [ blueVariation, greenVariation ],
		},
	} as ProductEntityRecord;

	const deleteEntityRecord = jest.fn();
	const editEntityRecord = jest.fn();
	const saveEditedEntityRecord = jest.fn();
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
					editEntityRecord,
					saveEditedEntityRecord,
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

	it( 'renames Quick edit to Bulk editing when multiple products are selected', () => {
		const action = quickEditAction( {
			navigate,
		} );

		expect( getActionLabel( action, [ product ] ) ).toBe( 'Quick edit' );
		expect( getActionLabel( action, [ product, hoodie ] ) ).toBe(
			'Bulk editing'
		);
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

	it( 'replaces the View action with the Select all variations action', () => {
		const { result } = renderHook( () => useProductActions() );
		const actionIds = result.current.map( ( action ) => action.id );

		expect( actionIds ).toContain( 'select-all-variations' );
		expect( actionIds ).not.toContain( 'view-product' );
	} );

	it( 'shows the Select all variations action only for variable products with variations', () => {
		const selectVariationsAction = selectAllVariationsAction( {
			navigate,
		} );

		expect( selectVariationsAction.isEligible?.( variableProduct ) ).toBe(
			true
		);
		expect(
			selectVariationsAction.isEligible?.( {
				...variableProduct,
				_embedded: {
					variations: [],
				},
			} )
		).toBe( false );
		expect( selectVariationsAction.isEligible?.( product ) ).toBe( false );
		expect(
			selectVariationsAction.isEligible?.( {
				...variableProduct,
				status: 'trash',
			} )
		).toBe( false );
	} );

	it( 'selects all variations when the Select all variations action is triggered', () => {
		const { result } = renderHook( () => useProductActions() );
		const selectVariationsAction = result.current.find(
			( action ) => action.id === 'select-all-variations'
		);

		expect( selectVariationsAction ).toBeDefined();

		if ( ! selectVariationsAction ) {
			throw new Error( 'Select all variations action not found.' );
		}

		getCallbackAction( selectVariationsAction ).callback(
			[ variableProduct ],
			{
				onActionPerformed,
			}
		);

		expect( navigate ).toHaveBeenCalledWith(
			'/products?activeView=draft&postId=56%2C57'
		);
		expect( onActionPerformed ).toHaveBeenCalledWith( [
			blueVariation,
			greenVariation,
		] );
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

	it( 'opens the parent product editor when the Edit action is triggered for a variation', () => {
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

		getCallbackAction( editProductAction ).callback( [ blueVariation ], {
			onActionPerformed,
		} );

		expect( window.location.href ).toBe( 'post.php?post=78&action=edit' );
		expect( onActionPerformed ).toHaveBeenCalledWith( [ blueVariation ] );

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

	describe( 'restoreAction', () => {
		const trashedProduct = {
			...product,
			status: 'trash',
		} as ProductEntityRecord;

		it( 'is eligible only for trashed products', () => {
			const action = restoreAction();

			expect( action.isEligible?.( trashedProduct ) ).toBe( true );
			expect( action.isEligible?.( product ) ).toBe( false );
		} );

		it( 'restores products by saving status as draft and invalidates the query', async () => {
			saveEditedEntityRecord.mockResolvedValue( {
				id: 12,
				status: 'draft',
			} );

			await getCallbackAction( restoreAction() ).callback(
				[ trashedProduct ],
				{ onActionPerformed }
			);

			expect( editEntityRecord ).toHaveBeenCalledWith(
				'root',
				'product',
				12,
				{ status: 'draft' }
			);
			expect( saveEditedEntityRecord ).toHaveBeenCalledWith(
				'root',
				'product',
				12,
				{ throwOnError: true }
			);
			expect( invalidateResolutionForStoreSelector ).toHaveBeenCalledWith(
				'getEntityRecords'
			);
			expect( createSuccessNotice ).toHaveBeenCalledWith(
				'Product successfully restored',
				{ type: 'snackbar' }
			);
			expect( onActionPerformed ).toHaveBeenCalledWith( [
				trashedProduct,
			] );
			expect( createErrorNotice ).not.toHaveBeenCalled();
		} );

		it( 'shows an error notice when restore fails', async () => {
			saveEditedEntityRecord.mockRejectedValueOnce(
				new Error( 'Restore failed' )
			);

			await getCallbackAction( restoreAction() ).callback(
				[ trashedProduct ],
				{ onActionPerformed }
			);

			expect( createSuccessNotice ).not.toHaveBeenCalled();
			expect( createErrorNotice ).toHaveBeenCalledWith(
				'Restore failed',
				{
					type: 'snackbar',
				}
			);
			expect( onActionPerformed ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'permanentlyDeleteAction', () => {
		const trashedProduct = {
			...product,
			status: 'trash',
		} as ProductEntityRecord;

		const getModalAction = (
			action: Action< ProductEntityRecord >
		): ActionModal< ProductEntityRecord > =>
			action as ActionModal< ProductEntityRecord >;

		it( 'is eligible for trashed products and for any variation', () => {
			const action = permanentlyDeleteAction();
			const variation = {
				...product,
				type: 'variation',
			} as ProductEntityRecord;

			expect( action.isEligible?.( trashedProduct ) ).toBe( true );
			expect( action.isEligible?.( variation ) ).toBe( true );
			expect( action.isEligible?.( product ) ).toBe( false );
		} );

		it( 'uses a singular modal header for a single product', () => {
			const action = getModalAction( permanentlyDeleteAction() );
			const header = action.modalHeader;

			expect( typeof header ).toBe( 'function' );
			expect(
				typeof header === 'function'
					? header( [ trashedProduct ] )
					: header
			).toBe( 'Delete product?' );
		} );

		it( 'uses a plural modal header for multiple products', () => {
			const action = getModalAction( permanentlyDeleteAction() );
			const header = action.modalHeader;

			expect(
				typeof header === 'function'
					? header( [ trashedProduct, trashedProduct ] )
					: header
			).toBe( 'Delete products?' );
		} );
	} );
} );
