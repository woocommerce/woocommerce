/**
 * External dependencies
 */
import { Panel } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SettingsPanel } from './settings-panel';
import { EmailTypeInfo } from './email-type-info';

export function EmailSettings() {
	return (
		<Panel>
			<EmailTypeInfo />
			<SettingsPanel />
		</Panel>
	);
}
