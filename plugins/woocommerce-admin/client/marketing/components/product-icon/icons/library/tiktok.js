/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '~/utils/admin-settings';

const tiktok = (
	<img
		src={ `${ WC_ASSET_URL }images/marketing/tiktok.jpg` }
		alt={ __( 'TikTok', 'woocommerce' ) }
	/>
);

export default tiktok;
