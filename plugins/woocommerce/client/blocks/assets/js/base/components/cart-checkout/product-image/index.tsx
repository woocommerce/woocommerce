/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { PLACEHOLDER_IMG_SRC } from '@woocommerce/settings';

interface ProductImageProps {
	image: { alt?: string; thumbnail?: string };
	fallbackAlt: string;
	width?: number;
	height?: number;
}
/**
 * Formats and returns an image element.
 *
 * @param {Object} props       Incoming props for the component.
 * @param {Object} props.image Image properties.
 */

const ProductImage = ( {
	image = {},
	fallbackAlt = '',
	width,
	height,
}: ProductImageProps ): JSX.Element => {
	const imageProps = image.thumbnail
		? {
				src: image.thumbnail,
				alt:
					decodeEntities( image.alt ) ||
					fallbackAlt ||
					'Product Image',
		  }
		: {
				src: PLACEHOLDER_IMG_SRC,
				alt: '',
		  };

	return (
		<img
			src={ imageProps.src }
			alt={ imageProps.alt }
			width={ width }
			height={ height }
		/>
	);
};

export default ProductImage;
