/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Card, Spinner } from '@wordpress/components';
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	ProductVariation,
} from '@woocommerce/data';
import { ListItem, Pagination, Sortable, Tag } from '@woocommerce/components';
import { useContext, useState } from '@wordpress/element';
import { useParams } from 'react-router-dom';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { CurrencyContext } from '../../../lib/currency-context';
import { getProductStockStatus } from '../../utils/get-product-stock-status';
import './variations.scss';

/**
 * Since the pagination component does not exposes the way of
 * changing the per page options which are [25, 50, 75, 100]
 * the default per page option will be the min in the list to
 * keep compatibility.
 *
 * @see https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/components/src/pagination/index.js#L12
 */
const DEFAULT_PER_PAGE_OPTION = 25;

export const Variations: React.FC = () => {
	const [ currentPage, setCurrentPage ] = useState( 1 );
	const [ perPage, setPerPage ] = useState( DEFAULT_PER_PAGE_OPTION );
	const { productId } = useParams();
	const context = useContext( CurrencyContext );
	const { formatAmount, getCurrencyConfig } = context;
	const { isLoading, variations, totalCount } = useSelect(
		( select ) => {
			const {
				getProductVariations,
				hasFinishedResolution,
				getProductVariationsTotalCount,
			} = select( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );
			const requestParams = {
				product_id: productId,
				page: currentPage,
				per_page: perPage,
			};
			return {
				isLoading: ! hasFinishedResolution( 'getProductVariations', [
					requestParams,
				] ),
				variations:
					getProductVariations< ProductVariation[] >( requestParams ),
				totalCount:
					getProductVariationsTotalCount< number >( requestParams ),
			};
		},
		[ currentPage, perPage ]
	);

	if ( ! variations || isLoading ) {
		return (
			<Card className="woocommerce-product-variations is-loading">
				<Spinner />
			</Card>
		);
	}

	const currencyConfig = getCurrencyConfig();

	return (
		<Card className="woocommerce-product-variations">
			<div className="woocommerce-product-variations__header">
				<h4>{ __( 'Variation', 'woocommerce' ) }</h4>
				<h4>
					{ sprintf(
						/** Translators: The 3 letter currency code for the store. */
						__( 'Price (%s)', 'woocommerce' ),
						currencyConfig.code
					) }
				</h4>
				<h4>{ __( 'Quantity', 'woocommerce' ) }</h4>
			</div>
			<Sortable>
				{ variations.map( ( variation ) => (
					<ListItem key={ variation.id }>
						<div className="woocommerce-product-variations__attributes">
							{ variation.attributes.map( ( attribute ) => (
								/* eslint-disable-next-line @typescript-eslint/ban-ts-comment */
								/* @ts-ignore Additional props are not required. */
								<Tag
									id={ attribute.id }
									className="woocommerce-product-variations__attribute"
									key={ attribute.id }
									label={ attribute.option }
								/>
							) ) }
						</div>
						<div className="woocommerce-product-variations__price">
							{ formatAmount( variation.price ) }
						</div>
						<div className="woocommerce-product-variations__quantity">
							{ getProductStockStatus( variation ) }
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
		</Card>
	);
};
