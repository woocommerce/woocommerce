/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Form } from './form';

export const LegacyContent = ( {
	settingsPage,
	activeSection,
	settingsData,
}: {
	settingsPage: SettingsPage;
	activeSection: string;
	settingsData: SettingsData;
} ) => {
	const section = settingsPage.sections[ activeSection ];

	if ( ! section ) {
		return null;
	}

	return (
		<Form
			settings={ section.settings }
			settingsData={ settingsData }
			settingsPage={ settingsPage }
			activeSection={ activeSection }
		/>
	);
};
