/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Display a preview for a given product.
 */
const ProductPreview = ( { product } ) => {
	let image = null;
	if ( product.images.length ) {
		image = <img src={ product.images[ 0 ].src } alt="" />;
	}

	return (
		<div className="wc-product-preview">
			{ image }
			<div className="wc-product-preview__title">{ product.name }</div>
			<div
				className="wc-product-preview__price"
				dangerouslySetInnerHTML={ { __html: product.price_html } }
			/>
			<span className="wc-product-preview__add-to-cart">
				{ __( 'Add to cart', 'woo-gutenberg-products-block' ) }
			</span>
		</div>
	);
};

ProductPreview.propTypes = {
	/**
	 * The product object as returned from the API.
	 */
	product: PropTypes.shape( {
		id: PropTypes.number,
		images: PropTypes.array,
		name: PropTypes.string,
		price_html: PropTypes.string,
	} ).isRequired,
};

export default ProductPreview;
