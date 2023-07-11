/**
 * External dependencies
 */
import { MenuGroup } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { HelpMenuItem } from './help-menu-item';

export const ToolsMenuGroup = () => {
	return (
		<MenuGroup label={ __( 'Tools', 'woocommerce' ) }>
			<HelpMenuItem />
		</MenuGroup>
	);
};
