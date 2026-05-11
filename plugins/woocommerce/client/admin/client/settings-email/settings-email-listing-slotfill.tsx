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

/**
 * Classification of an email post relative to the current core template.
 *
 * Sourced from `_wc_email_template_status` post meta (RSM-138), auto-surfaced
 * under `meta` in the `wp/v2/woo_email` REST response. Read-only client-side.
 * Public REST API contract — see RSM-140 spec § 4.3.
 */
export type TemplateStatus =
	| 'in_sync'
	| 'core_updated_uncustomized'
	| 'core_updated_customized';

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
	templateStatus: TemplateStatus | null;
	templateVersion: string | null;
	/**
	 * Registry-side current version of the canonical core template for this
	 * email. Sourced from `WCEmailTemplateSyncRegistry::get_email_sync_config()`
	 * server-side; serialized as `current_version` in the slotfill payload
	 * and projected to camelCase in the data hook. Combined with
	 * `templateVersion` to gate the "update available" indicator on both
	 * surfaces (list cell + RSM-141 editor banner): show only when the
	 * merchant has not yet reviewed this version.
	 */
	currentVersion: string | null;
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
