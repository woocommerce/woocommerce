/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_downloads_deliver_inline',
	label: __(
		'Open downloadable files in the browser, instead of saving them to the device.',
		'woocommerce'
	),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
