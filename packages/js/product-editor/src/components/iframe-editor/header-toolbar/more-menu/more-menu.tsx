/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { isWpVersion } from '@woocommerce/settings';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { MoreMenuDropdown } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { ToolsMenuGroup } from './tools-menu-group';
import { WritingMenu } from '../writing-menu';
import { getGutenbergVersion } from '../../../../utils/get-gutenberg-version';

export const MoreMenu = () => {
	const renderBlockToolbar =
		isWpVersion( '6.5', '>=' ) || getGutenbergVersion() > 17.3;
	return (
		<MoreMenuDropdown>
			{ () => (
				<>
					{ renderBlockToolbar && <WritingMenu /> }
					<ToolsMenuGroup />
				</>
			) }
		</MoreMenuDropdown>
	);
};
