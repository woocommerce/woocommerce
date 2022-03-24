/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { get } from 'lodash';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { placeholderWhiteBackground as placeholder } from './placeholder';

/**
 * Use `ProductImage` to display a product's or variation's featured image.
 * If no image can be found, a placeholder matching the front-end image
 * placeholder will be displayed.
 *
 * @param {Object} props
 * @param {Object} props.product
 * @param {string} props.alt
 * @param {number} props.width
 * @param {number} props.height
 * @param {string} props.className
 * @return {Object} -
 */
const ProductImage = ( {
	product,
	alt,
	width,
	height,
	className,
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

ProductImage.propTypes = {
	/**
	 * The width of image to display.
	 */
	width: PropTypes.number,
	/**
	 * The height of image to display.
	 */
	height: PropTypes.number,
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * Product or variation object. The image to display will be pulled from
	 * `product.images` or `variation.image`.
	 * See https://woocommerce.github.io/woocommerce-rest-api-docs/#product-properties
	 * and https://woocommerce.github.io/woocommerce-rest-api-docs/#product-variation-properties
	 */
	product: PropTypes.object,
	/**
	 * Text to use as the image alt attribute.
	 */
	alt: PropTypes.string,
};

ProductImage.defaultProps = {
	width: 33,
	height: 33,
	className: '',
};

export default ProductImage;
