/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '~/utils/admin-settings';

const klaviyo = (
	<img
		src={ `${ WC_ASSET_URL }images/marketing/klaviyo.png` }
		alt={ __( 'Klaviyo', 'woocommerce' ) }
	/>
);

export default klaviyo;
