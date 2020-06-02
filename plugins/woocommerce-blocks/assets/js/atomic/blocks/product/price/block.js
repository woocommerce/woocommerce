/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import { getCurrencyFromPriceResponse } from '@woocommerce/base-utils';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';

/**
 * Product Price Block Component.
 *
 * @param {Object} props             Incoming props.
 * @param {string} [props.className] CSS Class name for the component.
 * @param {Object} [props.product]   Optional product object. Product from context will be used if
 *                                   this is not provided.
 * @return {*} The component.
 */
const ProductPrice = ( { className, ...props } ) => {
	const productDataContext = useProductDataContext();
	const product = props.product || productDataContext.product;

	const { layoutStyleClassPrefix } = useInnerBlockLayoutContext();
	const componentClass = `${ layoutStyleClassPrefix }__product-price`;

	if ( ! product ) {
		return (
			<div
				className={ classnames(
					className,
					componentClass,
					'price',
					'is-loading'
				) }
			/>
		);
	}

	const prices = product.prices || {};
	const currency = getCurrencyFromPriceResponse( prices );

	return (
		<div className={ classnames( className, componentClass, 'price' ) }>
			{ hasPriceRange( prices ) ? (
				<PriceRange
					componentClass={ componentClass }
					currency={ currency }
					minAmount={ prices.price_range.min_amount }
					maxAmount={ prices.price_range.max_amount }
				/>
			) : (
				<Price
					componentClass={ componentClass }
					currency={ currency }
					price={ prices.price }
					regularPrice={ prices.regular_price }
				/>
			) }
		</div>
	);
};

const hasPriceRange = ( prices ) => {
	return (
		prices.price_range &&
		prices.price_range.min_amount &&
		prices.price_range.max_amount
	);
};

const PriceRange = ( { componentClass, currency, minAmount, maxAmount } ) => {
	return (
		<span className={ `${ componentClass }__value` }>
			<FormattedMonetaryAmount
				currency={ currency }
				value={ minAmount }
			/>
			&nbsp;&mdash;&nbsp;
			<FormattedMonetaryAmount
				currency={ currency }
				value={ maxAmount }
			/>
		</span>
	);
};

const Price = ( { componentClass, currency, price, regularPrice } ) => {
	return (
		<>
			{ regularPrice !== price && (
				<del className={ `${ componentClass }__regular` }>
					<FormattedMonetaryAmount
						currency={ currency }
						value={ regularPrice }
					/>
				</del>
			) }
			<span className={ `${ componentClass }__value` }>
				<FormattedMonetaryAmount
					currency={ currency }
					value={ price }
				/>
			</span>
		</>
	);
};

ProductPrice.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object,
};

export default ProductPrice;
