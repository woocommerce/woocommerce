/**
 * External dependencies
 */
import { createElement, Fragment, useState } from '@wordpress/element';
import { Button, Modal } from '@wordpress/components';
import { plugins } from '@wordpress/icons';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { PinnedItems } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { HEADER_PINNED_ITEMS_SCOPE } from '../../../constants';
import { PluginHeaderItemModalProps } from './types';

export const PluginHeaderItemModal: React.FC< PluginHeaderItemModalProps > = ( {
	children,
	label,
	icon,
	title,
} ) => {
	const [ isOpen, setOpen ] = useState( false );
	const childrenToRender =
		typeof children === 'function'
			? children( { isOpen, setOpen } )
			: children;
	return (
		<PinnedItems scope={ HEADER_PINNED_ITEMS_SCOPE }>
			<>
				<Button
					variant="tertiary"
					icon={ icon ?? plugins }
					label={ label }
					onClick={ () => setOpen( ! isOpen ) }
				/>
				{ isOpen && (
					<Modal
						title={ title }
						onRequestClose={ () => setOpen( false ) }
					>
						{ childrenToRender }
					</Modal>
				) }
			</>
		</PinnedItems>
	);
};
