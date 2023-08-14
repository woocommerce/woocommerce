/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Spinner, Tooltip } from '@wordpress/components';
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	ProductVariation,
} from '@woocommerce/data';
import {
	Link,
	ListItem,
	Pagination,
	Sortable,
	Tag,
} from '@woocommerce/components';
import { getNewPath } from '@woocommerce/navigation';
import { useContext, useState, createElement } from '@wordpress/element';
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
} from '../../constants';

const NOT_VISIBLE_TEXT = __( 'Not visible to customers', 'woocommerce' );
const VISIBLE_TEXT = __( 'Visible to customers', 'woocommerce' );
const UPDATING_TEXT = __( 'Updating product variation', 'woocommerce' );

export function VariationsTable() {
	const [ currentPage, setCurrentPage ] = useState( 1 );
	const [ perPage, setPerPage ] = useState( DEFAULT_PER_PAGE_OPTION );
	const [ isUpdating, setIsUpdating ] = useState< Record< string, boolean > >(
		{}
	);

	const productId = useEntityId( 'postType', 'product' );
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
				const requestParams = {
					product_id: productId,
					page: currentPage,
					per_page: perPage,
					order: 'asc',
					orderby: 'menu_order',
				};
				return {
					isLoading: ! hasFinishedResolution(
						'getProductVariations',
						[ requestParams ]
					),
					isGeneratingVariations: getIsGeneratingVariations( {
						product_id: productId,
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
			[ currentPage, perPage, productId ]
		);

	const { updateProductVariation } = useDispatch(
		EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
	);

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

	function handleCustomerVisibilityClick(
		variationId: number,
		status: 'private' | 'publish'
	) {
		if ( isUpdating[ variationId ] ) return;
		setIsUpdating( ( prevState ) => ( {
			...prevState,
			[ variationId ]: true,
		} ) );
		updateProductVariation< Promise< ProductVariation > >(
			{ product_id: productId, id: variationId },
			{ status }
		).finally( () =>
			setIsUpdating( ( prevState ) => ( {
				...prevState,
				[ variationId ]: false,
			} ) )
		);
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
			<Sortable>
				{ variations.map( ( variation ) => (
					<ListItem key={ `${ variation.id }` }>
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
											handleCustomerVisibilityClick(
												variation.id,
												'publish'
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
											handleCustomerVisibilityClick(
												variation.id,
												'private'
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

							<Link
								href={ getNewPath(
									{},
									`/product/${ productId }/variation/${ variation.id }`,
									{}
								) }
								type="wc-admin"
								className="components-button"
							>
								{ __( 'Edit', 'woocommerce' ) }
							</Link>
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
