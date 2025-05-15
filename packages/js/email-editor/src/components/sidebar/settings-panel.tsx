/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	// @ts-expect-error Type for PluginDocumentSettingPanel is missing in @types/wordpress__editor
	PluginDocumentSettingPanel,
} from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { RichTextWithButton } from '../personalization-tags/rich-text-with-button';
import { TemplateSelection } from './template-selection';

const SidebarExtensionComponent = applyFilters(
	'woocommerce_email_editor_setting_sidebar_extension_component',
	RichTextWithButton
) as () => JSX.Element;

const EmailStatusComponent = applyFilters(
	'woocommerce_email_editor_setting_sidebar_email_status_component',
	() => null
) as () => JSX.Element;

export function SettingsPanel() {
	return (
		<PluginDocumentSettingPanel
			name="email-settings-panel"
			title={ __( 'Settings', 'woocommerce' ) }
			className="woocommerce-email-editor__settings-panel"
		>
			{ <EmailStatusComponent /> }
			{ <TemplateSelection /> }
			{ <SidebarExtensionComponent /> }
		</PluginDocumentSettingPanel>
	);
}
