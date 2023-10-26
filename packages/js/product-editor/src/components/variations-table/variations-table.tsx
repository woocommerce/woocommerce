/**
 * External dependencies
 */
import { MouseEvent, useEffect } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import {
	Button,
	CheckboxControl,
	Notice,
	Spinner,
	Tooltip,
} from '@wordpress/components';
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	Product,
	ProductAttribute,
	ProductVariation,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { ListItem, Sortable, Tag } from '@woocommerce/components';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import {
	useContext,
	useState,
	createElement,
	useRef,
	Fragment,
	forwardRef,
} from '@wordpress/element';
import { useSelect, useDispatch, resolveSelect } from '@wordpress/data';
import classnames from 'classnames';
import truncate from 'lodash/truncate';
import { CurrencyContext } from '@woocommerce/currency';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId, useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { getProductStockStatus, getProductStockStatusClass } from '../../utils';
import { PRODUCT_VARIATION_TITLE_LIMIT, TRACKS_SOURCE } from '../../constants';
import { VariationActionsMenu } from './variation-actions-menu';

import { VariationsActionsMenu } from './variations-actions-menu';
import HiddenIcon from '../../icons/hidden-icon';
import { Pagination } from './pagination';
import { EmptyTableState } from './table-empty-state';
import { useProductVariationsHelper } from '../../hooks/use-product-variations-helper';
import { VariationsFilter } from './variations-filter';
import { useVariations } from './use-variations';

const NOT_VISIBLE_TEXT = __( 'Not visible to customers', 'woocommerce' );

type VariationsTableProps = {
	noticeText?: string;
	noticeStatus?: 'error' | 'warning' | 'success' | 'info';
	onNoticeDismiss?: () => void;
	noticeActions?: {
		label: string;
		onClick: (
			handleUpdateAll: ( values: Partial< ProductVariation >[] ) => void,
			handleDeleteAll: (
				values: Pick< ProductVariation, 'id' >[]
			) => void
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

type AttributeFilters = { attribute: string; terms: string[] };

function getEditVariationLink( variation: ProductVariation ) {
	return getNewPath(
		{},
		`/product/${ variation.parent_id }/variation/${ variation.id }`,
		{}
	);
}

export const VariationsTable = forwardRef<
	HTMLDivElement,
	VariationsTableProps
>( function Table(
	{
		noticeText,
		noticeActions = [],
		noticeStatus = 'error',
		onNoticeDismiss = () => {},
		onVariationTableChange = () => {},
	}: VariationsTableProps,
	ref
) {
	const [ filters, setFilters ] = useState< AttributeFilters[] >( [] );
	const productId = useEntityId( 'postType', 'product' );
	const [ attributes ] = useEntityProp< ProductAttribute[] >(
		'postType',
		'product',
		'attributes'
	);

	const context = useContext( CurrencyContext );
	const { formatAmount } = context;

	const { isGeneratingVariations } = useSelect(
		( select ) => {
			const { isGeneratingVariations: getIsGeneratingVariations } =
				select( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );
			return {
				isGeneratingVariations: getIsGeneratingVariations( {
					product_id: productId,
				} ),
			};
		},
		[ productId ]
	);

	const {
		updateProductVariation,
		deleteProductVariation,
		batchUpdateProductVariations,
		invalidateResolutionForStore,
	} = useDispatch( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

	const { invalidateResolution: coreInvalidateResolution } =
		useDispatch( 'core' );

	useEffect( () => {
		if ( isGeneratingVariations ) {
			setFilters( [] );
		}
	}, [ isGeneratingVariations ] );

	const { generateProductVariations } = useProductVariationsHelper();

	const [ productAttributes ] = useEntityProp< Product[ 'attributes' ] >(
		'postType',
		'product',
		'attributes'
	);

	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );

	const {
		isLoading,
		variations,
		totalCount,
		onPageChange,
		onPerPageChange,

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
		onBatchUpdate,
		onBatchDelete,
	} = useVariations( { productId } );

	function handleEmptyTableStateActionClick() {
		generateProductVariations( productAttributes );
	}

	if (
		! ( isLoading || isGeneratingVariations ) &&
		totalCount === 0 &&
		filters.length === 0
	) {
		return (
			<EmptyTableState
				onActionClick={ handleEmptyTableStateActionClick }
			/>
		);
	}

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

	function handleDeleteVariationClick( variationId: number ) {
		if ( isUpdating[ variationId ] ) return;
		// setIsUpdating( ( prevState ) => ( {
		// 	...prevState,
		// 	[ variationId ]: true,
		// } ) );
		deleteProductVariation< Promise< ProductVariation > >( {
			product_id: productId,
			id: variationId,
		} )
			.then( ( response: ProductVariation ) => {
				recordEvent( 'product_variations_delete', {
					source: TRACKS_SOURCE,
				} );
				createSuccessNotice( getSnackbarText( response, 'delete' ) );
				coreInvalidateResolution( 'getEntityRecord', [
					'postType',
					'product',
					productId,
				] );
				coreInvalidateResolution( 'getEntityRecord', [
					'postType',
					'product_variation',
					variationId,
				] );
				return invalidateResolutionForStore();
			} )
			.finally( () => {
				// setIsUpdating( ( prevState ) => ( {
				// 	...prevState,
				// 	[ variationId ]: false,
				// } ) );
				onVariationTableChange( 'delete' );
			} );

		recordEvent( 'product_variations_delete', {
			source: TRACKS_SOURCE,
			product_id: productId,
			variation_id: variationId,
		} );
	}

	function handleVariationChange(
		variationId: number,
		variation: Partial< ProductVariation >
	) {
		if ( isUpdating[ variationId ] ) return;
		// setIsUpdating( ( prevState ) => ( {
		// 	...prevState,
		// 	[ variationId ]: true,
		// } ) );
		updateProductVariation< Promise< ProductVariation > >(
			{ product_id: productId, id: variationId },
			variation
		)
			.then( ( response: ProductVariation ) => {
				createSuccessNotice( getSnackbarText( response, 'update' ) );
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to save variation.', 'woocommerce' )
				);
			} )
			.finally( () => {
				// setIsUpdating( ( prevState ) => ( {
				// 	...prevState,
				// 	[ variationId ]: false,
				// } ) );
				onVariationTableChange( 'update', [ variation ] );
			} );

		recordEvent( 'product_variations_change', {
			source: TRACKS_SOURCE,
			product_id: productId,
			variation_id: variationId,
		} );
	}

	// function handleUpdateAll( update: Partial< ProductVariation >[] ) {
	// 	const now = Date.now();

	// 	batchUpdateProductVariations< { update: [] } >(
	// 		{ product_id: productId },
	// 		{ update }
	// 	)
	// 		.then( ( response: VariationResponseProps ) => {
	// 			createSuccessNotice( getSnackbarText( response ) );
	// 			onVariationTableChange( 'update', update );
	// 			return invalidateResolutionForStore();
	// 		} )
	// 		.catch( () => {
	// 			createErrorNotice(
	// 				__( 'Failed to update variations.', 'woocommerce' )
	// 			);
	// 		} )
	// 		.finally( () => {
	// 			recordEvent( 'product_variations_update_all', {
	// 				source: TRACKS_SOURCE,
	// 				product_id: productId,
	// 				variations_count: values.length,
	// 				request_time: Date.now() - now,
	// 			} );
	// 		} );
	// }

	// function handleDeleteAll( values: Partial< ProductVariation >[] ) {
	// 	const now = Date.now();

	// 	batchUpdateProductVariations< { delete: [] } >(
	// 		{ product_id: productId },
	// 		{
	// 			delete: values.map( ( { id } ) => id ),
	// 		}
	// 	)
	// 		.then( ( response: VariationResponseProps ) => {
	// 			invalidateResolutionForStore();
	// 			coreInvalidateResolution( 'getEntityRecord', [
	// 				'postType',
	// 				'product',
	// 				productId,
	// 			] );
	// 			values.forEach( ( { id: variationId } ) => {
	// 				coreInvalidateResolution( 'getEntityRecord', [
	// 					'postType',
	// 					'product_variation',
	// 					variationId,
	// 				] );
	// 			} );
	// 			return response;
	// 		} )
	// 		.then( ( response: VariationResponseProps ) => {
	// 			createSuccessNotice( getSnackbarText( response ) );
	// 			onVariationTableChange( 'delete' );
	// 		} )
	// 		.catch( () => {
	// 			createErrorNotice(
	// 				__( 'Failed to delete variations.', 'woocommerce' )
	// 			);
	// 		} )
	// 		.finally( () => {
	// 			recordEvent( 'product_variations_delete_all', {
	// 				source: TRACKS_SOURCE,
	// 				product_id: productId,
	// 				variations_count: values.length,
	// 				request_time: Date.now() - now,
	// 			} );
	// 		} );
	// }

	function editVariationClickHandler( variation: ProductVariation ) {
		const url = getEditVariationLink( variation );

		return function handleEditVariationClick(
			event: MouseEvent< HTMLAnchorElement >
		) {
			event.preventDefault();

			navigateTo( { url } );

			recordEvent( 'product_variations_edit', {
				source: TRACKS_SOURCE,
				product_id: productId,
				variation_id: variation.id,
			} );
		};
	}

	function variationsFilterHandler( attribute: ProductAttribute ) {
		return function handleVariationsFilter( options: string[] ) {
			setFilters( ( current ) => {
				let isPresent = false;
				const newFilter = current.reduce< AttributeFilters[] >(
					( prev, item ) => {
						if ( item.attribute === attribute.slug ) {
							isPresent = true;
							if ( options.length === 0 ) {
								return prev;
							}
							return [ ...prev, { ...item, terms: options } ];
						}
						return [ ...prev, item ];
					},
					[]
				);

				if ( ! isPresent ) {
					newFilter.push( {
						attribute: attribute.slug,
						terms: options,
					} );
				}

				return newFilter;
			} );
		};
	}

	// async function handleSelectAllVariations() {
	// 	const { getProductVariations } = resolveSelect(
	// 		EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
	// 	);

	// 	const now = Date.now();

	// 	const allExistingVariations = await getProductVariations<
	// 		ProductVariation[]
	// 	>( {
	// 		product_id: productId,
	// 		per_page: 100,
	// 	} );

	// 	onSelectAll( allExistingVariations )( true );

	// 	recordEvent( 'product_variations_select_all', {
	// 		source: TRACKS_SOURCE,
	// 		product_id: productId,
	// 		variations_count: allExistingVariations.length,
	// 		request_time: Date.now() - now,
	// 	} );
	// }

	return (
		<div className="woocommerce-product-variations" ref={ ref }>
			{ ( isLoading || isGeneratingVariations ) && (
				<div className="woocommerce-product-variations__loading">
					<Spinner />
					{ isGeneratingVariations && (
						<span>
							{ __( 'Generating variations…', 'woocommerce' ) }
						</span>
					) }
				</div>
			) }
			{ noticeText && (
				<Notice
					status={ noticeStatus }
					className="woocommerce-product-variations__notice"
					onRemove={ onNoticeDismiss }
					actions={ noticeActions.map( ( action ) => ( {
						...action,
						onClick: () => {
							action?.onClick( onBatchUpdate, onBatchDelete );
						},
					} ) ) }
				>
					{ noticeText }
				</Notice>
			) }

			{ ( filters.length > 0 || totalCount > 0 ) && (
				<div className="woocommerce-product-variations__header">
					<div className="woocommerce-product-variations__selection">
						<CheckboxControl
							value="all"
							checked={ areAllSelected }
							// @ts-expect-error Property 'indeterminate' does not exist
							indeterminate={
								! areAllSelected && areSomeSelected
							}
							onChange={ onSelectPage }
						/>
					</div>
					<div className="woocommerce-product-variations__filters">
						{ areSomeSelected ? (
							<>
								<span>
									{ sprintf(
										// translators: %d is the amount of selected variations
										__( '%d selected', 'woocommerce' ),
										selectedCount
									) }
								</span>
								<Button
									variant="tertiary"
									onClick={ () => onSelectPage( true ) }
								>
									{ sprintf(
										// translators: %d the variations amount in the current page
										__( 'Select page (%d)', 'woocommerce' ),
										variations.length
									) }
								</Button>
								<Button
									variant="tertiary"
									isBusy={ isSelectingAll }
									onClick={ onSelectAll }
								>
									{ sprintf(
										// translators: %d the total existing variations amount
										__( 'Select all (%d)', 'woocommerce' ),
										totalCount
									) }
								</Button>
								<Button
									variant="tertiary"
									onClick={ onClearSelection }
								>
									{ __( 'Clear selection', 'woocommerce' ) }
								</Button>
							</>
						) : (
							attributes
								.filter( ( attribute ) => attribute.variation )
								.map( ( attribute ) => (
									<VariationsFilter
										key={ attribute.id }
										initialValues={
											filters.find(
												( filter ) =>
													filter.attribute ===
													attribute.slug
											)?.terms ?? []
										}
										attribute={ attribute }
										onFilter={ variationsFilterHandler(
											attribute
										) }
									/>
								) )
						) }
					</div>
					<div>
						<VariationsActionsMenu
							selection={ selected }
							disabled={ ! areSomeSelected && ! isSelectingAll }
							onChange={ onBatchUpdate }
							onDelete={ onBatchDelete }
						/>
					</div>
				</div>
			) }

			<Sortable className="woocommerce-product-variations__table">
				{ variations.map( ( variation ) => (
					<ListItem key={ `${ variation.id }` }>
						<div className="woocommerce-product-variations__selection">
							{ isUpdating[ variation.id ] ? (
								<Spinner />
							) : (
								<CheckboxControl
									value={ variation.id }
									checked={ isSelected( variation ) }
									onChange={ onSelect( variation ) }
									disabled={ isSelectingAll }
								/>
							) }
						</div>
						<div className="woocommerce-product-variations__attributes">
							{ variation.attributes.map( ( attribute ) => {
								const tag = (
									/* eslint-disable-next-line @typescript-eslint/ban-ts-comment */
									/* @ts-ignore Additional props are not required. */
									<Tag
										id={ attribute.id }
										className="woocommerce-product-variations__attribute"
										key={ attribute.id }
										label={ truncate( attribute.option, {
											length: PRODUCT_VARIATION_TITLE_LIMIT,
										} ) }
										screenReaderLabel={ attribute.option }
									/>
								);

								return attribute.option.length <=
									PRODUCT_VARIATION_TITLE_LIMIT ? (
									tag
								) : (
									<Tooltip
										key={ attribute.id }
										text={ attribute.option }
										position="top center"
									>
										<span>{ tag }</span>
									</Tooltip>
								);
							} ) }
						</div>
						<div
							className={ classnames(
								'woocommerce-product-variations__price',
								{
									'woocommerce-product-variations__price--fade':
										variation.status === 'private',
								}
							) }
						>
							{ variation.on_sale && (
								<span className="woocommerce-product-variations__sale-price">
									{ formatAmount( variation.sale_price ) }
								</span>
							) }
							<span
								className={ classnames(
									'woocommerce-product-variations__regular-price',
									{
										'woocommerce-product-variations__regular-price--on-sale':
											variation.on_sale,
									}
								) }
							>
								{ formatAmount( variation.regular_price ) }
							</span>
						</div>
						<div
							className={ classnames(
								'woocommerce-product-variations__quantity',
								{
									'woocommerce-product-variations__quantity--fade':
										variation.status === 'private',
								}
							) }
						>
							{ variation.regular_price && (
								<>
									<span
										className={ classnames(
											'woocommerce-product-variations__status-dot',
											getProductStockStatusClass(
												variation
											)
										) }
									>
										●
									</span>
									{ getProductStockStatus( variation ) }
								</>
							) }
						</div>
						<div className="woocommerce-product-variations__actions">
							{ ( variation.status === 'private' ||
								! variation.regular_price ) && (
								<Tooltip
									// @ts-expect-error className is missing in TS, should remove this when it is included.
									className="woocommerce-attribute-list-item__actions-tooltip"
									position="top center"
									text={ NOT_VISIBLE_TEXT }
								>
									<div className="woocommerce-attribute-list-item__actions-icon-wrapper">
										<HiddenIcon className="woocommerce-attribute-list-item__actions-icon-wrapper-icon" />
									</div>
								</Tooltip>
							) }

							<Button
								href={ getEditVariationLink( variation ) }
								onClick={ editVariationClickHandler(
									variation
								) }
							>
								{ __( 'Edit', 'woocommerce' ) }
							</Button>

							<VariationActionsMenu
								selection={ variation }
								onChange={ ( value ) =>
									handleVariationChange( variation.id, value )
								}
								onDelete={ ( { id } ) =>
									handleDeleteVariationClick( id )
								}
							/>
						</div>
					</ListItem>
				) ) }
			</Sortable>

			{ totalCount > 5 && (
				<Pagination
					className="woocommerce-product-variations__footer"
					totalCount={ totalCount }
					onPageChange={ onPageChange }
					onPerPageChange={ onPerPageChange }
				/>
			) }
		</div>
	);
} );
