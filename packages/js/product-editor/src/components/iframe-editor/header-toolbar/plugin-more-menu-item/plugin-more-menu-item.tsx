/**
 * External dependencies
 */
import { compose } from '@wordpress/compose';
import { MenuItem } from '@wordpress/components';
import { withPluginContext } from '@wordpress/plugins';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { ActionItem } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { MORE_MENU_ACTION_ITEM_SLOT_NAME } from '../../constants';

type PluginMoreMenuItemProps = {
	as?: React.ElementType;
	icon?: string | React.ReactNode;
};

export const PluginMoreMenuItem = compose(
	withPluginContext( ( context, ownProps: PluginMoreMenuItemProps ) => {
		return {
			as: ownProps.as ?? MenuItem,
			icon: ownProps.icon || context.icon,
			name: MORE_MENU_ACTION_ITEM_SLOT_NAME,
		};
	} )
)( ActionItem );
