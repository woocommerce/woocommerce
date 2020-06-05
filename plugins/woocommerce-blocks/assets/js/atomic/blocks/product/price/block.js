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
 * Internal dependencies
 */
import './style.scss';

/**
 * Product Price Block Component.
 *
 * @param {Object} props             Incoming props.
 * @param {string} [props.className] CSS Class name for the component.
 * @param {Object} [props.product]   Optional product object. Product from context will be used if
 *                                   this is not provided.
 * @return {*} The component.
 */
const Block = ( { className, ...props } ) => {
	const { parentClassName } = useInnerBlockLayoutContext();
	const productDataContext = useProductDataContext();
	const product = props.product || productDataContext.product;

	if ( ! product ) {
		return (
			<div
				className={ classnames(
					className,
					'price',
					'wc-block-components-product-price',
					`${ parentClassName }__product-price`
				) }
			/>
		);
	}

	const prices = product.prices || {};
	const currency = getCurrencyFromPriceResponse( prices );

	return (
		<div
			className={ classnames(
				className,
				'price',
				'wc-block-components-product-price',
				`${ parentClassName }__product-price`
			) }
		>
			{ hasPriceRange( prices ) ? (
				<PriceRange
					currency={ currency }
					minAmount={ prices.price_range.min_amount }
					maxAmount={ prices.price_range.max_amount }
				/>
			) : (
				<Price
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

const PriceRange = ( { currency, minAmount, maxAmount } ) => {
	const { parentClassName } = useInnerBlockLayoutContext();

	return (
		<span
			className={ classnames(
				'wc-block-components-product-price__value',
				`${ parentClassName }__product-price__value`
			) }
		>
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

const Price = ( { currency, price, regularPrice } ) => {
	const { parentClassName } = useInnerBlockLayoutContext();

	return (
		<>
			{ regularPrice !== price && (
				<del
					className={ classnames(
						'wc-block-components-product-price__regular',
						`${ parentClassName }__product-price__regular`
					) }
				>
					<FormattedMonetaryAmount
						currency={ currency }
						value={ regularPrice }
					/>
				</del>
			) }
			<span
				className={ classnames(
					'wc-block-components-product-price__value',
					`${ parentClassName }__product-price__value`
				) }
			>
				<FormattedMonetaryAmount
					currency={ currency }
					value={ price }
				/>
			</span>
		</>
	);
};

Block.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object,
};

export default Block;
