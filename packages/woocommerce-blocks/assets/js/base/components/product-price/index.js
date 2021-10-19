/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import classNames from 'classnames';
import PropTypes from 'prop-types';
import { formatPrice } from '@woocommerce/price-format';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';

const PriceRange = ( {
	currency,
	maxPrice,
	minPrice,
	priceClassName,
	priceStyle,
} ) => {
	return (
		<>
			<span className="screen-reader-text">
				{ sprintf(
					/* translators: %1$s min price, %2$s max price */
					__(
						'Price between %1$s and %2$s',
						'woocommerce'
					),
					formatPrice( minPrice ),
					formatPrice( maxPrice )
				) }
			</span>
			<span aria-hidden={ true }>
				<FormattedMonetaryAmount
					className={ classNames(
						'wc-block-components-product-price__value',
						priceClassName
					) }
					currency={ currency }
					value={ minPrice }
					style={ priceStyle }
				/>
				&nbsp;&mdash;&nbsp;
				<FormattedMonetaryAmount
					className={ classNames(
						'wc-block-components-product-price__value',
						priceClassName
					) }
					currency={ currency }
					value={ maxPrice }
					style={ priceStyle }
				/>
			</span>
		</>
	);
};

const SalePrice = ( {
	currency,
	regularPriceClassName,
	regularPriceStyle,
	regularPrice,
	priceClassName,
	priceStyle,
	price,
} ) => {
	return (
		<>
			<span className="screen-reader-text">
				{ __( 'Previous price:', 'woocommerce' ) }
			</span>
			<FormattedMonetaryAmount
				currency={ currency }
				renderText={ ( value ) => (
					<del
						className={ classNames(
							'wc-block-components-product-price__regular',
							regularPriceClassName
						) }
						style={ regularPriceStyle }
					>
						{ value }
					</del>
				) }
				value={ regularPrice }
			/>
			<span className="screen-reader-text">
				{ __( 'Discounted price:', 'woocommerce' ) }
			</span>
			<FormattedMonetaryAmount
				currency={ currency }
				renderText={ ( value ) => (
					<ins
						className={ classNames(
							'wc-block-components-product-price__value',
							'is-discounted',
							priceClassName
						) }
						style={ priceStyle }
					>
						{ value }
					</ins>
				) }
				value={ price }
			/>
		</>
	);
};

const ProductPrice = ( {
	align,
	className,
	currency,
	format = '<price/>',
	maxPrice = null,
	minPrice = null,
	price = null,
	priceClassName,
	priceStyle,
	regularPrice,
	regularPriceClassName,
	regularPriceStyle,
} ) => {
	const wrapperClassName = classNames(
		className,
		'price',
		'wc-block-components-product-price',
		{
			[ `wc-block-components-product-price--align-${ align }` ]: align,
		}
	);

	if ( ! format.includes( '<price/>' ) ) {
		format = '<price/>';
		// eslint-disable-next-line no-console
		console.error( 'Price formats need to include the `<price/>` tag.' );
	}

	const isDiscounted = regularPrice && price !== regularPrice;
	let priceComponent = (
		<span
			className={ classNames(
				'wc-block-components-product-price__value',
				priceClassName
			) }
		/>
	);

	if ( isDiscounted ) {
		priceComponent = (
			<SalePrice
				currency={ currency }
				price={ price }
				priceClassName={ priceClassName }
				priceStyle={ priceStyle }
				regularPrice={ regularPrice }
				regularPriceClassName={ regularPriceClassName }
				regularPriceStyle={ regularPriceStyle }
			/>
		);
	} else if ( minPrice !== null && maxPrice !== null ) {
		priceComponent = (
			<PriceRange
				currency={ currency }
				maxPrice={ maxPrice }
				minPrice={ minPrice }
				priceClassName={ priceClassName }
				priceStyle={ priceStyle }
			/>
		);
	} else if ( price !== null ) {
		priceComponent = (
			<FormattedMonetaryAmount
				className={ classNames(
					'wc-block-components-product-price__value',
					priceClassName
				) }
				currency={ currency }
				value={ price }
				style={ priceStyle }
			/>
		);
	}

	return (
		<span className={ wrapperClassName }>
			{ createInterpolateElement( format, {
				price: priceComponent,
			} ) }
		</span>
	);
};

ProductPrice.propTypes = {
	align: PropTypes.oneOf( [ 'left', 'center', 'right' ] ),
	className: PropTypes.string,
	currency: PropTypes.object,
	format: PropTypes.string,
	price: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ),
	priceClassName: PropTypes.string,
	priceStyle: PropTypes.object,
	// Range price props
	maxPrice: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ),
	minPrice: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ),
	// On sale price props
	regularPrice: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ),
	regularPriceClassName: PropTypes.string,
	regularPriceStyle: PropTypes.object,
};

export default ProductPrice;
