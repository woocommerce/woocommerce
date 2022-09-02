/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { ImageUpload } from '@woocommerce/components';

const ImageUploadExample = () => {
	const [ image, setImage ] = useState( null );

	return (
		<ImageUpload
			image={ image }
			onChange={ ( _image ) => setImage( _image ) }
		/>
	);
};

export const Basic = () => <ImageUploadExample />;

export default {
	title: 'WooCommerce Admin/components/ImageUpload',
	component: ImageUpload,
};
