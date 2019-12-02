/** @format */
/**
 * Internal dependencies
 */
import {
	ImageUpload,
} from '@woocommerce/components';

/**
 * External dependencies
 */
import { withState } from '@wordpress/compose';

export default withState( {
	image: null,
} )( ( { setState, logo } ) => (
	<ImageUpload image={ logo } onChange={ image => setState( { logo: image } ) } />
) );
