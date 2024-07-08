/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, CheckboxControl, Notice } from '@wordpress/components';
import {
	PartialProductVariation,
	Product,
	ProductVariation,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import {
	createElement,
	Fragment,
	forwardRef,
	useMemo,
	useEffect,
} from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId, useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../constants';
import { Pagination } from './pagination';
import { EmptyOrErrorTableState } from './table-empty-or-error-state';
import { VariationsFilter } from './variations-filter';
import { useVariations } from './use-variations';
import { TableRowSkeleton } from './table-row-skeleton';
import { VariationsTableRow } from './variations-table-row';
import { MultipleUpdateMenu } from './variation-actions-menus';

type VariationsTableProps = {
	isVisible?: boolean;
	noticeText?: string;
	noticeStatus?: 'error' | 'warning' | 'success' | 'info';
	onNoticeDismiss?: () => void;
	noticeActions?: {
		label: string;
		onClick: (
			handleUpdateAll: ( values: PartialProductVariation[] ) => void,
			handleDeleteAll: ( values: PartialProductVariation[] ) => void
		) => void;
		className?: string;
		variant?: string;
	}[];
	onVariationTableChange?: (
		type: 'update' | 'delete',
		updates?: Partial< ProductVariation >[]
	) => void;
};

type VariationResponseProps = {
	update?: Partial< ProductVariation >[];
	delete?: Partial< ProductVariation >[];
};

function getSnackbarText(
	response: VariationResponseProps | ProductVariation,
	type?: string
): string {
	if ( 'id' in response ) {
		const action = type === 'update' ? 'updated' : 'deleted';
		return sprintf(
			/* translators: The deleted or updated variations count */
			__( '1 variation %s.', 'woocommerce' ),
			action
		);
	}

	const { update = [], delete: deleted = [] } = response;
	const updatedCount = update.length;
	const deletedCount = deleted.length;

	if ( deletedCount > 0 ) {
		return sprintf(
			/* translators: The deleted variations count */
			__( '%s variations deleted.', 'woocommerce' ),
			deletedCount
		);
	} else if ( updatedCount > 0 ) {
		return sprintf(
			/* translators: The updated variations count */
			__( '%s variations updated.', 'woocommerce' ),
			updatedCount
		);
	}

	return '';
}

export const VariationsTable = forwardRef<
	HTMLDivElement,
	VariationsTableProps
>( function Table(
	{
		isVisible = false,
		noticeText,
		noticeActions = [],
		noticeStatus = 'error',
		onNoticeDismiss = () => {},
		onVariationTableChange = () => {},
	}: VariationsTableProps,
	ref
) {
	const productId = useEntityId( 'postType', 'product' );

	const [ productAttributes ] = useEntityProp< Product[ 'attributes' ] >(
		'postType',
		'product',
		'attributes'
	);

	const variableAttributes = useMemo(
		() => productAttributes.filter( ( attribute ) => attribute.variation ),
		[ productAttributes ]
	);

	const [ variationIds ] = useEntityProp< Product[ 'variations' ] >(
		'postType',
		'product',
		'variations'
	);

	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );

	const {
		isLoading,
		variations,
		totalCount,
		onPageChange,
		onPerPageChange,
		onFilter,
		getFilters,
		hasFilters,
		clearFilters,

		selected,
		isSelectingAll,
		selectedCount,
		areAllSelected,
		areSomeSelected,
		isSelected,
		onSelect,
		onSelectPage,
		onSelectAll,
		onClearSelection,

		isUpdating,
		onUpdate,
		onDelete,
		onBatchUpdate,
		onBatchDelete,

		isGenerating,
		variationsError,
		onGenerate,
		getCurrentVariations,
	} = useVariations( { productId } );

	useEffect( () => {
		if ( isVisible ) {
			getCurrentVariations();
		}
	}, [ isVisible, isGenerating, productId ] );

	function handleEmptyTableStateActionClick() {
		onGenerate( productAttributes );
	}

	const isError = variationsError !== undefined;

	if (
		! ( isLoading || isGenerating ) &&
		( variationIds.length === 0 || isError )
	) {
		return (
			<EmptyOrErrorTableState
				onActionClick={ handleEmptyTableStateActionClick }
				isError={ isError }
			/>
		);
	}

	function handleDeleteVariationClick( variation: PartialProductVariation ) {
		onDelete( variation.id )
			.then( ( response ) => {
				recordEvent( 'product_variations_delete', {
					source: TRACKS_SOURCE,
					product_id: productId,
					variation_id: variation.id,
				} );
				createSuccessNotice(
					getSnackbarText( response as ProductVariation, 'delete' )
				);
				onVariationTableChange( 'delete' );
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to delete variation.', 'woocommerce' )
				);
			} );
	}

	function handleVariationChange(
		variation: PartialProductVariation,
		showSuccess = true
	) {
		const { id, ...changes } = variation;

		onUpdate( variation )
			.then( ( response ) => {
				recordEvent( 'product_variations_change', {
					source: TRACKS_SOURCE,
					product_id: productId,
					variation_id: variation.id,
					updated_options: Object.keys( changes ),
				} );

				if ( showSuccess ) {
					createSuccessNotice(
						getSnackbarText(
							response as ProductVariation,
							'update'
						)
					);
				}

				onVariationTableChange( 'update', [ variation ] );
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to save variation.', 'woocommerce' )
				);
			} );
	}

	function handleUpdateAll( values: PartialProductVariation[] ) {
		const now = Date.now();

		onBatchUpdate( values )
			.then( ( response ) => {
				recordEvent( 'product_variations_update_all', {
					source: TRACKS_SOURCE,
					product_id: productId,
					variations_count: values.length,
					request_time: Date.now() - now,
				} );
				createSuccessNotice( getSnackbarText( response ) );
				onVariationTableChange( 'update', values );
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to update variations.', 'woocommerce' )
				);
			} );
	}

	function handleDeleteAll( values: PartialProductVariation[] ) {
		const now = Date.now();

		onBatchDelete( values )
			.then( ( response: VariationResponseProps ) => {
				recordEvent( 'product_variations_delete_all', {
					source: TRACKS_SOURCE,
					product_id: productId,
					variations_count: values.length,
					request_time: Date.now() - now,
				} );
				createSuccessNotice( getSnackbarText( response ) );
				onVariationTableChange( 'delete' );
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to delete variations.', 'woocommerce' )
				);
			} );
	}

	function editVariationClickHandler( variation: ProductVariation ) {
		return function handleEditVariationClick() {
			recordEvent( 'product_variations_edit', {
				source: TRACKS_SOURCE,
				product_id: productId,
				variation_id: variation.id,
			} );
		};
	}

	async function handleSelectAllVariations() {
		const now = Date.now();

		onSelectAll().then( ( total ) => {
			recordEvent( 'product_variations_select_all', {
				source: TRACKS_SOURCE,
				product_id: productId,
				variations_count: total,
				request_time: Date.now() - now,
			} );
		} );
	}

	function renderTableBody() {
		return totalCount > 0 ? (
			<div
				className="woocommerce-product-variations__table-body"
				role="rowgroup"
			>
				{ variations.map( ( variation ) => (
					<div
						key={ `${ variation.id }` }
						className="woocommerce-product-variations__table-row"
						role="row"
					>
						<VariationsTableRow
							variation={ variation }
							variableAttributes={ variableAttributes }
							isUpdating={ isUpdating[ variation.id ] }
							isSelected={ isSelected( variation ) }
							isSelectionDisabled={ isSelectingAll }
							hideActionButtons={ ! areSomeSelected }
							onChange={ handleVariationChange }
							onDelete={ handleDeleteVariationClick }
							onEdit={ editVariationClickHandler( variation ) }
							onSelect={ onSelect( variation ) }
						/>
					</div>
				) ) }
			</div>
		) : (
			<EmptyOrErrorTableState
				isError={ false }
				message={ __( 'No variations were found', 'woocommerce' ) }
				actionText={ __( 'Clear filters', 'woocommerce' ) }
				onActionClick={ clearFilters }
			/>
		);
	}

	return (
		<div className="woocommerce-product-variations" ref={ ref }>
			{ noticeText && (
				<Notice
					status={ noticeStatus }
					className="woocommerce-product-variations__notice"
					onRemove={ onNoticeDismiss }
					actions={ noticeActions.map( ( action ) => ( {
						...action,
						onClick: () => {
							action?.onClick( handleUpdateAll, handleDeleteAll );
						},
					} ) ) }
				>
					{ noticeText }
				</Notice>
			) }

			<div className="woocommerce-product-variations__table" role="table">
				{ ( hasFilters() || totalCount > 0 ) && (
					<div
						className="woocommerce-product-variations__table-header"
						role="rowgroup"
					>
						<div
							className="woocommerce-product-variations__table-row"
							role="rowheader"
						>
							<div className="woocommerce-product-variations__filters">
								{ areSomeSelected ? (
									<>
										<span>
											{ sprintf(
												// translators: %d is the amount of selected variations
												__(
													'%d selected',
													'woocommerce'
												),
												selectedCount
											) }
										</span>
										<Button
											variant="tertiary"
											onClick={ () =>
												onSelectPage( true )
											}
										>
											{ sprintf(
												// translators: %d the variations amount in the current page
												__(
													'Select page (%d)',
													'woocommerce'
												),
												variations.length
											) }
										</Button>
										<Button
											variant="tertiary"
											isBusy={ isSelectingAll }
											onClick={
												handleSelectAllVariations
											}
										>
											{ sprintf(
												// translators: %d the total existing variations amount
												__(
													'Select all (%d)',
													'woocommerce'
												),
												totalCount
											) }
										</Button>
										<Button
											variant="tertiary"
											onClick={ onClearSelection }
										>
											{ __(
												'Clear selection',
												'woocommerce'
											) }
										</Button>
									</>
								) : (
									variableAttributes.map( ( attribute ) => (
										<VariationsFilter
											key={ attribute.id }
											initialValues={ getFilters(
												attribute
											) }
											attribute={ attribute }
											onFilter={ onFilter( attribute ) }
										/>
									) )
								) }
							</div>
							<div className="woocommerce-product-variations__actions">
								<MultipleUpdateMenu
									selection={ selected }
									disabled={
										! areSomeSelected && ! isSelectingAll
									}
									onChange={ handleUpdateAll }
									onDelete={ handleDeleteAll }
								/>
							</div>
						</div>

						{ totalCount > 0 && (
							<div
								className="woocommerce-product-variations__table-row woocommerce-product-variations__table-rowheader"
								role="rowheader"
							>
								<div
									className="woocommerce-product-variations__table-column woocommerce-product-variations__selection"
									role="columnheader"
								>
									<CheckboxControl
										value="all"
										checked={ areAllSelected }
										// @ts-expect-error Property 'indeterminate' does not exist
										indeterminate={
											! areAllSelected && areSomeSelected
										}
										onChange={ onSelectPage }
										aria-label={ __(
											'Select all',
											'woocommerce'
										) }
									/>
								</div>
								<div
									className="woocommerce-product-variations__table-column"
									role="columnheader"
								>
									{ __( 'Variation', 'woocommerce' ) }
								</div>
								<div
									className="woocommerce-product-variations__table-column woocommerce-product-variations__price"
									role="columnheader"
								>
									{ __( 'Price', 'woocommerce' ) }
								</div>
								<div
									className="woocommerce-product-variations__table-column"
									role="columnheader"
								>
									{ __( 'Stock', 'woocommerce' ) }
								</div>
							</div>
						) }
					</div>
				) }

				{ isLoading || isGenerating ? (
					<div
						className="woocommerce-product-variations__table-body"
						role="presentation"
						aria-label={
							isGenerating
								? __( 'Generating variations…', 'woocommerce' )
								: __( 'Loading variations…', 'woocommerce' )
						}
					>
						{ Array.from( { length: variations.length || 5 } ).map(
							( _, index ) => (
								<TableRowSkeleton key={ index } />
							)
						) }
					</div>
				) : (
					renderTableBody()
				) }

				{ totalCount > 5 && (
					<div
						className="woocommerce-product-variations__table-footer"
						role="row"
					>
						<Pagination
							totalCount={ totalCount }
							onPageChange={ onPageChange }
							onPerPageChange={ onPerPageChange }
						/>
					</div>
				) }
			</div>
		</div>
	);
} );
