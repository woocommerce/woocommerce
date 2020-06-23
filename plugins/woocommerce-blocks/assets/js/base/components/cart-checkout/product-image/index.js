/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { PLACEHOLDER_IMG_SRC } from '@woocommerce/block-settings';
import PropTypes from 'prop-types';

/**
 * Formats and returns an image element.
 */
const ProductImage = ( { image = {} } ) => {
	const imageProps = {
		src: image.thumbnail || PLACEHOLDER_IMG_SRC,
		alt: decodeEntities( image.alt ) || '',
	};

	return <img { ...imageProps } alt={ imageProps.alt } />;
};

ProductImage.propTypes = {
	image: PropTypes.shape( {
		alt: PropTypes.string,
		thumbnail: PropTypes.string,
	} ),
};

export default ProductImage;
