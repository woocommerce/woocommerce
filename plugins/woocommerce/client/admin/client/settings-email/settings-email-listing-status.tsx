/**
 * Inspired by https://github.com/WordPress/gutenberg/blob/ee3406972d4688cf90efecb49cb0b158f49652a4/packages/fields/src/fields/status/index.tsx
 * The statusField provided by @wordpress/fields is not used because it doesn't allow custom statuses.
 */

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	scheduled,
	published,
	cancelCircleFilled,
	cautionFilled,
} from '@wordpress/icons';
import { __experimentalHStack as HStack, Icon } from '@wordpress/components';
import { Tooltip } from '@wordpress/ui';

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

export const Status = ( {
	slug,
	warning,
}: {
	slug: string | undefined;
	/** Why the email should be on, shown on hover or focus of a warning icon while it is disabled. */
	warning?: string | null;
} ) => {
	const status = slug
		? EMAIL_STATUSES.find( ( s ) => s.value === slug )
		: undefined;
	if ( ! status ) {
		return slug;
	}
	const icon =
		warning && slug === 'disabled' ? (
			<Tooltip.Root>
				<Tooltip.Trigger
					className="woocommerce-email-listing-status-warning-icon"
					aria-label={ warning }
				>
					<Icon icon={ cautionFilled } size={ 24 } />
				</Tooltip.Trigger>
				<Tooltip.Popup>{ warning }</Tooltip.Popup>
			</Tooltip.Root>
		) : (
			<Icon icon={ status.icon } size={ 24 } />
		);
	return (
		<HStack
			alignment="left"
			spacing={ 0 }
			className="woocommerce-email-listing-status"
		>
			{ icon }
			<span className="woocommerce-email-listing-status-label">
				{ status.label }
			</span>
		</HStack>
	);
};
