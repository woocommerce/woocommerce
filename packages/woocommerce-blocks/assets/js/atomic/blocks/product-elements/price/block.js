/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import ProductPrice from '@woocommerce/base-components/product-price';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { getColorClassName, getFontSizeClass } from '@wordpress/block-editor';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { withProductDataContext } from '@woocommerce/shared-hocs';

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

	const wrapperClassName = classnames( className, {
		[ `${ parentClassName }__product-price` ]: parentClassName,
	} );

	if ( ! product.id ) {
		return <ProductPrice align={ align } className={ wrapperClassName } />;
	}

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

	const prices = product.prices;
	const currency = getCurrencyFromPriceResponse( prices );
	const isOnSale = prices.price !== prices.regular_price;
	const priceClassName = isOnSale
		? classnames( {
				[ `${ parentClassName }__product-price__value` ]: parentClassName,
				[ saleClasses ]: isFeaturePluginBuild(),
		  } )
		: classnames( {
				[ `${ parentClassName }__product-price__value` ]: parentClassName,
				[ classes ]: isFeaturePluginBuild(),
		  } );
	const priceStyle = isOnSale ? saleStyle : style;

	return (
		<ProductPrice
			align={ align }
			className={ wrapperClassName }
			currency={ currency }
			price={ prices.price }
			priceClassName={ priceClassName }
			priceStyle={ isFeaturePluginBuild() ? priceStyle : {} }
			// Range price props
			minPrice={ prices?.price_range?.min_amount }
			maxPrice={ prices?.price_range?.max_amount }
			// This is the regular or original price when the `price` value is a sale price.
			regularPrice={ prices.regular_price }
			regularPriceClassName={ classnames( {
				[ `${ parentClassName }__product-price__regular` ]: parentClassName,
				[ classes ]: isFeaturePluginBuild(),
			} ) }
			regularPriceStyle={ isFeaturePluginBuild() ? style : {} }
		/>
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
