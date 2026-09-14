/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { PLACEHOLDER_IMG_SRC } from '@woocommerce/settings';

interface ProductImageProps {
	image: {
		alt?: string;
		thumbnail?: string;
		thumbnail_srcset?: string;
		thumbnail_sizes?: string;
	};
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
	const rawAlt = image.alt || fallbackAlt;

	// Use display width for sizes so the browser picks an appropriately
	// sized source from the thumbnail srcset.
	let sizesAttr;
	if ( image.thumbnail_srcset ) {
		sizesAttr = width ? `${ width }px` : '100px';
	}

	const imageProps = image.thumbnail
		? {
				src: image.thumbnail,
				alt: rawAlt ? decodeEntities( rawAlt ) : 'Product Image',
				srcSet: image.thumbnail_srcset || undefined,
				sizes: sizesAttr,
		  }
		: {
				src: PLACEHOLDER_IMG_SRC,
				alt: '',
				srcSet: undefined,
				sizes: undefined,
		  };

	return (
		<img
			src={ imageProps.src }
			alt={ imageProps.alt }
			srcSet={ imageProps.srcSet }
			sizes={ imageProps.sizes }
			width={ width }
			height={ height }
		/>
	);
};

export default ProductImage;
