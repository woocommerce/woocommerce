/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { TabPanel } from './tab-panel';

export function TemplateEventsTabPanel( {
	isSelected,
	postType,
}: {
	isSelected: boolean;
	postType: string;
} ) {
	const events =
		// @ts-ignore
		globalThis.productBlockEditorSettings.templateEvents[ postType ];

	return (
		<TabPanel isSelected={ isSelected }>
			<div className="woocommerce-product-editor-dev-tools-events">
				{ /* @ts-ignore */ }
				{ events.map( ( event, index ) => (
					<div key={ index }>
						<span>{ event.level }</span>
						<span>{ event.event_type }</span>
						<span>{ event.message }</span>
						<span>
							{ event.block
								? event.block.id + ' ' + event.block.name
								: '(none)' }
						</span>
						<span>
							{ event.container
								? event.container.id +
								  ' ' +
								  event.container.name
								: '(none)' }
						</span>
						<span>TODO: additional_info</span>
					</div>
				) ) }
			</div>
		</TabPanel>
	);
}
