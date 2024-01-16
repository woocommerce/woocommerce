/**
 * External dependencies
 */
import { createElement, Fragment, useState } from '@wordpress/element';
import { Button, Modal } from '@wordpress/components';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { PinnedItems } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { PINNED_ITEMS_SCOPE } from '../../../constants';
import { PluginHeaderItemModalProps } from './types';

export const PluginHeaderItemModal: React.FC< PluginHeaderItemModalProps > = ( {
	children,
	label,
	icon,
	title,
} ) => {
	const [ isOpen, setOpen ] = useState( false );
	return (
		<PinnedItems scope={ PINNED_ITEMS_SCOPE }>
			<>
				<Button
					variant="tertiary"
					icon={ icon }
					label={ label }
					onClick={ () => setOpen( ! isOpen ) }
				/>
				{ isOpen && (
					<Modal
						title={ title }
						onRequestClose={ () => setOpen( false ) }
					>
						{ children }
					</Modal>
				) }
			</>
		</PinnedItems>
	);
};
