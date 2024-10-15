/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '~/utils/admin-settings';

const zapier = (
	<img
		src={ `${ WC_ASSET_URL }images/marketing/zapier.png` }
		alt={ __( 'Zapier', 'woocommerce' ) }
	/>
);

export default zapier;
