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

export const PluginHeaderItemPopover: React.FC< {
	children?: React.ReactNode;
	label?: string;
	icon?: React.ReactNode;
	order?: number;
} > = ( { children, label, icon, order = 20 } ) => {
	const [ isVisible, setIsVisible ] = useState( false );
	return (
		<PinnedItems scope={ PINNED_ITEMS_SCOPE } order={ order }>
			<>
				<Button
					variant="tertiary"
					icon={ icon }
					label={ label }
					onClick={ () => setIsVisible( ! isVisible ) }
				/>
				{ isVisible && (
					<Popover
						onFocusOutside={ () => setIsVisible( false ) }
						onClose={ () => setIsVisible( false ) }
						focusOnMount="container"
					>
						{ children }
					</Popover>
				) }
			</>
		</PinnedItems>
	);
};
