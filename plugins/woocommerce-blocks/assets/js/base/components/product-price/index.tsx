/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import classNames from 'classnames';
import { formatPrice } from '@woocommerce/price-format';
import { createInterpolateElement } from '@wordpress/element';
import type { Currency } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import './style.scss';

interface PriceRangeProps {
	currency: Currency | Record< string, never >; // Currency configuration object;
	maxPrice: string | number;
	minPrice: string | number;
	priceClassName?: string;
	priceStyle?: React.CSSProperties;
}

const PriceRange = ( {
	currency,
	maxPrice,
	minPrice,
	priceClassName,
	priceStyle,
}: PriceRangeProps ) => {
	return (
		<>
			<span className="screen-reader-text">
				{ sprintf(
					/* translators: %1$s min price, %2$s max price */
					__(
						'Price between %1$s and %2$s',
						'woo-gutenberg-products-block'
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

interface SalePriceProps {
	currency: Currency | Record< string, never >; // Currency configuration object.
	regularPriceClassName?: string;
	regularPriceStyle?: Record< string, string >;
	regularPrice: number | string;
	priceClassName?: string;
	priceStyle?: Record< string, string >;
	price: number | string;
}

const SalePrice = ( {
	currency,
	regularPriceClassName,
	regularPriceStyle,
	regularPrice,
	priceClassName,
	priceStyle,
	price,
}: SalePriceProps ) => {
	return (
		<>
			<span className="screen-reader-text">
				{ __( 'Previous price:', 'woo-gutenberg-products-block' ) }
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
				{ __( 'Discounted price:', 'woo-gutenberg-products-block' ) }
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

interface ProductPriceProps {
	align?: 'left' | 'center' | 'right';
	className?: string;
	currency: Currency | Record< string, never >; // Currency configuration object.
	format: string;
	price: number | string;
	priceClassName?: string;
	priceStyle?: Record< string, string >;
	maxPrice?: number | string;
	minPrice?: number | string;
	regularPrice?: number | string;
	regularPriceClassName?: string;
	regularPriceStyle?: Record< string, string >;
}

const ProductPrice = ( {
	align,
	className,
	currency,
	format = '<price/>',
	maxPrice,
	minPrice,
	price,
	priceClassName,
	priceStyle,
	regularPrice,
	regularPriceClassName,
	regularPriceStyle,
}: ProductPriceProps ): JSX.Element => {
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
	} else if ( minPrice !== undefined && maxPrice !== undefined ) {
		priceComponent = (
			<PriceRange
				currency={ currency }
				maxPrice={ maxPrice }
				minPrice={ minPrice }
				priceClassName={ priceClassName }
				priceStyle={ priceStyle }
			/>
		);
	} else if ( price ) {
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

export default ProductPrice;
