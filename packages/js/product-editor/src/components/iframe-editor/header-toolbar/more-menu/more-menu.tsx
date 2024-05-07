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
import { SIDEBAR_COMPLEMENTARY_AREA_SCOPE } from '../../constants';

export const MoreMenu = () => {
	const renderBlockToolbar =
		isWpVersion( '6.5', '>=' ) || getGutenbergVersion() > 17.3;

	return (
		<MoreMenuDropdown>
			{ ( { onClose }: { onClose: () => void } ) => (
				<>
					{ renderBlockToolbar && <WritingMenu /> }

					<ActionItem.Slot
						name={ `${ SIDEBAR_COMPLEMENTARY_AREA_SCOPE }/plugin-more-menu` }
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
