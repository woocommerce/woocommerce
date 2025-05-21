/**
 * External dependencies
 */
import { createSlotFill, Button } from '@wordpress/components';
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SETTINGS_SLOT_FILL_CONSTANT } from '~/settings/settings-slots';
import { ListView } from './settings-email-listing-listview';

export type Recipients = {
	to: string;
	cc: string;
	bcc: string;
};

export type EmailStatus = 'enabled' | 'disabled' | 'manual';

export type EmailType = {
	title: string;
	description: string;
	id: string;
	email_key: string;
	post_id: string;
	recipients: Recipients;
	enabled: boolean;
	manual: boolean;
	link?: string;
	status?: EmailStatus;
};

const { Fill } = createSlotFill( SETTINGS_SLOT_FILL_CONSTANT );

const EmailListingFill: React.FC< {
	emailTypes: EmailType[];
	editTemplateUrl: string | null;
} > = ( { emailTypes, editTemplateUrl } ) => {
	return (
		<Fill>
			<div
				id="email_notification_settings-description"
				className="woocommerce-email-listing-description"
			>
				<p>
					{ __(
						"Manage email notifications sent from WooCommerce below or click on 'Edit template' to customize your email template design.",
						'woocommerce'
					) }
				</p>
				{ editTemplateUrl && (
					<Button
						variant="primary"
						href={ editTemplateUrl }
						className="woocommerce-email-listing-edit-template-button"
					>
						{ __( 'Edit template', 'woocommerce' ) }
					</Button>
				) }
			</div>
			<ListView emailTypes={ emailTypes } />
		</Fill>
	);
};

export const registerSettingsEmailListingFill = () => {
	const slotElementId = 'wc_settings_email_listing_slotfill';
	const slotElement = document.getElementById( slotElementId );
	if ( ! slotElement ) {
		return null;
	}
	const emailTypesData = slotElement.getAttribute( 'data-email-types' );
	const editTemplateUrl = slotElement.getAttribute(
		'data-edit-template-url'
	);
	let emailTypes: EmailType[] = [];
	try {
		emailTypes = JSON.parse( emailTypesData || '' );
	} catch ( e ) {}

	registerPlugin( 'woocommerce-admin-settings-email-listing', {
		scope: 'woocommerce-email-listing',
		render: () => (
			<EmailListingFill
				emailTypes={ emailTypes }
				editTemplateUrl={ editTemplateUrl }
			/>
		),
	} );
};
