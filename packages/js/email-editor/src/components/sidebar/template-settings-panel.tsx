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

interface TemplatePanelSection {
	id: string;
	render: () => JSX.Element | null;
}

export function TemplateSettingsPanel() {
	// Allow plugins to add custom template sections
	const templateSections = applyFilters(
		'woocommerce_email_editor_template_sections',
		[]
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
