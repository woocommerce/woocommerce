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
	const {
		placeholderImgSrc,
	} = wc_product_block_data; /* eslint-disable-line camelcase */

	let image = null;
	if ( product.images.length ) {
		image = (
			<img
				className="wc-product-preview__image"
				src={ product.images[ 0 ].src }
				alt=""
				style={ { width: `${ wc_product_block_data.thumbnail_size }px` } }
			/>
		);
	} else {
		image = (
			<img
				className="wc-product-preview__image"
				src={ placeholderImgSrc }
				alt=""
				style={ { width: `${ wc_product_block_data.thumbnail_size }px` } }
			/>
		);
	}

	const rating = Number( product.average_rating );
	let displayRating = false;
	if ( rating > 0 ) {
		displayRating = ( rating / 5 ) * 100;
	}

	return (
		<div
			className="wc-product-preview wc-block-grid__product"
		>
			<div className="wc-product-preview__image wc-block-grid__product-image">
				{ image }
			</div>
			<div
				className="wc-product-preview__title wc-block-grid__product-title"
				dangerouslySetInnerHTML={ { __html: product.name } }
			/>
			<div
				className="wc-product-preview__price wc-block-grid__product-price"
				dangerouslySetInnerHTML={ { __html: product.price_html } }
			/>

			{ displayRating && (
				<div className="wc-product-preview__rating star-rating wc-block-grid__product-rating" role="img">
					<span style={ { width: `${ displayRating }%` } } />
				</div>
			) }

			<span className="wp-block-button">
				<span className="wc-product-preview__add-to-cart wc-block-grid__product-add-to-cart wp-block-button__link">
					{ __( 'Add to cart', 'woo-gutenberg-products-block' ) }
				</span>
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
		average_rating: PropTypes.oneOf( [ 'PropTypes.number', 'PropTypes.string' ] ),
		images: PropTypes.array,
		name: PropTypes.string,
		price_html: PropTypes.string,
	} ).isRequired,
};

export default ProductPreview;
