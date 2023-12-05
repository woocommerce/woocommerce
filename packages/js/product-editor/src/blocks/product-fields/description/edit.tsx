/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import {
	BlockAttributes,
	BlockInstance,
	parse,
	serialize,
} from '@wordpress/blocks';
import { useSelect, useDispatch } from '@wordpress/data';
import { Button } from '@wordpress/components';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { recordEvent } from '@woocommerce/tracks';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ContentPreview } from '../../../components/content-preview';
import { ModalEditor } from '../../../components/modal-editor';
import { ProductEditorBlockEditProps } from '../../../types';
import ModalEditorWelcomeGuide from '../../../components/modal-editor-welcome-guide';
import { store as productEditorUiStore } from '../../../store/product-editor-ui';

/**
 * Internal dependencies
 */

/**
 * By default the blocks variable always contains one paragraph
 * block with empty content, that causes the description to never
 * be empty. This function removes the default block to keep
 * the description empty.
 *
 * @param blocks The block list
 * @return Empty array if there is only one block with empty content
 * in the list. The same block list otherwise.
 */
function clearDescriptionIfEmpty( blocks: BlockInstance[] ) {
	if ( blocks.length === 1 ) {
		const { content } = blocks[ 0 ].attributes;
		if ( ! content || ! content.trim() ) {
			return [];
		}
	}
	return blocks;
}

export function DescriptionBlockEdit( {
	attributes,
}: ProductEditorBlockEditProps< BlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const [ description, setDescription ] = useEntityProp< string >(
		'postType',
		'product',
		'description'
	);

	// Check if the Modal editor is open from the store.
	const isModalEditorOpen = useSelect( ( select ) => {
		return select( productEditorUiStore ).isModalEditorOpen();
	}, [] );

	const { openModalEditor, closeModalEditor } =
		useDispatch( productEditorUiStore );

	return (
		<div { ...blockProps }>
			<Button
				variant="secondary"
				onClick={ () => {
					openModalEditor();
					recordEvent( 'product_add_description_click' );
				} }
			>
				{ description.length
					? __( 'Edit description', 'woocommerce' )
					: __( 'Add description', 'woocommerce' ) }
			</Button>

			{ isModalEditorOpen && (
				<ModalEditor
					initialBlocks={ parse( description ) }
					onChange={ ( blocks ) => {
						const html = serialize(
							clearDescriptionIfEmpty( blocks )
						);
						setDescription( html );
					} }
					onClose={ closeModalEditor }
					title={ __( 'Edit description', 'woocommerce' ) }
				/>
			) }

			{ !! description.length && (
				<ContentPreview content={ description } />
			) }

			{ isModalEditorOpen && <ModalEditorWelcomeGuide /> }
		</div>
	);
}
