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
	getMixedProductEditFieldIds,
	getProductEditFields,
	getVisibleProductEditFields,
} from './utils';

const { useHistory, useLocation } = unlock( routerPrivateApis );

type ProductEditProps = {
	productIds?: number[];
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

export default function ProductEdit( { productIds }: ProductEditProps ) {
	const { navigate } = useHistory();
	const { path, query = {} } = useLocation();
	const selectedProductIdsFromRoute = getSelectionFromPostId( query.postId )
		.map( ( postId ) => parseInt( postId, 10 ) )
		.filter( Number.isFinite );
	const selectedProductIds = productIds ?? selectedProductIdsFromRoute;
	const [ isSaving, setIsSaving ] = useState( false );
	const editableFields = getProductEditFields( productFields );
	const { selectedProducts, isResolving, hasResolved, hasEdits } = useSelect(
		( select ) => {
			if ( selectedProductIds.length === 0 ) {
				return {
					selectedProducts: [],
					isResolving: false,
					hasResolved: true,
					hasEdits: false,
				};
			}

			const coreSelect = select( coreStore );
			const products = selectedProductIds
				.map(
					( productId ) =>
						coreSelect.getEditedEntityRecord(
							'root',
							'product',
							productId
						) as unknown as ProductEntityRecord | undefined
				)
				.filter(
					( product ): product is ProductEntityRecord =>
						product !== undefined
				);

			return {
				selectedProducts: products,
				isResolving: selectedProductIds.some( ( productId ) =>
					coreSelect.isResolving( 'getEditedEntityRecord', [
						'root',
						'product',
						productId,
					] )
				),
				hasResolved: selectedProductIds.every( ( productId ) =>
					coreSelect.hasFinishedResolution( 'getEditedEntityRecord', [
						'root',
						'product',
						productId,
					] )
				),
				hasEdits: selectedProductIds.some( ( productId ) =>
					coreSelect.hasEditsForEntityRecord(
						'root',
						'product',
						productId
					)
				),
			};
		},
		[ selectedProductIds ]
	);

	const { editEntityRecord, saveEditedEntityRecord } =
		useDispatch( coreStore );

	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );

	const isReady =
		selectedProductIds.length === 0 ||
		( hasResolved &&
			selectedProducts.length === selectedProductIds.length &&
			! isResolving );
	const mergedData = isReady
		? buildMergedProductEditData( selectedProducts )
		: ( {} as ProductEntityRecord );
	const visibleFields = getVisibleProductEditFields(
		editableFields,
		mergedData
	);
	const mixedFieldIds = getMixedProductEditFieldIds(
		visibleFields,
		selectedProducts
	);
	const form = {
		type: 'regular' as const,
		labelPosition: 'top' as const,
		fields: visibleFields.map( ( field ) => field.id ),
	};
	const title =
		selectedProducts[ 0 ]?.name || __( 'Quick edit', 'woocommerce' );

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

			if ( failedCount === 0 || successfulCount > 0 ) {
				createSuccessNotice( message, {
					type: 'snackbar',
				} );
				return;
			}

			createErrorNotice( message, {
				type: 'snackbar',
			} );
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

			{ selectedProductIds.length === 0 && (
				<div className="woocommerce-product-edit__empty-state">
					<p>
						{ __(
							'Select one or more products to edit them here.',
							'woocommerce'
						) }
					</p>
				</div>
			) }

			{ selectedProductIds.length > 0 && ! isReady && (
				<div className="woocommerce-product-edit__loading">
					<Spinner />
				</div>
			) }

			{ selectedProductIds.length > 0 && isReady && (
				<>
					{ mixedFieldIds.length > 0 && (
						<p className="woocommerce-product-edit__mixed-values">
							{ __(
								'Some selected products have different values. Those fields appear blank until you change them.',
								'woocommerce'
							) }
						</p>
					) }
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
			) }
		</div>
	);
}
