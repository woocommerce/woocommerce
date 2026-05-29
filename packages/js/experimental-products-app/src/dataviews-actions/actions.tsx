/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	__experimentalHStack as HStack,
	__experimentalText as Text,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { dispatch } from '@wordpress/data';
import { backup, edit, trash } from '@wordpress/icons';
import { __, _n, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { addQueryArgs } from '@wordpress/url';
import { getAdminLink } from '@woocommerce/settings';
import type { Action } from '@wordpress/dataviews';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';
import { unlock } from '../lock-unlock';
import {
	getProductEditPostId,
	getProductListNavigationPath,
} from '../product-list/utils';

const { useHistory, useLocation } = unlock( routerPrivateApis );

type EditActionOptions = {
	navigate: ( path: string ) => void;
	path?: string;
	query?: Record< string, string | undefined >;
};

function getQuickEditPath(
	path: string,
	query: Record< string, string | undefined >,
	productIds: number[]
) {
	const nextQuery = Object.entries( query ).reduce(
		( acc, [ key, value ] ) => {
			if ( typeof value === 'string' ) {
				acc[ key ] = value;
			}

			return acc;
		},
		{} as Record< string, string >
	);

	return getProductListNavigationPath( path, {
		...nextQuery,
		postId: productIds.join( ',' ),
		quickEdit: 'true',
	} );
}

function getSelectionPath(
	path: string,
	query: Record< string, string | undefined >,
	productIds: number[]
) {
	const nextQuery = Object.entries( query ).reduce(
		( acc, [ key, value ] ) => {
			if ( typeof value === 'string' ) {
				acc[ key ] = value;
			}

			return acc;
		},
		{} as Record< string, string >
	);

	return getProductListNavigationPath( path, {
		...nextQuery,
		postId: productIds.join( ',' ),
		quickEdit: undefined,
	} );
}

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

export const quickEditAction = ( {
	navigate,
	path = '/',
	query = {},
}: EditActionOptions ): Action< ProductEntityRecord > => ( {
	id: 'quick-edit-product',
	label: ( items ) =>
		items.length > 1
			? __( 'Bulk editing', 'woocommerce' )
			: __( 'Quick edit', 'woocommerce' ),
	isPrimary: true,
	supportsBulk: true,
	icon: edit,
	isEligible( product ) {
		return product.status !== 'trash';
	},
	callback( items, { onActionPerformed } ) {
		const productIds = items.map( ( product ) => product.id );

		if ( productIds.length > 0 ) {
			navigate( getQuickEditPath( path, query, productIds ) );
		}

		if ( onActionPerformed ) {
			onActionPerformed( items );
		}
	},
} );

export const editAction = (): Action< ProductEntityRecord > => ( {
	id: 'edit-product',
	label: __( 'Edit', 'woocommerce' ),
	isPrimary: true,
	isEligible( product ) {
		return product.status !== 'trash';
	},
	callback( items, { onActionPerformed } ) {
		const product = items[ 0 ];

		if ( product ) {
			window.location.href = getAdminLink(
				addQueryArgs( 'post.php', {
					post: getProductEditPostId( product ),
					action: 'edit',
				} )
			);
		}

		if ( onActionPerformed ) {
			onActionPerformed( items );
		}
	},
} );

export const selectAllVariationsAction = ( {
	navigate,
	path = '/',
	query = {},
}: EditActionOptions ): Action< ProductEntityRecord > => ( {
	id: 'select-all-variations',
	label: __( 'Select all variations', 'woocommerce' ),
	isPrimary: true,
	isEligible( product ) {
		return (
			product.status !== 'trash' &&
			product.type === 'variable' &&
			Boolean( product._embedded?.variations?.length )
		);
	},
	callback( items, { onActionPerformed } ) {
		const variations = items.flatMap(
			( product ) => product._embedded?.variations ?? []
		);
		const variationIds = Array.from(
			new Set( variations.map( ( variation ) => variation.id ) )
		);

		if ( variationIds.length > 0 ) {
			navigate( getSelectionPath( path, query, variationIds ) );
		}

		if ( onActionPerformed ) {
			onActionPerformed( variations );
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
			!! item &&
			item.status !== 'trash' &&
			item.status !== 'auto-draft' &&
			item.type !== 'variation'
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

export const moveToTrashAction = (): Action< ProductEntityRecord > => ( {
	id: 'move-to-trash-product',
	label: __( 'Move to trash', 'woocommerce' ),
	supportsBulk: true,
	icon: trash,
	isEligible( product ) {
		// Variations skip the trash and go straight to permanent delete
		// (see `permanentlyDeleteAction`), since the variations REST endpoint
		// doesn't support a soft-trash state.
		return product.status !== 'trash' && product.type !== 'variation';
	},
	async callback( items, { onActionPerformed } ) {
		const { deleteEntityRecord } = dispatch( coreStore );
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

export const restoreAction = (): Action< ProductEntityRecord > => ( {
	id: 'restore-product',
	label: __( 'Restore', 'woocommerce' ),
	supportsBulk: true,
	icon: backup,
	isEligible( product ) {
		return product.status === 'trash';
	},
	async callback( items, { onActionPerformed } ) {
		const {
			editEntityRecord,
			saveEditedEntityRecord,
			invalidateResolutionForStoreSelector,
		} = dispatch( coreStore );
		const { createErrorNotice, createSuccessNotice } =
			dispatch( noticesStore );

		const results = await Promise.allSettled(
			items.map( async ( product ) => {
				await editEntityRecord( 'root', 'product', product.id, {
					status: 'draft',
				} );
				return saveEditedEntityRecord( 'root', 'product', product.id, {
					throwOnError: true,
				} );
			} )
		);
		const successfulItems = getSuccessfulItems( items, results );
		const failedResults = results.filter(
			( result ) => result.status === 'rejected'
		);

		if ( successfulItems.length > 0 ) {
			await invalidateResolutionForStoreSelector( 'getEntityRecords' );
			createSuccessNotice(
				successfulItems.length === 1
					? __( 'Product successfully restored', 'woocommerce' )
					: sprintf(
							/* translators: %s: number of products. */
							_n(
								'%s product successfully restored',
								'%s products successfully restored',
								successfulItems.length,
								'woocommerce'
							),
							successfulItems.length
					  ),
				{ type: 'snackbar' }
			);
			onActionPerformed?.( successfulItems );
		}

		if ( failedResults.length > 0 ) {
			createErrorNotice(
				getErrorMessage(
					( failedResults[ 0 ] as PromiseRejectedResult ).reason
				),
				{ type: 'snackbar' }
			);
		}
	},
} );

export const permanentlyDeleteAction = (): Action< ProductEntityRecord > => ( {
	id: 'permanently-delete-product',
	label: __( 'Permanently delete', 'woocommerce' ),
	supportsBulk: true,
	icon: trash,
	isEligible( product ) {
		// Variations are deleted directly (no trash step), so show this
		// action for them regardless of status.
		return product.status === 'trash' || product.type === 'variation';
	},
	modalHeader: ( items ) =>
		items.length === 1
			? __( 'Delete product?', 'woocommerce' )
			: __( 'Delete products?', 'woocommerce' ),
	RenderModal: ( { items, closeModal, onActionPerformed } ) => {
		const onConfirm = async () => {
			const { deleteEntityRecord, invalidateResolutionForStoreSelector } =
				dispatch( coreStore );
			const { createErrorNotice, createSuccessNotice } =
				dispatch( noticesStore );

			const results = await Promise.allSettled(
				items.map( ( product ) =>
					deleteEntityRecord( 'root', 'product', product.id, {
						force: true,
						throwOnError: true,
					} )
				)
			);
			const successfulItems = getSuccessfulItems( items, results );
			const failedResults = results.filter(
				( result ) => result.status === 'rejected'
			);

			if ( successfulItems.length > 0 ) {
				await invalidateResolutionForStoreSelector(
					'getEntityRecords'
				);
				createSuccessNotice(
					successfulItems.length === 1
						? __( 'Product permanently deleted', 'woocommerce' )
						: sprintf(
								/* translators: %s: number of products. */
								_n(
									'%s product permanently deleted',
									'%s products permanently deleted',
									successfulItems.length,
									'woocommerce'
								),
								successfulItems.length
						  ),
					{ type: 'snackbar' }
				);
				onActionPerformed?.( successfulItems );
			}

			if ( failedResults.length > 0 ) {
				createErrorNotice(
					getErrorMessage(
						( failedResults[ 0 ] as PromiseRejectedResult ).reason
					),
					{ type: 'snackbar' }
				);
			}

			closeModal?.();
		};

		return (
			<VStack spacing="5">
				<Text>
					{ items.length === 1
						? sprintf(
								/* translators: %s: The product's name. */
								__(
									"%s will be permanently deleted and can't be restored.",
									'woocommerce'
								),
								items[ 0 ]?.name ?? ''
						  )
						: sprintf(
								/* translators: %s: number of products. */
								_n(
									"%s product will be permanently deleted and can't be restored.",
									"%s products will be permanently deleted and can't be restored.",
									items.length,
									'woocommerce'
								),
								items.length
						  ) }
				</Text>
				<HStack justify="flex-end">
					<Button
						__next40pxDefaultSize
						variant="tertiary"
						onClick={ closeModal }
					>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button
						__next40pxDefaultSize
						variant="primary"
						isDestructive
						onClick={ onConfirm }
					>
						{ __( 'Delete permanently', 'woocommerce' ) }
					</Button>
				</HStack>
			</VStack>
		);
	},
} );

export const useProductActions = () => {
	const { navigate } = useHistory();
	const { path, query = {} } = useLocation();

	return useMemo(
		() => [
			quickEditAction( {
				navigate,
				path,
				query,
			} ),
			editAction(),
			selectAllVariationsAction( {
				navigate,
				path,
				query,
			} ),
			duplicateProductAction(),
			moveToTrashAction(),
			restoreAction(),
			permanentlyDeleteAction(),
		],
		[ navigate, path, query ]
	);
};
