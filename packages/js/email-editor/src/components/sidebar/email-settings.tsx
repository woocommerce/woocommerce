/**
 * External dependencies
 */
import { Panel } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { DetailsPanel } from './details-panel';
import { EmailTypeInfo } from './email-type-info';

export function EmailSettings() {
	return (
		<Panel>
			<EmailTypeInfo />
			<DetailsPanel />
		</Panel>
	);
}
