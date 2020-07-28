/**
 * External dependencies
 */
import { withState } from '@wordpress/compose';
import { ImageUpload } from '@woocommerce/components';

export default withState( {
	image: null,
} )( ( { setState, logo } ) => (
	<ImageUpload
		image={ logo }
		onChange={ ( image ) => setState( { logo: image } ) }
	/>
) );
