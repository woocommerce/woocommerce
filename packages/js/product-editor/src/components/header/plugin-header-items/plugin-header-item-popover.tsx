/**
 * External dependencies
 */
import { createElement, Fragment, useState } from '@wordpress/element';
import { Button, Popover } from '@wordpress/components';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { PinnedItems } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { PINNED_ITEMS_SCOPE } from '../../../constants';
import { PluginHeaderItemPopoverProps } from './types';

export const PluginHeaderItemPopover: React.FC<
	PluginHeaderItemPopoverProps
> = ( { children, label, icon } ) => {
	const [ isVisible, setVisible ] = useState( false );
	return (
		<PinnedItems scope={ PINNED_ITEMS_SCOPE }>
			<>
				<Button
					variant="tertiary"
					icon={ icon }
					label={ label }
					onClick={ () => setVisible( ! isVisible ) }
				/>
				{ isVisible && (
					<Popover
						onFocusOutside={ () => setVisible( false ) }
						onClose={ () => setVisible( false ) }
						focusOnMount="container"
					>
						{ children }
					</Popover>
				) }
			</>
		</PinnedItems>
	);
};
