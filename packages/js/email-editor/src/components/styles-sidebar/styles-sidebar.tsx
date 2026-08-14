/**
 * External dependencies
 */
import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { styles } from '@wordpress/icons';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { StylesPanel } from './styles-panel';
import { useCanEditEmailStyles } from './hooks';

export function RawStylesSidebar(): JSX.Element {
	const userCanEditGlobalStyles = useCanEditEmailStyles();

	return (
		userCanEditGlobalStyles && (
			<>
				<PluginSidebarMoreMenuItem
					target="email-styles-sidebar"
					icon={ styles }
				>
					{ __( 'Email styles', __i18n_text_domain__ ) }
				</PluginSidebarMoreMenuItem>
				<PluginSidebar
					name="email-styles-sidebar"
					icon={ styles }
					title={ __( 'Styles', __i18n_text_domain__ ) }
					className="woocommerce-email-editor-styles-panel"
				>
					<StylesPanel />
				</PluginSidebar>
			</>
		)
	);
}

export const StylesSidebar = memo( RawStylesSidebar );
