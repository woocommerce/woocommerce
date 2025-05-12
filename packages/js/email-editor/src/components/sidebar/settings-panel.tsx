/**
 * External dependencies
 */
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { recordEvent } from '../../events';
import { RichTextWithButton } from '../personalization-tags/rich-text-with-button';
import { TemplateSelection } from './template-selection';

const SidebarExtensionComponent = applyFilters(
	'woocommerce_email_editor_setting_sidebar_extension_component',
	RichTextWithButton
) as () => JSX.Element;

export function SettingsPanel() {
	return (
		<PanelBody
			title={ __( 'Settings', 'woocommerce' ) }
			className="woocommerce-email-editor__settings-panel"
			onToggle={ ( data ) =>
				recordEvent( 'settings_panel_body_toggle', { opened: data } )
			}
		>
			<>
				<TemplateSelection />
				{ <SidebarExtensionComponent /> }
			</>
		</PanelBody>
	);
}
