/** @format */

/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Use `ProductImage` to display a product's featured image. If no image can be found, a placeholder matching the front-end image
 * placeholder will be displayed.
 *
 * @return { object } -
 */
const ProductImage = ( { product, alt, width, height, className, ...props } ) => {
	// The first returned image from the API is the featured/product image.
	const productImage = product && product.images && product.images[ 0 ];
	const src = ( productImage && productImage.src ) || false;
	const altText = alt || ( productImage && productImage.alt ) || '';

	const classes = classnames( 'woocommerce-product-image', className, {
		'is-placeholder': ! src,
	} );

	return (
		<img
			className={ classes }
			src={ src || wcSettings.wcAssetUrl + 'images/placeholder.png' }
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
	 * Product object. The image to display will be pulled from `product.images`.
	 * See https://woocommerce.github.io/woocommerce-rest-api-docs/#product-properties
	 */
	product: PropTypes.object,
	/**
	 * Text to use as the image alt attribute.
	 */
	alt: PropTypes.string,
};

ProductImage.defaultProps = {
	width: 60,
	height: 60,
	className: '',
};

export default ProductImage;
