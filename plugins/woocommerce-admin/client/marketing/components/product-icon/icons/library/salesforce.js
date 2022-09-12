/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '~/utils/admin-settings';

const salesforce = (
	<img
		src={ `${ WC_ASSET_URL }images/marketing/salesforce.jpg` }
		alt={ __( 'Salesforce', 'woocommerce' ) }
	/>
);

export default salesforce;
