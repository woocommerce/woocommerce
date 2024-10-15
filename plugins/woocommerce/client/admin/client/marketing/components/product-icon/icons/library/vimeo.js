/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '~/utils/admin-settings';

const vimeo = (
	<img
		src={ `${ WC_ASSET_URL }images/marketing/vimeo.png` }
		alt={ __( 'Vimeo', 'woocommerce' ) }
	/>
);

export default vimeo;
