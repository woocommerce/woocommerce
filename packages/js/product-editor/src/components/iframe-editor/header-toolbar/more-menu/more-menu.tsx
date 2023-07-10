/**
 * External dependencies
 */
import { Ref } from 'react';
import { createElement, forwardRef, Fragment } from '@wordpress/element';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { MoreMenuDropdown } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { ToolsMenuGroup } from './tools-menu-group';

export const MoreMenu = forwardRef( function ForwardedRefMoreMenu(
	ref: Ref< HTMLButtonElement >
) {
	return (
		<MoreMenuDropdown ref={ ref }>
			{ ( { onClose }: { onClose: () => void } ) => (
				<>
					<ToolsMenuGroup />
				</>
			) }
		</MoreMenuDropdown>
	);
} );
