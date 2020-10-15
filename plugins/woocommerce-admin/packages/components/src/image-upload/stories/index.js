/**
 * External dependencies
 */
import { withState } from '@wordpress/compose';
import { ImageUpload } from '@woocommerce/components';

const ImageUploadExample = withState( {
	image: null,
} )( ( { setState, logo } ) => (
	<ImageUpload
		image={ logo }
		onChange={ ( image ) => setState( { logo: image } ) }
	/>
) );

export const Basic = () => <ImageUploadExample />;

export default {
	title: 'WooCommerce Admin/components/ImageUpload',
	component: ImageUpload,
};
