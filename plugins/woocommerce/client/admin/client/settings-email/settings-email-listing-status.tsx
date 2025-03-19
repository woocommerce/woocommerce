/**
 * Inspired by https://github.com/WordPress/gutenberg/blob/ee3406972d4688cf90efecb49cb0b158f49652a4/packages/fields/src/fields/status/index.tsx
 * The statusField provided by @wordpress/fields is not used because it doesn't allow custom statuses.
 */

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { drafts, scheduled, published } from '@wordpress/icons';
import { __experimentalHStack as HStack, Icon } from '@wordpress/components';

const STATUSES = [
	{
		value: 'draft',
		label: __( 'Draft', 'woocommerce' ),
		icon: drafts,
	},
	{
		value: 'future',
		label: __( 'Scheduled', 'woocommerce' ),
		icon: scheduled,
	},
	{
		value: 'active',
		label: __( 'Active', 'woocommerce' ),
		icon: published,
	},
	{
		value: 'publish',
		label: __( 'Published', 'woocommerce' ),
		icon: published,
	},
];

export const Status = ( { slug }: { slug: string | undefined } ) => {
	const status = slug
		? STATUSES.find( ( s ) => s.value === slug )
		: undefined;
	if ( ! status ) {
		return slug;
	}
	return (
		<HStack
			alignment="left"
			spacing={ 0 }
			className="woocommerce-email-listing-status"
		>
			<Icon icon={ status.icon } size={ 24 } />
			<span className="woocommerce-email-listing-status-label">
				{ status.label }
			</span>
		</HStack>
	);
};
