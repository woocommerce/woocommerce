/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { FormattedMonetaryAmount } from '@woocommerce/blocks-components';
import type { Currency } from '@woocommerce/price-format';

/**
 * Internal dependencies
 */
import ProductBadge from '../product-badge';
import RollingPrice from '../../rolling-price';

interface ProductSaleBadgeProps {
	currency: Currency;
	saleAmount: number;
	format: string;
	animate?: boolean;
}
/**
 * ProductSaleBadge
 *
 * @param {Object}  props            Incoming props.
 * @param {Object}  props.currency   Currency object.
 * @param {number}  props.saleAmount Discounted amount.
 * @param {string}  [props.format]   Format to change the price.
 * @param {boolean} [props.animate]  Roll the amount's digits when it changes.
 * @return {*} The component.
 */
const ProductSaleBadge = ( {
	currency,
	saleAmount,
	format = '<price/>',
	animate = false,
}: ProductSaleBadgeProps ): JSX.Element | null => {
	if ( ! saleAmount || saleAmount <= 0 ) {
		return null;
	}
	if ( ! format.includes( '<price/>' ) ) {
		format = '<price/>';
		// eslint-disable-next-line no-console
		console.error( 'Price formats need to include the `<price/>` tag.' );
	}

	const formattedMessage = sprintf(
		/* translators: %s will be replaced by the discount amount */
		__( `Save %s`, 'woocommerce' ),
		format
	);

	return (
		<ProductBadge className="wc-block-components-sale-badge">
			{ createInterpolateElement( formattedMessage, {
				price: animate ? (
					<RollingPrice currency={ currency } value={ saleAmount } />
				) : (
					<FormattedMonetaryAmount
						currency={ currency }
						value={ saleAmount }
					/>
				),
			} ) }
		</ProductBadge>
	);
};

export default ProductSaleBadge;
