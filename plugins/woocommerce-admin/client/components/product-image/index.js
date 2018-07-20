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
	width: PropTypes.number,
	height: PropTypes.number,
	className: PropTypes.string,
	product: PropTypes.object,
	alt: PropTypes.string,
};

ProductImage.defaultProps = {
	width: 60,
	height: 60,
	className: '',
};

export default ProductImage;
