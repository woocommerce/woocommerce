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
import { withProductDataContext } from '@woocommerce/shared-hocs';

/**
 * Internal dependencies
 */
import {
	useColorProps,
	useTypographyProps,
} from '../../../../hooks/style-attributes';

/**
 * Product Price Block Component.
 *
 * @param {Object} props                          Incoming props.
 * @param {string} [props.className]              CSS Class name for the component.
 * @param {string} [props.textAlign]              Text alignment.
 * @param {string} [props.fontSize]               Normal Price font size name.
 * @param {string} [props.color]                  Normal Price text color.
 * context will be used if this is not provided.
 * @return {*} The component.
 */
const Block = ( props ) => {
	const { className, textAlign } = props;
	const { parentClassName } = useInnerBlockLayoutContext();
	const { product } = useProductDataContext();

	const colorProps = useColorProps( props );
	const typographyProps = useTypographyProps( props );

	const wrapperClassName = classnames(
		'wc-block-components-product-price',
		className,
		colorProps.className,
		{
			[ `${ parentClassName }__product-price` ]: parentClassName,
		}
	);

	const style = {
		...typographyProps.style,
		...colorProps.style,
	};

	if ( ! product.id ) {
		return (
			<ProductPrice align={ textAlign } className={ wrapperClassName } />
		);
	}

	const prices = product.prices;
	const currency = getCurrencyFromPriceResponse( prices );
	const isOnSale = prices.price !== prices.regular_price;
	const priceClassName = classnames( {
		[ `${ parentClassName }__product-price__value` ]: parentClassName,
		[ `${ parentClassName }__product-price__value--on-sale` ]: isOnSale,
	} );

	return (
		<ProductPrice
			align={ textAlign }
			className={ wrapperClassName }
			priceStyle={ style }
			regularPriceStyle={ style }
			priceClassName={ priceClassName }
			currency={ currency }
			price={ prices.price }
			// Range price props
			minPrice={ prices?.price_range?.min_amount }
			maxPrice={ prices?.price_range?.max_amount }
			// This is the regular or original price when the `price` value is a sale price.
			regularPrice={ prices.regular_price }
			regularPriceClassName={ classnames( {
				[ `${ parentClassName }__product-price__regular` ]: parentClassName,
			} ) }
		/>
	);
};

Block.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object,
	textAlign: PropTypes.oneOf( [ 'left', 'right', 'center' ] ),
	fontSize: PropTypes.string,
	fontWidth: PropTypes.string,
	fontStyle: PropTypes.string,
	color: PropTypes.string,
};

export default withProductDataContext( Block );
