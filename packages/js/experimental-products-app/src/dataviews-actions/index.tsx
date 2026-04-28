/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { store as coreStore } from '@wordpress/core-data';
import { dispatch } from '@wordpress/data';
import { edit, external, trash } from '@wordpress/icons';
import { __, _n, _x, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { addQueryArgs } from '@wordpress/url';
import { getAdminLink } from '@woocommerce/settings';
import type { Action } from '@wordpress/dataviews';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';
import type { ProductListQuery } from '../product-list/query';

type ProductActionsOptions = {
	query: ProductListQuery;
};

type ProductActionDependencies = {
	query: ProductListQuery;
};

function getErrorMessage( error: unknown ): string {
	if ( error instanceof Error ) {
		return error.message;
	}

	if ( typeof error === 'object' && error !== null && 'message' in error ) {
		const errorWithMessage = error as Record< string, unknown >;

		if ( typeof errorWithMessage.message === 'string' ) {
			return errorWithMessage.message;
		}
	}

	return __(
		'An error occurred while performing the action.',
		'woocommerce'
	);
}

function getSuccessfulItems(
	items: ProductEntityRecord[],
	results: PromiseSettledResult< unknown >[]
) {
	return items.filter(
		( _, index ) => results[ index ]?.status === 'fulfilled'
	);
}

type SettledNotice = {
	kind: 'success' | 'error';
	message: string;
};

function getNoticeFromSettledResults( {
	results,
	successMessage,
	errorMessage,
}: {
	results: PromiseSettledResult< ProductEntityRecord >[];
	successMessage: ( count: number ) => string;
	errorMessage: ( count: number ) => string;
} ): SettledNotice {
	const successfulCount = results.filter(
		( result ) => result.status === 'fulfilled'
	).length;
	const failedCount = results.length - successfulCount;

	if ( failedCount === 0 ) {
		return {
			kind: 'success',
			message: successMessage( successfulCount ),
		};
	}

	if ( successfulCount === 0 ) {
		return {
			kind: 'error',
			message: errorMessage( failedCount ),
		};
	}

	return {
		kind: 'success',
		message: successMessage( successfulCount ),
	};
}

export const editAction = (): Action< ProductEntityRecord > => ( {
	id: 'edit-product',
	label: __( 'Edit', 'woocommerce' ),
	isPrimary: true,
	icon: edit,
	isEligible( product ) {
		return product.status !== 'trash';
	},
	callback( items, { onActionPerformed } ) {
		const product = items[ 0 ];

		if ( product ) {
			window.location.href = getAdminLink(
				addQueryArgs( 'post.php', {
					post: product.id,
					action: 'edit',
				} )
			);
		}

		if ( onActionPerformed ) {
			onActionPerformed( items );
		}
	},
} );

export const viewAction = (): Action< ProductEntityRecord > => ( {
	id: 'view-product',
	label: _x( 'View', 'verb', 'woocommerce' ),
	isPrimary: true,
	icon: external,
	isEligible( product ) {
		return product.status !== 'trash' && !! product.permalink;
	},
	callback( items, { onActionPerformed } ) {
		const product = items[ 0 ];

		if ( product?.permalink ) {
			window.open( product.permalink, '_blank' );
		}

		if ( onActionPerformed ) {
			onActionPerformed( items );
		}
	},
} );

const duplicateProducts = async ( items: ProductEntityRecord[] ) => {
	return Promise.allSettled(
		items.map( ( item ) =>
			apiFetch< ProductEntityRecord >( {
				path: `/wc/v3/products/${ item.id }/duplicate`,
				method: 'POST',
			} )
		)
	);
};

const duplicateProduct = async ( items: ProductEntityRecord[] ) => {
	if ( items.length === 0 ) {
		return;
	}

	const promiseResult = await duplicateProducts( items );
	const failedItems = items.filter(
		( _, index ) => promiseResult[ index ].status === 'rejected'
	);
	const { createSuccessNotice, createErrorNotice } = dispatch( noticesStore );
	const notice = getNoticeFromSettledResults( {
		results: promiseResult,
		successMessage: ( count ) =>
			sprintf(
				/* translators: %1$s: The product's name. %2$d: The number of products. */
				_n(
					'"%1$s" duplicated successfully.',
					'%2$d products duplicated successfully.',
					count,
					'woocommerce'
				),
				items[ 0 ]?.name || '',
				count
			),
		errorMessage: ( count ) =>
			sprintf(
				/* translators: %1$s: The product's name. %2$d: The number of products. */
				_n(
					'Failed to duplicate "%1$s".',
					'Failed to duplicate %2$d products.',
					count,
					'woocommerce'
				),
				failedItems[ 0 ]?.name || '',
				count
			),
	} );

	if ( notice.kind === 'success' ) {
		const coreDispatch = dispatch( coreStore );

		await coreDispatch.invalidateResolutionForStoreSelector(
			'getEntityRecords'
		);

		const noticeOptions: Record< string, unknown > = {
			type: 'snackbar',
			id: 'duplicate-product-action',
		};

		if (
			promiseResult.length === 1 &&
			promiseResult[ 0 ]?.status === 'fulfilled'
		) {
			const newProduct = promiseResult[ 0 ].value;
			noticeOptions.actions = [
				{
					label: __( 'View product', 'woocommerce' ),
					onClick: () => {
						window.location.href = getAdminLink(
							addQueryArgs( 'post.php', {
								post: newProduct.id,
								action: 'edit',
							} )
						);
					},
				},
			];
		}

		void createSuccessNotice( notice.message, noticeOptions );
		return promiseResult;
	}

	void createErrorNotice( notice.message, {
		type: 'snackbar',
		id: 'duplicate-product-error',
	} );
};

export const duplicateProductAction = (): Action< ProductEntityRecord > => ( {
	id: 'duplicate-product',
	label: __( 'Duplicate', 'woocommerce' ),
	isPrimary: false,
	supportsBulk: true,
	isEligible( item ) {
		return (
			!! item && item.status !== 'trash' && item.status !== 'auto-draft'
		);
	},
	async callback( items, { onActionPerformed } ) {
		const newProducts = await duplicateProduct( items );
		const fulfilledResults =
			newProducts
				?.filter(
					(
						promiseResult
					): promiseResult is PromiseFulfilledResult< ProductEntityRecord > =>
						promiseResult.status === 'fulfilled'
				)
				.map( ( promiseResult ) => promiseResult.value ) || [];

		if ( onActionPerformed && fulfilledResults.length > 0 ) {
			onActionPerformed( fulfilledResults );
		}
	},
} );

export const moveToTrashAction = (
	dependencies: ProductActionDependencies
): Action< ProductEntityRecord > => ( {
	id: 'move-to-trash-product',
	label: __( 'Move to trash', 'woocommerce' ),
	supportsBulk: true,
	icon: trash,
	isEligible( product ) {
		return product.status !== 'trash';
	},
	async callback( items, { onActionPerformed } ) {
		const { deleteEntityRecord, invalidateResolution } =
			dispatch( coreStore );
		const { createErrorNotice, createSuccessNotice } =
			dispatch( noticesStore );

		const results = await Promise.allSettled(
			items.map( ( product ) =>
				deleteEntityRecord( 'root', 'product', product.id, {
					force: false,
					throwOnError: true,
				} )
			)
		);
		const successfulItems = getSuccessfulItems( items, results );
		const failedResults = results.filter(
			( result ) => result.status === 'rejected'
		);

		if ( successfulItems.length > 0 ) {
			await invalidateResolution( 'getEntityRecords', [
				'root',
				'product',
				dependencies.query,
			] );
			createSuccessNotice(
				successfulItems.length === 1
					? __( 'Product successfully deleted', 'woocommerce' )
					: sprintf(
							/* translators: %s: number of products. */
							_n(
								'%s product successfully deleted',
								'%s products successfully deleted',
								successfulItems.length,
								'woocommerce'
							),
							successfulItems.length
					  ),
				{
					type: 'snackbar',
				}
			);
			onActionPerformed?.( successfulItems );
		}

		if ( failedResults.length > 0 ) {
			createErrorNotice(
				getErrorMessage(
					( failedResults[ 0 ] as PromiseRejectedResult ).reason
				),
				{
					type: 'snackbar',
				}
			);
		}
	},
} );

export const useProductActions = ( { query }: ProductActionsOptions ) => {
	return useMemo(
		() => [
			editAction(),
			viewAction(),
			duplicateProductAction(),
			moveToTrashAction( { query } ),
		],
		[ query ]
	);
};
