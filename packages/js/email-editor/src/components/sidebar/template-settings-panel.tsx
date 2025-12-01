/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	// @ts-expect-error Type for PluginDocumentSettingPanel is missing in @types/wordpress__editor
	PluginDocumentSettingPanel,
	ErrorBoundary,
} from '@wordpress/editor';

/**
 * Internal dependencies
 */
import {
	recordEvent,
	recordEventOnce,
	debouncedRecordEvent,
} from '../../events';

interface TemplatePanelSection {
	id: string;
	render: () => JSX.Element | null;
}

const tracking = {
	recordEvent,
	recordEventOnce,
	debouncedRecordEvent,
};

export function TemplateSettingsPanel() {
	// Allow plugins to add custom template sections
	const templateSections = applyFilters(
		'woocommerce_email_editor_template_sections',
		[],
		tracking
	) as TemplatePanelSection[];

	if ( templateSections.length === 0 ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="template-settings-panel"
			title={ __( 'Settings', 'woocommerce' ) }
			className="woocommerce-email-editor__settings-panel"
		>
			{ templateSections.map( ( section ) => (
				// @ts-expect-error Type for ErrorBoundary is outdated in @types/wordpress__editor
				<ErrorBoundary key={ `error-boundary-${ section.id }` }>
					<div key={ section.id }>{ section.render() }</div>
				</ErrorBoundary>
			) ) }
		</PluginDocumentSettingPanel>
	);
}
