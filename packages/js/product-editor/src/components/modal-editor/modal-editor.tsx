/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
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
import { store as productEditorUiStore } from '../../store/product-editor-ui';

type ModalEditorProps = {
	initialBlocks?: BlockInstance[];
	onChange?: ( blocks: BlockInstance[] ) => void;
	onClose?: () => void;
	settings?: Partial< EditorSettings & EditorBlockListSettings > | undefined;
	title: string;
	name: string;
};

export function ModalEditor( {
	initialBlocks,
	onChange,
	onClose,
	title,
	name,
}: ModalEditorProps ) {
	const { closeModalEditor } = useDispatch( productEditorUiStore );

	const debouncedOnChange = useDebounce( ( blocks: BlockInstance[] ) => {
		onChange?.( blocks );
	}, 250 );

	function handleClose() {
		const blocks = debouncedOnChange.flush();
		if ( blocks ) {
			onChange?.( blocks );
		}
		closeModalEditor();
		onClose?.();
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
				onInput={ debouncedOnChange }
				onChange={ debouncedOnChange }
				onClose={ handleClose }
				name={ name }
			/>
		</Modal>
	);
}
