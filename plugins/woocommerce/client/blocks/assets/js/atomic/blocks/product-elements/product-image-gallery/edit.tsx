/**
 * External dependencies
 */
import { WC_BLOCKS_IMAGE_URL } from '@woocommerce/block-settings';
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './editor.scss';

const Placeholder = () => {
	return (
		<div className="wc-block-editor-product-gallery">
			<img
				src={ `${ WC_BLOCKS_IMAGE_URL }block-placeholders/product-image-gallery.svg` }
				alt="Placeholder"
			/>
			<div className="wc-block-editor-product-gallery__other-images">
				{ [ ...Array( 4 ).keys() ].map( ( index ) => {
					return (
						<img
							key={ index }
							src={ `${ WC_BLOCKS_IMAGE_URL }block-placeholders/product-image-gallery.svg` }
							alt="Placeholder"
						/>
					);
				} ) }
			</div>
		</div>
	);
};

const Edit = () => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Disabled>
				<Placeholder />
			</Disabled>
		</div>
	);
};

export default Edit;
