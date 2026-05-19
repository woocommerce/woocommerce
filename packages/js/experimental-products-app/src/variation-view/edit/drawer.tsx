/**
 * External dependencies
 */
import { Button, Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { select as wpSelect, useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { Drawer } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { unlock } from '../../lock-unlock';
import {
	getProductListNavigationPath,
	getSelectionFromPostId,
} from '../../product-list/utils';
import {
	buildMergedProductEditData,
	findProductInList,
	getProductEditRecord,
	getProductEditFields,
	getProductWithUpdatedVariation,
	isProductVariation,
} from '../../product-edit/utils';
import { saveSelectedProducts } from '../../product-edit/save';
import type { ProductEntityRecord } from '../fields/types';
import { variationEditFields } from '../fields/registry';
import { VariationEditForm } from './form';

const { useHistory, useLocation } = unlock( routerPrivateApis );

type VariationEditDrawerProps = {
	products: ProductEntityRecord[];
	isOpen: boolean;
};

function getSaveNoticeMessage( successCount: number, failedCount: number ) {
	if ( failedCount === 0 ) {
		if ( successCount === 1 ) {
			return __( 'Variation saved.', 'woocommerce' );
		}

		return sprintf(
			/* translators: %d number of saved variations. */
			__( '%d variations saved.', 'woocommerce' ),
			successCount
		);
	}

	if ( successCount === 0 ) {
		if ( failedCount === 1 ) {
			return __( 'Failed to save variation.', 'woocommerce' );
		}

		return sprintf(
			/* translators: %d number of variations that could not be saved. */
			__( 'Failed to save %d variations.', 'woocommerce' ),
			failedCount
		);
	}

	return sprintf(
		/* translators: 1: successful variation count, 2: failed variation count. */
		__(
			'Saved %1$d variations. %2$d variations could not be saved.',
			'woocommerce'
		),
		successCount,
		failedCount
	);
}

export function VariationEditDrawer( {
	products,
	isOpen,
}: VariationEditDrawerProps ) {
	const { navigate } = useHistory();
	const { path, query = {} } = useLocation();
	const requestedProductIdsFromRoute = getSelectionFromPostId( query.postId )
		.map( ( postId ) => Number( postId ) )
		.filter( ( postId ) => Number.isSafeInteger( postId ) && postId > 0 );
	const requestedProductIds = Array.from(
		new Set( requestedProductIdsFromRoute )
	);

	const [ isSaving, setIsSaving ] = useState( false );

	const editableFields = getProductEditFields( variationEditFields );
	const {
		selectedProducts,
		isResolving,
		hasResolved,
		hasMissingProducts,
		hasEdits,
	} = useSelect(
		( select ) => {
			if ( requestedProductIds.length === 0 ) {
				return {
					selectedProducts: [],
					isResolving: false,
					hasResolved: true,
					hasMissingProducts: false,
					hasEdits: false,
				};
			}

			const coreSelect = select( coreStore );
			const productResults = requestedProductIds.map( ( productId ) => {
				const resolutionArgs = [ 'root', 'product', productId ];
				const rootRecord = coreSelect.getEditedEntityRecord(
					'root',
					'product',
					productId
				) as unknown as ProductEntityRecord | false | undefined;
				const rootRecordEdits = coreSelect.getEntityRecordEdits(
					'root',
					'product',
					productId
				) as Partial< ProductEntityRecord > | undefined;
				const listedProduct = findProductInList( products, productId );
				const product = getProductEditRecord(
					listedProduct,
					rootRecord,
					rootRecordEdits
				);
				let record: ProductEntityRecord | false | undefined =
					product ?? rootRecord;

				if (
					product &&
					isProductVariation( product ) &&
					product.parent_id
				) {
					const parentProduct = coreSelect.getEditedEntityRecord(
						'root',
						'product',
						product.parent_id
					) as unknown as ProductEntityRecord | false | undefined;
					const editedParentProduct =
						parentProduct !== false ? parentProduct : undefined;
					const editedVariation =
						editedParentProduct?._embedded?.variations?.find(
							( variation ) => variation.id === product.id
						);

					record = editedVariation || product;
				}

				return {
					productId,
					record,
					isResolving: listedProduct
						? false
						: coreSelect.isResolving(
								'getEditedEntityRecord',
								resolutionArgs
						  ),
					hasFinishedResolution: listedProduct
						? true
						: coreSelect.hasFinishedResolution(
								'getEditedEntityRecord',
								resolutionArgs
						  ),
				};
			} );
			const resolvedProducts = productResults
				.map( ( { record } ) => record )
				.filter(
					( product ): product is ProductEntityRecord =>
						product !== undefined && product !== false
				);
			const editedProductIds = Array.from(
				new Set(
					resolvedProducts.map( ( product ) =>
						isProductVariation( product ) && product.parent_id
							? product.parent_id
							: product.id
					)
				)
			);

			return {
				selectedProducts: resolvedProducts,
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
				hasEdits: editedProductIds.some( ( productId ) =>
					coreSelect.hasEditsForEntityRecord(
						'root',
						'product',
						productId
					)
				),
			};
		},
		[ products, requestedProductIds ]
	);

	const { clearEntityRecordEdits, editEntityRecord, saveEditedEntityRecord } =
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

	let title = __( 'Edit variation', 'woocommerce' );

	if ( isReady ) {
		if ( selectedProducts.length === 1 ) {
			title = selectedProducts[ 0 ]?.name || title;
		} else {
			title = sprintf(
				/* translators: %d number of selected variations. */
				__( 'Edit %d variations', 'woocommerce' ),
				selectedProducts.length
			);
		}
	}

	const onChange = useCallback(
		( changes: Partial< ProductEntityRecord > ) => {
			const updatedParentProductsById = new Map<
				number,
				ProductEntityRecord
			>();

			selectedProducts.forEach( ( product ) => {
				if ( ! product.parent_id ) {
					return;
				}

				const parentProduct =
					updatedParentProductsById.get( product.parent_id ) ??
					( wpSelect( coreStore ).getEditedEntityRecord(
						'root',
						'product',
						product.parent_id
					) as ProductEntityRecord | false | undefined );

				if ( ! parentProduct ) {
					return;
				}

				updatedParentProductsById.set(
					product.parent_id,
					getProductWithUpdatedVariation( parentProduct, {
						...product,
						...changes,
					} )
				);
			} );

			updatedParentProductsById.forEach( ( parentProduct ) => {
				editEntityRecord( 'root', 'product', parentProduct.id, {
					_embedded: parentProduct._embedded,
				} );
			} );
		},
		[ editEntityRecord, selectedProducts ]
	);

	const closeDrawer = useCallback( () => {
		const editedProductIds = new Set(
			selectedProducts.map( ( product ) =>
				isProductVariation( product ) && product.parent_id
					? product.parent_id
					: product.id
			)
		);
		const nextQuery = {
			...query,
		} as Record< string, string >;

		editedProductIds.forEach( ( productId ) => {
			clearEntityRecordEdits( 'root', 'product', productId );
		} );

		delete nextQuery.quickEdit;

		navigate( getProductListNavigationPath( path, nextQuery ) );
	}, [ clearEntityRecordEdits, navigate, path, query, selectedProducts ] );

	const onSave = useCallback( async () => {
		if ( selectedProducts.length === 0 || isSaving ) {
			return;
		}

		setIsSaving( true );

		try {
			const results = await saveSelectedProducts( {
				selectedProducts,
				editEntityRecord,
				saveEditedEntityRecord,
			} );

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

			closeDrawer();
		} finally {
			setIsSaving( false );
		}
	}, [
		closeDrawer,
		createErrorNotice,
		createSuccessNotice,
		editEntityRecord,
		isSaving,
		saveEditedEntityRecord,
		selectedProducts,
	] );

	return (
		<Drawer.Root open={ isOpen } swipeDirection="right">
			<Drawer.Popup
				className="woocommerce-variation-edit__drawer"
				portal={
					<Drawer.Portal className="woocommerce-variation-edit__drawer-portal" />
				}
				style={ { width: 450 } }
			>
				<Drawer.Header className="woocommerce-variation-edit__header">
					<Drawer.Title className="woocommerce-variation-edit__title">
						{ title }
					</Drawer.Title>
					<Drawer.CloseIcon
						onClick={ closeDrawer }
						label={ __( 'Close', 'woocommerce' ) }
					/>
				</Drawer.Header>

				<Drawer.Content className="woocommerce-variation-edit">
					{ hasNoRequestedProducts && (
						<div className="woocommerce-variation-edit__empty-state">
							<p>
								{ __(
									'Select one or more variations to edit them here.',
									'woocommerce'
								) }
							</p>
						</div>
					) }

					{ ! hasNoRequestedProducts && isResolving && (
						<div className="woocommerce-variation-edit__loading">
							<Spinner />
						</div>
					) }

					{ ! hasNoRequestedProducts &&
						! isResolving &&
						hasMissingProducts && (
							<div className="woocommerce-variation-edit__empty-state">
								<p>
									{ __(
										'Select one or more variations to edit them here.',
										'woocommerce'
									) }
								</p>
							</div>
						) }

					{ isReady && (
						<VariationEditForm
							editableFields={ editableFields }
							onChange={ onChange }
							selectedVariations={ selectedProducts }
						/>
					) }
				</Drawer.Content>

				{ isReady && (
					<Drawer.Footer className="woocommerce-variation-edit__footer">
						<Button
							variant="tertiary"
							onClick={ closeDrawer }
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
					</Drawer.Footer>
				) }
			</Drawer.Popup>
		</Drawer.Root>
	);
}
