/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '~/utils/admin-settings';

const google = (
	<img
		src={ `${ WC_ASSET_URL }images/marketing/google.svg` }
		alt={ __( 'Google', 'woocommerce' ) }
		style={ { padding: '4px' } }
	/>
);

export default google;
