/**
 * External dependencies
 */
import { useViewportMatch } from '@wordpress/compose';
import { createElement, Fragment } from '@wordpress/element';
import { isWpVersion } from '@woocommerce/settings';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { MoreMenuDropdown, PinnedItems } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { ToolsMenuGroup } from './tools-menu-group';
import { WritingMenu } from '../writing-menu';
import { getGutenbergVersion } from '../../../../utils/get-gutenberg-version';

export const MoreMenu = () => {
	const isLargeViewport = useViewportMatch( 'large' );

	const renderBlockToolbar =
		isWpVersion( '6.5', '>=' ) || getGutenbergVersion() > 17.3;
	return (
		<MoreMenuDropdown>
			{ () => (
				<>
					{ ! isLargeViewport && (
						<PinnedItems.Slot scope="woocommerce-product-editor-iframe-editor" />
					) }
					{ renderBlockToolbar && <WritingMenu /> }
					<ToolsMenuGroup />
				</>
			) }
		</MoreMenuDropdown>
	);
};
