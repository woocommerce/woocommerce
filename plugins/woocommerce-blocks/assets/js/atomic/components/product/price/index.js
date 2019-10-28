/**
 * External dependencies
 */
import NumberFormat from 'react-number-format';
import classnames from 'classnames';

const ProductPrice = ( { className, product } ) => {
	const prices = product.prices || {};
	const numberFormatArgs = {
		displayType: 'text',
		thousandSeparator: prices.thousand_separator,
		decimalSeparator: prices.decimal_separator,
		decimalScale: prices.decimals,
		prefix: prices.price_prefix,
		suffix: prices.price_suffix,
	};

	if (
		prices.price_range &&
		prices.price_range.min_amount &&
		prices.price_range.max_amount
	) {
		return (
			<div
				className={ classnames(
					className,
					'wc-block-grid__product-price'
				) }
			>
				<span className="wc-block-grid__product-price__value">
					<NumberFormat
						value={ prices.price_range.min_amount }
						{ ...numberFormatArgs }
					/>
					&nbsp;&mdash;&nbsp;
					<NumberFormat
						value={ prices.price_range.max_amount }
						{ ...numberFormatArgs }
					/>
				</span>
			</div>
		);
	}

	return (
		<div
			className={ classnames(
				className,
				'wc-block-grid__product-price'
			) }
		>
			{ prices.regular_price !== prices.price && (
				<del className="wc-block-grid__product-price__regular">
					<NumberFormat
						value={ prices.regular_price }
						{ ...numberFormatArgs }
					/>
				</del>
			) }
			<span className="wc-block-grid__product-price__value">
				<NumberFormat value={ prices.price } { ...numberFormatArgs } />
			</span>
		</div>
	);
};

export default ProductPrice;
