/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '~/utils/admin-settings';

const trustpilot = (
	<img
		src={ `${ WC_ASSET_URL }images/marketing/trustpilot.png` }
		alt={ __( 'Trustpilot', 'woocommerce' ) }
	/>
);

export default trustpilot;
