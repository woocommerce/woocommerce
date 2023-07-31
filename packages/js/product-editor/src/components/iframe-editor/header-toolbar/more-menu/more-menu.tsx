/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { MoreMenuDropdown } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { ToolsMenuGroup } from './tools-menu-group';

export const MoreMenu = () => {
	return (
		<MoreMenuDropdown>
			{ () => (
				<>
					<ToolsMenuGroup />
				</>
			) }
		</MoreMenuDropdown>
	);
};
