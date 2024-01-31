/**
 * External dependencies
 */
import { createElement, Fragment, useState } from '@wordpress/element';
import { Button, Popover } from '@wordpress/components';
import { plugins } from '@wordpress/icons';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { PinnedItems } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { HEADER_PINNED_ITEMS_SCOPE } from '../../../constants';
import { PluginHeaderItemPopoverProps } from './types';

export const PluginHeaderItemPopover: React.FC<
	PluginHeaderItemPopoverProps
> = ( { children, label, icon } ) => {
	const [ isVisible, setVisible ] = useState( false );
	const childrenToRender =
		typeof children === 'function'
			? children( { isVisible, setVisible } )
			: children;
	return (
		<PinnedItems scope={ HEADER_PINNED_ITEMS_SCOPE }>
			<>
				<Button
					variant="tertiary"
					icon={ icon ?? plugins }
					label={ label }
					onClick={ () => setVisible( ! isVisible ) }
				/>
				{ isVisible && (
					<Popover
						onFocusOutside={ () => setVisible( false ) }
						onClose={ () => setVisible( false ) }
						focusOnMount="container"
					>
						{ childrenToRender }
					</Popover>
				) }
			</>
		</PinnedItems>
	);
};
