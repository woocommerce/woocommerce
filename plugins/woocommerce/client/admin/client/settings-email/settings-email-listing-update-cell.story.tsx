/**
 * Internal dependencies
 */
import type { EmailType } from './settings-email-listing-slotfill';
import { UpdatesCell } from './settings-email-listing-update-cell';

const baseEmail: EmailType = {
	id: 'new-order',
	post_id: '1',
	title: 'New order',
	description: 'Notifies admins when a new order is placed.',
	enabled: true,
	manual: false,
	email_key: 'new_order',
	email_class_name: 'WC_Email_New_Order',
	recipients: { to: '', cc: '', bcc: '' },
	templateStatus: null,
	templateVersion: null,
	currentVersion: null,
	wasBackfilled: false,
};

export const CoreUpdatedCustomized = () => (
	<UpdatesCell
		post={ {
			...baseEmail,
			templateStatus: 'core_updated_customized',
			templateVersion: '10.6.0',
			currentVersion: '10.7.0',
		} }
	/>
);

export const InSync = () => (
	<UpdatesCell post={ { ...baseEmail, templateStatus: 'in_sync' } } />
);

export const CoreUpdatedUncustomized = () => (
	<UpdatesCell
		post={ {
			...baseEmail,
			templateStatus: 'core_updated_uncustomized',
		} }
	/>
);

export const ThirdPartyNotOptedIn = () => (
	<UpdatesCell post={ { ...baseEmail, templateStatus: null } } />
);

export default {
	title: 'WooCommerce Admin/Settings · Email · Update indicator',
	component: UpdatesCell,
};
