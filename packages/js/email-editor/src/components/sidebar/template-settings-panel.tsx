/**
 * External dependencies
 */
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

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
				<div key={ section.id }>{ section.render() }</div>
			) ) }
		</PluginDocumentSettingPanel>
	);
}
