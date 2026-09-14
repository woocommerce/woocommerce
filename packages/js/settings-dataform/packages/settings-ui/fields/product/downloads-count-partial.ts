/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_downloads_count_partial',
	label: __(
		'Count downloads even if only part of a file is fetched.',
		'woocommerce'
	),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
