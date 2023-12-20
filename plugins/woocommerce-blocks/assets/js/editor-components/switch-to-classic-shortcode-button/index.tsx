/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, TabbableContainer, Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { createBlock, BlockInstance } from '@wordpress/blocks';
import { store as noticesStore } from '@wordpress/notices';
import { store as coreStore } from '@wordpress/core-data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { recordEvent } from '@woocommerce/tracks';
import { findBlock } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import { ModalContent } from './modal-content';
import './editor.scss';

interface SwitchToClassicShortcodeButtonProps {
	block: 'woocommerce/cart' | 'woocommerce/checkout';
	clientId: string;
}

export function SwitchToClassicShortcodeButton( {
	block,
	clientId,
}: SwitchToClassicShortcodeButtonProps ): JSX.Element {
	const { createInfoNotice } = useDispatch( noticesStore );
	const { replaceBlock, selectBlock } = useDispatch( blockEditorStore );

	const [ isOpen, setOpen ] = useState( false );
	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );
	const { undo } = useDispatch( coreStore );

	const switchButtonLabel =
		block === 'woocommerce/cart'
			? __( 'Switch to classic cart', 'woocommerce' )
			: __( 'Switch to classic checkout', 'woocommerce' );

	const snackbarLabel =
		block === 'woocommerce/cart'
			? __( 'Switched to classic cart.', 'woocommerce' )
			: __( 'Switched to classic checkout.', 'woocommerce' );

	const { getBlocks } = useSelect( ( select ) => {
		return {
			getBlocks: select( blockEditorStore ).getBlocks,
		};
	}, [] );

	const selectClassicShortcodeBlock = () => {
		const classicShortcodeBlock = findBlock( {
			blocks: getBlocks(),
			findCondition: ( foundBlock: BlockInstance ) =>
				foundBlock.name === 'woocommerce/classic-shortcode',
		} );

		if ( classicShortcodeBlock ) {
			selectBlock( classicShortcodeBlock.clientId );
		}
	};

	const handleSwitchToClassicShortcodeClick = () => {
		recordEvent( 'switch_to_classic_shortcode_click', {
			shortcode: block === 'woocommerce/checkout' ? 'checkout' : 'cart',
		} );
		openModal();
	};

	const handleUndoClick = () => {
		undo();
		recordEvent( 'switch_to_classic_shortcode_undo', {
			shortcode: block === 'woocommerce/checkout' ? 'checkout' : 'cart',
		} );
	};

	const handleSwitchClick = () => {
		replaceBlock(
			clientId,
			createBlock( 'woocommerce/classic-shortcode', {
				shortcode:
					block === 'woocommerce/checkout' ? 'checkout' : 'cart',
			} )
		);
		recordEvent( 'switch_to_classic_shortcode_confirm', {
			shortcode: block === 'woocommerce/checkout' ? 'checkout' : 'cart',
		} );
		selectClassicShortcodeBlock();
		createInfoNotice( snackbarLabel, {
			actions: [
				{
					label: __( 'Undo', 'woocommerce' ),
					onClick: handleUndoClick,
				},
			],
			type: 'snackbar',
		} );
		closeModal();
	};

	const handleCancelClick = () => {
		recordEvent( 'switch_to_classic_shortcode_cancel', {
			shortcode: block === 'woocommerce/checkout' ? 'checkout' : 'cart',
		} );
		closeModal();
	};

	return (
		<>
			<Button
				variant={ 'secondary' }
				onClick={ handleSwitchToClassicShortcodeClick }
			>
				{ switchButtonLabel }
			</Button>
			{ isOpen && (
				<Modal
					size="medium"
					title={ switchButtonLabel }
					onRequestClose={ closeModal }
					className="wc-blocks-switch-to-classic-shortcode-modal-content"
				>
					<ModalContent blockType={ block } />
					<TabbableContainer className="wc-blocks-switch-to-classic-shortcode-modal-actions">
						<Button
							variant="primary"
							isDestructive={ true }
							onClick={ handleSwitchClick }
						>
							{ __( 'Switch', 'woocommerce' ) }
						</Button>{ ' ' }
						<Button
							variant="secondary"
							onClick={ handleCancelClick }
						>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
					</TabbableContainer>
				</Modal>
			) }
		</>
	);
}
