/**
 * External dependencies
 */
import { Panel } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { TemplateInfo } from './template-info';
import { TemplateSettingsPanel } from './template-settings-panel';

export function TemplateSettings() {
	return (
		<Panel>
			<TemplateInfo />
			<TemplateSettingsPanel />
		</Panel>
	);
}
