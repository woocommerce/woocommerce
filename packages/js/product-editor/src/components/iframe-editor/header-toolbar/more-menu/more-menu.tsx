/**
 * External dependencies
 */
import { MenuGroup } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { isWpVersion } from '@woocommerce/settings';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	ActionItem,
	MoreMenuDropdown,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/interface';

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
			{ ( { onClose }: { onClose: () => void } ) => (
				<>
					{ renderBlockToolbar && <WritingMenu /> }

					<ActionItem.Slot
						name="woocommerce/product-editor-iframe-editor/plugin-more-menu"
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
