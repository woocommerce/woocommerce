/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Card, Spinner } from '@wordpress/components';
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	ProductVariation,
} from '@woocommerce/data';
import { ListItem, Sortable, Tag } from '@woocommerce/components';
import { useContext } from '@wordpress/element';
import { useParams } from 'react-router-dom';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { CurrencyContext } from '../../../lib/currency-context';
import { getProductStockStatus } from '../../utils/get-product-stock-status';
import './variations.scss';

export const Variations: React.FC = () => {
	const { productId } = useParams();
	const context = useContext( CurrencyContext );
	const { formatAmount, getCurrencyConfig } = context;
	const { isLoading, variations } = useSelect( ( select ) => {
		const { getProductVariations, hasFinishedResolution } = select(
			EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
		);
		return {
			isLoading: ! hasFinishedResolution( 'getProductVariations', [
				{
					product_id: productId,
				},
			] ),
			variations: getProductVariations< ProductVariation[] >( {
				product_id: productId,
			} ),
		};
	} );

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
		</Card>
	);
};
