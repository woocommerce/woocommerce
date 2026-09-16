/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_file_download_method',
	label: __( 'File download method', 'woocommerce' ),
	type: 'text' as const,
	description: __(
		'If you are using X-Accel-Redirect with NGINX, apply the settings described in the Digital/Downloadable Product Handling guide.',
		'woocommerce'
	),
	Edit: 'select',
	elements: [
		{ value: 'force', label: __( 'Force downloads', 'woocommerce' ) },
		{
			value: 'xsendfile',
			label: __( 'X-Accel-Redirect/X-Sendfile', 'woocommerce' ),
		},
		{
			value: 'redirect',
			label: __( 'Redirect only (Insecure)', 'woocommerce' ),
		},
	],
};
