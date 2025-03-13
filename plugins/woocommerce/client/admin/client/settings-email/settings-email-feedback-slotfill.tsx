/**
 * External dependencies
 */
import { createSlotFill } from '@wordpress/components';
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.scss';
import { SETTINGS_SLOT_FILL_CONSTANT } from '~/settings/settings-slots';
import { EmailCesFeedback } from './settings-email-ces-feedback';

const { Fill } = createSlotFill( SETTINGS_SLOT_FILL_CONSTANT );

const EmailFeedbackFill: React.FC = () => {
	const description = __(
		'Thank you for trying out our new email customization features. We’d love to hear your feedback on how we could improve the experience.',
		'woocommerce'
	);
	const question = __(
		'I was able to customize my email designs to match my store’s brand.',
		'woocommerce'
	);

	return (
		<Fill>
			<EmailCesFeedback
				action="email_improvements_disabled_feedback"
				description={ description }
				question={ question }
				showOnLoad={ true }
			/>
		</Fill>
	);
};

export const registerSettingsEmailFeedbackFill = () => {
	const slotElementId = 'wc_settings_features_email_feedback_slotfill';
	const slotElement = document.getElementById( slotElementId );
	if ( ! slotElement ) {
		return null;
	}
	registerPlugin( 'woocommerce-admin-settings-email-feedback', {
		scope: 'woocommerce-email-feedback-settings',
		render: () => <EmailFeedbackFill />,
	} );
};
