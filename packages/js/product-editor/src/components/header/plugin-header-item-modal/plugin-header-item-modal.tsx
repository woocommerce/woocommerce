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

export const PluginHeaderItemModal: React.FC< {
	children?: React.ReactNode;
	label?: string;
	icon?: React.ReactNode;
	order?: number;
} > = ( { children, label, icon, order = 20 } ) => {
	const [ isOpen, setOpen ] = useState( false );
	return (
		<PinnedItems scope={ PINNED_ITEMS_SCOPE } order={ order }>
			<>
				<Button
					variant="tertiary"
					icon={ icon }
					label={ label }
					onClick={ () => setOpen( ! isOpen ) }
				/>
				{ isOpen && (
					<Modal
						title={ label || '' }
						onRequestClose={ () => setOpen( false ) }
					>
						{ children }
					</Modal>
				) }
			</>
		</PinnedItems>
	);
};
