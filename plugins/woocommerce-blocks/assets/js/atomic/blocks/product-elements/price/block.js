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
import { getColorClassName, getFontSizeClass } from '@wordpress/block-editor';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { withProductDataContext } from '@woocommerce/shared-hocs';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Product Price Block Component.
 *
 * @param {Object} props                          Incoming props.
 * @param {string} [props.className]              CSS Class name for the component.
 * @param {string} [props.align]                  Text alignment.
 * @param {string} [props.fontSize]               Normal Price font size name.
 * @param {number} [props.customFontSize]         Normal Price custom font size.
 * @param {string} [props.saleFontSize]           Original Price font size name.
 * @param {number} [props.customSaleFontSize]     Original Price custom font size.
 * @param {string} [props.color]                  Normal Price text color.
 * @param {string} [props.customColor]            Normal Price custom text color.
 * @param {string} [props.saleColor]              Original Price text color.
 * @param {string} [props.customSaleColor]        Original Price custom text color.
 * @param {Object} [props.product]                Optional product object. Product from
 * context will be used if this is not provided.
 * @return {*} The component.
 */
const Block = ( {
	className,
	align,
	fontSize,
	customFontSize,
	saleFontSize,
	customSaleFontSize,
	color,
	customColor,
	saleColor,
	customSaleColor,
} ) => {
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();

	const colorClass = getColorClassName( 'color', color );
	const fontSizeClass = getFontSizeClass( fontSize );
	const saleColorClass = getColorClassName( 'color', saleColor );
	const saleFontSizeClass = getFontSizeClass( saleFontSize );

	const classes = classnames( {
		'has-text-color': color || customColor,
		'has-font-size': fontSize || customFontSize,
		[ colorClass ]: colorClass,
		[ fontSizeClass ]: fontSizeClass,
	} );

	const saleClasses = classnames( {
		'has-text-color': saleColor || customSaleColor,
		'has-font-size': saleFontSize || customSaleFontSize,
		[ saleColorClass ]: saleColorClass,
		[ saleFontSizeClass ]: saleFontSizeClass,
	} );

	const style = {
		color: customColor,
		fontSize: customFontSize,
	};

	const saleStyle = {
		color: customSaleColor,
		fontSize: customSaleFontSize,
	};

	if ( ! product.id ) {
		return (
			<div
				className={ classnames(
					className,
					'price',
					'wc-block-components-product-price',
					{
						[ `${ parentClassName }__product-price` ]: parentClassName,
					}
				) }
			/>
		);
	}

	const prices = product.prices;
	const currency = getCurrencyFromPriceResponse( prices );

	return (
		<div
			className={ classnames(
				className,
				'price',
				'wc-block-components-product-price',
				{
					[ `${ parentClassName }__product-price` ]: parentClassName,
					[ `wc-block-components-product-price__align-${ align }` ]:
						align && isFeaturePluginBuild(),
				}
			) }
		>
			{ /* eslint-disable-next-line no-nested-ternary */ }
			{ hasPriceRange( prices ) ? (
				<PriceRange
					currency={ currency }
					minAmount={ prices.price_range.min_amount }
					maxAmount={ prices.price_range.max_amount }
					classes={ classes }
					style={ style }
				/>
			) : prices.price !== prices.regular_price ? (
				<SalePrice
					currency={ currency }
					price={ prices.price }
					regularPrice={ prices.regular_price }
					saleClasses={ saleClasses }
					saleStyle={ saleStyle }
					classes={ classes }
					style={ style }
				/>
			) : (
				<Price
					currency={ currency }
					price={ prices.price }
					classes={ classes }
					style={ style }
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

const PriceRange = ( { currency, minAmount, maxAmount, classes, style } ) => {
	const { parentClassName } = useInnerBlockLayoutContext();

	return (
		<span
			className={ classnames(
				'wc-block-components-product-price__value',
				{
					[ `${ parentClassName }__product-price__value` ]: parentClassName,
					[ classes ]: isFeaturePluginBuild()
				}
			) }
			style={ isFeaturePluginBuild() ? style : {} }
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

const SalePrice = ( {
	currency,
	price,
	regularPrice,
	saleClasses = '',
	saleStyle = {},
	classes = '',
	style = {},
} ) => {
	const { parentClassName } = useInnerBlockLayoutContext();
	return (
		<>
			<del
				className={ classnames(
					'wc-block-components-product-price__regular',
					{
						[ `${ parentClassName }__product-price__regular` ]: parentClassName,
						[ classes ]: isFeaturePluginBuild() }
				) }
				style={ isFeaturePluginBuild() ? style : {} }
			>
				<FormattedMonetaryAmount
					currency={ currency }
					value={ regularPrice }
				/>
			</del>
			<span
				className={ classnames(
					'wc-block-components-product-price__value',
					{
						[ `${ parentClassName }__product-price__value` ]: parentClassName,
						[ saleClasses ]: isFeaturePluginBuild() }
				) }
				style={ isFeaturePluginBuild() ? saleStyle : {} }
			>
				<FormattedMonetaryAmount
					currency={ currency }
					value={ price }
				/>
			</span>
		</>
	);
};

const Price = ( { currency, price, classes = '', style = {} } ) => {
	const { parentClassName } = useInnerBlockLayoutContext();
	return (
		<span
			className={ classnames(
				'wc-block-components-product-price__value',
				`${ parentClassName }__product-price__value`,
				{ [ classes ]: isFeaturePluginBuild() }
			) }
			style={ isFeaturePluginBuild() ? style : {} }
		>
			<FormattedMonetaryAmount currency={ currency } value={ price } />
		</span>
	);
};

Block.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object,
	align: PropTypes.string,
	fontSize: PropTypes.string,
	customFontSize: PropTypes.number,
	saleFontSize: PropTypes.string,
	customSaleFontSize: PropTypes.number,
	color: PropTypes.string,
	customColor: PropTypes.string,
	saleColor: PropTypes.string,
	customSaleColor: PropTypes.string,
};

export default withProductDataContext( Block );
