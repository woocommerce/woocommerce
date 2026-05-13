/**
 * External dependencies
 */
import { Button, Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { select as wpSelect, useDispatch, useSelect } from '@wordpress/data';
import { DataForm } from '@wordpress/dataviews';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { Drawer } from '@wordpress/ui';

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
	findProductInList,
	getProductEditRecord,
	getProductWithUpdatedVariation,
	getProductEditFields,
	getVisibleProductEditFields,
	isProductVariation,
} from './utils';
import { saveSelectedProducts } from './save';

const { useHistory, useLocation } = unlock( routerPrivateApis );

type ProductEditFormProps = {
	editableFields: ReturnType< typeof getProductEditFields >;
	onChange: ( changes: Partial< ProductEntityRecord > ) => void;
	selectedProducts: ProductEntityRecord[];
};

type ProductEditProps = {
	products: ProductEntityRecord[];
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
	onChange,
	selectedProducts,
}: ProductEditFormProps ) {
	const mergedData = buildMergedProductEditData( selectedProducts );
	const visibleFields = getVisibleProductEditFields(
		editableFields,
		selectedProducts
	);

	const form = {
		type: 'regular' as const,
		labelPosition: 'top' as const,
		fields: visibleFields.map( ( field ) => field.id ),
	};

	return (
		<div className="woocommerce-product-edit__form">
			<DataForm
				data={ mergedData }
				fields={ visibleFields }
				form={ form }
				onChange={ onChange }
			/>
		</div>
	);
}

export default function ProductEdit( { products }: ProductEditProps ) {
	const { navigate } = useHistory();
	const { path, query = {} } = useLocation();
	const requestedProductIdsFromRoute = getSelectionFromPostId( query.postId )
		.map( ( postId ) => Number( postId ) )
		.filter( ( postId ) => Number.isSafeInteger( postId ) && postId > 0 );
	const requestedProductIds = Array.from(
		new Set( requestedProductIdsFromRoute )
	);

	const [ isSaving, setIsSaving ] = useState( false );
	const [ isDrawerOpen, setIsDrawerOpen ] = useState( false );
	const editableFields = getProductEditFields( productFields );
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
			const updatedParentProductsById = new Map<
				number,
				ProductEntityRecord
			>();

			selectedProducts.forEach( ( product ) => {
				if ( ! isProductVariation( product ) ) {
					editEntityRecord( 'root', 'product', product.id, changes );
					return;
				}

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
		} finally {
			setIsSaving( false );
		}
	}, [
		createErrorNotice,
		createSuccessNotice,
		editEntityRecord,
		isSaving,
		saveEditedEntityRecord,
		selectedProducts,
	] );

	useEffect( () => {
		if ( requestedProductIds.length > 0 && ! isDrawerOpen ) {
			setIsDrawerOpen( true );
		}
	}, [ requestedProductIds, isDrawerOpen ] );

	return (
		<Drawer.Root
			open={ isDrawerOpen }
			onOpenChangeComplete={ ( isOpen ) => {
				if ( ! isOpen ) {
					closeDrawer();
				}
			} }
			swipeDirection="right"
		>
			<Drawer.Popup
				className="woocommerce-product-edit__drawer"
				portal={
					<Drawer.Portal className="woocommerce-product-edit__drawer-portal" />
				}
				style={ { width: 450 } }
			>
				<Drawer.Header className="woocommerce-product-edit__header">
					<Drawer.Title className="woocommerce-product-edit__title">
						{ title }
					</Drawer.Title>
					<Drawer.CloseIcon
						onClick={ closeDrawer }
						label={ __( 'Close quick edit', 'woocommerce' ) }
					/>
				</Drawer.Header>

				<Drawer.Content className="woocommerce-product-edit">
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
							onChange={ onChange }
							selectedProducts={ selectedProducts }
						/>
					) }
				</Drawer.Content>

				{ isReady && (
					<Drawer.Footer className="woocommerce-product-edit__footer">
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
