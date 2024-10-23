/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '~/utils/admin-settings';

const facebook = (
	<img
		src={ `${ WC_ASSET_URL }images/marketing/facebook.svg` }
		alt={ __( 'Facebook', 'woocommerce' ) }
		style={ { padding: '4px' } }
	/>
);

export default facebook;
