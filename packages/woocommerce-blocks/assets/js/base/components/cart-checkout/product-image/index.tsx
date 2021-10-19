/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { PLACEHOLDER_IMG_SRC } from '@woocommerce/settings';

interface ProductImageProps {
	image: { alt?: string; thumbnail?: string };
}
/**
 * Formats and returns an image element.
 *
 * @param {Object} props       Incoming props for the component.
 * @param {Object} props.image Image properties.
 */
const ProductImage = ( { image = {} }: ProductImageProps ): JSX.Element => {
	const imageProps = {
		src: image.thumbnail || PLACEHOLDER_IMG_SRC,
		alt: decodeEntities( image.alt ) || '',
	};

	return <img { ...imageProps } alt={ imageProps.alt } />;
};

export default ProductImage;
