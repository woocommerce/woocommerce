/**
 * External dependencies
 */
import { compose } from '@wordpress/compose';
import { MenuItem } from '@wordpress/components';
import { withPluginContext } from '@wordpress/plugins';
import ActionItem from '@wordpress/interface/build-module/components/action-item';

/**
 * Internal dependencies
 */
import { MORE_MENU_ACTION_ITEM_SLOT_NAME } from '../../constants';

type PluginMoreMenuItemProps = {
	as?: React.ElementType;
	icon?: string | React.ReactNode;
};

export const PluginMoreMenuItem = compose(
	// @ts-expect-error The type defintion of withPluginContext is incorrect.
	withPluginContext( ( context, ownProps: PluginMoreMenuItemProps ) => {
		return {
			as: ownProps.as ?? MenuItem,
			icon: ownProps.icon || context.icon,
			name: MORE_MENU_ACTION_ITEM_SLOT_NAME,
		};
	} )
)( ActionItem );
