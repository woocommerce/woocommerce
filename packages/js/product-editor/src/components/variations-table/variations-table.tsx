/**
 * External dependencies
 */
import { MouseEvent } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import {
	Button,
	CheckboxControl,
	Notice,
	Spinner,
	Tooltip,
} from '@wordpress/components';
import { Product, ProductVariation } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { ListItem, Sortable, Tag } from '@woocommerce/components';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import {
	useContext,
	createElement,
	Fragment,
	forwardRef,
} from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
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

function getEditVariationLink( variation: ProductVariation ) {
	return getNewPath(
		{},
		`/product/${ variation.parent_id }/variation/${ variation.id }`,
		{}
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
	const productId = useEntityId( 'postType', 'product' );
	const context = useContext( CurrencyContext );
	const { formatAmount } = context;

	const [ productAttributes ] = useEntityProp< Product[ 'attributes' ] >(
		'postType',
		'product',
		'attributes'
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
		onGenerate,
	} = useVariations( { productId } );

	function handleEmptyTableStateActionClick() {
		onGenerate( productAttributes );
	}

	if ( ! ( isLoading || isGenerating ) && variationIds.length === 0 ) {
		return (
			<EmptyTableState
				onActionClick={ handleEmptyTableStateActionClick }
			/>
		);
	}

	function handleDeleteVariationClick( variationId: number ) {
		onDelete( variationId )
			.then( ( response ) => {
				recordEvent( 'product_variations_delete', {
					source: TRACKS_SOURCE,
					product_id: productId,
					variation_id: variationId,
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

	function handleVariationChange( variation: Partial< ProductVariation > ) {
		onUpdate( variation )
			.then( ( response ) => {
				recordEvent( 'product_variations_change', {
					source: TRACKS_SOURCE,
					product_id: productId,
					variation_id: variation.id,
				} );
				createSuccessNotice(
					getSnackbarText( response as ProductVariation, 'update' )
				);
				onVariationTableChange( 'update', [ variation ] );
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to save variation.', 'woocommerce' )
				);
			} );
	}

	function handleUpdateAll( values: Partial< ProductVariation >[] ) {
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

	function handleDeleteAll( values: Partial< ProductVariation >[] ) {
		const now = Date.now();

		onBatchDelete( values.map( ( variation ) => variation.id ) )
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

	return (
		<div className="woocommerce-product-variations" ref={ ref }>
			{ ( isLoading || isGenerating ) && (
				<div className="woocommerce-product-variations__loading">
					<Spinner />
					{ isGenerating && (
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
							action?.onClick( handleUpdateAll, handleDeleteAll );
						},
					} ) ) }
				>
					{ noticeText }
				</Notice>
			) }

			{ ( hasFilters() || totalCount > 0 ) && (
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
									onClick={ handleSelectAllVariations }
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
							productAttributes
								.filter( ( attribute ) => attribute.variation )
								.map( ( attribute ) => (
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
					<div>
						<VariationsActionsMenu
							selection={ selected }
							disabled={ ! areSomeSelected && ! isSelectingAll }
							onChange={ handleUpdateAll }
							onDelete={ handleDeleteAll }
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

							{ ! areSomeSelected && (
								<>
									<Button
										href={ getEditVariationLink(
											variation
										) }
										onClick={ editVariationClickHandler(
											variation
										) }
									>
										{ __( 'Edit', 'woocommerce' ) }
									</Button>

									<VariationActionsMenu
										selection={ variation }
										onChange={ ( value ) =>
											handleVariationChange( {
												id: variation.id,
												...value,
											} )
										}
										onDelete={ ( { id } ) =>
											handleDeleteVariationClick( id )
										}
									/>
								</>
							) }
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
