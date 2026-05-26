/**
 * Inspired by https://github.com/WordPress/gutenberg/blob/ee3406972d4688cf90efecb49cb0b158f49652a4/packages/fields/src/fields/status/index.tsx
 * The statusField provided by @wordpress/fields is not used because it doesn't allow custom statuses.
 */

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { scheduled, published, cancelCircleFilled } from '@wordpress/icons';

export const EMAIL_STATUSES = [
	{
		value: 'enabled',
		label: __( 'Active', 'woocommerce' ),
		icon: published,
		description: __(
			'Email would be sent if trigger is met',
			'woocommerce'
		),
	},
	{
		value: 'disabled',
		label: __( 'Inactive', 'woocommerce' ),
		icon: cancelCircleFilled,
		description: __( 'Email would not be sent', 'woocommerce' ),
	},
	{
		value: 'manual',
		label: __( 'Manually sent', 'woocommerce' ),
		icon: scheduled,
		description: __(
			'Email can only be sent manually from the order screen',
			'woocommerce'
		),
	},
];
