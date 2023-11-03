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
import { useSelect, useDispatch } from '@wordpress/data';
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
import {
	DEFAULT_VARIATION_PER_PAGE_OPTION,
	PRODUCT_VARIATION_TITLE_LIMIT,
	TRACKS_SOURCE,
} from '../../constants';
import { VariationActionsMenu } from './variation-actions-menu';
import { useSelection } from '../../hooks/use-selection';
import { VariationsActionsMenu } from './variations-actions-menu';
import HiddenIcon from '../../icons/hidden-icon';
import { Pagination } from './pagination';
import { EmptyTableState } from './table-empty-state';
import { useProductVariationsHelper } from '../../hooks/use-product-variations-helper';
import { VariationsFilter } from './variations-filter';

const NOT_VISIBLE_TEXT = __( 'Not visible to customers', 'woocommerce' );

type VariationsTableProps = {
	noticeText?: string;
	noticeStatus?: 'error' | 'warning' | 'success' | 'info';
	onNoticeDismiss?: () => void;
	noticeActions?: {
		label: string;
		onClick: (
			handleUpdateAll: ( update: Partial< ProductVariation >[] ) => void,
			handleDeleteAll: ( update: Partial< ProductVariation >[] ) => void
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
	const [ currentPage, setCurrentPage ] = useState( 1 );
	const lastVariations = useRef< ProductVariation[] | null >( null );
	const [ perPage, setPerPage ] = useState(
		DEFAULT_VARIATION_PER_PAGE_OPTION
	);
	const [ filters, setFilters ] = useState< AttributeFilters[] >( [] );
	const [ isUpdating, setIsUpdating ] = useState< Record< string, boolean > >(
		{}
	);
	const {
		areAllSelected,
		isSelected,
		hasSelection,
		onSelectAll,
		onSelectItem,
		onClearSelection,
	} = useSelection();

	const productId = useEntityId( 'postType', 'product' );
	const [ attributes ] = useEntityProp< ProductAttribute[] >(
		'postType',
		'product',
		'attributes'
	);

	const context = useContext( CurrencyContext );
	const { formatAmount } = context;
	const { isLoading, latestVariations, isGeneratingVariations, totalCount } =
		useSelect(
			( select ) => {
				const {
					getProductVariations,
					getProductVariationsTotalCount,
					hasFinishedResolution,
					isGeneratingVariations: getIsGeneratingVariations,
				} = select( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

				const requestParams = {
					product_id: productId,
					page: currentPage,
					per_page: perPage,
					order: 'asc',
					orderby: 'menu_order',
					attributes: filters,
				};

				return {
					isLoading: ! hasFinishedResolution(
						'getProductVariations',
						[ requestParams ]
					),
					isGeneratingVariations: getIsGeneratingVariations( {
						product_id: requestParams.product_id,
					} ),
					latestVariations:
						getProductVariations< ProductVariation[] >(
							requestParams
						),
					totalCount:
						getProductVariationsTotalCount< number >(
							requestParams
						),
				};
			},
			[ productId, currentPage, perPage, filters ]
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

	if ( latestVariations && latestVariations !== lastVariations.current ) {
		lastVariations.current = latestVariations;
	}

	if ( isLoading && lastVariations.current === null ) {
		return (
			<div className="woocommerce-product-variations__loading">
				<Spinner />
				{ isGeneratingVariations && (
					<span>
						{ __( 'Generating variations…', 'woocommerce' ) }
					</span>
				) }
			</div>
		);
	}

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

	// this prevents a weird jump from happening while changing pages.
	const variations = latestVariations || lastVariations.current;

	const variationIds = variations.map( ( { id } ) => id );

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
		setIsUpdating( ( prevState ) => ( {
			...prevState,
			[ variationId ]: true,
		} ) );
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
				setIsUpdating( ( prevState ) => ( {
					...prevState,
					[ variationId ]: false,
				} ) );
				onVariationTableChange( 'delete' );
			} );
	}

	function handleVariationChange(
		variationId: number,
		variation: Partial< ProductVariation >
	) {
		if ( isUpdating[ variationId ] ) return;
		setIsUpdating( ( prevState ) => ( {
			...prevState,
			[ variationId ]: true,
		} ) );
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
				setIsUpdating( ( prevState ) => ( {
					...prevState,
					[ variationId ]: false,
				} ) );
				onVariationTableChange( 'update', [ variation ] );
			} );
	}

	function handleUpdateAll( update: Partial< ProductVariation >[] ) {
		batchUpdateProductVariations< { update: [] } >(
			{ product_id: productId },
			{ update }
		)
			.then( ( response: VariationResponseProps ) => {
				createSuccessNotice( getSnackbarText( response ) );
				onVariationTableChange( 'update', update );
				return invalidateResolutionForStore();
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to update variations.', 'woocommerce' )
				);
			} );
	}

	function handleDeleteAll( values: Partial< ProductVariation >[] ) {
		batchUpdateProductVariations< { delete: [] } >(
			{ product_id: productId },
			{
				delete: values.map( ( { id } ) => id ),
			}
		)
			.then( ( response: VariationResponseProps ) => {
				createSuccessNotice( getSnackbarText( response ) );
				onVariationTableChange( 'delete' );

				coreInvalidateResolution( 'getEntityRecord', [
					'postType',
					'product',
					productId,
				] );
				values.forEach( ( { id: variationId } ) => {
					coreInvalidateResolution( 'getEntityRecord', [
						'postType',
						'product_variation',
						variationId,
					] );
				} );
				return invalidateResolutionForStore();
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
							action?.onClick( handleUpdateAll, handleDeleteAll );
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
							checked={ areAllSelected( variationIds ) }
							// @ts-expect-error Property 'indeterminate' does not exist
							indeterminate={
								! areAllSelected( variationIds ) &&
								hasSelection( variationIds )
							}
							onChange={ onSelectAll( variationIds ) }
						/>
					</div>
					<div className="woocommerce-product-variations__filters">
						{ hasSelection( variationIds ) ? (
							<>
								<Button
									variant="tertiary"
									onClick={ () =>
										onSelectAll( variationIds )( true )
									}
								>
									{ __( 'Select all', 'woocommerce' ) }
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
							selection={ variations.filter( ( variation ) =>
								isSelected( variation.id )
							) }
							disabled={ ! hasSelection( variationIds ) }
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
							<CheckboxControl
								value={ variation.id }
								checked={ isSelected( variation.id ) }
								onChange={ onSelectItem( variation.id ) }
							/>
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
					onPageChange={ setCurrentPage }
					onPerPageChange={ setPerPage }
				/>
			) }
		</div>
	);
} );
