/**
 * External dependencies
 */
import NumberFormat from 'react-number-format';
import classnames from 'classnames';
import { useProductLayoutContext } from '@woocommerce/base-context/product-layout-context';

const ProductPrice = ( { className, product } ) => {
	const { layoutStyleClassPrefix } = useProductLayoutContext();
	const prices = product.prices || {};
	const numberFormatArgs = {
		displayType: 'text',
		thousandSeparator: prices.thousand_separator,
		decimalSeparator: prices.decimal_separator,
		decimalScale: prices.decimals,
		fixedDecimalScale: true,
		prefix: prices.price_prefix,
		suffix: prices.price_suffix,
	};

	if (
		prices.price_range &&
		prices.price_range.min_amount &&
		prices.price_range.max_amount
	) {
		const minAmount = parseFloat( prices.price_range.min_amount );
		const maxAmount = parseFloat( prices.price_range.max_amount );
		return (
			<div
				className={ classnames(
					className,
					`${ layoutStyleClassPrefix }__product-price`
				) }
			>
				<span
					className={ `${ layoutStyleClassPrefix }__product-price__value` }
				>
					<NumberFormat value={ minAmount } { ...numberFormatArgs } />
					&nbsp;&mdash;&nbsp;
					<NumberFormat value={ maxAmount } { ...numberFormatArgs } />
				</span>
			</div>
		);
	}

	return (
		<div
			className={ classnames(
				className,
				`${ layoutStyleClassPrefix }__product-price`
			) }
		>
			{ prices.regular_price !== prices.price && (
				<del
					className={ `${ layoutStyleClassPrefix }__product-price__regular` }
				>
					<NumberFormat
						value={ prices.regular_price }
						{ ...numberFormatArgs }
					/>
				</del>
			) }
			<span
				className={ `${ layoutStyleClassPrefix }__product-price__value` }
			>
				<NumberFormat value={ prices.price } { ...numberFormatArgs } />
			</span>
		</div>
	);
};

export default ProductPrice;
