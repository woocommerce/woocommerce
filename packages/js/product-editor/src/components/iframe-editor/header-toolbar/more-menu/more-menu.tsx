/**
 * External dependencies
 */
import { MenuGroup } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ActionItem from '@wordpress/interface/build-module/components/action-item';

/**
 * Internal dependencies
 */
import { ToolsMenuGroup } from './tools-menu-group';
import { WritingMenu } from '../writing-menu';
import { MORE_MENU_ACTION_ITEM_SLOT_NAME } from '../../constants';
import { MoreMenuDropdown } from '../../../more-menu-dropdown';

export const MoreMenu = () => {
	return (
		<MoreMenuDropdown>
			{ ( onClose ) => (
				<>
					<WritingMenu />
					<ActionItem.Slot
						name={ MORE_MENU_ACTION_ITEM_SLOT_NAME }
						label={ __( 'Plugins', 'woocommerce' ) }
						as={ MenuGroup }
						fillProps={ { onClick: onClose } }
					/>

					<ToolsMenuGroup />
				</>
			) }
		</MoreMenuDropdown>
	);
};
