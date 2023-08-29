/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	Button,
	CheckboxControl,
	Spinner,
	Tooltip,
} from '@wordpress/components';
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	ProductVariation,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { ListItem, Pagination, Sortable, Tag } from '@woocommerce/components';
import {
	useContext,
	useState,
	createElement,
	useMemo,
} from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import classnames from 'classnames';
import truncate from 'lodash/truncate';
import { CurrencyContext } from '@woocommerce/currency';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import HiddenIcon from './hidden-icon';
import VisibleIcon from './visible-icon';
import { getProductStockStatus, getProductStockStatusClass } from '../../utils';
import {
	DEFAULT_PER_PAGE_OPTION,
	PRODUCT_VARIATION_TITLE_LIMIT,
	TRACKS_SOURCE,
} from '../../constants';
import { VariationActionsMenu } from './variation-actions-menu';
import { useSelection } from '../../hooks/use-selection';
import { VariationsActionsMenu } from './variations-actions-menu';

const NOT_VISIBLE_TEXT = __( 'Not visible to customers', 'woocommerce' );
const VISIBLE_TEXT = __( 'Visible to customers', 'woocommerce' );
const UPDATING_TEXT = __( 'Updating product variation', 'woocommerce' );

export function VariationsTable() {
	const [ currentPage, setCurrentPage ] = useState( 1 );
	const [ perPage, setPerPage ] = useState( DEFAULT_PER_PAGE_OPTION );
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
	const requestParams = useMemo(
		() => ( {
			product_id: productId,
			page: currentPage,
			per_page: perPage,
			order: 'asc',
			orderby: 'menu_order',
		} ),
		[ productId, currentPage, perPage ]
	);
	const context = useContext( CurrencyContext );
	const { formatAmount } = context;
	const { isLoading, variations, totalCount, isGeneratingVariations } =
		useSelect(
			( select ) => {
				const {
					getProductVariations,
					hasFinishedResolution,
					getProductVariationsTotalCount,
					isGeneratingVariations: getIsGeneratingVariations,
				} = select( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );
				return {
					isLoading: ! hasFinishedResolution(
						'getProductVariations',
						[ requestParams ]
					),
					isGeneratingVariations: getIsGeneratingVariations( {
						product_id: requestParams.product_id,
					} ),
					variations:
						getProductVariations< ProductVariation[] >(
							requestParams
						),
					totalCount:
						getProductVariationsTotalCount< number >(
							requestParams
						),
				};
			},
			[ requestParams ]
		);

	const {
		updateProductVariation,
		deleteProductVariation,
		batchUpdateProductVariations,
		invalidateResolution,
	} = useDispatch( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );

	if ( ! variations && isLoading ) {
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

	const variationIds = variations.map( ( { id } ) => id );

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
			.then( () => {
				recordEvent( 'product_variations_delete', {
					source: TRACKS_SOURCE,
				} );
			} )
			.finally( () =>
				setIsUpdating( ( prevState ) => ( {
					...prevState,
					[ variationId ]: false,
				} ) )
			);
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
			.then( () => {
				createSuccessNotice(
					/* translators: The updated variations count */
					sprintf( __( '%s variation/s updated.', 'woocommerce' ), 1 )
				);
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to save variation.', 'woocommerce' )
				);
			} )
			.finally( () =>
				setIsUpdating( ( prevState ) => ( {
					...prevState,
					[ variationId ]: false,
				} ) )
			);
	}

	function handleDeleteAll() {
		batchUpdateProductVariations< { delete: [] } >(
			{ product_id: productId },
			{
				delete: variations
					.filter( ( variation ) => isSelected( variation.id ) )
					.map( ( { id } ) => id ),
			}
		)
			.then( ( response ) =>
				invalidateResolution( 'getProductVariations', [
					requestParams,
				] ).then( () => response )
			)
			.then( ( response ) => {
				createSuccessNotice(
					/* translators: The updated variations count */
					sprintf(
						__( '%s variation/s updated.', 'woocommerce' ),
						response.delete.length
					)
				);
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to delete variations.', 'woocommerce' )
				);
			} )
			.finally( () => {} );
	}

	return (
		<div className="woocommerce-product-variations">
			{ isLoading ||
				( isGeneratingVariations && (
					<div className="woocommerce-product-variations__loading">
						<Spinner />
						{ isGeneratingVariations && (
							<span>
								{ __(
									'Generating variations…',
									'woocommerce'
								) }
							</span>
						) }
					</div>
				) ) }
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
					<Button
						variant="tertiary"
						disabled={ areAllSelected( variationIds ) }
						onClick={ () => onSelectAll( variationIds )( true ) }
					>
						{ __( 'Select all', 'woocommerce' ) }
					</Button>
					<Button
						variant="tertiary"
						disabled={ ! hasSelection( variationIds ) }
						onClick={ () => onClearSelection() }
					>
						{ __( 'Clear selection', 'woocommerce' ) }
					</Button>
				</div>
				<div>
					<VariationsActionsMenu
						disabled={ ! hasSelection( variationIds ) }
						onDelete={ handleDeleteAll }
					/>
				</div>
			</div>
			<Sortable>
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
							{ formatAmount( variation.price ) }
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
							<span
								className={ classnames(
									'woocommerce-product-variations__status-dot',
									getProductStockStatusClass( variation )
								) }
							>
								●
							</span>
							{ getProductStockStatus( variation ) }
						</div>
						<div className="woocommerce-product-variations__actions">
							{ variation.status === 'private' && (
								<Tooltip
									position="top center"
									text={ NOT_VISIBLE_TEXT }
								>
									<Button
										className="components-button--hidden"
										aria-label={
											isUpdating[ variation.id ]
												? UPDATING_TEXT
												: NOT_VISIBLE_TEXT
										}
										aria-disabled={
											isUpdating[ variation.id ]
										}
										onClick={ () =>
											handleVariationChange(
												variation.id,
												{ status: 'publish' }
											)
										}
									>
										{ isUpdating[ variation.id ] ? (
											<Spinner />
										) : (
											<HiddenIcon />
										) }
									</Button>
								</Tooltip>
							) }

							{ variation.status === 'publish' && (
								<Tooltip
									position="top center"
									text={ VISIBLE_TEXT }
								>
									<Button
										className="components-button--visible"
										aria-label={
											isUpdating[ variation.id ]
												? UPDATING_TEXT
												: VISIBLE_TEXT
										}
										aria-disabled={
											isUpdating[ variation.id ]
										}
										onClick={ () =>
											handleVariationChange(
												variation.id,
												{ status: 'private' }
											)
										}
									>
										{ isUpdating[ variation.id ] ? (
											<Spinner />
										) : (
											<VisibleIcon />
										) }
									</Button>
								</Tooltip>
							) }
							<VariationActionsMenu
								variation={ variation }
								onChange={ ( value ) =>
									handleVariationChange( variation.id, value )
								}
								onDelete={ handleDeleteVariationClick }
							/>
						</div>
					</ListItem>
				) ) }
			</Sortable>

			<Pagination
				className="woocommerce-product-variations__footer"
				page={ currentPage }
				perPage={ perPage }
				total={ totalCount }
				showPagePicker={ false }
				onPageChange={ setCurrentPage }
				onPerPageChange={ setPerPage }
			/>
		</div>
	);
}
