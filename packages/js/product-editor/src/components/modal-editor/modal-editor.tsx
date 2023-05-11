/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { createElement, useEffect, useRef, useState } from '@wordpress/element';
import {
	EditorSettings,
	EditorBlockListSettings,
} from '@wordpress/block-editor';
import { Modal } from '@wordpress/components';
import { useDebounce } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { IframeEditor } from '../iframe-editor';

type ModalEditorProps = {
	initialBlocks?: BlockInstance[];
	onChange: ( blocks: BlockInstance[] ) => void;
	onClose: () => void;
	settings?: Partial< EditorSettings & EditorBlockListSettings > | undefined;
	title: string;
};

export function ModalEditor( {
	initialBlocks,
	onChange,
	onClose,
	title,
}: ModalEditorProps ) {
	const [ isDebouncing, setIsDebouncing ] = useState( false );
	const [ isClosing, setIsClosing ] = useState( false );

	useEffect( () => {
		// Only close the modal once editor changes have persisted after debouncing.
		if ( isClosing && ! isDebouncing ) {
			onClose();
		}
	}, [ isDebouncing, isClosing, onClose ] );

	const debouncedOnChange = useDebounce( ( blocks: BlockInstance[] ) => {
		onChange( blocks );
		setIsDebouncing( false );
	}, 250 );

	function handleInput( blocks: BlockInstance[] ) {
		setIsDebouncing( true );
		debouncedOnChange( blocks );
	}

	function handleClose() {
		setIsClosing( true );
	}

	return (
		<Modal
			className="woocommerce-modal-editor"
			title={ title }
			onRequestClose={ handleClose }
			shouldCloseOnClickOutside={ false }
		>
			<IframeEditor
				initialBlocks={ initialBlocks }
				onInput={ handleInput }
			/>
		</Modal>
	);
}
