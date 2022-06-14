/**
 * Internal dependencies
 */
import { TriggerCronJob, TRIGGER_CRON_ACTION_NAME } from './trigger-cron';
import { DisableEmail } from './disable-email';
import {
	TriggerUpdateCallbacks,
	TRIGGER_UPDATE_CALLBACKS_ACTION_NAME,
} from './trigger-update-callbacks';

export default [
	{
		command: 'Trigger WCA Install',
		description: `This will trigger a WooCommerce Admin install, which usually
    happens when a new version (or new install) of WooCommerce
    Admin is installed. Triggering the install manually can
    run tasks such as removing obsolete admin notes.`,
		action: 'triggerWcaInstall',
	},
	{
		command: 'Reset Onboarding Wizard',
		description: 'Resets Onboarding Wizard progress.',
		action: 'resetOnboardingWizard',
	},
	{
		command: 'Reset Jetpack Connection',
		description: 'Resets Jepack Connection options.',
		action: 'resetJetpackConnection',
	},
	{
		command: 'Enable wc-admin Tracking',
		description:
			'Enable Tracking Debug mode. You should change your console level to verbose.',
		action: 'enableTrackingDebug',
	},
	{
		command: 'Update WC installation timestamp',
		description:
			'Updates woocommerce_admin_install_timestamp to a certain date',
		action: 'updateStoreAge',
	},
	{
		command: 'Run wc_admin_daily job',
		description: 'Run wc_admin_daily job',
		action: 'runWcAdminDailyJob',
	},
	{
		command: 'Delete all products',
		description: 'Delete all products',
		action: 'deleteAllProducts',
	},
	{
		command: 'Run a cron job',
		description: <TriggerCronJob />,
		action: TRIGGER_CRON_ACTION_NAME,
	},
	{
		command: 'Disable WC emails',
		description: <DisableEmail />,
		action: 'runDisableEmail',
	},
	{
		command: 'Run version update callbacks',
		description: <TriggerUpdateCallbacks />,
		action: TRIGGER_UPDATE_CALLBACKS_ACTION_NAME,
	},
];
