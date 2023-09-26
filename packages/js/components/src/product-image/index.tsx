/**
 * External dependencies
 */
import classnames from 'classnames';
import { get } from 'lodash';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { placeholderWhiteBackground as placeholder } from './placeholder';

type Image = {
	src?: string;
};

type ProductImageProps = {
	/**
	 * Product or variation object. The image to display will be pulled from
	 * `product.images` or `variation.image`.
	 * See https://woocommerce.github.io/woocommerce-rest-api-docs/#product-properties
	 * and https://woocommerce.github.io/woocommerce-rest-api-docs/#product-variation-properties
	 */
	product?: {
		images?: Array< Image >;
		image?: Image;
		// ProductImage is only interested in product.images or varation.image
		// but product object can have other properties that we don't control.
		// allowing `any` here
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
	} & Record< string, any >;
	/** The width of image to display. */
	width?: number;
	/** The height of image to display. */
	height?: number;
	/** Additional CSS classes. */
	className?: string;
	/** Text to use as the image alt attribute. */
	alt?: string;
};

/**
 * Use `ProductImage` to display a product's or variation's featured image.
 * If no image can be found, a placeholder matching the front-end image
 * placeholder will be displayed.
 */

const ProductImage: React.VFC< ProductImageProps > = ( {
	product,
	width = 33,
	height = 33,
	className = '',
	alt,
	...props
} ) => {
	// The first returned image from the API is the featured/product image.
	const productImage =
		get( product, [ 'images', 0 ] ) || get( product, [ 'image' ] );
	const src = ( productImage && productImage.src ) || false;
	const altText = alt || ( productImage && productImage.alt ) || '';

	const classes = classnames( 'woocommerce-product-image', className, {
		'is-placeholder': ! src,
	} );

	return (
		<img
			className={ classes }
			src={ src || placeholder }
			width={ width }
			height={ height }
			alt={ altText }
			{ ...props }
		/>
	);
};

export default ProductImage;
