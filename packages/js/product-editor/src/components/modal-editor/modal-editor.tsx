/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
import {
	EditorSettings,
	EditorBlockListSettings,
} from '@wordpress/block-editor';
import { Modal } from '@wordpress/components';

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
	return (
		<Modal
			className="woocommerce-modal-editor"
			title={ title }
			onRequestClose={ onClose }
			shouldCloseOnClickOutside={ false }
		>
			<IframeEditor
				initialBlocks={ initialBlocks }
				onChange={ onChange }
			/>
		</Modal>
	);
}
