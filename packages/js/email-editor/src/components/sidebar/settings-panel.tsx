/**
 * External dependencies
 */
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { RichTextWithButton } from '../personalization-tags/rich-text-with-button';
import { TemplateSelection } from './template-selection';

const SidebarExtensionComponent = applyFilters(
	'woocommerce_email_editor_setting_sidebar_extension_component',
	RichTextWithButton
) as () => JSX.Element;

export function SettingsPanel() {
	return (
		<PluginDocumentSettingPanel
			name="email-settings-panel"
			title={ __( 'Settings', 'woocommerce' ) }
			className="woocommerce-email-editor__settings-panel"
		>
			<TemplateSelection />
			{ <SidebarExtensionComponent /> }
		</PluginDocumentSettingPanel>
	);
}
