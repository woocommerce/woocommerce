/**
 * External dependencies
 */
import { Button, Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { DataForm } from '@wordpress/dataviews';
import { useCallback, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';
import { store as noticesStore } from '@wordpress/notices';
import { privateApis as routerPrivateApis } from '@wordpress/router';

/**
 * Internal dependencies
 */
import { productFields } from '../product-list/fields';
import {
	getProductListNavigationPath,
	getSelectionFromPostId,
} from '../product-list/utils';
import type { ProductEntityRecord } from '../fields/types';
import { unlock } from '../lock-unlock';
import {
	buildMergedProductEditData,
	getProductEditFields,
	getVisibleProductEditFields,
} from './utils';

const { useHistory, useLocation } = unlock( routerPrivateApis );

type ProductEditFormProps = {
	editableFields: ReturnType< typeof getProductEditFields >;
	hasEdits: boolean;
	isSaving: boolean;
	onChange: ( changes: Partial< ProductEntityRecord > ) => void;
	onClose: () => void;
	onSave: () => void;
	selectedProducts: ProductEntityRecord[];
};

function getSaveNoticeMessage( successCount: number, failedCount: number ) {
	if ( failedCount === 0 ) {
		if ( successCount === 1 ) {
			return __( 'Product saved.', 'woocommerce' );
		}

		return sprintf(
			/* translators: %d number of saved products. */
			__( '%d products saved.', 'woocommerce' ),
			successCount
		);
	}

	if ( successCount === 0 ) {
		if ( failedCount === 1 ) {
			return __( 'Failed to save product.', 'woocommerce' );
		}

		return sprintf(
			/* translators: %d number of products that could not be saved. */
			__( 'Failed to save %d products.', 'woocommerce' ),
			failedCount
		);
	}

	return sprintf(
		/* translators: 1: successful products count, 2: failed products count. */
		__(
			'Saved %1$d products. %2$d products could not be saved.',
			'woocommerce'
		),
		successCount,
		failedCount
	);
}

function ProductEditForm( {
	editableFields,
	hasEdits,
	isSaving,
	onChange,
	onClose,
	onSave,
	selectedProducts,
}: ProductEditFormProps ) {
	const mergedData = buildMergedProductEditData( selectedProducts );
	const visibleFields = getVisibleProductEditFields(
		editableFields,
		mergedData
	);

	const form = {
		type: 'regular' as const,
		labelPosition: 'top' as const,
		fields: visibleFields.map( ( field ) => field.id ),
	};

	return (
		<>
			<div className="woocommerce-product-edit__form">
				<DataForm
					data={ mergedData }
					fields={ visibleFields }
					form={ form }
					onChange={ onChange }
				/>
			</div>
			<div className="woocommerce-product-edit__footer">
				<Button
					variant="tertiary"
					onClick={ onClose }
					disabled={ isSaving }
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ onSave }
					isBusy={ isSaving }
					disabled={ isSaving || ! hasEdits }
				>
					{ __( 'Save', 'woocommerce' ) }
				</Button>
			</div>
		</>
	);
}

export default function ProductEdit() {
	const { navigate } = useHistory();
	const { path, query = {} } = useLocation();
	const requestedProductIdsFromRoute = getSelectionFromPostId( query.postId )
		.map( ( postId ) => Number( postId ) )
		.filter( ( postId ) => Number.isSafeInteger( postId ) && postId > 0 );
	const requestedProductIds = Array.from(
		new Set( requestedProductIdsFromRoute )
	);

	const [ isSaving, setIsSaving ] = useState( false );
	const editableFields = getProductEditFields( productFields );
	const {
		selectedProducts,
		selectedProductIds,
		isResolving,
		hasResolved,
		hasMissingProducts,
		hasEdits,
	} = useSelect(
		( select ) => {
			if ( requestedProductIds.length === 0 ) {
				return {
					selectedProducts: [],
					selectedProductIds: [],
					isResolving: false,
					hasResolved: true,
					hasMissingProducts: false,
					hasEdits: false,
				};
			}

			const coreSelect = select( coreStore );
			const productResults = requestedProductIds.map( ( productId ) => {
				const resolutionArgs = [ 'root', 'product', productId ];

				return {
					productId,
					record: coreSelect.getEditedEntityRecord(
						'root',
						'product',
						productId
					) as unknown as ProductEntityRecord | false | undefined,
					isResolving: coreSelect.isResolving(
						'getEditedEntityRecord',
						resolutionArgs
					),
					hasFinishedResolution: coreSelect.hasFinishedResolution(
						'getEditedEntityRecord',
						resolutionArgs
					),
				};
			} );
			const products = productResults
				.map( ( { record } ) => record )
				.filter(
					( product ): product is ProductEntityRecord =>
						product !== undefined && product !== false
				);
			const validSelectedProductIds = products.map(
				( product ) => product.id
			);

			return {
				selectedProducts: products,
				selectedProductIds: validSelectedProductIds,
				isResolving: productResults.some(
					( result ) =>
						result.isResolving || ! result.hasFinishedResolution
				),
				hasResolved: productResults.every(
					( result ) => result.hasFinishedResolution
				),
				hasMissingProducts: productResults.some(
					( result ) =>
						result.hasFinishedResolution && result.record === false
				),
				hasEdits: validSelectedProductIds.some( ( productId ) =>
					coreSelect.hasEditsForEntityRecord(
						'root',
						'product',
						productId
					)
				),
			};
		},
		[ requestedProductIds ]
	);

	const { editEntityRecord, saveEditedEntityRecord } =
		useDispatch( coreStore );

	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );

	const hasNoRequestedProducts = requestedProductIds.length === 0;
	const isReady =
		hasResolved &&
		! isResolving &&
		! hasMissingProducts &&
		selectedProducts.length === requestedProductIds.length &&
		selectedProducts.length > 0;

	let title = __( 'Quick edit', 'woocommerce' );

	if ( isReady ) {
		if ( selectedProducts.length === 1 ) {
			title = selectedProducts[ 0 ]?.name || title;
		} else {
			title = sprintf(
				/* translators: %d number of selected products. */
				__( 'Edit %d products', 'woocommerce' ),
				selectedProducts.length
			);
		}
	}

	const onChange = useCallback(
		( changes: Partial< ProductEntityRecord > ) => {
			selectedProductIds.forEach( ( productId ) => {
				editEntityRecord( 'root', 'product', productId, changes );
			} );
		},
		[ editEntityRecord, selectedProductIds ]
	);

	const onClose = useCallback( () => {
		const nextQuery = {
			...query,
		} as Record< string, string >;

		delete nextQuery.quickEdit;

		navigate( getProductListNavigationPath( path, nextQuery ) );
	}, [ navigate, path, query ] );

	const onSave = useCallback( async () => {
		if ( selectedProductIds.length === 0 || isSaving ) {
			return;
		}

		setIsSaving( true );

		try {
			const results = await Promise.allSettled(
				selectedProductIds.map( ( productId ) =>
					saveEditedEntityRecord( 'root', 'product', productId, {
						throwOnError: true,
					} )
				)
			);
			const successfulCount = results.filter(
				( result ) => result.status === 'fulfilled'
			).length;
			const failedCount = results.length - successfulCount;
			const message = getSaveNoticeMessage(
				successfulCount,
				failedCount
			);

			if ( failedCount > 0 ) {
				createErrorNotice( message, {
					type: 'snackbar',
				} );
				return;
			}

			if ( successfulCount > 0 ) {
				createSuccessNotice( message, {
					type: 'snackbar',
				} );
			}
		} finally {
			setIsSaving( false );
		}
	}, [
		createErrorNotice,
		createSuccessNotice,
		isSaving,
		saveEditedEntityRecord,
		selectedProductIds,
	] );

	return (
		<div className="woocommerce-product-edit">
			<div className="woocommerce-product-edit__header">
				<h2 className="woocommerce-product-edit__title">{ title }</h2>
				<Button
					className="woocommerce-product-edit__close"
					icon={ closeSmall }
					label={ __( 'Close quick edit', 'woocommerce' ) }
					onClick={ onClose }
				/>
			</div>

			{ hasNoRequestedProducts && (
				<div className="woocommerce-product-edit__empty-state">
					<p>
						{ __(
							'Select one or more products to edit them here.',
							'woocommerce'
						) }
					</p>
				</div>
			) }

			{ ! hasNoRequestedProducts && isResolving && (
				<div className="woocommerce-product-edit__loading">
					<Spinner />
				</div>
			) }

			{ ! hasNoRequestedProducts &&
				! isResolving &&
				hasMissingProducts && (
					<div className="woocommerce-product-edit__empty-state">
						<p>
							{ __(
								'Select one or more products to edit them here.',
								'woocommerce'
							) }
						</p>
					</div>
				) }

			{ isReady && (
				<ProductEditForm
					editableFields={ editableFields }
					hasEdits={ hasEdits }
					isSaving={ isSaving }
					onChange={ onChange }
					onClose={ onClose }
					onSave={ onSave }
					selectedProducts={ selectedProducts }
				/>
			) }
		</div>
	);
}
