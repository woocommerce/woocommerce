/**
 * External dependencies
 */
import { Panel } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { TemplateInfo } from './template-info';

export function TemplateSettings() {
	return (
		<Panel>
			<TemplateInfo />
		</Panel>
	);
}
