/**
 * External dependencies
 */
import { Button, CheckboxControl, Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { select as wpSelect, useDispatch, useSelect } from '@wordpress/data';
import {
	DataForm,
	type DataFormControlProps,
	type FormField,
} from '@wordpress/dataviews';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
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
	findProductInList,
	getProductEditRecord,
	getProductWithUpdatedVariation,
	getProductEditFields,
	getProductTypeFormFields,
	getVisibleProductEditFields,
	isProductVariation,
} from './utils';
import {
	buildProductBulkEditData,
	DEFAULT_BULK_NUMERIC_EDIT,
	getBulkNumericEditFromData,
	getBulkNumericEditsFromData,
	getBulkNumericChangesForProduct,
	getBulkNumericOperationFieldId,
	isBulkNumericEditPending,
	isBulkNumericEditValid,
	isBulkNumericFieldId,
	isBulkNumericOperationFieldId,
	validateBulkNumericEdits,
} from './bulk-edit';
import type {
	BulkNumericEdit,
	BulkNumericFieldId,
	ProductBulkEditFormData,
	ProductBulkEditFieldState,
} from './bulk-edit';
import { saveSelectedProducts } from './save';
import { createBulkNumericOperationField } from './bulk-numeric-control';

const { useHistory, useLocation } = unlock( routerPrivateApis );

type ProductEditFormProps = {
	bulkEditData: ProductBulkEditFormData;
	editableFields: ReturnType< typeof getProductEditFields >;
	onChange: ( changes: Partial< ProductEntityRecord > ) => void;
	selectedProducts: ProductEntityRecord[];
};

type ProductEditProps = {
	products: ProductEntityRecord[];
	isOpen: boolean;
};

type ProductEditField = ReturnType< typeof getProductEditFields >[ number ];

function BulkBooleanControl( {
	data,
	field,
	onChange,
}: DataFormControlProps< ProductEntityRecord > ) {
	return (
		<CheckboxControl
			label={ field.label }
			checked={ false }
			indeterminate
			onChange={ ( value ) => {
				onChange(
					field.setValue( {
						item: data,
						value,
					} )
				);
			} }
		/>
	);
}

function getBulkNumericPlaceholder(
	fieldState: ProductBulkEditFieldState | undefined
) {
	if ( fieldState?.placeholder ) {
		return fieldState.placeholder;
	}

	if ( ! fieldState || fieldState.isEmpty ) {
		return undefined;
	}

	return String( fieldState.value ?? '' );
}

function getCostOfGoodsSoldDataWithValue(
	data: ProductEntityRecord,
	value: string
) {
	const costOfGoodsSold = data.cost_of_goods_sold ?? {};
	const [ firstValue = {}, ...remainingValues ] =
		costOfGoodsSold.values ?? [];

	return {
		...costOfGoodsSold,
		values: [
			{
				...firstValue,
				defined_value: value,
			},
			...remainingValues,
		],
	};
}

function isDimensionChanges(
	value: unknown
): value is Partial< ProductEntityRecord[ 'dimensions' ] > {
	return (
		Boolean( value ) &&
		typeof value === 'object' &&
		! Array.isArray( value )
	);
}

function mergeProductChangesForProduct(
	product: ProductEntityRecord,
	changes: Partial< ProductEntityRecord >
): Partial< ProductEntityRecord > {
	const { dimensions, ...remainingChanges } = changes;

	if ( ! isDimensionChanges( dimensions ) ) {
		return changes;
	}

	return {
		...remainingChanges,
		dimensions: {
			...product.dimensions,
			...dimensions,
		} as ProductEntityRecord[ 'dimensions' ],
	};
}

function getBulkEditFormData(
	mergedData: ProductEntityRecord,
	bulkEditData: ProductBulkEditFormData,
	fieldStates: Record< string, ProductBulkEditFieldState >
): ProductBulkEditFormData {
	const data: ProductBulkEditFormData = {
		...mergedData,
		...bulkEditData,
	};

	if ( isDimensionChanges( bulkEditData.dimensions ) ) {
		data.dimensions = {
			...( isDimensionChanges( mergedData.dimensions )
				? mergedData.dimensions
				: {} ),
			...bulkEditData.dimensions,
		} as ProductEntityRecord[ 'dimensions' ];
	}

	Object.keys( fieldStates ).forEach( ( fieldId ) => {
		if ( fieldId === 'stock' && fieldStates[ fieldId ].isMixed ) {
			( data as Record< string, unknown > ).stock_status = '';
			return;
		}

		if ( fieldId === 'variation_active' ) {
			( data as Record< string, unknown > ).variation_active =
				fieldStates[ fieldId ].isMixed
					? undefined
					: fieldStates[ fieldId ].value;
			return;
		}

		if ( ! isBulkNumericFieldId( fieldId ) ) {
			return;
		}

		const operationFieldId = getBulkNumericOperationFieldId( fieldId );
		data[ operationFieldId ] =
			bulkEditData[ operationFieldId ] ??
			DEFAULT_BULK_NUMERIC_EDIT.operation;

		if ( fieldId === 'cost_of_goods_sold' ) {
			const editValue = getBulkNumericEditFromData(
				bulkEditData,
				fieldId
			).value;

			data.cost_of_goods_sold = getCostOfGoodsSoldDataWithValue(
				mergedData,
				editValue
			);
			return;
		}

		( data as Record< string, unknown > )[ fieldId ] =
			bulkEditData[ fieldId ] ?? DEFAULT_BULK_NUMERIC_EDIT.value;
	} );

	return data;
}

function getBulkEnhancedProductEditFields( {
	fieldStates,
	isBulkEdit,
	visibleFields,
}: {
	fieldStates: Record< string, ProductBulkEditFieldState >;
	isBulkEdit: boolean;
	visibleFields: ProductEditField[];
} ) {
	if ( ! isBulkEdit ) {
		return visibleFields;
	}

	return visibleFields
		.map( ( field ) => {
			const fieldState = fieldStates[ field.id ];
			const enhancedField = {
				...field,
				placeholder: fieldState?.placeholder ?? field.placeholder,
				...( field.id === 'name'
					? {
							isValid: {
								...field.isValid,
								required: false,
							},
					  }
					: {} ),
			};

			if ( isBulkNumericFieldId( field.id ) ) {
				const fieldId = field.id;

				return [
					createBulkNumericOperationField( enhancedField, fieldId ),
					{
						...enhancedField,
						placeholder: getBulkNumericPlaceholder( fieldState ),
						isDisabled: ( {
							item,
						}: {
							item: ProductEntityRecord;
						} ) =>
							getBulkNumericEditFromData(
								item as ProductBulkEditFormData,
								fieldId
							).operation === DEFAULT_BULK_NUMERIC_EDIT.operation,
					},
				];
			}

			if (
				fieldState?.isMixed &&
				( field.type === 'boolean' || field.Edit === 'toggle' )
			) {
				return {
					...enhancedField,
					Edit: BulkBooleanControl,
				};
			}

			return [ enhancedField ];
		} )
		.flat();
}

function injectBulkNumericOperationFormFields(
	formFields: Array< FormField | string >,
	fieldLabels: Map< string, string | undefined >
): Array< FormField | string > {
	return formFields.map( ( formField ) => {
		if ( typeof formField === 'string' ) {
			if ( ! isBulkNumericFieldId( formField ) ) {
				return formField;
			}

			return {
				id: `${ formField }-bulk-edit-fields`,
				label: fieldLabels.get( formField ),
				layout: { type: 'row' as const },
				children: [
					{
						id: getBulkNumericOperationFieldId( formField ),
						layout: {
							type: 'regular' as const,
							labelPosition: 'none' as const,
						},
					},
					{
						id: formField,
						layout: {
							type: 'regular' as const,
							labelPosition: 'none' as const,
						},
					},
				],
			};
		}

		return {
			...formField,
			children: formField.children
				? injectBulkNumericOperationFormFields(
						formField.children as Array< FormField | string >,
						fieldLabels
				  )
				: formField.children,
		};
	} );
}

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
	bulkEditData,
	editableFields,
	onChange,
	selectedProducts,
}: ProductEditFormProps ) {
	const visibleFields = getVisibleProductEditFields(
		editableFields,
		selectedProducts
	);
	const { data: mergedData, fieldStates } = buildProductBulkEditData(
		selectedProducts,
		visibleFields
	);
	const enhancedFields = getBulkEnhancedProductEditFields( {
		fieldStates,
		isBulkEdit: selectedProducts.length > 1,
		visibleFields,
	} );
	const formFields = getProductTypeFormFields(
		selectedProducts,
		enhancedFields
	);
	const fieldLabels = new Map(
		visibleFields.map( ( field ) => [ field.id, field.label ] )
	);
	const data =
		selectedProducts.length > 1
			? getBulkEditFormData( mergedData, bulkEditData, fieldStates )
			: mergedData;

	const form = {
		type: 'regular' as const,
		labelPosition: 'top' as const,
		fields:
			selectedProducts.length > 1
				? injectBulkNumericOperationFormFields(
						formFields,
						fieldLabels
				  )
				: formFields,
	};

	return (
		<div className="woocommerce-product-edit__form">
			<DataForm
				data={ data }
				fields={ enhancedFields }
				form={ form }
				onChange={ onChange }
			/>
		</div>
	);
}

export default function ProductEdit( { products, isOpen }: ProductEditProps ) {
	const { navigate } = useHistory();
	const { path, query = {} } = useLocation();
	const requestedProductIdsFromRoute = getSelectionFromPostId( query.postId )
		.map( ( postId ) => Number( postId ) )
		.filter( ( postId ) => Number.isSafeInteger( postId ) && postId > 0 );
	const requestedProductIds = Array.from(
		new Set( requestedProductIdsFromRoute )
	);
	const requestedProductIdsKey = requestedProductIds.join( ',' );

	const [ isSaving, setIsSaving ] = useState( false );
	const [ bulkEditData, setBulkEditData ] =
		useState< ProductBulkEditFormData >( {} as ProductBulkEditFormData );

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

	useEffect( () => {
		setBulkEditData( {} as ProductBulkEditFormData );
	}, [ requestedProductIdsKey ] );

	const activeBulkNumericEdits = useMemo(
		() =>
			Object.fromEntries(
				Object.entries(
					getBulkNumericEditsFromData( bulkEditData )
				).filter(
					( [ fieldId, edit ] ) =>
						isBulkNumericFieldId( fieldId ) &&
						isBulkNumericEditPending( edit ) &&
						isBulkNumericEditValid( fieldId, edit )
				)
			) as Partial< Record< BulkNumericFieldId, BulkNumericEdit > >,
		[ bulkEditData ]
	);
	const hasValidBulkNumericEdits =
		Object.keys( activeBulkNumericEdits ).length > 0;
	const hasInvalidBulkNumericEdits = Object.entries(
		getBulkNumericEditsFromData( bulkEditData )
	).some(
		( [ fieldId, edit ] ) =>
			isBulkNumericFieldId( fieldId ) &&
			isBulkNumericEditPending( edit ) &&
			! isBulkNumericEditValid( fieldId, edit )
	);

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

	const applySelectedProductChanges = useCallback(
		(
			getChangesForProduct: (
				product: ProductEntityRecord
			) => Partial< ProductEntityRecord >
		) => {
			const updatedParentProductsById = new Map<
				number,
				ProductEntityRecord
			>();

			selectedProducts.forEach( ( product ) => {
				const changes = getChangesForProduct( product );

				if ( Object.keys( changes ).length === 0 ) {
					return;
				}

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

	const onChange = useCallback(
		( changes: Partial< ProductEntityRecord > ) => {
			if ( selectedProducts.length <= 1 ) {
				applySelectedProductChanges( ( product ) =>
					mergeProductChangesForProduct( product, changes )
				);
				return;
			}

			const bulkChanges: Record< string, unknown > = {};
			const productChanges: Partial< ProductEntityRecord > = {};

			Object.entries( changes ).forEach( ( [ fieldId, value ] ) => {
				if (
					isBulkNumericOperationFieldId( fieldId ) ||
					isBulkNumericFieldId( fieldId )
				) {
					bulkChanges[ fieldId ] = value;
					return;
				}

				if ( fieldId === 'dimensions' && isDimensionChanges( value ) ) {
					bulkChanges[ fieldId ] = value;
				}

				productChanges[ fieldId as keyof ProductEntityRecord ] =
					value as never;
			} );

			if ( Object.keys( bulkChanges ).length > 0 ) {
				setBulkEditData( ( currentData ) => ( {
					...currentData,
					...bulkChanges,
					...( isDimensionChanges( bulkChanges.dimensions )
						? {
								dimensions: {
									...( isDimensionChanges(
										currentData.dimensions
									)
										? currentData.dimensions
										: {} ),
									...bulkChanges.dimensions,
								} as ProductEntityRecord[ 'dimensions' ],
						  }
						: {} ),
				} ) );
			}

			if ( Object.keys( productChanges ).length > 0 ) {
				applySelectedProductChanges( ( product ) =>
					mergeProductChangesForProduct( product, productChanges )
				);
			}
		},
		[ applySelectedProductChanges, selectedProducts.length ]
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
			quickEdit: undefined,
		} as Record< string, string | undefined >;

		editedProductIds.forEach( ( productId ) => {
			clearEntityRecordEdits( 'root', 'product', productId );
		} );

		setBulkEditData( {} as ProductBulkEditFormData );

		navigate( getProductListNavigationPath( path, nextQuery ) );
	}, [ clearEntityRecordEdits, navigate, path, query, selectedProducts ] );

	const onSave = useCallback( async () => {
		if ( selectedProducts.length === 0 || isSaving ) {
			return;
		}

		if ( hasInvalidBulkNumericEdits ) {
			createErrorNotice(
				__( 'Please enter a valid value.', 'woocommerce' ),
				{
					type: 'snackbar',
				}
			);
			return;
		}

		const bulkNumericValidationError = validateBulkNumericEdits(
			selectedProducts,
			activeBulkNumericEdits
		);

		if ( bulkNumericValidationError ) {
			createErrorNotice( bulkNumericValidationError, {
				type: 'snackbar',
			} );
			return;
		}

		if ( hasValidBulkNumericEdits ) {
			applySelectedProductChanges( ( product ) =>
				getBulkNumericChangesForProduct(
					product,
					activeBulkNumericEdits
				)
			);
			setBulkEditData( {} as ProductBulkEditFormData );
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
		activeBulkNumericEdits,
		applySelectedProductChanges,
		createErrorNotice,
		createSuccessNotice,
		editEntityRecord,
		hasInvalidBulkNumericEdits,
		hasValidBulkNumericEdits,
		isSaving,
		saveEditedEntityRecord,
		selectedProducts,
	] );

	return (
		<Drawer.Root open={ isOpen } swipeDirection="right">
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
							bulkEditData={ bulkEditData }
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
							disabled={
								isSaving ||
								hasInvalidBulkNumericEdits ||
								( ! hasEdits && ! hasValidBulkNumericEdits )
							}
						>
							{ __( 'Save', 'woocommerce' ) }
						</Button>
					</Drawer.Footer>
				) }
			</Drawer.Popup>
		</Drawer.Root>
	);
}
