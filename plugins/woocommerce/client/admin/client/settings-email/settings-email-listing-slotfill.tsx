/**
 * External dependencies
 */
import { createSlotFill } from '@wordpress/components';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { SETTINGS_SLOT_FILL_CONSTANT } from '~/settings/settings-slots';

const { Fill } = createSlotFill( SETTINGS_SLOT_FILL_CONSTANT );

const EmailListingFill: React.FC< {} > = () => {
	return (
		<Fill>
			<h1>Block emails</h1>
		</Fill>
	);
};

export const registerSettingsEmailListingFill = () => {
	const slotElementId = 'wc_settings_email_listing_slotfill';
	const slotElement = document.getElementById( slotElementId );
	if ( ! slotElement ) {
		return null;
	}

	registerPlugin( 'woocommerce-admin-settings-email-listing', {
        scope: 'woocommerce-email-listing',
		render: () => (
			<EmailListingFill />
		),
	} );
};
